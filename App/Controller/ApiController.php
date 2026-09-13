<?php

/**
 * ApiController — puntos de acceso JSON de la aplicación.
 *
 * Expone datos geográficos (provincias, cantones y distritos de Costa
 * Rica) de forma GRATUITA y embebida, sin claves externas, para
 * autocompletar los formularios de ubicación. También geolocaliza al
 * cliente por IP (api externa gratuita ip-api.com) al iniciar sesión,
 * para hacer recomendaciones según su ubicación actual.
 */
class ApiController
{
  /**
   * Dataset geográfico de Costa Rica (App/Data/locations.php).
   * 7 provincias → 84 cantones → distritos (IGN/SNITCR 2022).
   */
  private static function locationData(): array
  {
    static $locations = null;

    if ($locations === null) {
      $locations = require __DIR__ . '/../Data/locations.php';
    }

    return $locations;
  }

  /**
   * Devuelve las ubicaciones en JSON.
   * - Sin parámetros: dataset completo {provincia: {cantón: [distritos]}}.
   * - Con ?provincia=X: {cantón: [distritos]} (compatibilidad).
   */
  public function locations(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $province = trim($_GET['provincia'] ?? '');

    if ($province !== '') {
      $cantones = self::locationData()[$province] ?? [];

      if (empty($cantones)) {
        http_response_code(404);
      }

      echo json_encode($cantones, JSON_UNESCAPED_UNICODE);
      return;
    }

    echo json_encode(self::locationData(), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Geolocaliza al cliente por IP (servidor-servidor, sin clave).
   *
   * Consulta ip-api.com y mapea la provincia y el cantón devueltos al
   * dataset embebido de Costa Rica. Si la IP entrante es privada o
   * reservada (local, NAT), se delega al auto-detect del servidor.
   */
  public function geolocate(): void
  {
    $url = 'http://ip-api.com/json/';

    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

    $isPublic = filter_var($remoteIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

    if ($isPublic !== false) {
      $url .= $remoteIp;
    }

    $url .= '?fields=status,message,countryCode,regionName,city,lat,lon';

    $response = @file_get_contents($url, false, stream_context_create([
      'http' => ['timeout' => 4]
    ]));

    if ($response === false) {
      respond_json(['ok' => false, 'message' => 'No pudimos detectar tu ubicación en este momento.']);
      return;
    }

    $data = json_decode($response, true);

    if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
      respond_json(['ok' => false, 'message' => 'No pudimos detectar tu ubicación en este momento.']);
      return;
    }

    if (($data['countryCode'] ?? '') !== 'CR') {
      respond_json(['ok' => false, 'message' => 'Solo hacemos recomendaciones por ubicación dentro de Costa Rica.']);
      return;
    }

    $province = $this->matchProvince((string) ($data['regionName'] ?? ''));
    $canton = $province !== null
      ? $this->matchCanton($province, (string) ($data['city'] ?? ''))
      : null;

    if ($province === null || $canton === null) {
      respond_json(['ok' => false, 'message' => 'No pudimos identificar tu cantón; puedes configurarlo en Mi perfil.']);
      return;
    }

    $district = $this->matchDistrict($province, $canton, (string) ($data['city'] ?? ''));

    respond_json([
      'ok'       => true,
      'province' => $province,
      'canton'   => $canton,
      'district' => $district,
      'lat'      => $data['lat'] ?? null,
      'lon'      => $data['lon'] ?? null,
    ]);
  }

  // =========================================================
  // COINCIDENCIA CONTRA EL DATASET EMBEBIDO
  // =========================================================
  private function matchProvince(string $name): ?string
  {
    // ip-api devuelve el ADM1 como "{Provincia} Province" para CR.
    $name = preg_replace('/\s+Province$/i', '', $name) ?? $name;

    return $this->findDatasetKey(self::locationData(), $name);
  }

  private function matchCanton(string $province, string $name): ?string
  {
    $cantones = self::locationData()[$province] ?? [];

    return $this->findDatasetKey($cantones, $name);
  }

  /**
   * ip-api no entrega el distrito: si la ciudad coincide con algún
   * distrito del cantón lo usamos; si no, el primero del cantón.
   */
  private function matchDistrict(string $province, string $canton, string $city): string
  {
    $districts = self::locationData()[$province][$canton] ?? [];

    if (empty($districts)) {
      return $canton;
    }

    $normalized = $this->normalizeName($city);

    foreach ($districts as $district) {
      if ($this->normalizeName($district) === $normalized) {
        return $district;
      }
    }

    return $districts[0];
  }

  /**
   * Busca una clave del dataset ignorando acentos y mayúsculas/minúsculas.
   */
  private function findDatasetKey(array $dataset, string $name): ?string
  {
    $normalized = $this->normalizeName($name);

    foreach ($dataset as $key => $_value) {
      if ($this->normalizeName((string) $key) === $normalized) {
        return (string) $key;
      }
    }

    return null;
  }

  private function normalizeName(string $value): string
  {
    $value = function_exists('mb_strtolower')
      ? mb_strtolower(trim($value), 'UTF-8')
      : strtolower(trim($value));

    return strtr($value, [
      'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
      'ü' => 'u', 'ñ' => 'n',
    ]);
  }

  /**
   * HTML de los comentarios de un local (para refresco AJAX sin recargar).
   * ?id= (idVenue)
   */
  public function venueComments(): void
  {
    require_once __DIR__ . '/../../Configuration/DataBase.php';
    require_once __DIR__ . '/../Service/VenueRatingService.php';

    $idVenue = (int) ($_GET['id'] ?? 0);
    $service = new VenueRatingService(DataBase::getConnection());

    respond_json([
      'html' => render_partial(
        __DIR__ . '/../View/Venue/_venueComments.php',
        ['venueComments' => $service->getPublicComments($idVenue)]
      ),
    ]);
  }

  /**
   * HTML de los comentarios de un servicio (para refresco AJAX sin recargar).
   * ?id= (idService)
   */
  public function serviceComments(): void
  {
    require_once __DIR__ . '/../../Configuration/DataBase.php';
    require_once __DIR__ . '/../Service/ServiceRatingService.php';

    $idService = (int) ($_GET['id'] ?? 0);
    $service = new ServiceRatingService(DataBase::getConnection());

    respond_json([
      'html' => render_partial(
        __DIR__ . '/../View/Venue/_serviceComments.php',
        ['comments' => $service->getPublicComments($idService)]
      ),
    ]);
  }
}