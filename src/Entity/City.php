<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'city')]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'city_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'city', type: 'string', length: 50)]
    private string $city;

    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'cities')]
    #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'country_id', nullable: false)]
    private Country $country;

    #[ORM\OneToMany(mappedBy: 'city', targetEntity: Address::class)]
    private Collection $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function setCountry(Country $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function __toString(): string
    {
        return $this->city . ' (' . $this->country->getCountry() . ')';
    }
}
