<?php

namespace Manifester;

use Manifester\Command\FulfillManifestCommand;
use Manifester\Command\UpdateManifestCommand;
use Symfony\Component\Console\Application;

/**
 * Class Manifester
 * @package Manifester
 */
class Manifester extends Application
{

    /**
     * Manifester constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->add(new FulfillManifestCommand());
        $this->add(new UpdateManifestCommand());
    }
}