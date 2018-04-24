<?php

namespace Manifester\Command;

use Manifester\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class FulfillManifestCommand extends Command
{
    /**
     * @var string
     */
    private $instancePath;

    /**
     * @var string
     */
    private $manifestPath;

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var Manifest
     */
    private $manifest;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('fulfill')
            ->setDescription('Brings files into the manifest folder.')
            ->addArgument('manifest-path', InputArgument::REQUIRED, 'The folder in which to find the manifest.')
            ->addArgument('instance-path', InputArgument::REQUIRED, 'The folder in which the SCRM instance lives.')
            ->setHelp(
                'Copies files specified in a manifest, from their SCRM instance folders, to their respective folders relative to the manifest. '
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->instancePath = $input->getArgument('instance-path');
        $this->manifestPath = $input->getArgument('manifest-path');

        try {
            $this->loadManifest();
            $this->copyFiles();
        }
        catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 0;
        }

        $output->writeln(sprintf('Copied %s files to manifest folder structure', $this->count));
        $output->writeln($this->manifestPath);

        return 1;
    }

    /**
     * @throws \Exception
     */
    private function loadManifest()
    {
        if (!is_writable($this->manifestPath)) {
            throw new \Exception('Manifest folder is not writable!');
        }
        $path = $this->manifestPath . '/manifest.php';
        $this->manifest = Manifest::fromFile($path);

        if (!$this->manifest->validateManifest()) {
            throw new \Exception('Invalid manifest array!');
        }
        if (!$this->manifest->validateInstallDefs()) {
            throw new \Exception('Invalid installdefs array!');
        }
    }

    /**
     *
     */
    private function copyFiles()
    {
        $installdefs = $this->manifest->getInstallDefs();
        foreach ($installdefs as $category) {
            if (!is_array($category)) {
                continue;
            }
            foreach ($category as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (!isset($item['from'])) {
                    continue;
                }
                $from = str_replace('<basepath>', '', $item['from']);

                $this->createSubDirectories($from);

                $fromPath = $this->instancePath . $from;
                $toPath = $this->manifestPath . $from;

                copy($fromPath, $toPath);
                $this->count++;
            }
        }
    }

    /**
     * @param $from
     */
    private function createSubDirectories($from)
    {
        $folder = dirname($from);
        $directories = explode('/', $folder);
        $path = rtrim($this->manifestPath, '/');
        foreach ($directories as $directory) {
            if ($directory === '') {
                continue;
            }
            $path = $path . '/' . $directory;
            if (!is_dir($path)) {
                mkdir($path);
            }
        }
    }
}