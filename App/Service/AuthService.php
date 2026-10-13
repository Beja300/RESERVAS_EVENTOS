<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
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
    public function validateEmailFormat(string $email): void
    {
        $email = trim($email);

        if ($email === '') {
            throw new BusinessRuleException("El correo electrónico es obligatorio.");
        }

        if (strlen($email) > 300) {
            throw new BusinessRuleException("El correo electrónico no puede superar 300 caracteres.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessRuleException("El formato del correo electrónico no es válido.");
        }
    }

    public function validateEmailIsUnique(string $email): void
    {
        if ($this->roleRepo->findByEmail($email) !== null) {
            throw new BusinessRuleException("Ya existe una cuenta registrada con ese correo.");
        }
    }

    public function validatePasswordStrength(string $password): void
    {
        if (strlen($password) < 8 || !preg_match('/[0-9]/', $password)) {
            throw new BusinessRuleException("La contraseña debe tener al menos 8 caracteres y un número.");
        }
    }

    public function validatePhoneFormat(?string $phoneNumber): void
    {
        if ($phoneNumber !== null && $phoneNumber !== '' && !preg_match('/^[0-9]{8}$/', $phoneNumber)) {
            throw new BusinessRuleException("El teléfono debe tener 8 dígitos.");
        }
    }

    public function validateIdentificationIsUnique(string $identificationNumber): void
    {
        if ($this->ownerRepo->findByIdentificationNumber($identificationNumber) !== null) {
            throw new BusinessRuleException("Ya existe un propietario registrado con ese número de identificación.");
        }
    }

    public function validateIdentificationFormat(?string $identificationNumber): void
    {
        if ($identificationNumber === null || trim($identificationNumber) === '') {
            return;
        }

        if (!preg_match('/^[0-9]{9}$/', trim($identificationNumber))) {
            throw new BusinessRuleException("La identificación debe tener 9 dígitos numéricos.");
        }
    }


    // =========================================================
    // REGISTRAR CLIENTE
    // =========================================================
    public function registerClient(string $name, string $email, string $password, ?string $phoneNumber = null): Client
    {
        $this->validateEmailFormat($email);
        $this->validateEmailIsUnique($email);
        $this->validatePasswordStrength($password);
        $this->validatePhoneFormat($phoneNumber);

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
        $this->validateEmailFormat($email);
        $this->validateEmailIsUnique($email);
        $this->validatePasswordStrength($password);
        $this->validatePhoneFormat($phoneNumber);

        if ($ownerIdentification !== null) {
            $ownerIdentification = preg_replace('/\D/', '', $ownerIdentification);
            $ownerIdentification = $ownerIdentification === '' ? null : $ownerIdentification;
        }

        if ($ownerIdentification !== null) {
            $this->validateIdentificationFormat($ownerIdentification);
            $this->validateIdentificationIsUnique($ownerIdentification);
        }

        $owner = new Owner(
            id: 0,
            name: $name,
            email: $email,
            password: $password,
            isActive: true,
            idOwner: 0,
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
        $this->validateEmailFormat($email);
        $this->validateEmailIsUnique($email);
        $this->validatePasswordStrength($password);
        $this->validatePhoneFormat($phoneNumber);

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
    /** @return array{type: string, user: Admin|Client|Owner} */
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

            return ['type' => $type, 'user' => $user];
        }

        throw new BusinessRuleException("Correo o contraseña incorrectos.");
    }
}
