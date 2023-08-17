<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: AddressRepository::class, readOnly: true)]
#[ORM\UniqueConstraint(
    name: 'unique_address',
    columns: ['country','region', 'district', 'postcode', 'street', 'number', 'unit']
)]
#[ORM\UniqueConstraint(
    name: 'unique_hash',
    columns: ['hash']
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
class Address
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.ulid_generator')]
    private ?Ulid $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $district = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $postcode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $street = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hash = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $external_id = null;

    #[ORM\ManyToOne(targetEntity: Coordinate::class)]
    #[ORM\JoinColumn(name: 'coordinate_id', referencedColumnName: 'id', nullable: true)]
    private ?Coordinate $coordinate = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): Address
    {
        $this->country = $country;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): Address
    {
        $this->region = $region;

        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): Address
    {
        $this->district = $district;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): Address
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): Address
    {
        $this->street = $street;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): Address
    {
        $this->number = $number;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): Address
    {
        $this->unit = $unit;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): Address
    {
        $this->hash = $hash;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    public function setExternalId(?string $external_id): Address
    {
        $this->external_id = $external_id;

        return $this;
    }

    public function getCoordinate(): ?Coordinate
    {
        return $this->coordinate;
    }

    public function setCoordinate(?Coordinate $coordinate): Address
    {
        $this->coordinate = $coordinate;

        return $this;
    }
}
