<?php

namespace Manifester\Command;

use Manifester\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class UpdateManifestCommand extends Command
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
     * @var string
     */
    private $commit;

    /**
     * @var array
     */
    private $installDefs;

    /**
     * @var Manifest
     */
    private $manifest;

    /**
     * @var array
     */
    private $files;

    /**
     * UpdateManifestCommand constructor.
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
            ->setName('update')
            ->setDescription('Updates a manifest with changed files.')
            ->addArgument('manifest-path', InputArgument::REQUIRED, 'The folder in which to find the manifest.')
            ->addArgument('instance-path', InputArgument::REQUIRED, 'The folder in which the SCRM instance lives.')
            ->addArgument('commit', InputArgument::REQUIRED, 'The commit from which to look for changed files.')
            ->setHelp(
                'Updates a manifest file with files changes in an instance since a certain commit. '
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
        $this->commit = $input->getArgument('commit');

        try {
            $this->loadManifest();
            $this->parseChangedFiles();
            $this->manifest->parseChangedFiles($this->files);
            $this->manifest->setPublishedToNow();
            $this->manifest->writeOut();
        }
        catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
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
            throw new \Exception('$manifest not an array in manifest.php?');
        }
    }

    /**
     * @throws \Exception
     */
    private function parseChangedFiles()
    {
        if (!is_dir($this->instancePath)) {
            throw new \Exception('Invalid instance path: ' . $this->instancePath);
        }

        $command = 'cd ' . $this->instancePath . ' && git diff --name-only ' . $this->commit;
        exec($command, $this->files);
    }
}