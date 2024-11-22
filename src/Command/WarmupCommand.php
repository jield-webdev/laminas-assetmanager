<?php

declare(strict_types=1);

namespace AssetManager\Command;


use AssetManager\Service\AssetManager;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;

final class WarmupCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'assetmanager:warmup';

    public function __construct(private readonly AssetManager $assetManager, private readonly array $appConfig)
    {
        parent::__construct(name: self::$defaultName);
    }

    #[Override]
    protected function configure(): void
    {
        $this->setName(self::$defaultName);

        $this->addOption(name: 'purge', shortcut: 'p', mode: InputOption::VALUE_NONE, description: 'Purge index');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $purge   = $input->getOption(name: 'purge');
        $verbose = $input->getOption(name: 'verbose');

        if ($verbose) {
            $output->writeln(messages: '<info>Start warming up</info>');
        }

        if ($purge) {
            if ($verbose) {
                $output->writeln(messages: '<comment>Purge requested, starting purge</comment>');
            }

            $this->purgeCache(output: $output, verbose: $verbose);
        }

        if ($verbose) {
            $output->writeln(messages: '<info>Collecting all assets</info>');
        }

        $collection = $this->assetManager->getResolver()->collect();

        if (count(value: $collection) === 0) {
            if ($verbose) {
                $output->writeln(messages: '<comment>No assets found</comment>');
            }
        } else {
            if ($verbose) {
                $output->writeln(messages: sprintf('<comment>Collected %d assets, warming up</comment>', count(value: $collection)));
            }

            foreach ($collection as $path) {
                $asset = $this->assetManager->getResolver()->resolve(fileName: $path);

                $this->assetManager->getAssetFilterManager()->setFilters(path: $path, asset: $asset);
                $this->assetManager->getAssetCacheManager()->setCache(path: $path, asset: $asset)->dump();
            }
        }

        if ($verbose) {
            $output->writeln(messages: '<info>Warming up finished</info>');
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
                $output->writeln(messages: sprintf('Purging %s on "%s"', $configName, $config['options']['dir']));
            }

            $node = $config['options']['dir'];

            if ($configName !== 'default') {
                $node .= '/' . $configName;
            }

            $this->recursiveRemove(node: $node, output: $output, verbose: $verbose);
        }

    }

    /**
     * Removes given node from filesystem (recursively).
     */
    private function recursiveRemove(string $node, OutputInterface $output, bool $verbose = false): void
    {
        if (is_dir(filename: $node)) {
            $objects = scandir(directory: $node);

            if ($verbose) {
                $output->writeln(messages: sprintf('Found folder %s', $node));
            }

            foreach ($objects as $object) {
                if ($object === '.' || $object === '..') {
                    continue;
                }

                $this->recursiveRemove(node: $node . '/' . $object, output: $output, verbose: $verbose);
            }
        } elseif (is_file(filename: $node)) {

            //Filetype check (we only want to purge css and js files)
            if (!str_contains(haystack: $node, needle: '.css') && !str_contains(haystack: $node, needle: '.js')) {
                return;
            }

            unlink(filename: $node);
        }
    }
}
