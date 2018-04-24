<?php

namespace Manifester\Command;

use Manifester\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class BuildManifestCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Creates a new installable package from a modified SuiteCRM instance.')
            ->addArgument('manifest-path', InputArgument::REQUIRED, 'The destination folder.')
            ->addArgument('instance-path', InputArgument::REQUIRED, 'The folder in which the SCRM instance lives.')
            ->addArgument(
                'commit',
                InputArgument::OPTIONAL,
                'The commit from which to look for changed files (defaults to master).'
            )
            ->setHelp(
                'Creates a new installable package from a modified SuiteCRM instance. '
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $target = $input->getArgument('manifest-path');
        $source = $input->getArgument('instance-path');
        $commit = $input->getArgument('commit');

        if (!$this->runCommand('new', ['manifest-path' => $target])) {
            return 1;
        }

        $updateArgs = [
            'manifest-path' => $target,
            'instance-path' => $source,
            'commit' => $commit
        ];
        if (!$this->runCommand('update', $updateArgs)) {
            return 1;
        }

        $fulfillArgs = [
            'manifest-path' => $target,
            'instance-path' => $source
        ];
        if (!$this->runCommand('fulfill', $fulfillArgs)) {
            return 1;
        }

        return 0;
    }

    /**
     * @param $command
     * @param $args
     * @return int
     * @throws \Exception
     */
    private function runCommand($command, array $args)
    {
        $commandObj = $this->getApplication()->find($command);
        $args['command'] = $command;
        $argArr = new ArrayInput($args);
        return $commandObj->run($argArr, $this->output);
    }
}