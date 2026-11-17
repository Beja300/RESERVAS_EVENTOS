<?php

/**
 * DataBase — Maneja la conexión a MySQL usando PDO.
 *
 * Usa el patrón Singleton: sin importar cuántos Models pidan una
 * conexión, todos comparten la MISMA instancia de PDO en toda la
 * ejecución de la petición. Esto evita abrir conexiones repetidas
 * innecesariamente.
 */
class DataBase
{
    // "static" hace que esta propiedad pertenezca a la CLASE, no a un
    // objeto individual -- por eso se accede con "self::" y no "$this->".
    // Empieza en null porque todavía no existe ninguna conexión creada.
    private static ?PDO $instance = null;

    // -----------------------------------------------------------
    // Datos de conexión por defecto. Los valores reales pueden venir de
    // Configuration/DataBase.local.php (ignorado por git) o de variables
    // de entorno. NO uses el usuario root del sistema: en Ubuntu/Debian
    // normalmente usa auth_socket y solo permite "sudo mysql", no PDO/TCP
    // (error SQLSTATE[HY000] [1698]).
    // -----------------------------------------------------------
    private const HOST     = '127.0.0.1';
    private const PORT     = '3306';
    private const DB_NAME  = 'dbeventhall';
    private const USER     = 'eventhall_app';
    private const PASS     = '';

    /**
     * Devuelve la configuración efectiva de la conexión.
     * Prioridad: DataBase.local.php > variables de entorno > valores por defecto.
     *
     * @return array{host:string,port:string,database:string,user:string,password:string}
     */
    private static function config(): array
    {
        $localFile = __DIR__ . '/DataBase.local.php';
        $local = is_file($localFile) ? require $localFile : [];
        if (!is_array($local)) {
            $local = [];
        }

        $pick = static function (string $key, string $env, string $default) use ($local): string {
            $value = $local[$key] ?? ($_ENV[$env] ?? getenv($env));
            return $value === false || $value === null || $value === '' ? $default : (string) $value;
        };

        return [
            'host'     => $pick('host', 'DB_HOST', self::HOST),
            'port'     => $pick('port', 'DB_PORT', self::PORT),
            'database' => $pick('database', 'DB_NAME', self::DB_NAME),
            'user'     => $pick('user', 'DB_USER', self::USER),
            'password' => $pick('password', 'DB_PASS', self::PASS),
        ];
    }

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
            try {
                $config = self::config();

                // DSN (Data Source Name): le dice a PDO qué motor,
                // en qué servidor/puerto, y a qué base de datos conectarse.
                $dsn = 'mysql:host=' . $config['host']
                     . ';port=' . $config['port']
                     . ';dbname=' . $config['database']
                     . ';charset=utf8mb4'; // utf8mb4 para que tildes y "ñ" se guarden bien

                self::$instance = new PDO($dsn, $config['user'], $config['password'], [
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
}