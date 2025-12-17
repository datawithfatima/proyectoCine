<?php

namespace App\Entity;

use App\Repository\ActorRepository;
use Doctrine\ORM\Mapping as ORM;

// 🔹 AGREGADO (necesario para relaciones)
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

// 🔹 AGREGADO (faltaba este use)
use App\Entity\FilmActor;

#[ORM\Entity(repositoryClass: ActorRepository::class)]
#[ORM\Table(name: 'actor')]
class Actor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'actor_id', type: 'integer')]
    private ?int $actorId = null;

    #[ORM\Column(name: 'first_name', type: 'string', length: 45)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 45)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'last_update', type: 'datetime')]
    private ?\DateTimeInterface $lastUpdate = null;

    // 🔹 AGREGADO (relación con film_actor)
    #[ORM\OneToMany(mappedBy: 'actor', targetEntity: FilmActor::class)]
    private Collection $filmActors;

    public function __construct()
    {
        $this->lastUpdate = new \DateTime();

        // 🔹 AGREGADO
        $this->filmActors = new ArrayCollection();
    }

    // ========================
    // Getters y Setters
    // ========================

    public function getActorId(): ?int
    {
        return $this->actorId;
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
    // 🔹 AGREGADO: Relaciones
    // ========================

    /**
     * @return Collection<int, FilmActor>
     */
    public function getFilmActors(): Collection
    {
        return $this->filmActors;
    }

    // ========================
    // Helpers (opcional, útil para vistas)
    // ========================

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    // 🔹 AGREGADO (opcional, MUY útil)
    public function getFilmCount(): int
    {
        return $this->filmActors->count();
    }
}
