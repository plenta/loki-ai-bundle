<?php

declare(strict_types=1);

/**
 * Plenta Jobs Basic Geo Search Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2024, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plenta\LokiAiBundle\Entity\Model;

class ModelRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $managerRegistry,
    ) {
        parent::__construct($managerRegistry, Model::class);
    }

    public function deleteOlderThan(int $time)
    {
        $this->createQueryBuilder('m')
            ->delete()
            ->where('m.tstamp < :time')
            ->setParameter('time', $time)
            ->getQuery()
            ->execute()
        ;
    }
}
