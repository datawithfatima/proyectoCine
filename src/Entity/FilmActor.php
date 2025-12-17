<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'film_actor')]
class FilmActor
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Actor::class, inversedBy: 'filmActors')]
    #[ORM\JoinColumn(
        name: 'actor_id',
        referencedColumnName: 'actor_id',
        nullable: false
    )]
    private ?Actor $actor = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Film::class, inversedBy: 'filmActors')]
    #[ORM\JoinColumn(
        name: 'film_id',
        referencedColumnName: 'film_id',
        nullable: false
    )]
    private ?Film $film = null;

    #[ORM\Column(name: 'last_update', type: 'datetime')]
    private ?\DateTimeInterface $lastUpdate = null;

    public function __construct()
    {
        $this->lastUpdate = new \DateTime();
    }

    public function getActor(): ?Actor
    {
        return $this->actor;
    }

    public function setActor(?Actor $actor): self
    {
        $this->actor = $actor;
        return $this;
    }

    public function getFilm(): ?Film
    {
        return $this->film;
    }

    public function setFilm(?Film $film): self
    {
        $this->film = $film;
        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;
        return $this;
    }
}
