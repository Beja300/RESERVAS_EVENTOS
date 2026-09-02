<?php

/**
 * Helpers reutilizables para las vistas.
 *
 * No renderiza nada por sí mismo; solo define funciones/comandos que
 * cada vista puede usar al principio (include + guard) o al final.
 */

if (!function_exists('css_url')) {
    /**
     * Devuelve la URL absoluta hacia un CSS de la aplicación.
     *
     * Sin argumentos devuelve el núcleo común (app.css). Con un nombre,
     * devuelve la hoja específica de una vista (Public/css/{name}.css).
     */
    function css_url(?string $name = null): string
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/');
        $file = ($name === null || $name === '') ? 'app' : $name;
        return $base . '/css/' . $file . '.css';
    }
}

if (!function_exists('js_url')) {
    /**
     * Devuelve la URL absoluta hacia un JavaScript de la aplicación.
     *
     * Sin argumentos devuelve el núcleo común (app.js). Con un nombre,
     * devuelve el script específico de una vista (Public/js/{name}.js).
     */
    function js_url(?string $name = null): string
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/');
        $file = ($name === null || $name === '') ? 'app' : $name;
        return $base . '/js/' . $file . '.js';
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

if (!function_exists('image_url')) {
    /**
     * Convierte una ruta de imagen (relativa a Public/ o URL completa)
     * en una URL absoluta servible desde el navegador.
     */
    function image_url(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Devuelve (y si hace falta genera) el token CSRF de la sesión.
     */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Campo oculto a insertar en los formularios POST.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_validate')) {
    /**
     * Valida el token CSRF recibido por POST. Lanza excepción si falla.
     */
    function csrf_validate(): void
    {
        $sent = $_POST['csrf_token'] ?? '';
        $expected = $_SESSION['csrf_token'] ?? '';

        if ($sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
            http_response_code(403);
            exit('Sesión inválida (token de seguridad). Por favor vuelve a intentarlo.');
        }
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

if (!function_exists('is_ajax')) {
    /**
     * Detecta si la petición espera JSON (objetivo: interacciones AJAX).
     */
    function is_ajax(): bool
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT'])
                && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
}

if (!function_exists('respond_json')) {
    /**
     * Responde en JSON, finalizando el script. Espera un array.
     */
    function respond_json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (!function_exists('render_partial')) {
    /**
     * Renderiza una vista parcial y devuelve su HTML (sin imprimir).
     * $vars se extrae en el ámbito local de la vista.
     */
    function render_partial(string $path, array $vars = []): string
    {
        if (!is_file($path)) {
            return '';
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
