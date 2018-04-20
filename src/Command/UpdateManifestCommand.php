<?php

namespace Manifester\Command;

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
     * @var array
     */
    private $manifest;

    /**
     * @var array
     */
    private $files;

    /**
     * @var resource
     */
    private $file;

    /**
     * @var int
     */
    private $indentation = 0;

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
            $this->setUpDefs();
            $this->writeManifest();
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
        if (!file_exists($path)) {
            throw new \Exception('Unable to locate manifest at: ' . $path);
        }
        $manifest = [];
        $installdefs = [];
        require_once $path;
        if (!is_array($manifest)) {
            throw new \Exception('$manifest not an array in manifest.php?');
        }
        $this->installDefs = $installdefs;
        $this->manifest = $manifest;
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

    /**
     *
     */
    private function setUpDefs()
    {
        $langFiles = [];
        $copyFiles = [];
        foreach ($this->files as $file) {
            if (strpos($file, 'en_us') !== false) {
                $module = 'application';
                if (strpos($file, 'custom/Extension/modules/') !== false) {
                    $startPos = strpos($file, 'custom/Extension/modules/')
                        + strlen('custom/Extension/modules/');
                    $module = substr(
                        $file,
                        $startPos,
                        strpos($file, '/', $startPos) - $startPos
                    );

                }
                $langFiles[] = [
                    'from' => '<basepath>/' . $file,
                    'to_module' => $module,
                    'language' => 'en_us',
                ];
                continue;
            }
            $copyFiles[] = [
                'from' => '<basepath>/' . $file,
                'to' => $file,
            ];
        }

        $this->installDefs['copy'] = $copyFiles;
        $this->installDefs['language'] = $langFiles;
    }

    private function writeManifest()
    {
        $this->file = fopen($this->manifestPath . '/manifest.php', 'w');

        $this->write('<?php');

        $this->endLine();
        $this->write('$manifest = ');
        $this->writeVariable($this->manifest);
        $this->write(';');

        $this->endLine();
        $this->write('$installdefs = ');
        $this->writeVariable($this->installDefs);
        $this->write(';');

        fclose( $this->file);
    }

    private function writeVariable($var)
    {
        if (is_array($var)) {
            $this->write('[');
            $this->indentation += 4;
            $this->endLine();
            foreach ($var as $key => $value) {
                $this->write("'" . $key . "' => ");
                $this->writeVariable($value);
                $this->write(',');
                $this->endLine();
            }
            $this->indentation -= 4;
            $this->write(']');
            return;
        }
        $this->write("'" . $var . "'");
    }

    private function write($value)
    {
        fwrite($this->file, $value);
    }

    private function endLine()
    {
        $this->write(PHP_EOL);
        fwrite($this->file, str_repeat(' ', $this->indentation));
    }
}