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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Plenta\LokiAiBundle\Repository\PromptRepository;

#[ORM\Entity(repositoryClass: PromptRepository::class)]
#[ORM\Table(self::TABLE)]
class Prompt extends DCADefault
{
    public const string TABLE = 'tl_loki_prompt';

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    protected string $title;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Field::class, orphanRemoval: true)]
    protected Collection $fields;

    #[ORM\Column(type: 'text', nullable: true)]
    protected string $prompt;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    protected string $model = '';

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $maxTokens = null;

    #[ORM\Column(type: 'float', nullable: true)]
    protected ?float $temperature = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected bool $published = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected bool $autoRun = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected bool $protected = false;

    #[ORM\Column(type: 'text', nullable: true)]
    protected $userGroups = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected int $rootPage = 0;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => '', 'collation' => 'utf8mb4_bin'])]
    protected string $alias;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected bool $skipIfEmpty = false;

    public function getFields()
    {
        return $this->fields;
    }

    public function addField(Field $field)
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);

            if (empty($field->getParent())) {
                $field->setParent($this);
            }
        }
    }

    public function removeField(Field $field)
    {
        if ($this->fields->contains($field)) {
            $this->fields->removeElement($field);
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Prompt
    {
        $this->title = $title;
        return $this;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): Prompt
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): Prompt
    {
        $this->model = $model;
        return $this;
    }

    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function setMaxTokens(?int $maxTokens): Prompt
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): Prompt
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): Prompt
    {
        $this->published = $published;
        return $this;
    }

    public function isAutoRun(): bool
    {
        return $this->autoRun;
    }

    public function setAutoRun(bool $autoRun): Prompt
    {
        $this->autoRun = $autoRun;
        return $this;
    }

    public function isProtected(): bool
    {
        return $this->protected;
    }

    public function setProtected(bool $protected): Prompt
    {
        $this->protected = $protected;
        return $this;
    }

    public function getUserGroups(): ?string
    {
        return $this->userGroups;
    }

    public function setUserGroups(?string $userGroups): Prompt
    {
        $this->userGroups = $userGroups;
        return $this;
    }

    public function getRootPage(): int
    {
        return $this->rootPage;
    }

    public function setRootPage(int $rootPage): Prompt
    {
        $this->rootPage = $rootPage;
        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): Prompt
    {
        $this->alias = $alias;
        return $this;
    }

    public function isSkipIfEmpty(): bool
    {
        return $this->skipIfEmpty;
    }

    public function setSkipIfEmpty(bool $skipIfEmpty): void
    {
        $this->skipIfEmpty = $skipIfEmpty;
    }
}