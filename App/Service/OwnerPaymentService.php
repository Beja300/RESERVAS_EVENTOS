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
  public function save(int $ownerPk, int $paymentMethodPk, string $holder, string $account, string $instructions, bool $active, int $ownerPaymentPk = 0): int
  {
    if ($paymentMethodPk <= 0) {
      throw new BusinessRuleException('Selecciona un método de pago.');
    }

    if (trim($holder) === '') {
      throw new BusinessRuleException('El titular es obligatorio para este método de cobro.');
    }

    if (trim($account) === '') {
      throw new BusinessRuleException('La cuenta bancaria o teléfono SINPE es obligatoria.');
    }

    $this->assertValidAccount($account);

    if ($ownerPaymentPk > 0) {
      $op = $this->repo->findById($ownerPaymentPk);

      if ($op === null || $op->getIdOwner() !== $ownerPk) {
        throw new BusinessRuleException('Este método de cobro no existe.');
      }

      $op->setIdPaymentMethod($paymentMethodPk);
      $op->setHolder($holder);
      $op->setAccount($account);
      $op->setInstructions($instructions);
      $op->setIsActive($active);

      $this->repo->update($op);

      return $op->getIdOwnerPayment();
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

  // =========================================================
  // LA CUENTA DEBE SER UN SINPE MÓVIL (8 DÍGITOS) O UN IBAN.
  // =========================================================
  private function assertValidAccount(string $account): void
  {
    $normalized = preg_replace('/[\s-]/', '', $account);

    $isSinpe = preg_match('/^\d{8}$/', $normalized) === 1;
    $isIban = preg_match('/^[A-Za-z]{2}[\dA-Za-z]{18,22}$/', $normalized) === 1;

    if (!$isSinpe && !$isIban) {
      throw new BusinessRuleException('La cuenta debe ser un teléfono SINPE de 8 dígitos o un IBAN válido.');
    }
  }
}
