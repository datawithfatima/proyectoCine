<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'country')]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'country_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'country', type: 'string', length: 50)]
    private string $country;

    #[ORM\OneToMany(mappedBy: 'country', targetEntity: City::class)]
    private Collection $cities;

    public function __construct()
    {
        $this->cities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getCities(): Collection
    {
        return $this->cities;
    }

    public function __toString(): string
    {
        return $this->country;
    }
}
