<?php


namespace Plenta\LokiAiBundle\Entity;

use Plenta\LokiAiBundle\Repository\SystemInstructionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: SystemInstructionRepository::class)]
#[ORM\Table(self::TABLE)]
class SystemInstruction extends DCADefault
{
    public const string TABLE = 'tl_loki_system_instruction';

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    protected string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    protected string|null $systemInstructionPrompt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected bool $published = false;

    #[ORM\OneToMany(mappedBy: 'systemInstruction', targetEntity: Prompt::class)]
    protected Collection $prompts;

    public function __construct()
    {
        $this->prompts = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSystemInstructionPrompt(): string
    {
        return $this->systemInstructionPrompt;
    }

    public function setSystemInstructionPrompt(string $systemInstructionPrompt): self
    {
        $this->systemInstructionPrompt = $systemInstructionPrompt;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    public function getPrompts(): Collection
    {
        return $this->prompts;
    }

    public function setPrompts(Collection $prompts): SystemInstruction
    {
        $this->prompts = $prompts;
        return $this;
    }
}
