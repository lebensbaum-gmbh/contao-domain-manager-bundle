<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Command;

use Lebensbaum\ContaoDomainManagerBundle\Sync\InstallationSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'domain-manager:sync-installation',
    description: 'Synchronisiert die Systeminformationen einer Installation.',
)]
final class SyncInstallationCommand extends Command
{
    public function __construct(
        private readonly InstallationSynchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'installation-id',
            InputArgument::REQUIRED,
            'Datenbank-ID des Datensatzes in tl_domain_manager_installation'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $installationId = filter_var(
            $input->getArgument('installation-id'),
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if (false === $installationId) {
            $io->error('Die Installations-ID muss eine positive Ganzzahl sein.');

            return Command::INVALID;
        }

        try {
            $result = $this->synchronizer->synchronize($installationId);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Die Installation „%s“ wurde erfolgreich synchronisiert.',
            $result['domain']
        ));

        $io->table(
            ['Angabe', 'Vorher', 'Nachher'],
            [
                [
                    'Contao-Version',
                    $result['old_contao_version'],
                    $result['new_contao_version'],
                ],
                [
                    'PHP-Version',
                    $result['old_php_version'],
                    $result['new_php_version'],
                ],
            ]
        );

        return Command::SUCCESS;
    }
}