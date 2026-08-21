<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../Model/PaymentMethod.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';


class PaymentMethodService
{
    private PaymentMethodRepository $paymentMethodRepo;

    public function __construct()
    {
        $this->paymentMethodRepo = new PaymentMethodRepository(DataBase::getConnection());
    }

    public function validateTypeIsUnique(string $type): void
    {
        foreach ($this->paymentMethodRepo->findActive() as $method) {
            if (strcasecmp($method->getType(), $type) === 0) {
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
        return $this->paymentMethodRepo->save(new PaymentMethod($type));
    }
}