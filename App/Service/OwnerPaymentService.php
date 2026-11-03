<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/OwnerPaymentRepository.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';

class OwnerPaymentService
{
  private OwnerPaymentRepository $repo;
  private PaymentMethodRepository $paymentMethodRepo;

  public function __construct(PDO $connection)
  {
    $this->repo = new OwnerPaymentRepository($connection);
    $this->paymentMethodRepo = new PaymentMethodRepository($connection);
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

    $method = $this->paymentMethodRepo->findById($paymentMethodPk);
    if ($method === null || !$method->getIsActive()) {
      throw new BusinessRuleException('Este método de pago no está disponible.');
    }

    $methodName = $method->getPaymentMethod();
    $this->assertValidAccountForMethod($methodName, $account);

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
  private function assertValidAccountForMethod(string $methodName, string $account): void
  {
    $kind = self::methodKind($methodName);

    if ($kind === 'cash') {
      return;
    }

    if (trim($account) === '') {
      throw new BusinessRuleException(
        $kind === 'sinpe'
          ? 'El teléfono SINPE Móvil es obligatorio.'
          : 'La cuenta bancaria / IBAN es obligatoria.'
      );
    }

    $normalized = preg_replace('/[\s-]/', '', $account);

    if ($kind === 'sinpe') {
      if (preg_match('/^\d{8}$/', $normalized) !== 1) {
        throw new BusinessRuleException('El teléfono SINPE debe tener 8 dígitos.');
      }
      return;
    }

    if ($kind === 'transfer') {
      if (preg_match('/^[A-Za-z]{2}[\dA-Za-z]{18,22}$/', $normalized) !== 1) {
        throw new BusinessRuleException('Indica un IBAN válido para la transferencia bancaria.');
      }
      return;
    }

    $isSinpe = preg_match('/^\d{8}$/', $normalized) === 1;
    $isIban = preg_match('/^[A-Za-z]{2}[\dA-Za-z]{18,22}$/', $normalized) === 1;
    if (!$isSinpe && !$isIban) {
      throw new BusinessRuleException('La cuenta debe ser un teléfono SINPE de 8 dígitos o un IBAN válido.');
    }
  }

  public static function methodKind(string $methodName): string
  {
    $name = mb_strtolower($methodName);
    if (str_contains($name, 'efectivo') || str_contains($name, 'cash')) {
      return 'cash';
    }
    if (str_contains($name, 'sinpe')) {
      return 'sinpe';
    }
    if (str_contains($name, 'transfer')) {
      return 'transfer';
    }
    return 'other';
  }
}
