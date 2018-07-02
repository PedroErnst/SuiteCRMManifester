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
    private $commit = 'master';

    /**
     * @var Manifest
     */
    private $manifest;

    /**
     * @var array
     */
    private $files;

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
            ->addArgument(
                'commit',
                InputArgument::OPTIONAL,
                'The commit from which to look for changed files (defaults to master).'
            )
            ->setHelp(
                'Updates a manifest file with files changes in an instance since a certain commit. '
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

        if ($input->getArgument('commit')) {
            $this->commit = $input->getArgument('commit');
        }

        try {
            $this->loadManifest();
            $this->parseChangedFiles();
            $this->manifest->parseChangedFiles($this->files);
            $this->manifest->setPublishedToNow();
            $this->manifest->updateAuthor();
            $this->manifest->incrementVersion();
            $this->manifest->writeOut();
        }
        catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 0;
        }

        foreach ($this->files as $file) {
            $output->writeln($file);
        }
        $output->writeln('');
        $output->writeln(sprintf('Updated manifest with %s files', count($this->files)));
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

        $command = 'cd ' . $this->instancePath . ' && git diff --name-only --diff-filter=d ' . $this->commit;
        exec($command, $this->files);
    }
}