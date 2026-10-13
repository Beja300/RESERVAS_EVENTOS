<?php

require_once __DIR__ . '/DotEnv.php';

/**
 * DataBase — Maneja la conexión a MySQL usando PDO.
 *
 * Usa el patrón Singleton: sin importar cuántos Models pidan una
 * conexión, todos comparten la MISMA instancia de PDO en toda la
 * ejecución de la petición. Esto evita abrir conexiones repetidas
 * innecesariamente.
 *
 * Las credenciales se leen del archivo .env (raíz del proyecto) mediante
 * DotEnv. Si una clave falta, se usa un fallback seguro para XAMPP local.
 */
class DataBase
{
    // "static" hace que esta propiedad pertenezca a la CLASE, no a un
    // objeto individual -- por eso se accede con "self::" y no "$this->".
    // Empieza en null porque todavía no existe ninguna conexión creada.
    private static ?PDO $instance = null;

    // Profundidad de transacciones anidadas. 0 = sin transacción activa;
    // cada begin adicional usa un SAVEPOINT en vez de abrir otra transacción.
    private static int $transactionDepth = 0;

    // Conexión sobre la que se abrió la transacción más externa. Se fija en el
    // primer begin y se limpia al llegar de nuevo a profundidad 0.
    private static ?PDO $txConnection = null;

    /**
     * Constructor privado: NADIE puede hacer "new DataBase()" desde
     * fuera de esta clase. Es lo que obliga a usar getConnection()
     * como único punto de entrada, garantizando que exista una sola
     * conexión real.
     */
    private function __construct()
    {
    }

    /**
     * Punto de entrada único para obtener la conexión PDO.
     *
     * Primera llamada  -> $instance está en null -> crea la conexión.
     * Siguientes llamadas -> $instance ya existe -> la reutiliza.
     *
     * @return PDO La conexión activa (siempre la misma).
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            // Carga las credenciales desde .env (idempotente: si el archivo
            // no existe, se conservan los fallbacks de abajo).
            DotEnv::load(__DIR__ . '/../.env');

            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $name = $_ENV['DB_NAME'] ?? 'dbeventhall';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            try {
                // DSN (Data Source Name): le dice a PDO qué motor,
                // en qué servidor/puerto, y a qué base de datos conectarse.
                $dsn = 'mysql:host=' . $host
                     . ';port=' . $port
                     . ';dbname=' . $name
                     . ';charset=utf8mb4'; // utf8mb4 para que tildes y "ñ" se guarden bien

                self::$instance = new PDO($dsn, $user, $pass, [
                    // Si algo falla (credenciales, BD inexistente), PDO lanza
                    // una excepción que SÍ podemos capturar, en vez de fallar
                    // en silencio.
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                    // Al pedir datos (fetch), los devuelve como array asociativo,
                    // ej. ['tbrolpk' => 1, 'tbrolnombre' => 'Ana'], en vez de
                    // mezclar índices numéricos y de texto.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                // No exponemos detalles de la BD/SQL al usuario: se registra
                // el error real en el log y se responde con un mensaje genérico.
                error_log('[EventHall] Error de conexión a la base de datos: ' . $e->getMessage());
                http_response_code(500);
                exit('Ocurrió un error al conectar con la base de datos. Intenta más tarde.');
            }
        }

        return self::$instance;
    }

    /**
     * Devuelve la conexión ya creada, SIN instanciarla si aún no existe.
     * Pensado para el manejador global de excepciones: llamar a getConnection()
     * ahí podría abrir una conexión nueva (o fallar) y enmascarar el error real.
     */
    public static function getActiveConnection(): ?PDO
    {
        return self::$instance;
    }

    /**
     * Inicia una transacción anidable.
     *
     * - Si no hay ninguna activa (profundidad 0), abre una transacción real.
     * - Si ya hay una (llamada anidada: orquestador -> servicio), crea un
     *   SAVEPOINT para poder deshacer solo ese nivel.
     *
     * @param PDO|null $connection Conexión del llamador; si se omite se usa la
     *                             conexión Singleton. Solo se fija en el nivel 0.
     */
    public static function beginTransaction(?PDO $connection = null): void
    {
        $pdo = $connection ?? self::getConnection();

        if (self::$transactionDepth === 0) {
            self::$txConnection = $pdo;
            $pdo->beginTransaction();
        } else {
            $pdo->exec('SAVEPOINT ' . self::savepointName(self::$transactionDepth));
        }

        self::$transactionDepth++;
    }

    /**
     * Confirma el nivel de transacción más interno. En el nivel 0 confirma la
     * transacción real; en niveles anidados libera el SAVEPOINT correspondiente.
     */
    public static function commit(): void
    {
        if (self::$transactionDepth <= 0) {
            return;
        }

        self::$transactionDepth--;
        $pdo = self::$txConnection;

        if (self::$transactionDepth === 0) {
            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->commit();
            }
            self::$txConnection = null;
            return;
        }

        $pdo->exec('RELEASE SAVEPOINT ' . self::savepointName(self::$transactionDepth));
    }

    /**
     * Deshace el nivel de transacción más interno. En el nivel 0 revierte toda
     * la transacción real; en niveles anidados revierte hasta el SAVEPOINT.
     * Es seguro llamarlo aunque no haya transacción activa.
     */
    public static function rollBack(): void
    {
        if (self::$transactionDepth <= 0) {
            return;
        }

        self::$transactionDepth--;
        $pdo = self::$txConnection;

        if (self::$transactionDepth === 0) {
            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::$txConnection = null;
            return;
        }

        $savepoint = self::savepointName(self::$transactionDepth);
        $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
        $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
    }

    /**
     * Revierte por completo cualquier transacción activa, sin importar la
     * profundidad. Usado por el manejador global de excepciones antes de
     * responder un 500, para no dejar datos a medio escribir.
     */
    public static function rollBackActive(): void
    {
        $pdo = self::$instance;

        if ($pdo !== null && $pdo->inTransaction()) {
            try {
                $pdo->rollBack();
            } catch (\Throwable $ignored) {
                // No debe enmascarar la excepción original que se está manejando.
            }
        }

        self::$transactionDepth = 0;
        self::$txConnection = null;
    }

    public static function inTransaction(): bool
    {
        return self::$transactionDepth > 0;
    }

    public static function transactionDepth(): int
    {
        return self::$transactionDepth;
    }

    private static function savepointName(int $level): string
    {
        return 'eventhall_sp_' . $level;
    }
}