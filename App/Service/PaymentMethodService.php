<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../Model/PaymentMethod.php';

class PaymentMethodService
{
    private PaymentMethodRepository $paymentMethodRepo;

    public function __construct()
    {
        $connection = DataBase::getConnection();

        $this->paymentMethodRepo = new PaymentMethodRepository($connection);
    }

    public function validateTypeIsUnique(string $type, int $excludeId = 0): void
    {
        foreach ($this->paymentMethodRepo->findActive() as $method) {
            if ($method->getIdPaymentMethod() === $excludeId) {
                continue;
            }

            if (strcasecmp($method->getPaymentMethod(), $type) === 0) {
                throw new BusinessRuleException("Ya existe un método de pago con ese nombre.");
            }
        }
    }

    public function assertIsSelectable(int $paymentMethodPk): void
    {
        $method = $this->paymentMethodRepo->findById($paymentMethodPk);
        if ($method === null || !$method->getIsActive()) {
            throw new BusinessRuleException("Este método de pago no está disponible.");
        }
    }

    public function validateAndCreate(string $type): int
    {
        $this->validateTypeIsUnique($type);

        $paymentMethod = new PaymentMethod(
            idPaymentMethod: 0,
            paymentMethod: $type,
            isActive: true
        );

        return $this->paymentMethodRepo->save($paymentMethod);
    }

    public function updateMethod(int $idPaymentMethod, string $type, bool $isActive): void
    {
        if (trim($type) === '') {
            throw new BusinessRuleException("El tipo de método de pago es obligatorio.");
        }

        $paymentMethod = $this->paymentMethodRepo->findById($idPaymentMethod);
        if ($paymentMethod === null) {
            throw new BusinessRuleException("El método de pago no existe.");
        }

        $this->validateTypeIsUnique($type, $idPaymentMethod);

        $paymentMethod->setPaymentMethod($type);
        $paymentMethod->setIsActive($isActive);

        $this->paymentMethodRepo->update($paymentMethod);
    }

    public function deleteMethod(int $idPaymentMethod): void
    {
        $paymentMethod = $this->paymentMethodRepo->findById($idPaymentMethod);
        if ($paymentMethod === null) {
            throw new BusinessRuleException("El método de pago no existe.");
        }

        $this->paymentMethodRepo->deactivate($idPaymentMethod);
    }
}
