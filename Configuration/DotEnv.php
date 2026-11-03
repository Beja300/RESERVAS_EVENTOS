<?php

/**
 * DotEnv — Cargador liviano de variables de entorno desde un archivo .env.
 *
 * No depende de Composer ni de ningún paquete externo: lee el archivo
 * línea por línea y expone cada par clave=valor en $_ENV, $_SERVER y en
 * el entorno del proceso (putenv), de forma que la aplicación pueda
 * consumirlas con $_ENV['CLAVE'] ?? 'fallback'.
 *
 * Formato soportado:
 *   - Líneas vacías y comentarios que empiezan por '#' se ignoran.
 *   - CLAVE=valor   (el valor puede contener '='; se corta solo en el primero)
 *   - Comillas simples o dobles envolventes son opcionales y se eliminan.
 */
class DotEnv
{
    /**
     * Carga las variables definidas en $path.
     *
     * Si el archivo no existe o no es legible, no hace nada (silencioso):
     * así la aplicación sigue funcionando con los fallbacks de cada consumidor.
     */
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            // Comillas envolventes opcionales (ej. DB_PASS="secreto").
            if (
                strlen($value) >= 2
                && ($value[0] === '"' || $value[0] === "'")
                && $value[0] === substr($value, -1)
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
