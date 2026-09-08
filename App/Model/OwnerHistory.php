<?php

/**
 * ENTIDAD: OwnerHistory
 * Representa una fila de tbownerhistory -- una acción registrada de un
 * propietario (ej. crear/editar un local, subir método de cobro).
 */
class OwnerHistory
{
    private ?int $id;
    private int $ownerId;
    private string $action;
    private ?string $detail;
    private ?string $date;
    private bool $active;

    public function __construct(
        int $ownerId,
        string $action,
        ?string $detail = null,
        ?int $id = null,
        ?string $date = null,
        bool $active = true
    ) {
        $this->ownerId = $ownerId;
        $this->action = $action;
        $this->detail = $detail;
        $this->id = $id;
        $this->date = $date;
        $this->active = $active;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}