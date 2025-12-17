<?php

namespace App\Entity;

use App\Repository\StaffRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StaffRepository::class)]
#[ORM\Table(name: 'staff')]
class Staff
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'staff_id', type: 'smallint')] 
    private ?int $staffId = null;

    #[ORM\Column(name: 'first_name', length: 45)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 45)]
    private ?string $lastName = null;

    // 1. Relación con DIRECCIÓN (Address)
    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'address_id', nullable: false)]
    private ?Address $address = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private mixed $picture = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $email = null;

    // 2. Relación con TIENDA (Store)
    #[ORM\ManyToOne(targetEntity: Store::class)]
    #[ORM\JoinColumn(name: 'store_id', referencedColumnName: 'store_id', nullable: false)]
    private ?Store $store = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private ?bool $active = true;

    #[ORM\Column(length: 16)]
    private ?string $username = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(name: 'last_update', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastUpdate = null;

    public function getStaffId(): ?int
    {
        return $this->staffId;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getAddress(): ?Address 
    { 
        return $this->address; 
    }
    public function setAddress(?Address $address): static 
    { 
        $this->address = $address; 
        return $this; 
    }
    public function getPicture(): mixed
    {
        return $this->picture;
    }

    public function setPicture(mixed $picture): static
    {
        $this->picture = $picture;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getStore(): ?Store 
    { 
        return $this->store; 
    }
    public function setStore(?Store $store): static 
    { 
        $this->store = $store; 
        return $this; 
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
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
}