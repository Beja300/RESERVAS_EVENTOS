<?php

require_once __DIR__ . '/../Configuration/Autoloader.php';

Autoloader::register();

final class SuiteRunner
{
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    public function test(string $category, string $name, callable $test): void
    {
        $this->total++;

        try {
            $test();
            $this->passed++;
            $this->results[] = [$category, $name, true, ''];
        } catch (Throwable $e) {
            $this->failed++;
            $this->results[] = [$category, $name, false, $e->getMessage()];
        }
    }

    public function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new RuntimeException($message !== '' ? $message : 'Se esperaba que la condición fuera verdadera.');
        }
    }

    public function assertFalse(bool $condition, string $message = ''): void
    {
        if ($condition) {
            throw new RuntimeException($message !== '' ? $message : 'Se esperaba que la condición fuera falsa.');
        }
    }

    public function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException(
                ($message !== '' ? $message . ': ' : '')
                . 'esperado ' . $this->export($expected)
                . ', obtenido ' . $this->export($actual)
            );
        }
    }

    public function assertFloat(float $expected, float $actual, string $message = '', float $epsilon = 0.00001): void
    {
        if (abs($expected - $actual) > $epsilon) {
            throw new RuntimeException(
                ($message !== '' ? $message . ': ' : '')
                . 'esperado ' . $this->export($expected)
                . ', obtenido ' . $this->export($actual)
            );
        }
    }

    public function assertNotNull(mixed $value, string $message = ''): void
    {
        if ($value === null) {
            throw new RuntimeException($message !== '' ? $message : 'Se esperaba un valor distinto de null.');
        }
    }

    public function assertInstanceOf(string $class, mixed $value, string $message = ''): void
    {
        if (!$value instanceof $class) {
            throw new RuntimeException(
                ($message !== '' ? $message . ': ' : '')
                . 'se esperaba una instancia de ' . $class
                . ', obtenido ' . get_debug_type($value)
            );
        }
    }

    public function assertThrows(string $class, callable $callback, string $message = ''): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            if ($e instanceof $class) {
                return;
            }

            throw new RuntimeException(
                ($message !== '' ? $message . ': ' : '')
                . 'se esperaba ' . $class
                . ', se lanzó ' . get_class($e)
                . ' (' . $e->getMessage() . ')'
            );
        }

        throw new RuntimeException(
            ($message !== '' ? $message . ': ' : '')
            . 'no se lanzó la excepción esperada ' . $class
        );
    }

    public function printReport(): int
    {
        $useColor = $this->useColor();
        $okLabel = $useColor ? "\033[32m[OK]\033[0m" : '[OK]';
        $failLabel = $useColor ? "\033[31m[FALLÓ]\033[0m" : '[FALLÓ]';

        echo "\n";
        echo $this->separator() . "\n";
        printf("| %-3s | %-24s | %-64s | %-12s |\n", '#', 'CATEGORÍA', 'PRUEBA', 'RESULTADO');
        echo $this->separator() . "\n";

        foreach ($this->results as $index => $result) {
            [$category, $name, $passed, $error] = $result;

            printf(
                "| %-3d | %-24s | %-64s | %-12s |\n",
                $index + 1,
                $this->truncate($category, 24),
                $this->truncate($name, 64),
                $passed ? $okLabel : $failLabel
            );

            if (!$passed) {
                echo '    Detalle: ' . $error . "\n";
            }
        }

        echo $this->separator() . "\n";
        printf(
            "Total: %d | Aprobadas: %d | Fallidas: %d\n",
            $this->total,
            $this->passed,
            $this->failed
        );

        if ($this->failed === 0) {
            echo ($useColor ? "\033[32m" : '') . 'RESULTADO FINAL: TODAS LAS PRUEBAS PASARON' . ($useColor ? "\033[0m" : '') . "\n";
            return 0;
        }

        echo ($useColor ? "\033[31m" : '') . 'RESULTADO FINAL: EXISTEN PRUEBAS FALLIDAS' . ($useColor ? "\033[0m" : '') . "\n";
        return 1;
    }

    private function separator(): string
    {
        return '+' . str_repeat('-', 5) . '+' . str_repeat('-', 26) . '+' . str_repeat('-', 66) . '+' . str_repeat('-', 14) . '+';
    }

    private function truncate(string $value, int $length): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value) > $length) {
                return mb_substr($value, 0, max(0, $length - 1)) . '…';
            }

            return $value;
        }

        return strlen($value) > $length ? substr($value, 0, max(0, $length - 1)) . '~' : $value;
    }

    private function export(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_string($value)) {
            return '"' . $value . '"';
        }

        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    private function useColor(): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        if (strtoupper((string) getenv('TERM')) === 'DUMB') {
            return false;
        }

        if (function_exists('stream_isatty')) {
            return @stream_isatty(STDOUT);
        }

        return true;
    }
}

