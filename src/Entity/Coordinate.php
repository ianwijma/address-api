<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CoordinateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: CoordinateRepository::class, readOnly: true)]
#[ORM\UniqueConstraint(
    name: 'unique_coordinate',
    columns: ['north', 'east']
)]
#[ApiResource(operations: [
    new Get(),
    new GetCollection()
])]
class Coordinate
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.ulid_generator')]
    private ?Ulid $id = null;

    #[ORM\Column(type: 'decimal', precision: 7)]
    private ?string $north = null;

    #[ORM\Column(type: 'decimal', precision: 7)]
    private ?string $east = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getNorth(): ?string
    {
        return $this->north;
    }

    public function setNorth(string $north): static
    {
        $this->north = $north;

        return $this;
    }

    public function getEast(): ?string
    {
        return $this->east;
    }

    public function setEast(string $east): static
    {
        $this->east = $east;

        return $this;
    }
}
