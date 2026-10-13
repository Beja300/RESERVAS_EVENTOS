<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/UserSessionDTO.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';

class AuthService
{
    private RoleRepository $roleRepo;
    private AdminRepository $adminRepo;
    private ClientRepository $clientRepo;
    private OwnerRepository $ownerRepo;

    public function __construct()
    {
        $connection = DataBase::getConnection();

        $this->roleRepo = new RoleRepository($connection);
        $this->adminRepo = new AdminRepository($connection);
        $this->clientRepo = new ClientRepository($connection);
        $this->ownerRepo = new OwnerRepository($connection);
    }

    // =========================================================
    // VALIDACIONES
    // =========================================================
    public function validateEmailIsUnique(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessRuleException("El correo electrónico no es válido.");
        }

        if ($this->roleRepo->findByEmail($email) !== null) {
            throw new BusinessRuleException("Ya existe una cuenta registrada con ese correo.");
        }
    }

    public function validateNameNotEmpty(string $name, string $message = "El nombre es obligatorio."): void
    {
        if (trim($name) === '') {
            throw new BusinessRuleException($message);
        }
    }

    public function validatePasswordStrength(string $password): void
    {
        if (strlen($password) < 8 || !preg_match('/[0-9]/', $password)) {
            throw new BusinessRuleException("La contraseña debe tener al menos 8 caracteres y un número.");
        }
    }

    /**
     * Normaliza un teléfono dejando solo sus dígitos.
     *
     * Permite aceptar entradas con formato (ej. "8888-8888", "8888 8888"
     * o "+506 8888-8888") y almacenar siempre los 8 dígitos limpios.
     * Devuelve null cuando el valor viene vacío.
     */
    public function normalizePhone(?string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phoneNumber);

        return $digits === '' ? null : $digits;
    }

    /**
     * Valida que el teléfono (si viene) tenga exactamente 8 dígitos.
     *
     * Se normaliza primero, por lo que acepta tanto "88888888" como
     * "8888-8888" o "8888 8888".
     */
    public function validatePhoneFormat(?string $phoneNumber): void
    {
        if ($phoneNumber === null || trim($phoneNumber) === '') {
            return;
        }

        if (!preg_match('/^\d{8}$/', preg_replace('/\D/', '', $phoneNumber))) {
            throw new BusinessRuleException("El teléfono debe contener 8 dígitos (ej. 8888-8888).");
        }
    }

    public function validateIdentificationIsUnique(string $identificationNumber): void
    {
        if ($this->ownerRepo->findByIdentificationNumber($identificationNumber) !== null) {
            throw new BusinessRuleException("Ya existe un propietario registrado con ese número de identificación.");
        }
    }

    public function validateEmailFormat(string $email): void
    {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessRuleException("El correo electrónico no es válido.");
        }
    }

    /**
     * Normaliza una identificación dejando solo sus dígitos.
     */
    public function normalizeIdentification(?string $identificationNumber): string
    {
        return preg_replace('/\D/', '', (string) $identificationNumber);
    }

    /**
     * Valida el formato de una identificación costarricense.
     *
     * Acepta:
     *  - Cédula física   con guiones: 1-2345-0678
     *  - Cédula jurídica con guiones: 3-101-123456
     *  - DIMEX           con guiones: 1-2345-67890 / 1-2345-678901
     *  - Cualquiera de las anteriores como 9 a 12 dígitos sin guiones.
     */
    public function validateIdentificationFormat(string $identificationNumber): void
    {
        $digits = $this->normalizeIdentification($identificationNumber);

        $valid = preg_match('/^\d{1}-\d{4}-\d{4}$/', $identificationNumber)      // física
            || preg_match('/^\d{1}-\d{3}-\d{6}$/', $identificationNumber)        // jurídica
            || preg_match('/^\d{1}-\d{4}-\d{5,6}$/', $identificationNumber)      // DIMEX
            || preg_match('/^\d{9,12}$/', $digits);                              // sin guiones

        if (!$valid) {
            throw new BusinessRuleException(
                "El formato de la cédula no es válido. Usa cédula física (1-2345-0678), "
                . "jurídica (3-101-123456) o 9 a 12 dígitos (DIMEX/NITE)."
            );
        }
    }


    // =========================================================
    // REGISTRAR CLIENTE
    // =========================================================
    public function registerClient(string $name, string $email, string $password, ?string $phoneNumber = null): Client
    {
        $this->validateNameNotEmpty($name);
        $this->validateEmailIsUnique($email);
        $this->validatePasswordStrength($password);
        $this->validatePhoneFormat($phoneNumber);

        $phoneNumber = $this->normalizePhone($phoneNumber);

        $client = new Client(
            id: 0,
            name: $name,
            email: $email,
            password: $password,
            isActive: true,
            idClient: 0,
            isClientActive: true,
            idRol: 1,
            imageClient: '',
            phoneNumber: $phoneNumber
        );

        $this->clientRepo->save($client);

        return $this->clientRepo->findByEmail($email);
    }


    // =========================================================
    // REGISTRAR PROPIETARIO
    // =========================================================
    public function registerOwner(
        string $name,
        string $email,
        string $password,
        string $ownerFirstName,
        ?string $ownerLastName = null,
        ?string $phoneNumber = null,
        ?string $ownerAlias = null,
        ?string $ownerIdentification = null
    ): Owner {
        $this->validateNameNotEmpty($name, "El nombre de negocio es obligatorio.");
        $this->validateNameNotEmpty($ownerFirstName, "El nombre del propietario es obligatorio.");
        $this->validateEmailIsUnique($email);
        $this->validatePasswordStrength($password);
        $this->validatePhoneFormat($phoneNumber);

        $phoneNumber = $this->normalizePhone($phoneNumber);

        // La identificación, si viene, debe tener un formato válido y ser
        // única. Se valida el valor tal como llegó (acepta con o sin guiones)
        // y luego se normaliza a solo dígitos para almacenar.
        $rawIdentification = trim((string) $ownerIdentification);

        if ($rawIdentification !== '') {
            $this->validateIdentificationFormat($rawIdentification);
        }

        $ownerIdentification = $this->normalizeIdentification($rawIdentification);

        if ($ownerIdentification !== '') {
            $this->validateIdentificationIsUnique($ownerIdentification);
        }

        $owner = new Owner(
            id: 0,
            name: $name,
            email: $email,
            password: $password,
            isActive: true,
            idOwner: 0,
            firstName: $ownerFirstName,
            lastName: $ownerLastName ?? '',
            alias: $ownerAlias ?? '',
            identificationNumber: $ownerIdentification ?? '',
            isOwnerActive: true,
            idRol: 1,
            imageOwner: '',
            phoneNumber: $phoneNumber
        );

        $this->ownerRepo->save($owner);

        return $this->ownerRepo->findByEmail($email);
    }


    // =========================================================
    // ¿EXISTE AL MENOS UN ADMINISTRADOR?
    // =========================================================
    public function hasAnyAdmin(): bool
    {
        return $this->adminRepo->countAll() > 0;
    }


    // =========================================================
    // REGISTRAR ADMIN
    // =========================================================
    public function registerAdmin(string $name, string $email, string $password, ?string $phoneNumber = null): Admin
    {
        $this->validateEmailIsUnique($email);
        $this->validatePasswordStrength($password);
        $this->validatePhoneFormat($phoneNumber);

        $phoneNumber = $this->normalizePhone($phoneNumber);

        $admin = new Admin(
            id: 0,
            name: $name,
            email: $email,
            password: $password,
            isActive: true,
            idAdmin: 0,
            isAdminActive: true,
            idRol: 1,
            imageAdmin: '',
            phoneNumber: $phoneNumber
        );

        $this->adminRepo->save($admin);

        return $this->adminRepo->findByEmail($email);
    }


    // =========================================================
    // LOGIN
    // =========================================================
    /** @return array{type: string, user: UserSessionDTO} */
    public function login(string $email, string $password): array
    {
        $finders = [
            'admin'  => fn() => $this->adminRepo->findByEmail($email),
            'client' => fn() => $this->clientRepo->findByEmail($email),
            'owner'  => fn() => $this->ownerRepo->findByEmail($email),
        ];

        foreach ($finders as $type => $find) {
            $user = $find();

            if ($user === null) {
                continue;
            }

            if (!password_verify($password, $user->getPassword())) {
                throw new BusinessRuleException("Correo o contraseña incorrectos.");
            }

            if (!$user->getIsActive()) {
                throw new BusinessRuleException("Esta cuenta está desactivada.");
            }

            // Se guarda en sesión un DTO liviano (sin contraseña), no la
            // entidad de dominio completa.
            return ['type' => $type, 'user' => UserSessionDTO::fromObject($user)];
        }

        throw new BusinessRuleException("Correo o contraseña incorrectos.");
    }
}
