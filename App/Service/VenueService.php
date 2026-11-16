<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/LocationService.php';
require_once __DIR__ . '/ImageStorageService.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/VenueImageRepository.php';
require_once __DIR__ . '/../Repository/VenueRentalBlockRepository.php';
require_once __DIR__ . '/../Model/Venue.php';
require_once __DIR__ . '/../Model/VenueImage.php';
require_once __DIR__ . '/../Model/VenueRentalBlock.php';

class VenueService
{
  private VenueRepository $venueRepo;
  private LocationService $locationService;
  private VenueImageRepository $imageRepo;
  private VenueRentalBlockRepository $blockRepo;

  public function __construct(PDO $connection)
  {
    $this->venueRepo = new VenueRepository($connection);
    $this->locationService = new LocationService(new LocationRepository($connection));
    $this->imageRepo = new VenueImageRepository($connection);
    $this->blockRepo = new VenueRentalBlockRepository($connection);
  }

  public function validateAndCreate(
    int $ownerPk,
    string $province,
    string $canton,
    string $district,
    ?string $town,
    ?string $description,
    string $name,
    ?string $type,
    ?int $capacity,
    float $price,
    ?string $image,
    ?float $latitude = null,
    ?float $longitude = null,
    string $locationType = 'exacta',
    ?string $approximate = null,
    array $extra = []
  ): int {
    $this->validateCommon($name, $type, $capacity, $price, $extra);

    if ($description === null || trim($description) === '') {
      throw new BusinessRuleException('Las otras señas de la ubicación son obligatorias.');
    }

    if (strtolower(trim($locationType)) === 'aproximada') {
      $locationPk = $this->locationService->saveApproximate(
        null,
        (string) $town,
        (string) $approximate,
        $description
      );
    } else {
      if (trim($province) === '' || trim($canton) === '' || trim($district) === '') {
        throw new BusinessRuleException('Debes completar provincia, cantón y distrito.');
      }

      $locationPk = $this->locationService->findOrCreateByParts(
        $province,
        $canton,
        $district,
        $town,
        $description,
        $latitude,
        $longitude
      );

      if ($locationPk === null) {
        throw new BusinessRuleException('No se pudo guardar la ubicación exacta.');
      }
    }

    $venue = new Venue(
      idVenue: 0,
      idOwner: $ownerPk,
      idLocation: $locationPk,
      nameVenue: $name,
      typeVenue: $type ?? '',
      capacityVenue: $capacity ?? 0,
      priceVenue: $price,
      imageVenue: $image ?? '',
      isActive: true,
      descriptionVenue: $this->nullableString($extra['venueDescription'] ?? null),
      sizeVenue: $this->nullableString($extra['size'] ?? null),
      bathroomsVenue: $this->nullableInt($extra['bathrooms'] ?? null),
      hasParking: !empty($extra['parking']),
      parkingCapacity: $this->nullableInt($extra['parkingCapacity'] ?? null),
      minCapacity: $this->nullableInt($extra['minCapacity'] ?? null),
      cleaningFee: (float) ($extra['cleaningFee'] ?? 0),
      checkInTime: $this->nullableTime($extra['checkInTime'] ?? null),
      checkOutTime: $this->nullableTime($extra['checkOutTime'] ?? null)
    );

    return $this->venueRepo->save($venue);
  }

  public function validateAndUpdate(
    Venue $venue,
    string $name,
    ?string $type,
    ?int $capacity,
    float $price,
    ?string $image,
    bool $active,
    ?int $idLocation,
    array $extra = []
  ): void {
    $this->validateCommon($name, $type, $capacity, $price, $extra);

    if ($idLocation === null || $idLocation <= 0) {
      throw new BusinessRuleException('La ubicación del local no es válida.');
    }

    $venue->setNameVenue($name);
    $venue->setTypeVenue($type ?? $venue->getTypeVenue());
    $venue->setCapacityVenue($capacity ?? $venue->getCapacityVenue());
    $venue->setPriceVenue($price);
    $venue->setImageVenue($image ?? $venue->getImageVenue());
    $venue->setIdLocation($idLocation);
    $venue->setIsActive($active);
    $venue->setDescriptionVenue($this->nullableString($extra['venueDescription'] ?? null));
    $venue->setSizeVenue($this->nullableString($extra['size'] ?? null));
    $venue->setBathroomsVenue($this->nullableInt($extra['bathrooms'] ?? null));
    $venue->setHasParking(!empty($extra['parking']));
    $venue->setParkingCapacity($this->nullableInt($extra['parkingCapacity'] ?? null));
    $venue->setMinCapacity($this->nullableInt($extra['minCapacity'] ?? null));
    $venue->setCleaningFee((float) ($extra['cleaningFee'] ?? 0));
    $checkIn = $this->nullableTime($extra['checkInTime'] ?? null);
    $checkOut = $this->nullableTime($extra['checkOutTime'] ?? null);
    if (($checkIn === null) !== ($checkOut === null)) {
      throw new BusinessRuleException('Completa tanto la hora de entrada como la hora de salida.');
    }
    if ($checkIn !== null && $checkOut <= $checkIn) {
      throw new BusinessRuleException('La hora de salida debe ser posterior a la hora de entrada.');
    }
    $venue->setCheckInTime($checkIn);
    $venue->setCheckOutTime($checkOut);

    $this->venueRepo->update($venue);
  }

  public function findById(int $idVenue): ?Venue
  {
    return $this->venueRepo->findById($idVenue);
  }

  public function findActive(): array
  {
    return $this->venueRepo->findActive();
  }

  public function findByFilters(array $filters = []): array
  {
    return $this->venueRepo->findByFilters($filters);
  }

