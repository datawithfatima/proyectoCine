<?php

namespace App\Entity;

use App\Repository\StoreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StoreRepository::class)]
#[ORM\Table(name: 'store')]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'store_id', type: 'smallint')]
    private ?int $storeId = null;

    // Relación con el Manager (Staff)
    #[ORM\OneToOne(targetEntity: Staff::class)]
    #[ORM\JoinColumn(name: 'manager_staff_id', referencedColumnName: 'staff_id', nullable: false)]
    private ?Staff $manager = null;

    // Relación con la Dirección (Address)
    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'address_id', nullable: false)]
    private ?Address $address = null;

    #[ORM\Column(name: 'last_update', type: 'datetime')]
    private ?\DateTimeInterface $lastUpdate = null;

    public function getStoreId(): ?int { return $this->storeId; }

    public function getManager(): ?Staff { return $this->manager; }
    public function setManager(?Staff $manager): static { $this->manager = $manager; return $this; }

    public function getAddress(): ?Address { return $this->address; }
    public function setAddress(?Address $address): static { $this->address = $address; return $this; }
    
    public function getLastUpdate(): ?\DateTimeInterface { return $this->lastUpdate; }
}