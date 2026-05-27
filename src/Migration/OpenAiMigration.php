<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class OpenAiMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_loki_prompt'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_loki_prompt');

        if (!isset($columns['provider'])) {
            return false;
        }

        return (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_loki_prompt WHERE provider = '' OR provider IS NULL"
        );
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement(
            "UPDATE tl_loki_prompt SET provider = 'openai' WHERE provider = '' OR provider IS NULL"
        );

        return $this->createResult(
            true,
            'All Loki AI prompts without a provider have been set to "openai"'
        );
    }
}
