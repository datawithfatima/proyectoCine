<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'inventory')]
class Inventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'inventory_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Film::class)]
    #[ORM\JoinColumn(name: 'film_id', referencedColumnName: 'film_id', nullable: false)]
    private Film $film;

    #[ORM\ManyToOne(targetEntity: Store::class)]
    #[ORM\JoinColumn(name: 'store_id', referencedColumnName: 'store_id', nullable: false)]
    private Store $store;

    #[ORM\Column(name: 'last_update', type: 'datetime')]
    private \DateTimeInterface $lastUpdate;

    #[ORM\OneToMany(mappedBy: 'inventory', targetEntity: Rental::class)]
    private Collection $rentals;

    public function __construct()
    {
        $this->lastUpdate = new \DateTimeImmutable();
        $this->rentals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilm(): Film
    {
        return $this->film;
    }

    public function setFilm(Film $film): self
    {
        $this->film = $film;
        return $this;
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

    public function getLastUpdate(): \DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $date): self
    {
        $this->lastUpdate = $date;
        return $this;
    }

    /** @return Collection<int, Rental> */
    public function getRentals(): Collection
    {
        return $this->rentals;
    }

    public function __toString(): string
    {
        return 'Inventario #' . $this->id . ' - ' . $this->film->getTitle();
    }
}
