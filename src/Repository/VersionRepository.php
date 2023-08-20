<?php

namespace App\Repository;

use App\Entity\Version;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\This;

/**
 * @extends ServiceEntityRepository<Version>
 *
 * @method Version|null find($id, $lockMode = null, $lockVersion = null)
 * @method Version|null findOneBy(array $criteria, array $orderBy = null)
 * @method Version[]    findAll()
 * @method Version[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Version::class);
    }

    public function getByVersionNumber(int $versionNumber): ?Version
    {
        return $this->findOneBy(['versionNumber' => $versionNumber]);
    }

    public function createNewVersion(): Version
    {
        $nextVersionNumber = $this->getNextVersionNumber();

        $version = (new Version())
            ->setVersionNumber($nextVersionNumber)
            ->setActive(false);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($version);
        $entityManager->flush();

        return $version;
    }

    public function getNextVersionNumber(): int
    {
        if ($latestVersion = $this->getLatestVersion()) {
            return $latestVersion->getVersionNumber() + 1;
        }

        return 1;
    }

    public function getLatestVersion(): ?Version
    {
        /** @var Version[] $results */
        $results = $this
            ->createQueryBuilder('v')
            ->orderBy('v.versionNumber', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if ($first = current($results)) {
            return $first;
        }

        return null;
    }

    public function findByVersionNumberOrThrow(int $versionNumber): Version
    {
        if ($version = $this->getByVersionNumber($versionNumber)) {
            return $version;
        }

        throw new EntityNotFoundException(sprintf(
            'Version %s not found',
            $versionNumber
        ));
    }

    public function findByVersionNumber(int $versionNumber): Version
    {
        return $this->findOneBy(['versionNumber' => $versionNumber]);
    }

    /**
     * @throws EntityNotFoundException
     */
    public function activateVersion(int $version): Version
    {
        $entityManager = $this->getEntityManager();

        $targetVersion = $this->findByVersionNumberOrThrow($version);

        $activeVersions = $this->findBy(['active' => true]);
        foreach ($activeVersions as $activeVersion) {
            $activeVersion->setActive(false);
            $entityManager->persist($activeVersion);
        }

        $targetVersion->setActive(true);
        $entityManager->persist($targetVersion);
        $entityManager->flush();

        return $targetVersion;
    }
}
