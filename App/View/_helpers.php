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

if (!function_exists('current_user')) {
    /**
     * Devuelve el DTO del usuario logueado (UserSessionDTO) o null.
     *
     * Normaliza el formato de la sesión: acepta tanto el DTO ya guardado
     * como, por compatibilidad con sesiones antiguas, una entidad de
     * dominio o un arreglo con los datos del usuario.
     */
    function current_user(): ?UserSessionDTO
    {
        $user = $_SESSION['user'] ?? null;

        if ($user === null) {
            return null;
        }

        if ($user instanceof UserSessionDTO) {
            return $user;
        }

        if ($user instanceof Client || $user instanceof Owner || $user instanceof Admin) {
            return UserSessionDTO::fromObject($user);
        }

        if (is_array($user)) {
            return UserSessionDTO::fromArray($user);
        }

        return null;
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

if (!function_exists('format_venue_location')) {
    /**
     * Devuelve la ubicación completa como "Provincia · Cantón · Distrito",
     * omitiendo las partes que falten (nunca deja separadores colgantes).
     *
     * Si el cantón o el distrito vienen vacíos en BD pero existen en el
     * dataset oficial de Costa Rica, se completan en pantalla con el primer
     * valor real de esa provincia/cantón para que nunca se vea incompleto.
     */
    function format_venue_location(?Location $location): string
    {
        if ($location === null) {
            return '';
        }

        $province = trim((string) $location->getProvinceLocation());
        $canton = trim((string) $location->getCantonLocation());
        $district = trim((string) $location->getDistrictLocation());

        if ($province === '') {
            return '';
        }

        $locations = require __DIR__ . '/../Data/locations.php';

        if ($canton === '') {
            $firstCanton = array_key_first($locations[$province] ?? []);
            if ($firstCanton !== null) {
                $canton = (string) $firstCanton;
            }
        }

        if ($district === '') {
            $districts = $locations[$province][$canton] ?? [];
            if (!empty($districts)) {
                $district = (string) $districts[0];
            }
        }

        $parts = array_values(array_filter(
            [$province, $canton, $district],
            static fn(string $part): bool => $part !== ''
        ));

        return $parts === [] ? '' : e(implode(' · ', $parts));
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

if (!function_exists('input')) {
    /**
     * Lee y recorta un campo de formulario (POST).
     */
    function input(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $default));
    }
}

if (!function_exists('require_login')) {
    /**
     * Exige sesión iniciada (cualquier rol). Sin sesión:
     *  - AJAX -> responde 401 JSON.
     *  - Normal -> redirige al login.
     */
    function require_login(): void
    {
        if (empty($_SESSION['type'])) {
            if (is_ajax()) {
                respond_json(['ok' => false, 'error' => 'Debes iniciar sesión para continuar.'], 401);
            }
            header('Location: ' . base_url('auth', 'showLogin'));
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    /**
     * Exige que el usuario autenticado sea del rol dado. Sin sesión o con
     * rol distinto:
     *  - AJAX -> responde 401 (sin sesión) o 403 (rol incorrecto) JSON.
     *  - Normal -> redirige al login.
     */
    function require_role(string $expectedType): void
    {
        if (empty($_SESSION['type'])) {
            require_login();
            return;
        }
        if (($_SESSION['type'] ?? '') !== $expectedType) {
            if (is_ajax()) {
                respond_json(['ok' => false, 'error' => 'No tienes permisos para realizar esta acción.'], 403);
            }
            header('Location: ' . base_url('auth', 'showLogin'));
            exit;
        }
    }
}

if (!function_exists('require_post')) {
    /**
     * Exige método POST para operaciones que mutan estado. Evita que una
     * acción sensible se dispare por GET (enlace/imagen) saltándose el CSRF.
     */
    function require_post(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            if (is_ajax()) {
                respond_json(['ok' => false, 'error' => 'Método no permitido.'], 405);
            }
            http_response_code(405);
            exit('Método no permitido.');
        }
    }
}

if (!function_exists('redirect_to')) {
    /**
     * Redirige al front controller (controller/acción) y termina el script.
     */
    function redirect_to(string $controller, string $action, array $params = []): void
    {
        header('Location: ' . base_url($controller, $action, $params));
        exit;
    }
}

if (!function_exists('respond_or_redirect')) {
    /**
     * Respuesta común AJAX + redirección fallback.
     * Si la petición es AJAX responde JSON (con $status); si no, redirige.
     */
    function respond_or_redirect(array $payload, string $controller, string $action, array $params = [], int $status = 200): void
    {
        if (is_ajax()) {
            respond_json($payload, $status);
        }
        redirect_to($controller, $action, $params);
    }
}

if (!function_exists('parse_year_month')) {
    /**
     * Valida/normaliza un año-mes "YYYY-MM". Devuelve el valor vigente,
     * el anterior y el siguiente, y una etiqueta legible (Ej: "Agosto 2026").
     */
    function parse_year_month(string $raw): array
    {
        $current = (preg_match('~^\d{4}-\d{2}$~', $raw)) ? $raw : date('Y-m');

        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $label = $months[(int) substr($current, 5, 2)] . ' ' . substr($current, 0, 4);

        return [
            'value' => $current,
            'prev'  => date('Y-m', strtotime($current . ' first day of this month -1 month')),
            'next'  => date('Y-m', strtotime($current . ' first day of this month +1 month')),
            'label' => $label,
        ];
    }
}

if (!function_exists('venue_comments_html')) {
    /**
     * HTML de los comentarios de un local (para refresco AJAX sin recargar).
     */
    function venue_comments_html(int $idVenue): string
    {
        require_once __DIR__ . '/../Service/VenueRatingService.php';
        require_once __DIR__ . '/../../Configuration/DataBase.php';

        $service = new VenueRatingService(DataBase::getConnection());

        return render_partial(
            __DIR__ . '/Venue/_venueComments.php',
            ['venueComments' => $service->getPublicComments($idVenue)]
        );
    }
}

if (!function_exists('detect_gender')) {
    /**
     * Intenta inferir el género ('f' o 'm') a partir del primer nombre.
     *
     * No existe un campo de género en la base de datos, por lo que se usa
     * una heurística: se revisan listas de excepciones conocidas y, si el
     * nombre no está en ellas, se asume femenino cuando termina en "a" y
     * masculino en cualquier otro caso. Devuelve null si no hay nombre.
     */
    function detect_gender(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $firstName = strtolower(preg_replace('/\s+/', ' ', $name));
        $firstName = explode(' ', $firstName)[0];
        $firstName = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $firstName
        );

        $feminineNames = [
            'raquel', 'beatriz', 'pilar', 'carmen', 'isabel', 'ines', 'mercedes',
            'soledad', 'ruth', 'esther', 'elizabeth', 'consuelo', 'rosario',
            'dolores', 'luz', 'sol', 'yolanda', 'miriam', 'noemi', 'abigail',
        ];

        $masculineNames = [
            'jose', 'joshua', 'noa', 'elias', 'isaias', 'tomas', 'matias',
            'lucas', 'andres', 'nicolas', 'diego', 'borja',
        ];

        if (in_array($firstName, $feminineNames, true)) {
            return 'f';
        }

        if (in_array($firstName, $masculineNames, true)) {
            return 'm';
        }

        return substr($firstName, -1) === 'a' ? 'f' : 'm';
    }
}

if (!function_exists('welcome_greeting')) {
    /**
     * Devuelve el saludo de bienvenida usando solo el primer nombre del
     * usuario: "Bienvenida <nombre>", "Bienvenido <nombre>" o el saludo
     * genérico cuando no hay nombre disponible.
     */
    function welcome_greeting(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return 'Bienvenid@ a Event Hall';
        }

        $firstName = explode(' ', preg_replace('/\s+/', ' ', $name))[0];
        $treatment = detect_gender($firstName) === 'f' ? 'Bienvenida' : 'Bienvenido';

        return $treatment . ' ' . $firstName;
    }
}

if (!function_exists('format_colones')) {
    /**
     * Da formato monetario en colones costarricenses.
     *
     * Ej.: format_colones(1234.5) -> "₡1,234.50". Se usa coma como separador
     * de miles y punto como separador decimal, con dos decimales fijos.
     */
    function format_colones($amount): string
    {
        return "\u{20A1}" . number_format((float) $amount, 2, '.', ',');
    }
}

if (!function_exists('format_phone')) {
    /**
     * Da formato legible a un teléfono de 8 dígitos: "8888-8888".
     *
     * Si el valor no contiene exactamente 8 dígitos se devuelve tal cual
     * (sin inventar separadores). Acepta entradas ya formateadas.
     */
    function format_phone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if (strlen($digits) === 8) {
            return substr($digits, 0, 4) . '-' . substr($digits, 4, 4);
        }

        return (string) $phone;
    }
}

