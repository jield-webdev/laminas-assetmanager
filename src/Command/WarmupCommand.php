<?php

declare(strict_types=1);

namespace AssetManager\Command;


use AssetManager\Service\AssetManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;

final class WarmupCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'assetmanager:warmup';

    public function __construct(private AssetManager $assetManager, private array $appConfig)
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName);

        $this->addOption(name: 'purge', shortcut: 'p', mode: InputOption::VALUE_OPTIONAL, description: 'Purge index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $purge   = $input->getOption(name: 'purge');
        $verbose = $input->getOption(name: 'verbose');

        if ($verbose) {
            $output->writeln('<info>Start warmup</info>');
        }

        if ($purge) {
            $this->purgeCache($output, $verbose);
        }

        if ($verbose) {
            $output->writeln('Collecting all assets');
        }

        $collection = $this->assetManager->getResolver()->collect();

        if (count($collection) === 0) {
            if ($verbose) {
                $output->writeln('<comment>No assets found</comment>');
            }
        } else {
            if ($verbose) {
                $output->writeln(sprintf('Collected %d assets, warming up', count($collection)));
            }

            foreach ($collection as $path) {
                $asset = $this->assetManager->getResolver()->resolve($path);

                $this->assetManager->getAssetFilterManager()->setFilters($path, $asset);
                $this->assetManager->getAssetCacheManager()->setCache($path, $asset)->dump();
            }
        }

        if ($verbose) {
            $output->writeln('<info>Warming up finished</info>');
        }

        return Command::SUCCESS;
    }

    /**
     * Purges all directories defined as AssetManager cache dir.
     */
    private function purgeCache(OutputInterface $output, bool $verbose = false): void
    {

        if (empty($this->appConfig['asset_manager']['caching'])) {
            return;
        }

        foreach ($this->appConfig['asset_manager']['caching'] as $configName => $config) {

            if (empty($config['options']['dir'])) {
                continue;
            }

            if ($verbose) {
                $output->writeln(sprintf('<info>Purging %s on "%s"</info>', $config['options']['dir'], $configName));
            }

            $node = $config['options']['dir'];

            if ($configName !== 'default') {
                $node .= '/' . $configName;
            }

            $this->recursiveRemove($node, $output, $verbose);
        }

    }

    /**
     * Removes given node from filesystem (recursively).
     */
    private function recursiveRemove(string $node, OutputInterface $output, bool $verbose = false): void
    {
        if (is_dir($node)) {
            $objects = scandir($node);

            foreach ($objects as $object) {
                if ($object === '.' || $object === '..') {
                    continue;
                }
                $this->recursiveRemove($node . '/' . $object, $output, $verbose);
            }
        } elseif (is_file($node)) {
            if ($verbose) {
                $output->writeln(sprintf('<info>unlinking %s</info>', $node));
            }
            unlink($node);
        }
    }
}