function containsSensitiveKey(array $data): bool
{
    $blocked = ['password', 'passwd', 'hash', 'secret', 'token', 'credential', 'salt'];

    foreach (array_keys($data) as $key) {
        $normalized = strtolower((string) $key);

        foreach ($blocked as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }
    }

    return false;
}

function invokePrivateMethod(object $object, string $method, array $arguments = []): mixed
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs($object, $arguments);
}

function findBookableSlot(PDO $connection, BookingRepository $bookingRepo): array
{
    $venues = $connection
        ->query("SELECT tbvenueid FROM tbvenue WHERE tbvenueactive = TRUE AND tbvenueprice > 0 ORDER BY tbvenueid")
        ->fetchAll(PDO::FETCH_COLUMN);

    $clientId = $connection
        ->query("SELECT tbclientid FROM tbclient WHERE tbclientactive = TRUE ORDER BY tbclientid LIMIT 1")
        ->fetchColumn();

    if ($venues === [] || $clientId === false) {
        throw new RuntimeException('No hay datos base suficientes (venue activo y cliente) para probar una reserva válida.');
    }

    $today = new DateTimeImmutable('today');

    for ($offset = 30; $offset <= 360; $offset++) {
        $date = $today->modify('+' . $offset . ' days')->format('Y-m-d');

        foreach ($venues as $venueId) {
            $venueId = (int) $venueId;

            if (!$bookingRepo->hasActiveRangeConflict($venueId, $date, $date)) {
                return [(int) $clientId, $venueId, $date, $date];
            }
        }
    }

    throw new RuntimeException('No se encontró un rango libre en los próximos 365 días para probar una reserva válida.');
}

$runner = new SuiteRunner();
$connection = null;

$runner->test('Infraestructura', 'Autoloader resuelve clases clave', function (): void {
    foreach (['DataBase', 'UserSessionDTO', 'BookingService', 'VenueService', 'LocationService', 'OwnerVenueController'] as $class) {
        if (!class_exists($class)) {
            throw new RuntimeException('El autoloader no resolvió la clase ' . $class . '.');
        }
    }
});

$runner->test('Infraestructura', 'Conexión Singleton PDO a MySQL', function () use (&$connection, $runner): void {
    $connection = DataBase::getConnection();

    $runner->assertInstanceOf(PDO::class, $connection, 'Conexión PDO');
    $runner->assertSame(1, (int) $connection->query('SELECT 1')->fetchColumn(), 'SELECT 1');

    $database = $connection->query('SELECT DATABASE()')->fetchColumn();
    $runner->assertTrue(is_string($database) && $database !== '', 'No se pudo determinar la base de datos activa.');

    $runner->assertSame($connection, DataBase::getConnection(), 'El Singleton devolvió una conexión distinta');
});

