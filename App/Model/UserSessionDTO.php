<?php

/**
 * UserSessionDTO — DTO liviano para guardar al usuario autenticado en
 * $_SESSION['user'].
 *
 * Sustituye la serialización del objeto de dominio completo (Client/Owner/
 * Admin) por un objeto inmutable que SOLO contiene datos escalares y
 * públicos: nunca la contraseña ni su hash. Incluye getters de
 * compatibilidad para que las vistas y controladoras que leían el objeto
 * de sesión sigan funcionando sin cambios.
 */
class UserSessionDTO
{
    public const ROLE_ADMIN  = 'admin';
    public const ROLE_CLIENT = 'client';
    public const ROLE_OWNER  = 'owner';

    private int $id;
    private string $role;
    private int $roleId;
    private string $name;
    private string $email;
    private ?string $phoneNumber;
    private string $image;
    private ?int $locationId;
    private string $ownerLastName;
    private string $ownerAlias;
    private string $ownerIdentification;

    public function __construct(
        int $id,
        string $role,
        int $roleId,
        string $name,
        string $email,
        ?string $phoneNumber = null,
        string $image = '',
        ?int $locationId = null,
        string $ownerLastName = '',
        string $ownerAlias = '',
        string $ownerIdentification = ''
    ) {
        $this->id = $id;
        $this->role = $role;
        $this->roleId = $roleId;
        $this->name = $name;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->image = $image;
        $this->locationId = $locationId;
        $this->ownerLastName = $ownerLastName;
        $this->ownerAlias = $ownerAlias;
        $this->ownerIdentification = $ownerIdentification;
    }

    // =========================================================
    // CONSTRUCCIÓN DESDE UNA ENTIDAD DE DOMINIO
    // =========================================================
    /**
     * Crea el DTO a partir de un Client/Owner/Admin (o entidad Role base).
     * Copia solo campos públicos; descarta la contraseña.
     */
    public static function fromObject(object $entity): self
    {
        if ($entity instanceof Client) {
            return new self(
                id: $entity->getIdClient(),
                role: self::ROLE_CLIENT,
                roleId: $entity->getIdRol(),
                name: $entity->getName(),
                email: $entity->getEmail(),
                phoneNumber: $entity->getPhoneNumber(),
                image: $entity->getImageClient(),
                locationId: $entity->getLocationId()
            );
        }

        if ($entity instanceof Owner) {
            return new self(
                id: $entity->getIdOwner(),
                role: self::ROLE_OWNER,
                roleId: $entity->getIdRol(),
                name: $entity->getName(),
                email: $entity->getEmail(),
                phoneNumber: $entity->getPhoneNumber(),
                image: $entity->getImageOwner(),
                ownerLastName: $entity->getLastNameOwner(),
                ownerAlias: $entity->getAliasOwner(),
                ownerIdentification: $entity->getIdentificationNumberOwner()
            );
        }

        if ($entity instanceof Admin) {
            return new self(
                id: $entity->getIdAdmin(),
                role: self::ROLE_ADMIN,
                roleId: $entity->getIdRol(),
                name: $entity->getName(),
                email: $entity->getEmail(),
                phoneNumber: $entity->getPhoneNumber(),
                image: $entity->getImageAdmin()
            );
        }

        // Entidad Role base (sin rol específico conocido).
        return new self(
            id: $entity->getIdRole(),
            role: '',
            roleId: $entity->getIdRole(),
            name: $entity->getName(),
            email: $entity->getEmail(),
            phoneNumber: $entity->getPhoneNumber()
        );
    }

    // =========================================================
    // SERIALIZACIÓN LIMPIA
    // =========================================================
    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'role'                 => $this->role,
            'roleId'               => $this->roleId,
            'name'                 => $this->name,
            'email'                => $this->email,
            'phoneNumber'          => $this->phoneNumber,
            'image'                => $this->image,
            'locationId'           => $this->locationId,
            'ownerLastName'        => $this->ownerLastName,
            'ownerAlias'           => $this->ownerAlias,
            'ownerIdentification'  => $this->ownerIdentification,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            role: (string) ($data['role'] ?? ''),
            roleId: (int) ($data['roleId'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            phoneNumber: isset($data['phoneNumber']) && $data['phoneNumber'] !== null
                ? (string) $data['phoneNumber']
                : null,
            image: (string) ($data['image'] ?? ''),
            locationId: isset($data['locationId']) && $data['locationId'] !== null
                ? (int) $data['locationId']
                : null,
            ownerLastName: (string) ($data['ownerLastName'] ?? ''),
            ownerAlias: (string) ($data['ownerAlias'] ?? ''),
            ownerIdentification: (string) ($data['ownerIdentification'] ?? '')
        );
    }

    // =========================================================
    // GETTERS PRINCIPALES
    // =========================================================
    public function getId(): int
    {
        return $this->id;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getLocationId(): ?int
    {
        return $this->locationId;
    }

    /**
     * Primer token del nombre (para saludos).
     */
    public function getFirstName(): string
    {
        $trimmed = trim($this->name);

        if ($trimmed === '') {
            return '';
        }

        return explode(' ', preg_replace('/\s+/', ' ', $trimmed))[0];
    }

    // =========================================================
    // GETTERS DE COMPATIBILIDAD (con la entidad de dominio)
    // =========================================================
    public function getIdRol(): int
    {
        return $this->roleId;
    }

    public function getIdRole(): int
    {
        return $this->roleId;
    }

    public function getIdClient(): int
    {
        return $this->role === self::ROLE_CLIENT ? $this->id : 0;
    }

    public function getIdOwner(): int
    {
        return $this->role === self::ROLE_OWNER ? $this->id : 0;
    }

    public function getIdAdmin(): int
    {
        return $this->role === self::ROLE_ADMIN ? $this->id : 0;
    }

    public function getImageClient(): string
    {
        return $this->role === self::ROLE_CLIENT ? $this->image : '';
    }

    public function getImageOwner(): string
    {
        return $this->role === self::ROLE_OWNER ? $this->image : '';
    }

    public function getImageAdmin(): string
    {
        return $this->role === self::ROLE_ADMIN ? $this->image : '';
    }

    public function getLastNameOwner(): string
    {
        return $this->ownerLastName;
    }

    public function getAliasOwner(): string
    {
        return $this->ownerAlias;
    }

    public function getIdentificationNumberOwner(): string
    {
        return $this->ownerIdentification;
    }
}
