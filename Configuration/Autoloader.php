<?php

/**
 * Autoloader — Carga automática de clases (Deuda Técnica 4.2).
 *
 * Estrategia de resolución, registrada con spl_autoload_register:
 *
 *   a) PSR-4 preparado: prefijos "App\" y "Configuration\" se mapean a las
 *      carpetas /App y /Configuration (camino para una migración futura a
 *      namespaces). Hoy el proyecto no usa namespaces, así que este branch
 *      es reserva.
 *
 *   b) Fallback legado: clases en namespace global se buscan por nombre de
 *      archivo ($Clase.php) -iterativamente- en las carpetas clave del
 *      proyecto. Se respeta un orden fijo para resolver colisiones de nombre
 *      de archivo de forma determinista (ej. PromotionService.php existe en
 *      Service/ y en Model/ con contenidos distintos).
 *
 * La autocarga es ADITIVA: nunca se eliminan los require_once existentes,
 * que siguen funcionando como red de seguridad (p. ej. clases cuyo nombre
 * de archivo no coincide, como PromotionServiceLink).
 */
class Autoloader
{
    private static bool $registered = false;

    /**
     * Registra el autoloader una sola vez por proceso (idempotente).
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register(static function (string $class): void {
            $base = dirname(__DIR__);

            // --- a) PSR-4 (preparado para migración futura a namespaces) ---
            $prefixes = [
                'App\\'          => $base . '/App/',
                'Configuration\\' => $base . '/Configuration/',
            ];

            foreach ($prefixes as $prefix => $directory) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }

                $relative = substr($class, strlen($prefix));
                $file = $directory . str_replace('\\', '/', $relative) . '.php';

                if (is_file($file)) {
                    require_once $file;
                }

                return;
            }

            // --- b) Fallback legado: clases en namespace global ---
            // Namespace no mapeado -> no resolver, PHP emitirá su error claro.
            if (str_contains($class, '\\')) {
                return;
            }

            $candidateDirectories = [
                $base . '/App/Controller',
                $base . '/App/Service',
                $base . '/App/Service/Recommendation',
                $base . '/App/Repository',
                $base . '/App/Model',
                $base . '/Configuration',
            ];

            foreach ($candidateDirectories as $directory) {
                $file = $directory . '/' . $class . '.php';

                if (is_file($file)) {
                    require_once $file;
                    return;
                }
            }
        });

        self::$registered = true;
    }
}