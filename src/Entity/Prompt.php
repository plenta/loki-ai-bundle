<?php

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
}