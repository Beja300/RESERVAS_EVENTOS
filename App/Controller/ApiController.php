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
   * Dataset geográfico de Costa Rica.
   * 7 provincias → 84 cantones → distritos (División Territorial
   * Administrativa oficial IGN/SNITCR 2022, 492 distritos).
   */
  private const LOCATIONS = [
    'San José' => [
      'San José'          => ['Carmen', 'Merced', 'Hospital', 'Catedral', 'Zapote', 'San Francisco de Dos Ríos', 'La Uruca', 'Mata Redonda', 'Pavas', 'Hatillo', 'San Sebastián'],
      'Escazú'            => ['Escazú', 'San Antonio', 'San Rafael'],
      'Desamparados'      => ['Desamparados', 'San Miguel', 'San Juan de Dios', 'San Rafael Arriba', 'San Rafael Abajo', 'San Antonio', 'Frailes', 'Patarrá', 'San Cristóbal', 'Rosario', 'Damas', 'Gravilias', 'Los Guido'],
      'Puriscal'          => ['Santiago', 'Mercedes Sur', 'Barbacoas', 'Grifo Alto', 'San Rafael', 'Candelarita', 'Desamparaditos', 'San Antonio', 'Chires'],
      'Tarrazú'           => ['San Marcos', 'San Lorenzo', 'San Carlos'],
      'Aserrí'            => ['Aserrí', 'Tarbaca', 'Vuelta de Jorco', 'San Gabriel', 'Legua', 'Monterrey', 'Salitrillos'],
      'Mora'              => ['Colón', 'Guayabo', 'Tabarcia', 'Piedras Negras', 'Picagres', 'Jaris', 'Quitirrisí'],
      'Goicoechea'        => ['Guadalupe', 'San Francisco', 'Calle Blancos', 'Mata de Plátano', 'Ipís', 'Rancho Redondo', 'Purral'],
      'Santa Ana'         => ['Santa Ana', 'Salitral', 'Pozos', 'Uruca', 'Piedades', 'Brasil'],
      'Alajuelita'        => ['Alajuelita', 'San Josecito', 'San Antonio', 'Concepción', 'San Felipe'],
      'Vázquez de Coronado' => ['San Isidro', 'San Rafael', 'Dulce Nombre de Jesús', 'Patalillo', 'Cascajal'],
      'Acosta'            => ['San Ignacio', 'Guaitil', 'Palmichal', 'Cangrejal', 'Sabanillas'],
      'Tibás'             => ['San Juan', 'Cinco Esquinas', 'Anselmo Llorente', 'León XIII', 'Colima'],
      'Moravia'           => ['San Vicente', 'San Jerónimo', 'La Trinidad'],
      'Montes de Oca'     => ['San Pedro', 'Sabanilla', 'Mercedes', 'San Rafael'],
      'Turrubares'        => ['San Pablo', 'San Pedro', 'San Juan de Mata', 'San Luis', 'Carara'],
      'Dota'              => ['Santa María', 'Jardín', 'Copey'],
      'Curridabat'        => ['Curridabat', 'Granadilla', 'Sánchez', 'Tirrases'],
      'Pérez Zeledón'     => ['San Isidro de El General', 'El General', 'Daniel Flores', 'Rivas', 'San Pedro', 'Platanares', 'Pejibaye', 'Cajón', 'Barú', 'Río Nuevo', 'Páramo', 'La Amistad'],
      'León Cortés Castro' => ['San Pablo', 'San Andrés', 'Llano Bonito', 'San Isidro', 'Santa Cruz', 'San Antonio'],
    ],
    'Alajuela' => [
      'Alajuela'    => ['Alajuela', 'San José', 'Carrizal', 'San Antonio', 'Guácima', 'San Isidro', 'Sabanilla', 'San Rafael', 'Río Segundo', 'Desamparados', 'Turrúcares', 'Tambor', 'La Garita', 'Sarapiquí'],
      'San Ramón'   => ['San Ramón', 'Santiago', 'San Juan', 'Piedades Norte', 'Piedades Sur', 'San Rafael', 'San Isidro', 'Ángeles', 'Alfaro', 'Volio', 'Concepción', 'Zapotal', 'Peñas Blancas', 'San Lorenzo'],
      'Grecia'      => ['Grecia', 'San Isidro', 'San José', 'San Roque', 'Tacares', 'Puente de Piedra', 'Bolívar'],
      'San Mateo'   => ['San Mateo', 'Desmonte', 'Jesús María', 'Labrador'],
      'Atenas'      => ['Atenas', 'Jesús', 'Mercedes', 'San Isidro', 'Concepción', 'San José', 'Santa Eulalia', 'Escobal'],
      'Naranjo'     => ['Naranjo', 'San Miguel', 'San José', 'Cirrí Sur', 'San Jerónimo', 'San Juan', 'El Rosario', 'Palmitos'],
      'Palmares'    => ['Palmares', 'Zaragoza', 'Buenos Aires', 'Santiago', 'Candelaria', 'Esquipulas', 'La Granja'],
      'Poás'        => ['San Pedro', 'San Juan', 'San Rafael', 'Carrillos', 'Sabana Redonda'],
      'Orotina'     => ['Orotina', 'El Mastate', 'Hacienda Vieja', 'Coyolar', 'La Ceiba'],
      'San Carlos'  => ['Quesada', 'Florencia', 'Buenavista', 'Aguas Zarcas', 'Venecia', 'Pital', 'La Fortuna', 'La Tigra', 'La Palmera', 'Venado', 'Cutris', 'Monterrey', 'Pocosol'],
      'Zarcero'     => ['Zarcero', 'Laguna', 'Tapesco', 'Guadalupe', 'Palmira', 'Zapote', 'Brisas'],
      'Sarchí'      => ['Sarchí Norte', 'Sarchí Sur', 'Toro Amarillo', 'San Pedro', 'Rodríguez'],
'Upala'              => ['Upala', 'Aguas Claras', 'San José (Pizote)', 'Bijagua', 'Delicias', 'Dos Ríos', 'Yolillal', 'Canalete'],
      'Los Chiles'  => ['Los Chiles', 'Caño Negro', 'El Amparo', 'San Jorge'],
      'Guatuso'     => ['San Rafael', 'Buenavista', 'Cote', 'Katira'],
      'Río Cuarto'  => ['Río Cuarto', 'Santa Rita', 'Santa Isabel'],
    ],
    'Cartago' => [
      'Cartago'     => ['Oriental', 'Occidental', 'Carmen', 'San Nicolás', 'Aguacaliente', 'Guadalupe', 'Corralillo', 'Tierra Blanca', 'Dulce Nombre', 'Llano Grande', 'Quebradilla'],
      'Paraíso'     => ['Paraíso', 'Santiago', 'Orosi', 'Cachí', 'Llanos de Santa Lucía', 'Birrisito'],
      'La Unión'    => ['Tres Ríos', 'San Diego', 'San Juan', 'San Rafael', 'Concepción', 'Dulce Nombre', 'San Ramón', 'Río Azul'],
      'Jiménez'     => ['Juan Viñas', 'Tucurrique', 'Pejibaye', 'La Victoria'],
      'Turrialba'   => ['Turrialba', 'La Suiza', 'Peralta', 'Santa Cruz', 'Santa Teresita', 'Pavones', 'Tuis', 'Tayutic', 'Santa Rosa', 'Tres Equis', 'La Isabel', 'Chirripó'],
      'Alvarado'    => ['Pacayas', 'Cervantes', 'Capellades'],
      'Oreamuno'    => ['San Rafael', 'Cot', 'Potrero Cerrado', 'Cipreses', 'Santa Rosa'],
      'El Guarco'   => ['El Tejar', 'San Isidro', 'Tobosi', 'Patio de Agua'],
    ],
    'Heredia' => [
      'Heredia'     => ['Heredia', 'Mercedes', 'San Francisco', 'Ulloa', 'Vara Blanca'],
      'Barva'       => ['Barva', 'San Pedro', 'San Pablo', 'San Roque', 'Santa Lucía', 'San José de la Montaña', 'Puente Salas'],
      'Santo Domingo' => ['Santo Domingo', 'San Vicente', 'San Miguel', 'Paracito', 'Santo Tomás', 'Santa Rosa', 'Tures', 'Pará'],
      'Santa Bárbara' => ['Santa Bárbara', 'San Pedro', 'San Juan', 'Jesús', 'Santo Domingo', 'Purabá'],
      'San Rafael'  => ['San Rafael', 'San Josecito', 'Santiago', 'Ángeles', 'Concepción'],
      'San Isidro'  => ['San Isidro', 'San José', 'Concepción', 'San Francisco'],
      'Belén'       => ['La Asunción', 'San Antonio', 'La Ribera'],
      'Flores'      => ['San Joaquín', 'Barrantes', 'Llorente'],
      'San Pablo'   => ['San Pablo', 'Rincón de Sabanilla'],
      'Sarapiquí'   => ['Puerto Viejo', 'La Virgen', 'Horquetas', 'Llanuras del Gaspar', 'Cureña'],
    ],
    'Guanacaste' => [
      'Liberia'     => ['Liberia', 'Cañas Dulces', 'Mayorga', 'Nacascolo', 'Curubandé'],
      'Nicoya'      => ['Nicoya', 'Mansión', 'San Antonio', 'Quebrada Honda', 'Sámara', 'Nosara', 'Belén de Nosarita'],
      'Santa Cruz'  => ['Santa Cruz', 'Bolsón', 'Veintisiete de Abril', 'Tempate', 'Cartagena', 'Cuajiniquil', 'Diriá', 'Cabo Velas', 'Tamarindo'],
      'Bagaces'     => ['Bagaces', 'La Fortuna', 'Mogote', 'Río Naranjo'],
      'Carrillo'    => ['Filadelfia', 'Palmira', 'Sardinal', 'Belén'],
      'Cañas'       => ['Cañas', 'Palmira', 'San Miguel', 'Bebedero', 'Porozal'],
      'Abangares'   => ['Las Juntas', 'Sierra', 'San Juan', 'Colorado'],
      'Tilarán'     => ['Tilarán', 'Quebrada Grande', 'Tronadora', 'Santa Rosa', 'Líbano', 'Tierras Morenas', 'Arenal', 'Cabeceras'],
      'Nandayure'   => ['Carmona', 'Santa Rita', 'Zapotal', 'San Pablo', 'Porvenir', 'Bejuco'],
      'La Cruz'     => ['La Cruz', 'Santa Cecilia', 'La Garita', 'Santa Elena'],
      'Hojancha'    => ['Hojancha', 'Monte Romo', 'Puerto Carrillo', 'Huacas', 'Matambú'],
    ],
    'Puntarenas' => [
      'Puntarenas'    => ['Puntarenas', 'Pitahaya', 'Chomes', 'Lepanto', 'Paquera', 'Manzanillo', 'Guacimal', 'Barranca', 'Isla del Coco', 'Cóbano', 'Chacarita', 'Chira', 'Acapulco', 'El Roble', 'Arancibia'],
      'Esparza'       => ['Espíritu Santo', 'San Juan Grande', 'Macacona', 'San Rafael', 'San Jerónimo', 'Caldera'],
      'Buenos Aires'  => ['Buenos Aires', 'Volcán', 'Potrero Grande', 'Boruca', 'Pilas', 'Colinas', 'Chánguena', 'Biolley', 'Brunka'],
      'Montes de Oro' => ['Miramar', 'La Unión', 'San Isidro'],
      'Osa'           => ['Puerto Cortés', 'Palmar', 'Sierpe', 'Bahía Ballena', 'Piedras Blancas', 'Bahía Drake'],
      'Quepos'        => ['Quepos', 'Savegre', 'Naranjito'],
      'Golfito'       => ['Golfito', 'Guaycará', 'Pavón'],
      'Coto Brus'     => ['San Vito', 'Sabalito', 'Aguabuena', 'Limoncito', 'Pittier', 'Gutiérrez Braun'],
      'Parrita'       => ['Parrita'],
      'Corredores'    => ['Corredor', 'La Cuesta', 'Canoas', 'Laurel'],
      'Garabito'      => ['Jacó', 'Tárcoles', 'Lagunillas'],
      'Monteverde'    => ['Monteverde'],
      'Puerto Jiménez' => ['Puerto Jiménez'],
    ],
    'Limón' => [
      'Limón'     => ['Limón', 'Valle La Estrella', 'Río Blanco', 'Matama'],
      'Pococí'    => ['Guápiles', 'Jiménez', 'Rita', 'Roxana', 'Cariari', 'Colorado', 'La Colonia'],
      'Siquirres' => ['Siquirres', 'Pacuarito', 'Florida', 'Germania', 'El Cairo', 'Alegría', 'Reventazón'],
      'Talamanca' => ['Bratsi', 'Sixaola', 'Cahuita', 'Telire'],
      'Matina'    => ['Matina', 'Batán', 'Carrandi'],
      'Guácimo'   => ['Guácimo', 'Mercedes', 'Pocora', 'Río Jiménez', 'Duacarí'],
    ],
  ];

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
      $cantones = self::LOCATIONS[$province] ?? [];

      if (empty($cantones)) {
        http_response_code(404);
      }

      echo json_encode($cantones, JSON_UNESCAPED_UNICODE);
      return;
    }

    echo json_encode(self::LOCATIONS, JSON_UNESCAPED_UNICODE);
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

    respond_json([
      'ok'       => true,
      'province' => $province,
      'canton'   => $canton,
      'district' => $canton, // ip-api no entrega distrito; se usa el cantón como aproximación.
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

    return $this->findDatasetKey(self::LOCATIONS, $name);
  }

  private function matchCanton(string $province, string $name): ?string
  {
    $cantones = self::LOCATIONS[$province] ?? [];

    return $this->findDatasetKey($cantones, $name);
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