  public function findByOwner(int $ownerPk): array
  {
    return $this->venueRepo->findByOwner($ownerPk);
  }

  public function findActiveByOwner(int $ownerPk): array
  {
    return $this->venueRepo->findActiveByOwner($ownerPk);
  }

  public function getImages(int $idVenue): array
  {
    return $this->imageRepo->findByVenue($idVenue);
  }

  public function addImage(int $idVenue, array $file): void
  {
    $path = ImageStorageService::store($file, 'venues', 'venue_' . $idVenue);
    $this->imageRepo->save(new VenueImage(
      idVenue: $idVenue,
      path: $path,
      sort: count($this->imageRepo->findByVenue($idVenue))
    ));
  }

  public function addUploadedGallery(int $idVenue): void
  {
    if (!isset($_FILES['gallery']) || !is_array($_FILES['gallery']['name'] ?? null)) {
      return;
    }

    $files = $_FILES['gallery'];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
      if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        continue;
      }

      $file = [
        'name' => $files['name'][$i],
        'type' => $files['type'][$i],
        'tmp_name' => $files['tmp_name'][$i],
        'error' => $files['error'][$i],
        'size' => $files['size'][$i],
      ];
      $this->addImage($idVenue, $file);
    }
  }

  public function removeImage(int $idImage): void
  {
    $this->imageRepo->deactivate($idImage);
  }

  public function getRentalBlocks(int $idVenue, bool $onlyActive = false): array
  {
    return $this->blockRepo->findByVenue($idVenue, $onlyActive);
  }

  public function saveRentalBlocks(int $idVenue, array $names, array $starts, array $ends, array $prices, array $ids = []): void
  {
    $blocks = [];
    $count = max(count($names), count($starts), count($ends), count($prices));

    for ($i = 0; $i < $count; $i++) {
      $name = trim((string) ($names[$i] ?? ''));
      $start = trim((string) ($starts[$i] ?? ''));
      $end = trim((string) ($ends[$i] ?? ''));
      $price = (float) ($prices[$i] ?? 0);
      $idBlock = (int) ($ids[$i] ?? 0);

      if ($name === '' && $start === '' && $end === '') {
        continue;
      }

      if ($name === '' || $start === '' || $end === '' || $price < 0) {
        throw new BusinessRuleException('Completa nombre, hora de inicio, hora final y precio de cada bloque.');
      }

      if ($end <= $start) {
        throw new BusinessRuleException('La hora final de un bloque debe ser posterior a la hora de inicio.');
      }

      $blocks[] = new VenueRentalBlock(
        idBlock: $idBlock,
        idVenue: $idVenue,
        name: $name,
        startTime: $start,
        endTime: $end,
        price: $price
      );
    }

    usort($blocks, static fn(VenueRentalBlock $a, VenueRentalBlock $b): int => strcmp($a->getStartTime(), $b->getStartTime()));

    for ($i = 1; $i < count($blocks); $i++) {
      if ($blocks[$i]->getStartTime() < $blocks[$i - 1]->getEndTime()) {
        throw new BusinessRuleException('Los bloques de alquiler no pueden superponerse entre sí.');
      }
    }

    $this->blockRepo->replaceForVenue($idVenue, $blocks);
  }

  private function validateCommon(string $name, ?string $type, ?int $capacity, float $price, array $extra): void
  {
    $checkIn = $this->nullableTime($extra['checkInTime'] ?? null);
    $checkOut = $this->nullableTime($extra['checkOutTime'] ?? null);
    if (($checkIn === null) !== ($checkOut === null)) {
      throw new BusinessRuleException('Completa tanto la hora de entrada como la hora de salida.');
    }
    if ($checkIn !== null && $checkOut <= $checkIn) {
      throw new BusinessRuleException('La hora de salida debe ser posterior a la hora de entrada.');
    }

    if ($capacity === null || $capacity <= 0) {
      throw new BusinessRuleException('La capacidad máxima del local debe ser mayor a 0.');
    }

    if ($price <= 0) {
      throw new BusinessRuleException('El precio de renta del local debe ser mayor a 0.');
    }

    if (trim($name) === '') {
      throw new BusinessRuleException('El nombre del local es obligatorio.');
    }

    if (trim((string) $type) === '') {
      throw new BusinessRuleException('El tipo de local es obligatorio.');
    }

    $minCapacity = $this->nullableInt($extra['minCapacity'] ?? null);
    if ($minCapacity !== null && ($minCapacity <= 0 || $minCapacity > $capacity)) {
      throw new BusinessRuleException('La capacidad mínima debe ser mayor a 0 y no superar la máxima.');
    }

    $bathrooms = $this->nullableInt($extra['bathrooms'] ?? null);
    if ($bathrooms !== null && $bathrooms < 0) {
      throw new BusinessRuleException('La cantidad de baños no puede ser negativa.');
    }

    $parkingCapacity = $this->nullableInt($extra['parkingCapacity'] ?? null);
    if ($parkingCapacity !== null && $parkingCapacity < 0) {
      throw new BusinessRuleException('La capacidad de parqueo no puede ser negativa.');
    }

    if ((float) ($extra['cleaningFee'] ?? 0) < 0) {
      throw new BusinessRuleException('El costo de limpieza no puede ser negativo.');
    }
  }

  private function nullableString(mixed $value): ?string
  {
    $value = trim((string) $value);
    return $value === '' ? null : $value;
  }

  private function nullableInt(mixed $value): ?int
  {
    return ($value === null || $value === '') ? null : (int) $value;
  }

  private function nullableTime(mixed $value): ?string
  {
    $value = trim((string) $value);
    if ($value === '') return null;
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
      throw new BusinessRuleException('Indica una hora válida para el horario general.');
    }
    return $value;
  }
}
