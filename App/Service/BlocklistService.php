<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BlockedClientRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Model/BlockedClient.php';

class BlocklistService
{
  private BlockedClientRepository $blockedRepo;
  private ClientRepository $clientRepo;

  public function __construct(PDO $connection)
  {
    $this->blockedRepo = new BlockedClientRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
  }

  public function listForOwner(int $ownerId): array
  {
    return $this->blockedRepo->findActiveByOwner($ownerId);
  }

  public function suggestionsForOwner(int $ownerId): array
  {
    $suggestions = $this->blockedRepo->overdueClientsByOwner($ownerId);

    return array_values(array_filter(
      $suggestions,
      fn(array $row): bool => !$this->blockedRepo->isBlocked($ownerId, (int) $row['tbclientid'])
    ));
  }

  public function block(int $ownerId, int $clientId, string $reason): int
  {
    if ($clientId <= 0 || $this->clientRepo->findByClientPk($clientId) === null) {
      throw new BusinessRuleException('El cliente indicado no existe.');
    }

    if ($this->blockedRepo->isBlocked($ownerId, $clientId)) {
      throw new BusinessRuleException('Ese cliente ya está bloqueado.');
    }

    return $this->blockedRepo->save(new BlockedClient(
      idOwner: $ownerId,
      idClient: $clientId,
      reason: trim($reason) !== '' ? trim($reason) : 'Bloqueado por el propietario',
      date: date('Y-m-d H:i:s')
    ));
  }

  public function blockByEmail(int $ownerId, string $email, string $reason): int
  {
    $client = $this->clientRepo->findByEmail(trim($email));

    if ($client === null) {
      throw new BusinessRuleException('No existe un cliente con ese correo.');
    }

    return $this->block($ownerId, $client->getIdClient(), $reason);
  }

  public function unblock(int $ownerId, int $blockId): void
  {
    if ($blockId <= 0 || !$this->blockedRepo->deactivate($blockId, $ownerId)) {
      throw new BusinessRuleException('El bloqueo no existe o no te pertenece.');
    }
  }

  public function isBlocked(int $ownerId, int $clientId): bool
  {
    return $this->blockedRepo->isBlocked($ownerId, $clientId);
  }
}
