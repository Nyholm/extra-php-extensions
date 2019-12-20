<?php

declare(strict_types=1);

namespace Bref\Extra\Command;

use Bref\Extra\Aws\LayerPublisher;
use Bref\Extra\Service\RegionProvider;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * This script publishes all the layers in all the regions.
 */
class PublishCommand
{
    /**
     * @var LayerPublisher
     */
    private $publisher;
    /**
     * @var string
     */
    private $projectDir;
    /**
     * @var RegionProvider
     */
    private $regionProvider;

    public function __construct(LayerPublisher $publisher, RegionProvider $regionProvider, string $projectDir)
    {
        $this->publisher = $publisher;
        $this->projectDir = $projectDir;
        $this->regionProvider = $regionProvider;
    }

    public function __invoke(OutputInterface $output)
    {
        $checksums = file_get_contents($this->projectDir.'/checksums.json');
        $discoveredChecksums = [];

        $layers = [];
        $finder = new Finder();
        $finder->in(__DIR__.'/../export')
            ->name('layer-*');
        foreach ($finder->files() as $file) {
            /** @var \SplFileInfo $file */
            $layerFile = $file->getFilename();
            $layerName = substr($file->getFilenameWithoutExtension(), 6);
            $md5 = md5_file($file->getRealPath());
            $discoveredChecksums[$layerName] = $md5;
            if (false === strstr($checksums, $md5)) {
                // This layer is new.
                $layers[$layerName] = $layerFile;
            }
        }
        $output->writeln(sprintf('Found %d new layers', count($layers)));

        try {
            $this->publisher->publishLayers(array_values($layers), $this->regionProvider->getAll());
        }catch(\Exception $e) {
            // TODO write output.
            exit(1);
        }

        // Dump checksums
        file_put_contents($this->projectDir.'/checksums.json', json_encode($discoveredChecksums));

        $output->writeln('Done');
        $output->writeln('Remember to commit and push changes to ./checksums.json');

        return 0;
    }
}