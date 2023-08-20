<?php

namespace App\Repository;

use App\Entity\Coordinate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coordinate>
 *
 * @method Coordinate|null find($id, $lockMode = null, $lockVersion = null)
 * @method Coordinate|null findOneBy(array $criteria, array $orderBy = null)
 * @method Coordinate[]    findAll()
 * @method Coordinate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CoordinateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coordinate::class);
    }
}
