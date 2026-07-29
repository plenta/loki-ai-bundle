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

class VisionPromptMigration extends AbstractMigration
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

        return !isset($columns['visionprompt']) || !isset($columns['visionsystemprompt']);
    }

    public function run(): MigrationResult
    {
        $columns = $this->connection->createSchemaManager()->listTableColumns('tl_loki_prompt');

        if (!isset($columns['visionprompt'])) {
            $this->connection->executeStatement(
                "ALTER TABLE tl_loki_prompt
                 ADD COLUMN visionPrompt TINYINT(1) NOT NULL DEFAULT 0,
                 ADD COLUMN visionLanguages VARCHAR(255) NOT NULL DEFAULT ''"
            );
        }

        if (!isset($columns['visionsystemprompt'])) {
            $this->connection->executeStatement(
                'ALTER TABLE tl_loki_prompt ADD COLUMN visionSystemPrompt LONGTEXT NULL'
            );
        }

        return $this->createResult(true, 'Added visionPrompt and visionLanguages columns to tl_loki_prompt.');
    }
}
