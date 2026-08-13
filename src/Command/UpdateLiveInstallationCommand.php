<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Command;

use Lebensbaum\ContaoDomainManagerBundle\Sync\LiveInstallationUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'domain-manager:update-live',
    description: 'Ermittelt und aktualisiert die aktive Installation einer Hauptdomain.',
)]
final class UpdateLiveInstallationCommand extends Command
{
    public function __construct(
        private readonly LiveInstallationUpdater $updater,
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
            $result = $this->updater->update($domainId);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if ($result['changed']) {
            $io->success(sprintf(
                'Das aktuelle Ziel wurde auf „%s“ geändert.',
                $result['installation_domain']
            ));
        } else {
            $io->success(sprintf(
                '„%s“ war bereits als aktuelles Ziel eingetragen.',
                $result['installation_domain']
            ));
        }

        $io->table(
            ['Angabe', 'Wert'],
            [
                [
                    'Öffentliche Hauptdomain',
                    $result['public_domain'],
                ],
                [
                    'Erkannte Installation',
                    $result['installation_domain'],
                ],
                [
                    'Datensatz-ID',
                    (string) $result['installation_id'],
                ],
                [
                    'Live-Status geändert',
                    $result['changed'] ? 'Ja' : 'Nein',
                ],
            ]
        );

        return Command::SUCCESS;
    }
}