<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payment')]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'payment_id', type: 'integer')]
    private ?int $paymentId = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'customer_id', nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Staff::class)]
    #[ORM\JoinColumn(name: 'staff_id', referencedColumnName: 'staff_id', nullable: false)]
    private ?Staff $staff = null;

    #[ORM\ManyToOne(targetEntity: Rental::class)]
    #[ORM\JoinColumn(name: 'rental_id', referencedColumnName: 'rental_id', nullable: false)]
    private ?Rental $rental = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(name: 'payment_date', type: 'datetime')]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(name: 'last_update', type: 'datetime')]
    private ?\DateTimeInterface $lastUpdate = null;



    public function __construct()
    {
        $this->lastUpdate = new \DateTime();
    }

    // ========================
    // Getters y Setters
    // ========================

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function setPaymentId(int $paymentId): static
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    // --- ESTE ES EL MÉTODO QUE SYMFONY ESTÁ BUSCANDO Y NO ENCUENTRA ---
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }
    // ------------------------------------------------------------------

    public function getStaff(): ?Staff
    {
        return $this->staff;
    }

    public function setStaff(?Staff $staff): static
    {
        $this->staff = $staff;
        return $this;
    }

    public function getRental(): ?Rental
    {
        return $this->rental;
    }

    public function setRental(?Rental $rental): static
    {
        $this->rental = $rental;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): static
    {
        $this->paymentDate = $paymentDate;
        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;
        return $this;
    }

    // ========================
    // Helpers (opcional, útil para vistas)
    // ========================

    /*public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }*/
}