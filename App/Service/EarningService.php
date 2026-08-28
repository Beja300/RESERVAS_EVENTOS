<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/CommissionConfigService.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/EarningRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/CommissionConfigRepository.php';
require_once __DIR__ . '/../Model/Earning.php';

class EarningService
{
  private EarningRepository $earningRepo;
  private BookingRepository $bookingRepo;
  private VenueRepository $venueRepo;
  private CommissionConfigService $configService;

  public function __construct(PDO $connection)
  {
    $this->earningRepo = new EarningRepository($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->configService = new CommissionConfigService(
      new CommissionConfigRepository($connection)
    );
  }

  // =========================================================
  // REGISTRAR REPARTICIÓN DE UNA RESERVA PAGADA
  // total pagado por el cliente, comisión de la plataforma,
  // IVA retenido e ingreso neto del propietario.
  // =========================================================
  public function recordEarning(int $bookingPk, float $total, ?int $reviewedByRole = null): Earning
  {
    $booking = $this->bookingRepo->findById($bookingPk);

    if ($booking === null) {
      throw new BusinessRuleException('La reserva no existe.');
    }

    if ($this->earningRepo->findByBooking($bookingPk) !== null) {
      throw new BusinessRuleException('Esta reserva ya tiene su ganancia registrada.');
    }

    $config = $this->configService->getActive();

    $commission = round($total * ($config->getPercentage() / 100), 2);
    $tax        = round($commission * ($config->getTax() / 100), 2);
    $ownerAmount = round($total - $commission - $tax, 2);

    $earning = new Earning(
      idEarning: 0,
      idBooking: $bookingPk,
      total: $total,
      commission: $commission,
      tax: $tax,
      ownerAmount: $ownerAmount,
      reviewedByRole: $reviewedByRole
    );

    $this->earningRepo->save($earning);

    $earning->setIdEarning(
      $this->earningRepo->findByBooking($bookingPk)->getIdEarning()
    );

    return $earning;
  }

  // =========================================================
  // REPARTICIÓN DE UNA RESERVA
  // =========================================================
  public function findByBooking(int $bookingPk): ?Earning
  {
    return $this->earningRepo->findByBooking($bookingPk);
  }

  // =========================================================
  // TODAS LAS GANANCIAS
  // =========================================================
  public function findAll(): array
  {
    return $this->earningRepo->findAll();
  }

  // =========================================================
  // GANANCIAS DE UN MES (YYYY-MM)
  // =========================================================
  public function findByMonth(string $yearMonth): array
  {
    $totals = [
      'total'       => 0.0,
      'commission'  => 0.0,
      'tax'         => 0.0,
      'ownerAmount' => 0.0,
    ];

    foreach ($this->bookingRepo->findByMonth($yearMonth) as $booking) {
      $earning = $this->earningRepo->findByBooking($booking->getIdBooking());

      if ($earning !== null) {
        $totals['total']       += $earning->getTotal();
        $totals['commission']  += $earning->getCommission();
        $totals['tax']         += $earning->getTax();
        $totals['ownerAmount'] += $earning->getOwnerAmount();
      }
    }

    foreach ($totals as $key => $value) {
      $totals[$key] = round($value, 2);
    }

    return $totals;
  }
}
