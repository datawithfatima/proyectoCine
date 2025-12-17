<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'address')]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'address_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'address', type: 'string', length: 50)]
    private string $address;

    #[ORM\Column(name: 'postal_code', type: 'string', length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(name: 'phone', type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\ManyToOne(targetEntity: City::class, inversedBy: 'addresses')]
    #[ORM\JoinColumn(name: 'city_id', referencedColumnName: 'city_id', nullable: false)]
    private City $city;

    #[ORM\OneToMany(mappedBy: 'address', targetEntity: Customer::class)]
    private Collection $customers;

    public function __construct()
    {
        $this->customers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCity(): City
    {
        return $this->city;
    }

    public function setCity(City $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCustomers(): Collection
    {
        return $this->customers;
    }

    public function __toString(): string
    {
        return $this->address . ' - ' . $this->city;
    }
}
