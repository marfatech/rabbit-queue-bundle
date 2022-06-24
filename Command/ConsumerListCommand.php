<?php

declare(strict_types=1);

/*
 * This file is part of the RabbitQueueBundle package.
 *
 * (c) MarfaTech <https://marfa-tech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MarfaTech\Bundle\RabbitQueueBundle\Command;

use MarfaTech\Bundle\RabbitQueueBundle\Registry\ConsumerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function ksort;
use function sprintf;

class ConsumerListCommand extends Command
{
    protected static $defaultName = 'rabbit:consumer:list';

    private ConsumerRegistry $consumerRegistry;

    public function dependencyInjection(ConsumerRegistry $consumerRegistry): void
    {
        $this->consumerRegistry = $consumerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Shows all registered consumers')
            ->setHelp('This command allows you to view list of the all consumers in the system')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consumerList = $this->consumerRegistry->getConsumerList();

        ksort($consumerList);

        $consumerCount = count($consumerList);

        if ($consumerCount === 0) {
            $output->writeln('<comment>You have not yet any registered consumer</comment>');

            return 0;
        }

        $consoleStyle = new SymfonyStyle($input, $output);

        $table = new Table($output);
        $table->setHeaders(['Queue Name', 'Batch Size']);

        foreach ($consumerList as $consumer) {
            $batchSize = $consumer->getBatchSize();
            $table->addRow([$consumer->getBindQueueName(), $batchSize]);
        }

        $consoleStyle->text(sprintf('Total consumers count: <comment>%s</comment>', $consumerCount));

        $table->render();

        return 0;
    }
}
