<?php

/**
 * Configuration — Ajustes de la aplicación.
 *
 * Las credenciales NO se versionan: se leen de variables de entorno del
 * sistema (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS). Los valores listados
 * como respaldo son SOLO defaults para el desarrollo local; nunca se colocan
 * contraseñas reales de producción en el código.
 */
class Configuration
{
    /** @var array<string,string> Variables de entorno esperadas y su default local. */
    private const ENV_DEFAULTS = [
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_NAME' => 'dbeventhall',
        'DB_USER' => 'root',
        'DB_PASS' => '',
    ];

    public static function databaseHost(): string
    {
        return self::env('DB_HOST');
    }

    public static function databasePort(): string
    {
        return self::env('DB_PORT');
    }

    public static function databaseName(): string
    {
        return self::env('DB_NAME');
    }

    public static function databaseUser(): string
    {
        return self::env('DB_USER');
    }

    public static function databasePassword(): string
    {
        return self::env('DB_PASS');
    }

    private static function env(string $name): string
    {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
        return self::ENV_DEFAULTS[$name] ?? '';
    }
}