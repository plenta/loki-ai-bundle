<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\Entity\Field;
use Plenta\LokiAiBundle\OpenAi\Api;
use Plenta\LokiAiBundle\Prompt\PromptBuilder;
use Plenta\LokiAiBundle\Repository\FieldRepository;
use Plenta\LokiAiBundle\Repository\PromptRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'loki:prompts:run',
    description: 'Runs Loki AI prompts on the command line.',
)]
class RunPromptsCommand extends Command
{
    public function __construct(
        protected ContaoFramework $framework,
        protected FieldRepository $fieldRepository,
        protected Connection $connection,
        protected PromptBuilder $promptBuilder,
        protected Api $openAiApi,
        protected PromptRepository $promptRepository,
        string|null $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit the number of prompts.', 10);
        $this->addOption('prompt', 'p', InputOption::VALUE_OPTIONAL, 'Run a specific prompt. Provide the alias.');
        $this->addOption('all', 'a', InputOption::VALUE_OPTIONAL, 'Runs this command for all entries, not only empty fields.', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();

        if ($input->getOption('prompt')) {
            $prompt = $this->promptRepository->findOneBy(['alias' => $input->getOption('prompt'), 'published' => true]);

            if (!$prompt) {
                $output->writeln('<error>Prompt not found.</error>');

                return Command::FAILURE;
            }

            $prompts = [$prompt];
        } else {
            $prompts = $this->promptRepository->findBy(['autoRun' => true, 'published' => true]);
        }

        foreach ($prompts as $promptObj) {
            $fields = $promptObj->getFields();
            $titlePrinted = false;

            foreach ($fields as $field) {
                $dataFields = StringUtil::deserialize($field->getField(), true);
                $requirements = StringUtil::deserialize($field->getRequirements(), true);

                foreach ($dataFields as $dataField) {
                    if ($field->getParent()->getRootPage()) {
                        if ('tl_page' === $field->getTableName()) {
                            $ids = $this->promptBuilder->getPages($field);
                        } elseif ('tl_content' === $field->getTableName()) {
                            $ids = $this->promptBuilder->getContentElements($field);
                        }

                        if (!empty($ids)) {
                            $objects = $this->getObjects($field, $input, $requirements, $dataField, $ids);
                        } else {
                            $objects = null;
                        }
                    } else {
                        $objects = $this->getObjects($field, $input, $requirements, $dataField);
                    }

                    if ($objects) {
                        $progressBar = null;

                        foreach ($objects as $object) {
                            $prompt = $this->promptBuilder->build($field, $object, $dataField);

                            if (empty($prompt)) {
                                continue;
                            }

                            if (!$progressBar) {
                                if (!$titlePrinted) {
                                    $output->writeln(\sprintf('<info>%s</info>', $promptObj->getTitle()));
                                    $titlePrinted = true;
                                }

                                $output->writeln('<comment>'.$field->getTableName().' - '.$dataField.'</comment>');

                                $progressBar = new ProgressBar($output, \count($objects));
                                $progressBar->start();
                            }

                            $newValue = $this->promptBuilder->buildHeadline($this->openAiApi->chat($prompt, $field->getParent()->getModel(), $field->getParent()->getTemperature(), $field->getParent()->getMaxTokens()), $object, $field, $dataField);

                            $this->connection->update($field->getTableName(), [$dataField => $newValue], ['id' => $object]);

                            $progressBar->advance();
                        }

                        if ($progressBar) {
                            $progressBar->finish();
                            $output->writeln('');
                        }
                    }
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param  array<array<string, string>> $requirements
     * @param  array<string>|null           $ids
     * @return array<int>
     */
    protected function getObjects(Field $field, InputInterface $input, array $requirements, string $dataField, array|null $ids = null): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('t.id')
            ->from($field->getTableName(), 't')
        ;

        if (!empty($ids)) {
            $qb->where($qb->expr()->in('t.id', $ids));
        }

        if (false === $input->getOption('all')) {
            $qb
                ->andWhere($qb->expr()->or('t.'.$dataField.' = :empty', $qb->expr()->isNull('t.'.$dataField)))
                ->setParameter('empty', '')
            ;
        }

        foreach ($requirements as $requirement) {
            if (empty($requirement['key'])) {
                continue;
            }

            $qb
                ->andWhere('t.'.$requirement['key'].' = :value')
                ->setParameter('value', $requirement['value'])
            ;
        }

        return $qb
            ->setMaxResults($input->getOption('limit'))
            ->executeQuery()
            ->fetchFirstColumn()
        ;
    }
}