if (!function_exists('format_cedula')) {
    /**
     * Da formato legible a una cédula costarricense a partir de sus dígitos.
     *
     * - Cédula física  (9 dígitos):  1-2345-0678
     * - Cédula jurídica (10 dígitos): 3-101-123456
     * - DIMEX          (11 dígitos): 1-2345-67890
     * - DIMEX          (12 dígitos): 1-2345-678901
     *
     * Si la cantidad de dígitos no coincide con un patrón conocido se
     * devuelve el valor original sin alterar.
     */
    function format_cedula(?string $identification): string
    {
        $digits = preg_replace('/\D/', '', (string) $identification);

        switch (strlen($digits)) {
            case 9:  // cédula física
                return substr($digits, 0, 1) . '-' . substr($digits, 1, 4) . '-' . substr($digits, 5, 4);
            case 10: // cédula jurídica
                return substr($digits, 0, 1) . '-' . substr($digits, 1, 3) . '-' . substr($digits, 4, 6);
            case 11: // DIMEX
                return substr($digits, 0, 1) . '-' . substr($digits, 1, 4) . '-' . substr($digits, 5, 6);
            case 12: // DIMEX (12 dígitos)
                return substr($digits, 0, 1) . '-' . substr($digits, 1, 4) . '-' . substr($digits, 5, 7);
        }

        return (string) $identification;
    }
}

if (!function_exists('service_comments_html')) {
    /**
     * HTML de los comentarios de un servicio (para refresco AJAX sin recargar).
     */
    function service_comments_html(int $idService): string
    {
        require_once __DIR__ . '/../Service/ServiceRatingService.php';
        require_once __DIR__ . '/../../Configuration/DataBase.php';

        $service = new ServiceRatingService(DataBase::getConnection());

        return render_partial(
            __DIR__ . '/Venue/_serviceComments.php',
            ['comments' => $service->getPublicComments($idService)]
        );
    }
}
