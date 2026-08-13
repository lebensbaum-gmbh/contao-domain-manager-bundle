<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Command;

use Lebensbaum\ContaoDomainManagerBundle\Sync\LiveInstallationDetector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'domain-manager:detect-live',
    description: 'Ermittelt die aktuell hinter einer Hauptdomain laufende Installation.',
)]
final class DetectLiveInstallationCommand extends Command
{
    public function __construct(
        private readonly LiveInstallationDetector $detector,
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
            $result = $this->detector->detect($domainId);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(
            'Die aktive Installation wurde eindeutig erkannt.'
        );

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
                    'Installations-ID',
                    'stimmt mit der Antwort überein',
                ],
            ]
        );

        return Command::SUCCESS;
    }
}