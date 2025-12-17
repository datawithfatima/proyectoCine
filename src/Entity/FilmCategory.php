<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'film_category')]
class FilmCategory
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Film::class, inversedBy: 'filmCategories')]
    #[ORM\JoinColumn(name: 'film_id', referencedColumnName: 'film_id', nullable: false)]
    private ?Film $film = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'category_id', nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(name: 'last_update', type: 'datetime')]
    private ?\DateTimeInterface $lastUpdate = null;

    public function __construct()
    {
        $this->lastUpdate = new \DateTime();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
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