$runner->test('DTO de sesión', 'Cliente expone ID, rol y datos públicos', function () use ($runner): void {
    $dto = new UserSessionDTO(
        id: 7,
        role: UserSessionDTO::ROLE_CLIENT,
        roleId: 2,
        name: 'Ana Vargas',
        email: 'ana@example.com',
        phoneNumber: '88880001',
        image: 'client.jpg',
        locationId: 4
    );

    $runner->assertSame(7, $dto->getId(), 'ID');
    $runner->assertSame('client', $dto->getRole(), 'Rol');
    $runner->assertSame(2, $dto->getRoleId(), 'Role ID');
    $runner->assertSame(7, $dto->getIdClient(), 'ID cliente');
    $runner->assertSame(0, $dto->getIdOwner(), 'ID owner debe ser 0');
    $runner->assertSame(0, $dto->getIdAdmin(), 'ID admin debe ser 0');
    $runner->assertSame('Ana', $dto->getFirstName(), 'Primer nombre');
    $runner->assertFalse(containsSensitiveKey($dto->toArray()), 'El DTO filtró una clave sensible');
});

$runner->test('DTO de sesión', 'Owner expone identificación sin datos sensibles', function () use ($runner): void {
    $dto = new UserSessionDTO(
        id: 11,
        role: UserSessionDTO::ROLE_OWNER,
        roleId: 3,
        name: 'María Fernández',
        email: 'owner@example.com',
        ownerLastName: 'Rodríguez',
        ownerAlias: 'Mari',
        ownerIdentification: '207610598'
    );

    $runner->assertSame(11, $dto->getIdOwner(), 'ID owner');
    $runner->assertSame(0, $dto->getIdClient(), 'ID cliente debe ser 0');
    $runner->assertSame('Rodríguez', $dto->getLastNameOwner(), 'Apellido owner');
    $runner->assertSame('Mari', $dto->getAliasOwner(), 'Alias owner');
    $runner->assertSame('207610598', $dto->getIdentificationNumberOwner(), 'Identificación owner');
    $runner->assertFalse(containsSensitiveKey($dto->toArray()), 'El DTO filtró una clave sensible');
});

$runner->test('DTO de sesión', 'Admin expone ID y rol correctamente', function () use ($runner): void {
    $dto = new UserSessionDTO(
        id: 21,
        role: UserSessionDTO::ROLE_ADMIN,
        roleId: 1,
        name: 'Admin General',
        email: 'admin@example.com'
    );

    $runner->assertSame(21, $dto->getIdAdmin(), 'ID admin');
    $runner->assertSame(0, $dto->getIdOwner(), 'ID owner debe ser 0');
    $runner->assertSame(0, $dto->getIdClient(), 'ID cliente debe ser 0');
    $runner->assertSame('', $dto->getImageOwner(), 'Imagen owner debe estar vacía');
    $runner->assertFalse(containsSensitiveKey($dto->toArray()), 'El DTO filtró una clave sensible');
});

$runner->test('DTO de sesión', 'fromArray ignora claves sensibles y no hay setters públicos', function () use ($runner): void {
    $dto = UserSessionDTO::fromArray([
        'id' => 5,
        'role' => 'client',
        'roleId' => 2,
        'name' => 'Cliente Seguro',
        'email' => 'seguro@example.com',
        'password' => 'NoDebeEntrar',
        'passwordHash' => '$2y$hash',
        'token' => 'abc123',
    ]);

    $array = $dto->toArray();
    $runner->assertFalse(containsSensitiveKey($array), 'fromArray conservó claves sensibles');
    $runner->assertSame(5, $dto->getId(), 'ID');

    $reflection = new ReflectionClass(UserSessionDTO::class);

    foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        throw new RuntimeException('El DTO expone la propiedad pública ' . $property->getName() . '.');
    }

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if (str_starts_with(strtolower($method->getName()), 'set')) {
            throw new RuntimeException('El DTO expone el setter público ' . $method->getName() . '.');
        }
    }
});

