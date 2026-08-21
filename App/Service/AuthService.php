<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Model/RoleRepository.php';
require_once __DIR__ . '/../Model/AdminRepository.php';
require_once __DIR__ . '/../Model/ClientRepository.php';
require_once __DIR__ . '/../Model/OwnerRepository.php';

class AuthService
{
    private RoleRepository $roleRepo;
    private AdminRepository $adminRepo;
    private ClientRepository $clientRepo;
    private OwnerRepository $ownerRepo;

    public function __construct()
    {
        $this->roleRepo = new RoleRepository();
        $this->adminRepo = new AdminRepository();
        $this->clientRepo = new ClientRepository();
        $this->ownerRepo = new OwnerRepository();
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

    public function validatePhoneFormat(?string $phone): void
    {
        if ($phone !== null && $phone !== '' && !preg_match('/^[0-9]{8}$/', $phone)) {
            throw new BusinessRuleException("El teléfono debe tener 8 dígitos.");
        }
    }

    public function registerClient(string $name, string $email, string $password, ?string $phone = null): Client
    {
        $this->validateEmailIsUnique($email);
        $this->validatePasswordStrength($password);
        $this->validatePhoneFormat($phone);

        $client = new Client(name: $name, email: $email, password: $password, phone: $phone);
        $this->clientRepo->save($client);
        return $this->clientRepo->findByEmail($email);
    }

    public function registerOwner(
        string $name,
        string $email,
        string $password,
        string $ownerName,
        ?string $ownerLastName = null,
        ?string $phone = null,
        ?string $ownerAlias = null,
        ?string $ownerIdentification = null
    ): Owner {
        $this->validateEmailIsUnique($email);
        $this->validatePasswordStrength($password);
        $this->validatePhoneFormat($phone);

        $owner = new Owner(
            name: $name, email: $email, password: $password, ownerName: $ownerName,
            ownerLastName: $ownerLastName, phone: $phone, ownerAlias: $ownerAlias,
            ownerIdentification: $ownerIdentification
        );
        $this->ownerRepo->save($owner);
        return $this->ownerRepo->findByEmail($email);
    }

    public function registerAdmin(string $name, string $email, string $password, ?string $phone = null): Admin
{
    $this->validateEmailIsUnique($email);
    $this->validatePasswordStrength($password);
    $this->validatePhoneFormat($phone);

    $admin = new Admin(name: $name, email: $email, password: $password, phone: $phone);
    $this->adminRepo->save($admin);
    return $this->adminRepo->findByEmail($email);
}

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
            if (!$user->isActive()) {
                throw new BusinessRuleException("Esta cuenta está desactivada.");
            }
            return ['type' => $type, 'user' => $user];
        }

        throw new BusinessRuleException("Correo o contraseña incorrectos.");
    }
}