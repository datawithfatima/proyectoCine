<?php

namespace App\Entity;

use App\Repository\FilmRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: FilmRepository::class)]
#[ORM\Table(name: 'film')]
class Film
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'film_id')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'release_year', nullable: true)]
    private ?int $releaseYear = null;

    #[ORM\Column(name: 'rental_duration')]
    private ?int $rentalDuration = 3;

    #[ORM\Column(name: 'rental_rate', type: Types::DECIMAL, precision: 4, scale: 2)]
    private ?string $rentalRate = '4.99';

    #[ORM\Column(nullable: true)]
    private ?int $length = null;

    #[ORM\Column(name: 'replacement_cost', type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $replacementCost = '19.99';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $rating = null;

    #[ORM\Column(name: 'special_features', type: Types::TEXT, nullable: true)]
    private ?string $specialFeatures = null;

    #[ORM\Column(name: 'last_update')]
    private ?\DateTimeImmutable $lastUpdate = null;

    #[ORM\OneToMany(targetEntity: FilmActor::class, mappedBy: 'film')]
    private Collection $filmActors;

    #[ORM\OneToMany(targetEntity: FilmCategory::class, mappedBy: 'film')]
    private Collection $filmCategories;

    public function __construct()
    {
        $this->lastUpdate = new \DateTimeImmutable();
        $this->filmActors = new ArrayCollection();
        $this->filmCategories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilmId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(?int $releaseYear): static
    {
        $this->releaseYear = $releaseYear;
        return $this;
    }

    public function getRentalDuration(): ?int
    {
        return $this->rentalDuration;
    }

    public function setRentalDuration(int $rentalDuration): static
    {
        $this->rentalDuration = $rentalDuration;
        return $this;
    }

    public function getRentalRate(): ?string
    {
        return $this->rentalRate;
    }

    public function setRentalRate(string $rentalRate): static
    {
        $this->rentalRate = $rentalRate;
        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length): static
    {
        $this->length = $length;
        return $this;
    }

    public function getReplacementCost(): ?string
    {
        return $this->replacementCost;
    }

    public function setReplacementCost(string $replacementCost): static
    {
        $this->replacementCost = $replacementCost;
        return $this;
    }

    public function getRating(): ?string
    {
        return $this->rating;
    }

    public function setRating(?string $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getSpecialFeatures(): ?string
    {
        return $this->specialFeatures;
    }

    public function setSpecialFeatures(?string $specialFeatures): static
    {
        $this->specialFeatures = $specialFeatures;
        return $this;
    }

    public function getLastUpdate(): ?\DateTimeImmutable
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeImmutable $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;
        return $this;
    }

    /**
     * @return Collection<int, FilmActor>
     */
    public function getFilmActors(): Collection
    {
        return $this->filmActors;
    }

    /**
     * @return Collection<int, FilmCategory>
     */
    public function getFilmCategories(): Collection
    {
        return $this->filmCategories;
    }
}