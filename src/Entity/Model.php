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

#[ORM\Entity]
#[ORM\Table(name: self::TABLE)]
class Model extends DCADefault
{
    public const string TABLE = 'tl_loki_model';

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    protected string $name;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    protected string $owner;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected int $created;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Model
    {
        $this->name = $name;
        return $this;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): Model
    {
        $this->owner = $owner;
        return $this;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function setCreated(int $created): Model
    {
        $this->created = $created;
        return $this;
    }
}