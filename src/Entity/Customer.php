<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customer')]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'customer_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Store::class, inversedBy: 'customers')]
    #[ORM\JoinColumn(name: 'store_id', referencedColumnName: 'store_id', nullable: false)]
    private Store $store;

    #[ORM\ManyToOne(targetEntity: Address::class, inversedBy: 'customers')]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'address_id', nullable: false)]
    private Address $address;

    #[ORM\Column(name: 'first_name', type: 'string', length: 45)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: 'string', length: 45)]
    private string $lastName;

    #[ORM\Column(name: 'email', type: 'string', length: 50, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'active', type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(name: 'create_date', type: 'datetime')]
    private \DateTimeInterface $createDate;

    #[ORM\Column(name: 'last_update', type: 'datetime')]
    private \DateTimeInterface $lastUpdate;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Rental::class)]
    private Collection $rentals;

    public function __construct()
    {
        $now = new \DateTime();
        $this->createDate = $now;
        $this->lastUpdate = $now;
        $this->active = true;
        $this->rentals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    public function setStore(Store $store): self
    {
        $this->store = $store;
        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getCreateDate(): \DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeInterface $createDate): self
    {
        $this->createDate = $createDate;
        return $this;
    }

    public function getLastUpdate(): \DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;
        return $this;
    }

    /**
     * @return Collection<int, Rental>
     */
    public function getRentals(): Collection
    {
        return $this->rentals;
    }

    public function addRental(Rental $rental): self
    {
        if (!$this->rentals->contains($rental)) {
            $this->rentals[] = $rental;
            $rental->setCustomer($this);
        }
        return $this;
    }

    public function removeRental(Rental $rental): self
    {
        if ($this->rentals->removeElement($rental)) {
            if ($rental->getCustomer() === $this) {
                $rental->setCustomer(null);
            }
        }
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
