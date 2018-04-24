<?php

namespace Manifester\Command;

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
     * @var array
     */
    private $installDefs;

    /**
     * @var int
     */
    private $count = 0;

    /**
     * FulfillManifestCommand constructor.
     * @param null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

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
     * @return void
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
        }

        $output->writeln(sprintf('Copied %s files to manifest folder structure', $this->count));
        $output->writeln($this->manifestPath);
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
        if (!file_exists($path)) {
            throw new \Exception('Unable to locate manifest at: ' . $path);
        }
        $installdefs = [];
        require_once $path;
        if (!is_array($installdefs)) {
            throw new \Exception('$installdefs not an array in manifest.php?');
        }
        $this->installDefs = $installdefs;
    }

    /**
     *
     */
    private function copyFiles()
    {
        foreach ($this->installDefs as $category) {
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