<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/OwnerPaymentRepository.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';

class OwnerPaymentService
{
  private OwnerPaymentRepository $repo;
  private PaymentMethodRepository $methodRepo;

  public function __construct(PDO $connection)
  {
    $this->repo = new OwnerPaymentRepository($connection);
    $this->methodRepo = new PaymentMethodRepository($connection);
  }

  // =========================================================
  // CONFIGURAR / ACTUALIZAR UN MÉTODO DE COBRO DEL OWNER
  // =========================================================
  public function save(int $ownerPk, int $paymentMethodPk, string $holder, string $account, string $instructions, bool $active, int $ownerPaymentPk = 0): int
  {
    if ($paymentMethodPk <= 0) {
      throw new BusinessRuleException('Selecciona un método de pago.');
    }

    $method = $this->methodRepo->findById($paymentMethodPk);
    if ($method === null || !$method->getIsActive()) throw new BusinessRuleException('Selecciona un método de pago activo.');
    $kind = $this->methodType($method->getPaymentMethod());
    if ($kind === 'onsite') {
      $holder = '';
      $account = '';
      $instructions = stripos($method->getPaymentMethod(), 'tarjeta') !== false
        ? 'Pago con tarjeta en el lugar.'
        : 'Pago en el lugar.';
    } else {
      if (trim($holder) === '') throw new BusinessRuleException('Indica el nombre del titular.');
      if ($kind === 'sinpe') $account = $this->normalizeSinpe($account);
      elseif ($kind === 'iban') $account = $this->normalizeIban($account);
      elseif (trim($account) === '') throw new BusinessRuleException('Indica el número de cuenta o teléfono.');
    }

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
  // Normalización y validación según el método seleccionado.
  // =========================================================
  private function methodType(string $method): string
  {
    $name = strtolower($method);
    if (str_contains($name, 'sinpe')) return 'sinpe';
    if (str_contains($name, 'transfer') || str_contains($name, 'iban')) return 'iban';
    if (str_contains($name, 'efectivo') || str_contains($name, 'lugar') || str_contains($name, 'local') || str_contains($name, 'cash') || str_contains($name, 'tarjeta')) return 'onsite';
    return 'other';
  }

  private function normalizeSinpe(string $account): string
  {
    $digits = preg_replace('/\D/', '', $account);
    if (strlen($digits) === 11 && str_starts_with($digits, '506')) $digits = substr($digits, 3);
    if (strlen($digits) !== 8) throw new BusinessRuleException('El teléfono SINPE Móvil debe tener exactamente 8 dígitos.');
    return $digits;
  }

  private function normalizeIban(string $account): string
  {
    $iban = strtoupper(preg_replace('/[\s-]/', '', $account));
    if (!preg_match('/^CR\d{20}$/', $iban) || !$this->isValidIbanChecksum($iban)) {
      throw new BusinessRuleException('El IBAN de Costa Rica debe tener 22 caracteres: CR seguido de 20 dígitos, con verificación válida.');
    }
    return $iban;
  }

  private function isValidIbanChecksum(string $iban): bool
  {
    $rearranged = substr($iban, 4) . substr($iban, 0, 4);
    $remainder = 0;
    foreach (str_split($rearranged) as $char) {
      $digits = ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
      foreach (str_split($digits) as $digit) $remainder = ($remainder * 10 + (int) $digit) % 97;
    }
    return $remainder === 1;
  }
}