$runner->test('Sanitización Owner', 'Precio con colón y separadores', function () use ($runner): void {
    $reflection = new ReflectionClass(OwnerVenueController::class);
    $controller = $reflection->newInstanceWithoutConstructor();

    $runner->assertFloat(1250500.0, invokePrivateMethod($controller, 'parsePrice', ['₡ 1,250,500.00']), 'Formato con comas');
    $runner->assertFloat(1250500.0, invokePrivateMethod($controller, 'parsePrice', ['₡ 1.250.500,00']), 'Formato europeo');
    $runner->assertFloat(0.0, invokePrivateMethod($controller, 'parsePrice', ['']), 'Valor vacío');
    $runner->assertFloat(0.0, invokePrivateMethod($controller, 'parsePrice', ['texto sin número']), 'Valor inválido');
});

$runner->test('Sanitización Owner', 'Capacidad extrae solo dígitos', function () use ($runner): void {
    $reflection = new ReflectionClass(OwnerVenueController::class);
    $controller = $reflection->newInstanceWithoutConstructor();

    $runner->assertSame(350, invokePrivateMethod($controller, 'parseCapacity', ['350 personas']), 'Capacidad con texto');
    $runner->assertSame(1200, invokePrivateMethod($controller, 'parseCapacity', ['1,200']), 'Capacidad con separador');
    $runner->assertSame(null, invokePrivateMethod($controller, 'parseCapacity', ['']), 'Capacidad vacía');
    $runner->assertSame(null, invokePrivateMethod($controller, 'parseCapacity', ['sin dígitos']), 'Capacidad inválida');
});

$runner->test('Sanitización Owner', 'Otras señas conserva y limpia el texto', function () use (&$connection, $runner): void {
    $runner->assertInstanceOf(PDO::class, $connection, 'Conexión PDO');

    DataBase::beginTransaction();

    try {
        $service = new LocationService(new LocationRepository($connection));
        $marker = substr(bin2hex(random_bytes(8)), 0, 10);
        $expected = 'Otras señas: portón negro, 200 m norte del parque.';

        $idLocation = $service->validateAndCreate(
            'Provincia Prueba ' . $marker,
            'Cantón Prueba ' . $marker,
            'Distrito Prueba',
            '  Barrio Norte  ',
            '  ' . $expected . '  ',
            9.93,
            -84.08
        );

        $runner->assertTrue($idLocation > 0, 'No se creó la ubicación de prueba.');

        $location = $service->findById($idLocation);
        $runner->assertNotNull($location, 'No se recuperó la ubicación de prueba.');
        $runner->assertSame($expected, $location->getDescriptionLocation(), 'Texto de otras señas');
        $runner->assertSame('Barrio Norte', $location->getTownLocation(), 'Barrio normalizado');
    } finally {
        if (DataBase::inTransaction()) {
            DataBase::rollBack();
        }
    }
});

$runner->test('Reservas', 'Reserva válida procesa y genera detalle', function () use (&$connection, $runner): void {
    $runner->assertInstanceOf(PDO::class, $connection, 'Conexión PDO');

    $bookingRepo = new BookingRepository($connection);
    [$clientId, $venueId, $startDate, $endDate] = findBookableSlot($connection, $bookingRepo);

    DataBase::beginTransaction();

    try {
        $service = new BookingService();
        $bookingId = $service->createBooking(
            $clientId,
            $venueId,
            $startDate,
            $endDate,
            'Boda',
            'Reserva generada por la suite de pruebas'
        );

        $runner->assertTrue($bookingId > 0, 'No se obtuvo el ID de la reserva.');

        $booking = $bookingRepo->findById($bookingId);
        $runner->assertNotNull($booking, 'La reserva no existe dentro de la transacción.');
        $runner->assertSame($clientId, $booking->getIdClient(), 'Cliente de la reserva');
        $runner->assertSame($venueId, $booking->getIdLocal(), 'Local de la reserva');
        $runner->assertSame($startDate, $booking->getBookingDate(), 'Fecha de inicio');
        $runner->assertSame($endDate, $booking->getBookingEndDate(), 'Fecha final');

        $stmt = $connection->prepare(
            'SELECT COUNT(*) FROM tbdetail d INNER JOIN tbbookingdetail bd ON bd.tbdetailid = d.tbdetailid WHERE bd.tbbookingid = :id'
        );
        $stmt->execute([':id' => $bookingId]);

        $runner->assertTrue((int) $stmt->fetchColumn() > 0, 'La reserva no generó línea de detalle.');
    } finally {
        if (DataBase::inTransaction()) {
            DataBase::rollBack();
        }
    }
});

