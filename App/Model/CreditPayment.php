<?php

/**
 * ENTIDAD: CreditPayment — abono (pago parcial) aplicado a un crédito.
 */
class CreditPayment
{
  private int $idCreditPayment;
  private int $idCredit;
  private float $amount;
  private string $note;
  private ?int $idRole;
  private string $paymentDate;
  private bool $isActive;
  private ?string $proofFile;
  private string $state;
  private ?string $reviewedAt;
  private ?int $reviewedBy;

  public function __construct(
    int $idCreditPayment = 0,
    int $idCredit = 0,
    float $amount = 0.0,
    string $note = '',
    ?int $idRole = null,
    string $paymentDate = '',
    bool $isActive = true,
    ?string $proofFile = null,
    string $state = 'pendiente',
    ?string $reviewedAt = null,
    ?int $reviewedBy = null
  ) {
    $this->idCreditPayment = $idCreditPayment;
    $this->idCredit = $idCredit;
    $this->amount = $amount;
    $this->note = $note;
    $this->idRole = $idRole;
    $this->paymentDate = $paymentDate;
    $this->isActive = $isActive;
    $this->proofFile = $proofFile;
    $this->state = $state;
    $this->reviewedAt = $reviewedAt;
    $this->reviewedBy = $reviewedBy;
  }

  public function getIdCreditPayment(): int { return $this->idCreditPayment; }
  public function getIdCredit(): int { return $this->idCredit; }
  public function getAmount(): float { return $this->amount; }
  public function getNote(): string { return $this->note; }
  public function getIdRole(): ?int { return $this->idRole; }
  public function getPaymentDate(): string { return $this->paymentDate; }
  public function getIsActive(): bool { return $this->isActive; }
  public function getProofFile(): ?string { return $this->proofFile; }
  public function getState(): string { return $this->state; }
  public function getReviewedAt(): ?string { return $this->reviewedAt; }
  public function getReviewedBy(): ?int { return $this->reviewedBy; }

  public function setIdCreditPayment(int $idCreditPayment): void { $this->idCreditPayment = $idCreditPayment; }
  public function setIdCredit(int $idCredit): void { $this->idCredit = $idCredit; }
  public function setAmount(float $amount): void { $this->amount = $amount; }
  public function setNote(string $note): void { $this->note = $note; }
  public function setIdRole(?int $idRole): void { $this->idRole = $idRole; }
  public function setPaymentDate(string $paymentDate): void { $this->paymentDate = $paymentDate; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
  public function setProofFile(?string $proofFile): void { $this->proofFile = $proofFile; }
  public function setState(string $state): void { $this->state = $state; }
  public function setReviewedAt(?string $reviewedAt): void { $this->reviewedAt = $reviewedAt; }
  public function setReviewedBy(?int $reviewedBy): void { $this->reviewedBy = $reviewedBy; }
}
