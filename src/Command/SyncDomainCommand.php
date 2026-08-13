<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Command;

use Lebensbaum\ContaoDomainManagerBundle\Sync\DomainSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'domain-manager:sync-domain',
    description: 'Synchronisiert alle Installationen einer Hauptdomain.',
)]
final class SyncDomainCommand extends Command
{
    public function __construct(
        private readonly DomainSynchronizer $domainSynchronizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'domain-id',
            InputArgument::REQUIRED,
            'Datenbank-ID des Datensatzes in tl_domain_manager_domain'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $domainId = filter_var(
            $input->getArgument('domain-id'),
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if (false === $domainId) {
            $io->error(
                'Die Domain-ID muss eine positive Ganzzahl sein.'
            );

            return Command::INVALID;
        }

        try {
            $result = $this->domainSynchronizer->synchronize(
                $domainId
            );
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $synchronized = $result['synchronized'];
        $skipped = $result['skipped'];
        $failed = $result['failed'];

        $io->title(sprintf(
            'Synchronisation der Hauptdomain „%s“',
            $result['domain']
        ));

        if ([] !== $synchronized) {
            $rows = [];

            foreach ($synchronized as $installation) {
                $rows[] = [
                    $installation['domain'],
                    $installation['old_contao_version'],
                    $installation['new_contao_version'],
                    $installation['old_php_version'],
                    $installation['new_php_version'],
                ];
            }

            $io->section('Erfolgreich synchronisiert');

            $io->table(
                [
                    'Installation',
                    'Contao vorher',
                    'Contao nachher',
                    'PHP vorher',
                    'PHP nachher',
                ],
                $rows
            );
        }

        if ([] !== $skipped) {
            $rows = [];

            foreach ($skipped as $installation) {
                $rows[] = [
                    $installation['domain'],
                    $installation['reason'],
                ];
            }

            $io->section('Übersprungen');

            $io->table(
                ['Installation', 'Grund'],
                $rows
            );
        }

        if ([] !== $failed) {
            $rows = [];

            foreach ($failed as $installation) {
                $rows[] = [
                    $installation['domain'],
                    $installation['error'],
                ];
            }

            $io->section('Fehlgeschlagen');

            $io->table(
                ['Installation', 'Fehler'],
                $rows
            );
        }

        $io->success(sprintf(
            '%d synchronisiert, %d übersprungen, %d fehlgeschlagen.',
            count($synchronized),
            count($skipped),
            count($failed)
        ));

        return [] === $failed
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}