$runner->test('Reservas', 'Fecha final anterior a la inicial es rechazada', function () use (&$connection, $runner): void {
    $runner->assertInstanceOf(PDO::class, $connection, 'Conexión PDO');

    $bookingRepo = new BookingRepository($connection);
    [$clientId, $venueId] = findBookableSlot($connection, $bookingRepo);

    $start = (new DateTimeImmutable('today'))->modify('+60 days')->format('Y-m-d');
    $end = (new DateTimeImmutable('today'))->modify('+59 days')->format('Y-m-d');

    $runner->assertThrows(
        BusinessRuleException::class,
        fn() => (new BookingService())->createBooking($clientId, $venueId, $start, $end),
        'Rango invertido'
    );
});

$runner->test('Reservas', 'Fecha del año 2050 excede el límite de 365 días', function () use (&$connection, $runner): void {
    $runner->assertInstanceOf(PDO::class, $connection, 'Conexión PDO');

    $bookingRepo = new BookingRepository($connection);
    [$clientId, $venueId] = findBookableSlot($connection, $bookingRepo);

    $runner->assertThrows(
        BusinessRuleException::class,
        fn() => (new BookingService())->createBooking($clientId, $venueId, '2050-01-01', '2050-01-02'),
        'Fecha fuera de rango'
    );

    $runner->assertFalse(DataBase::inTransaction(), 'La validación dejó una transacción abierta.');
});

$runner->test('Atomicidad', 'Rollback restaura el estado baseline', function () use (&$connection, $runner): void {
    $runner->assertInstanceOf(PDO::class, $connection, 'Conexión PDO');
    $runner->assertFalse(DataBase::inTransaction(), 'La conexión ya tenía una transacción abierta.');
    $runner->assertFalse($connection->inTransaction(), 'PDO ya tenía una transacción abierta.');

    $marker = 'SUITE_ROLLBACK_' . bin2hex(random_bytes(8));

    try {
        DataBase::beginTransaction();

        $runner->assertTrue(DataBase::inTransaction(), 'La transacción no se inició.');
        $runner->assertTrue($connection->inTransaction(), 'PDO no registró la transacción.');

        $stmt = $connection->prepare(
            'INSERT INTO tbbookinghistory (tbbookingid, tbroleid, tbbookinghistoryaction, tbbookinghistorydetail, tbbookinghistoryactive)
             VALUES (0, NULL, :action, :detail, TRUE)'
        );
        $stmt->execute([':action' => 'SUITE_TEST', ':detail' => $marker]);

        $countStmt = $connection->prepare('SELECT COUNT(*) FROM tbbookinghistory WHERE tbbookinghistorydetail = :detail');
        $countStmt->execute([':detail' => $marker]);
        $runner->assertSame(1, (int) $countStmt->fetchColumn(), 'La fila marcadora no se insertó.');

        DataBase::rollBack();

        $runner->assertFalse(DataBase::inTransaction(), 'DataBase mantiene una transacción pendiente.');
        $runner->assertFalse($connection->inTransaction(), 'PDO mantiene una transacción pendiente.');
        $runner->assertSame(0, DataBase::transactionDepth(), 'La profundidad de transacción no volvió a 0.');

        $countStmt->execute([':detail' => $marker]);
        $runner->assertSame(0, (int) $countStmt->fetchColumn(), 'El rollback no eliminó la fila marcadora.');
    } finally {
        if (DataBase::inTransaction() || $connection->inTransaction()) {
            DataBase::rollBackActive();
        }
    }
});

exit($runner->printReport());
