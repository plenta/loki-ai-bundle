<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Repository;

use Contao\BackendUser;
use Contao\StringUtil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plenta\LokiAiBundle\Entity\Field;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @extends ServiceEntityRepository<Field>
 */
class FieldRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        protected TokenStorageInterface $tokenStorage,
    ) {
        parent::__construct($managerRegistry, Field::class);
    }

    /**
     * @return array<Field>
     */
    public function findByTableNameAndField(string $tableName, string $field): array
    {
        $qb = $this->createQueryBuilder('f');
        $user = $this->tokenStorage->getToken()->getUser();
        $qb
            ->where('f.tableName = :tableName')
            ->leftJoin('f.parent', 'p')
            ->andWhere($qb->expr()->like('f.field', ':field'))
            ->andWhere('p.published = :true')
        ;

        if ($user instanceof BackendUser && !$user->isAdmin) {
            $groups = StringUtil::deserialize($user->groups);
            $criteria = [];

            foreach ($groups as $key => $group) {
                $criteria[] = $qb->expr()->like('p.userGroups', ':group_'.$key);
                $qb->setParameter('group_'.$key, '%"'.$group.'"%');
            }

            $qb
                ->andWhere($qb->expr()->orX('p.protected = :false', ...$criteria))
                ->setParameter('false', false)
            ;
        }

        return $qb
            ->setParameter('true', true)
            ->setParameter('tableName', $tableName)
            ->setParameter('field', '%"'.$field.'"%')
            ->getQuery()
            ->getResult()
        ;
    }
}
