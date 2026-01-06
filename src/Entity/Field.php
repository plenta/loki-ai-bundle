<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mvo\ContaoGroupWidget\Entity\AbstractGroupElementEntity;
use Plenta\LokiAiBundle\Repository\FieldRepository;

#[ORM\Entity(repositoryClass: FieldRepository::class)]
#[ORM\Table(name: self::TABLE)]
class Field extends AbstractGroupElementEntity
{
    public const string TABLE = 'tl_loki_field';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    /**
     * @phpstan-ignore-next-line missingType.property
     */
    protected $id;

    #[ORM\Column(name: 'position', type: 'integer', options: ['unsigned' => true])]
    /**
     * @phpstan-ignore-next-line missingType.property
     */
    protected $position = 0;

    #[ORM\ManyToOne(targetEntity: Prompt::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(name: 'parent', nullable: false)]
    /**
     * @phpstan-ignore-next-line missingType.property
     */
    protected $parent;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    protected string $tableName = '';

    #[ORM\Column(type: 'text', nullable: true)]
    protected string|null $field = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected string|null $includeFields = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected string|null $requirements = null;

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function getIncludeFields(): string
    {
        return $this->includeFields;
    }

    public function setIncludeFields(string $includeFields): void
    {
        $this->includeFields = $includeFields;
    }

    public function getRequirements(): string|null
    {
        return $this->requirements;
    }

    public function setRequirements(string|null $requirements): void
    {
        $this->requirements = $requirements;
    }
}
