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
use Plenta\LokiAiBundle\Entity\Field;

class FieldRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $managerRegistry,
    ) {
        parent::__construct($managerRegistry, Field::class);
    }

    public function findByTableNameAndField($tableName, $field)
    {
        $qb = $this->createQueryBuilder('f');

        return $qb
            ->where('f.tableName = :tableName')
            ->andWhere($qb->expr()->like('f.field', ':field'))
            ->setParameter('tableName', $tableName)
            ->setParameter('field', '%"'.$field.'"%')
            ->getQuery()
            ->getResult()
        ;
    }
}
