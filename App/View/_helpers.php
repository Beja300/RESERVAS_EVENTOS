<?php

/**
 * Helpers reutilizables para las vistas.
 *
 * No renderiza nada por sí mismo; solo define funciones/comandos que
 * cada vista puede usar al principio (include + guard) o al final.
 */

if (!function_exists('css_url')) {
    /**
     * Devuelve la URL absoluta hacia el CSS de la aplicación,
     * calculada a partir de la ubicación del front controller (Public/).
     */
    function css_url(): string
    {
        $base = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        return rtrim($base, '/') . '/css/app.css';
    }
}

if (!function_exists('base_url')) {
    /**
     * Devuelve la URL hacia el front controller con el controller/acción
     * dados. Ej: base_url('venue', 'catalog', ['venueId' => 3]).
     */
    function base_url(string $controller, string $action, array $params = []): string
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/');
        $query = http_build_query(array_merge(
            ['controller' => $controller, 'action' => $action],
            $params
        ));
        return $base . '/index.php?' . $query;
    }
}

if (!function_exists('e')) {
    /**
     * Escapa una cadena para mostrarla de forma segura en HTML.
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('current_user_type')) {
    /**
     * Devuelve el tipo del usuario logueado ('admin', 'client', 'owner')
     * o null si no hay sesión.
     */
    function current_user_type(): ?string
    {
        return $_SESSION['type'] ?? null;
    }
}
