<?php

declare(strict_types=1);

namespace BonsaiPress\Command;

use BonsaiPress\EcmsConfig;
use BonsaiPress\PageRenderer;
use BonsaiPress\StaticExporter;
use BonsaiPress\XmlPageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'static', description: 'Build static HTML export')]
class StaticCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $basePath = dirname(__DIR__, 3);
        $config   = new EcmsConfig($basePath);
        $lang     = $config->defaultLang();
        $xmlPath  = $basePath . '/current/config/' . $lang . '/site_structure.xml';

        if (!file_exists($xmlPath)) {
            $output->writeln('<error>site_structure.xml nicht gefunden: ' . $xmlPath . '</error>');
            return Command::FAILURE;
        }

        $repo     = new XmlPageRepository($xmlPath);
        $renderer = new PageRenderer($config, $basePath);
        $exporter = new StaticExporter($renderer, $repo, $basePath, $config->baseUrl());

        $output->writeln('Starte statischen Export...');

        foreach ($exporter->export() as $title => $path) {
            $output->writeln('  ✓ ' . $title . ' → ' . str_replace($basePath, '', $path));
        }

        $output->writeln('  ✓ sitemap.xml');
        $output->writeln('<info>Fertig.</info>');
        return Command::SUCCESS;
    }
}
