<?php

namespace Manifester\Command;

use Manifester\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class NewManifestCommand extends Command
{
    /**
     * @var string
     */
    private $manifestPath;

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
            ->setName('new')
            ->setDescription('Creates a new manifest.')
            ->addArgument('manifest-path', InputArgument::REQUIRED, 'The folder in which to create the manifest.')
            ->setHelp(
                'Creates a manifest in the specified folder. Creates the folder if it doesn\'t exist. '
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->manifestPath = $input->getArgument('manifest-path');

        try {
            $this->loadDefaultManifest();
            $this->manifest->setPublishedToNow();
            $this->manifest->updateAuthor();
            $this->manifest->writeOut($this->manifestPath . '/manifest.php');
            copy(__DIR__ . '/../boilerplate/LICENSE.txt', $this->manifestPath . '/LICENSE.txt');
        }
        catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 0;
        }

        $output->writeln('Created a new manifest in:');
        $output->writeln($this->manifestPath);

        return 1;
    }

    /**
     * @throws \Exception
     */
    private function loadDefaultManifest()
    {
        if (!is_dir($this->manifestPath)) {
            $folder = dirname($this->manifestPath);
            if (!is_writable($folder)) {
                throw new \Exception('Manifest parent folder is not writable!');
            }
            mkdir($this->manifestPath);
        }
        if (!is_writable($this->manifestPath)) {
            throw new \Exception('Manifest folder is not writable!');
        }
        $path = __DIR__ . '/../boilerplate/default-manifest.php';
        $this->manifest = Manifest::fromFile($path);

        if (!$this->manifest->validateManifest()) {
            throw new \Exception('$manifest not an array in manifest.php?');
        }
    }
}