<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\EventListener\Contao\DCA;

use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Contao\CoreBundle\Slug\Slug;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

#[AsCallback(table: 'tl_loki_prompt', target: 'fields.alias.save')]
class TlLokiPromptAlias
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Slug $slugGenerator
    )
    {
    }

    public function __invoke($value, DataContainer $dc)
    {
        $aliasExists = function (string $alias) use ($dc): bool {
            $qb = $this->connection->createQueryBuilder();

            $qb->select('1')
                ->from($this->connection->quoteIdentifier($dc->table))
                ->where('alias = :alias')
                ->andWhere('id <> :id')
                ->setParameter('alias', $alias)
                ->setParameter('id', (int) $dc->id)
                ->setMaxResults(1);

            return $qb->executeQuery()->fetchOne() !== false;
        };

        if (!$value) {
            $value = $this->slugGenerator->generate($dc->activeRecord->title, [], $aliasExists);
        } elseif (preg_match('/^[1-9]\d*$/', $value)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $value));
        } elseif ($aliasExists($value)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $value));
        }

        return $value;
    }
}
