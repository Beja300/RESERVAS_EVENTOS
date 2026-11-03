<?php

/**
 * ENTIDAD: History
 * Representa una fila de tbuserhistory -- una acción que un usuario
 * (cualquier subtipo de Role) realizó en el sistema.
 */
class History
{
    private ?int $id;
    private int $roleId;
    private string $action;
    private ?string $entity;
    private ?int $entityId;
    private ?string $date;

    public function __construct(
        int $roleId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        ?int $id = null,
        ?string $date = null
    ) {
        $this->roleId = $roleId;
        $this->action = $action;
        $this->entity = $entity;
        $this->entityId = $entityId;
        $this->id = $id;
        $this->date = $date;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }
}
