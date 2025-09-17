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

use Contao\BackendUser;
use Contao\StringUtil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Plenta\LokiAiBundle\Entity\Field;
use Symfony\Component\Security\Core\Security;

class FieldRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        protected Security $security,
    ) {
        parent::__construct($managerRegistry, Field::class);
    }

    /**
     * @return Collection[Field]
     */
    public function findByTableNameAndField($tableName, $field)
    {
        $qb = $this->createQueryBuilder('f');
        $user = $this->security->getToken()->getUser();
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
