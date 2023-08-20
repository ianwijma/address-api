<?php

namespace App\Service;

use App\Entity\Address;
use App\Exceptions\VersionMissMatchException;
use App\Repository\AddressRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class AddressVersionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    /**
     * @throws VersionMissMatchException
     * @throws Exception
     */
    public function getVersion(): int
    {
        $sql = <<<SQL
SELECT (
    SELECT version AS address_version 
    FROM address
    ORDER BY version DESC
    LIMIT 1
),
(
    SELECT version AS coordinate_version
    FROM coordinate 
    ORDER BY version DESC
    LIMIT 1
)
SQL;

        $statement = $this->entityManager->getConnection()->prepare($sql);
        $results = $statement->executeQuery()->fetchNumeric();

        // Check if the version matches
        [$addressVersion, $coordinateVersion] = $results;
        if ($addressVersion !== $coordinateVersion) {
            throw new VersionMissMatchException($addressVersion, $coordinateVersion);
        }

        if (null === $addressVersion) {
            return 0;
        }

        return (int) $addressVersion;
    }
}
