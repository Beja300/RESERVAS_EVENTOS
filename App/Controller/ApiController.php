<?php

/**
 * ApiController — puntos de acceso JSON de la aplicación.
 *
 * Por ahora solo expone datos geográficos (provincias, cantones y
 * distritos de Costa Rica) de forma GRATUITA y embebida, sin claves
 * externas. Se usa para autocompletar los formularios de ubicación.
 */
class ApiController
{
  /**
   * Dataset geográfico de Costa Rica.
   * 7 provincias → cantones → distritos principales.
   */
  private const LOCATIONS = [
    'San José' => [
      'San José'      => ['Carmen', 'Merced', 'Hospital', 'Catedral', 'Zapote'],
      'Escazú'        => ['Escazú', 'San Antonio', 'San Rafael'],
      'Desamparados'  => ['Desamparados', 'San Miguel', 'San Juan de Dios'],
      'Pérez Zeledón' => ['San Isidro de El General', 'Daniel Flores', 'Rivas'],
      'Goicoechea'    => ['Guadalupe', 'San Francisco', 'Calle Blancos'],
      'Santa Ana'     => ['Santa Ana', 'Salitral', 'Pozos', 'Uruca'],
      'Alajuelita'    => ['Alajuelita', 'San Josecito', 'San Antonio'],
      'Vázquez de Coronado' => ['San Isidro', 'San Rafael', 'Dulce Nombre'],
      'Acosta'        => ['San Ignacio', 'Guaitil', 'Palmichal'],
      'Tibás'         => ['San Juan', 'Cinco Esquinas', 'Anselmo Llorente'],
    ],
    'Alajuela' => [
      'Alajuela'      => ['Alajuela', 'San José', 'Carrizal', 'San Antonio'],
      'San Ramón'     => ['San Ramón', 'Santiago', 'San Juan'],
      'Grecia'        => ['Grecia', 'San Isidro', 'San José'],
      'San Carlos'    => ['Quesada', 'Florencia', 'Buenavista', 'Aguas Zarcas'],
      'Atenas'        => ['Atenas', 'Jesús', 'Mercedes'],
      'Naranjo'       => ['Naranjo', 'San Miguel', 'San José'],
      'Palmares'      => ['Palmares', 'Zaragoza', 'Buenos Aires'],
      'Poás'          => ['San Pedro', 'San Juan', 'San Rafael'],
      'Orotina'       => ['Orotina', 'El Mastate', 'Hacienda Vieja'],
    ],
    'Cartago' => [
      'Cartago'       => ['Oriental', 'Occidental', 'Carmen', 'San Nicolás'],
      'Paraíso'       => ['Paraíso', 'Santiago', 'Orosi'],
      'La Unión'      => ['Tres Ríos', 'San Diego', 'San Juan', 'Concepción'],
      'Turrialba'     => ['Turrialba', 'La Suiza', 'Peralta'],
      'El Guarco'     => ['El Tejar', 'San Isidro', 'Tobosi'],
      'Oreamuno'      => ['San Rafael', 'Cot', 'Potrero Cerrado'],
    ],
    'Heredia' => [
      'Heredia'       => ['Heredia', 'Mercedes', 'San Francisco', 'Ulloa'],
      'San Rafael'    => ['San Rafael', 'San Josecito', 'Los Ángeles'],
      'San Isidro'    => ['San Isidro', 'San José', 'Concepción'],
      'Belén'         => ['La Asunción', 'San Antonio', 'San Rafael'],
      'Flores'        => ['San Joaquín', 'Barrantes', 'Llorente'],
      'San Pablo'     => ['San Pablo'],
      'Santo Domingo' => ['Santo Domingo', 'San Vicente', 'San Miguel'],
      'Barva'         => ['Barva', 'San Pedro', 'San Pablo'],
    ],
    'Guanacaste' => [
      'Liberia'       => ['Liberia', 'Cañas Dulces', 'Mayorga'],
      'Nicoya'        => ['Nicoya', 'Mansión', 'San Antonio'],
      'Santa Cruz'    => ['Santa Cruz', 'Bolsón', 'Veintisiete de Abril'],
      'Bagaces'       => ['Bagaces', 'La Fortuna', 'Mogote'],
      'Carrillo'      => ['Filadelfia', 'Palmira', 'Sardinal'],
      'Cañas'         => ['Cañas', 'Bebedero', 'Porozal'],
      'Tilarán'       => ['Tilarán', 'Quebrada Grande', 'Tronadora'],
    ],
    'Puntarenas' => [
      'Puntarenas'    => ['Puntarenas', 'Pitahaya', 'Chomes'],
      'Esparza'       => ['Espíritu Santo', 'San Juan Grande', 'Macacona'],
      'Buenos Aires'  => ['Buenos Aires', 'Volcán', 'Potrero Grande'],
      'Montes de Oro' => ['Miramar', 'San Isidro', 'Unión'],
      'Osa'           => ['Puerto Cortés', 'Palmar', 'Piedras Blancas'],
      'Quepos'        => ['Quepos', 'Savegre', 'Naranjito'],
      'Golfito'       => ['Golfito', 'Puerto Jiménez', 'Guaycará'],
      'Coto Brus'     => ['San Vito', 'Sabalito', 'Aguabuena'],
    ],
    'Limón' => [
      'Limón'         => ['Limón', 'Valle La Estrella', 'Río Blanco'],
      'Pococí'        => ['Guápiles', 'La Rita', 'Jiménez'],
      'Siquirres'     => ['Siquirres', 'Pacuarito', 'Florida'],
      'Talamanca'     => ['Bratsi', 'Sixaola', 'Cahuita'],
      'Matina'        => ['Matina', 'Batán', 'Carrandi'],
      'Guácimo'       => ['Guácimo', 'Río Jiménez', 'Duacarí'],
    ],
  ];

  /**
   * Devuelve las ubicaciones en JSON.
   * Parámetro opcional ?provincia= para filtrar cantones y distritos.
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

    echo json_encode(array_keys(self::LOCATIONS), JSON_UNESCAPED_UNICODE);
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
