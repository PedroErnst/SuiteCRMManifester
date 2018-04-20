<?php

namespace Manifester;

use Manifester\Command\FulfillManifestCommand;
use Symfony\Component\Console\Application;


class Manifester extends Application
{

    public function __construct()
    {
        parent::__construct();
        $this->add(new FulfillManifestCommand());
    }
}