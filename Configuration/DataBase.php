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
    // Datos de conexión. Ajusta estos valores a tu entorno local.
    //
    // IMPORTANTE en Linux: usa '127.0.0.1' y NO 'localhost'. Y NO uses
    // el usuario 'root' del sistema -- en Ubuntu/Debian normalmente usa
    // auth_socket (solo permite entrar con "sudo mysql", nunca por
    // contraseña/TCP -> error SQLSTATE[HY000] [1698]). Por eso se usa
    // un usuario dedicado para la app, creado con:
    //   CREATE USER 'paradigmas_app'@'127.0.0.1' IDENTIFIED BY '...';
    //   GRANT ALL PRIVILEGES ON proyectoparadigmas_db.* TO 'paradigmas_app'@'127.0.0.1';
    // -----------------------------------------------------------
    private const HOST     = '127.0.0.1';
    private const PORT     = '3306';
    private const DB_NAME  = 'dbeventhall'; // nombre real de la BD en Workbench
    private const USER     = 'root';
    private const PASS     = ''; // <-- debe coincidir EXACTO con la de MySQL

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
                // DSN (Data Source Name): le dice a PDO qué motor,
                // en qué servidor/puerto, y a qué base de datos conectarse.
                $dsn = 'mysql:host=' . self::HOST
                     . ';port=' . self::PORT
                     . ';dbname=' . self::DB_NAME
                     . ';charset=utf8mb4'; // utf8mb4 para que tildes y "ñ" se guarden bien

                self::$instance = new PDO($dsn, self::USER, self::PASS, [
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
                // Detenemos la ejecución con un mensaje claro. En producción,
                // esto normalmente se reemplaza por un log + página de error
                // genérica, para no exponer detalles de la BD al usuario final.
                die('Error de conexión a la base de datos: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}