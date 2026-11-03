<?php

/**
 * ENTIDAD BASE: Role
 *
 * Representa un registro de la tabla `role` -- la identidad "raíz"
 * que comparten Admin, Client y Owner. Solo tiene los datos
 * comunes a cualquier persona con cuenta en el sistema.
 *
 * Admin, Client y Owner EXTIENDEN esta clase (herencia): cada uno
 * hereda estos atributos y agrega los suyos propios.
 */
class Role
{
    protected int $idRole;
    protected string $name;
    protected string $email;
    protected string $password;
    protected ?string $phoneNumber;
    protected bool $isActive;

    public function __construct(
        int $idRole,
        string $name,
        string $email,
        string $password,
        ?string $phoneNumber = null,
        bool $isActive = true
    ) {
        $this->idRole = $idRole;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->phoneNumber = $phoneNumber;
        $this->isActive = $isActive;
    }

    // Getters
    public function getIdRole(): int
    {
        return $this->idRole;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    // Setters
    public function setIdRole(int $idRole): void
    {
        $this->idRole = $idRole;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}
