<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/OwnerPaymentRepository.php';

class OwnerPaymentService
{
  private OwnerPaymentRepository $repo;

  public function __construct(PDO $connection)
  {
    $this->repo = new OwnerPaymentRepository($connection);
  }

  // =========================================================
  // CONFIGURAR / ACTUALIZAR UN MÉTODO DE COBRO DEL OWNER
  // =========================================================
  public function save(int $ownerPk, int $paymentMethodPk, string $holder, string $account, string $instructions, bool $active): int
  {
    if ($paymentMethodPk <= 0) {
      throw new BusinessRuleException('Selecciona un método de pago.');
    }

    if ($active && trim($holder) === '') {
      throw new BusinessRuleException('El titular es obligatorio para este método de cobro.');
    }

    $existing = $this->repo->findByOwnerAndMethod($ownerPk, $paymentMethodPk);

    if ($existing !== null) {
      $op = $this->repo->findById((int) $existing['tbownerpaymentid']);
      $op->setHolder($holder);
      $op->setAccount($account);
      $op->setInstructions($instructions);
      $op->setIsActive($active);
      $this->repo->update($op);
      return $op->getIdOwnerPayment();
    }

    $op = new OwnerPayment(
      idOwner: $ownerPk,
      idPaymentMethod: $paymentMethodPk,
      holder: $holder,
      account: $account,
      instructions: $instructions,
      isActive: $active
    );

    return $this->repo->save($op);
  }

  // =========================================================
  // LISTAR MÉTODOS DE COBRO CONFIGURADOS DEL OWNER
  // =========================================================
  public function findByOwner(int $ownerPk): array
  {
    return $this->repo->findByOwner($ownerPk);
  }

  // =========================================================
  // ELIMINAR UN MÉTODO DE COBRO DEL OWNER
  // =========================================================
  public function remove(int $ownerPk, int $ownerPaymentPk): void
  {
    $op = $this->repo->findById($ownerPaymentPk);

    if ($op === null || $op->getIdOwner() !== $ownerPk) {
      throw new BusinessRuleException('No tienes permiso sobre ese método de cobro.');
    }

    $this->repo->delete($ownerPaymentPk);
  }
}
