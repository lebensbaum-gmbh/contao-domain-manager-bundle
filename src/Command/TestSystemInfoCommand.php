<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Command;

use Lebensbaum\ContaoDomainManagerBundle\Sync\SystemInfoClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'domain-manager:test-system-info',
    description: 'Testet den signierten Abruf einer Contao-Installation.',
)]
final class TestSystemInfoCommand extends Command
{
    public function __construct(
        private readonly SystemInfoClient $systemInfoClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'Basis-URL der Installation, z. B. https://install1.example.de'
            )
            ->addArgument(
                'system-id',
                InputArgument::REQUIRED,
                'Installations-ID der abzufragenden Installation'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $baseUrl = trim((string) $input->getArgument('url'));
        $systemId = trim((string) $input->getArgument('system-id'));

        try {
            $systemInfo = $this->systemInfoClient->fetch(
                $baseUrl,
                $systemId
            );
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(
            'Der Systeminfo-Endpunkt wurde erfolgreich abgerufen.'
        );

        $io->table(
            ['Angabe', 'Wert'],
            [
                [
                    'Installations-ID',
                    'stimmt mit der erwarteten ID überein',
                ],
                [
                    'Contao-Version',
                    $systemInfo['contao_version'] ?? 'nicht ermittelbar',
                ],
                [
                    'PHP-Version',
                    $systemInfo['php_version'],
                ],
                [
                    'Umgebung',
                    $systemInfo['app_environment'],
                ],
                [
                    'Antwort erzeugt',
                    $systemInfo['generated_at'],
                ],
            ]
        );

        return Command::SUCCESS;
    }
}