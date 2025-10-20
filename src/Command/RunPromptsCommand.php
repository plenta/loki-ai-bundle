<?php

namespace Plenta\LokiAiBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
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
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit the number of prompts.', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();

        $prompts = $this->promptRepository->findBy(['autoRun' => true, 'published' => true]);

        foreach ($prompts as $prompt) {
            $fields = $prompt->getFields();
            $titlePrinted = false;

            foreach ($fields as $field) {
                $dataFields = StringUtil::deserialize($field->getField(), true);

                foreach ($dataFields as $dataField) {
                    if ($field->getTableName() === 'tl_page' && $field->getParent()->getRootPage()) {
                        $ids = $this->promptBuilder->getPages($field);

                        if (!empty($ids)) {
                            $objects = $this->connection->fetchAllAssociative('SELECT id FROM '.$field->getTableName().' WHERE id IN ('.implode(',', $ids).') AND ('.$dataField.' = ? OR '.$dataField.' IS NULL) LIMIT '.$input->getOption('limit'), ['']);
                        } else {
                            $objects = null;
                        }

                    } else {
                        $objects = $this->connection->fetchAllAssociative('SELECT id FROM '.$field->getTableName().' WHERE '.$dataField.' = ? OR '.$dataField.' IS NULL LIMIT '.$input->getOption('limit'), ['']);
                    }

                    if ($objects) {
                        if (!$titlePrinted) {
                            $output->writeln(sprintf('<info>%s</info>', $prompt->getTitle()));
                            $titlePrinted = true;
                        }

                        $output->writeln('<comment>'.$field->getTableName().' - '.$dataField.'</comment>');

                        $progressBar = new ProgressBar($output, count($objects));
                        $progressBar->start();

                        foreach ($objects as $object) {
                            $prompt = $this->promptBuilder->build($field, $object['id'], $dataField);

                            $newValue = $this->openAiApi->chat($prompt, $field->getParent()->getModel(), $field->getParent()->getTemperature(), $field->getParent()->getMaxTokens());

                            $this->connection->update($field->getTableName(), [$dataField => $newValue], ['id' => $object['id']]);

                            $progressBar->advance();
                        }

                        $progressBar->finish();
                        $output->writeln('');
                    }

                }
            }
        }

        return Command::SUCCESS;
    }
}