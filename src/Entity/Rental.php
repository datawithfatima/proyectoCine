<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'rental')]
class Rental
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'rental_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'rental_date', type: 'datetime')]
    private \DateTimeInterface $rentalDate;

    #[ORM\ManyToOne(targetEntity: Inventory::class, inversedBy: 'rentals')]
    #[ORM\JoinColumn(name: 'inventory_id', referencedColumnName: 'inventory_id', nullable: false)]
    private Inventory $inventory;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'rentals')]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'customer_id', nullable: false)]
    private Customer $customer;

    #[ORM\ManyToOne(targetEntity: Staff::class)]
    #[ORM\JoinColumn(name: 'staff_id', referencedColumnName: 'staff_id', nullable: false)]
    private Staff $staff;

    #[ORM\Column(name: 'return_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $returnDate = null;

    #[ORM\Column(name: 'last_update', type: 'datetime')]
    private \DateTimeInterface $lastUpdate;

    #[ORM\OneToMany(mappedBy: 'rental', targetEntity: Payment::class)]
    private Collection $payments;

    public function __construct()
    {
        $this->lastUpdate = new \DateTime();
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRentalDate(): \DateTimeInterface
    {
        return $this->rentalDate;
    }

    public function setRentalDate(\DateTimeInterface $date): self
    {
        $this->rentalDate = $date;
        return $this;
    }

    public function getInventory(): Inventory
    {
        return $this->inventory;
    }

    public function setInventory(Inventory $inventory): self
    {
        $this->inventory = $inventory;
        return $this;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getStaff(): Staff
    {
        return $this->staff;
    }

    public function setStaff(Staff $staff): self
    {
        $this->staff = $staff;
        return $this;
    }

    public function getReturnDate(): ?\DateTimeInterface
    {
        return $this->returnDate;
    }

    public function setReturnDate(?\DateTimeInterface $date): self
    {
        $this->returnDate = $date;
        return $this;
    }

    public function getLastUpdate(): \DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $date): self
    {
        $this->lastUpdate = $date;
        return $this;
    }

    /** @return Collection<int, Payment> */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setRental($this);
        }
        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getRental() === $this) {
                $payment->setRental(null);
            }
        }
        return $this;
    }
}
