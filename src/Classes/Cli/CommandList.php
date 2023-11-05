<?php

declare(strict_types=1);

/*
 * This file is part of MMLC - ModifiedModuleLoaderClient.
 *
 * (c) Robin Wieschendorf <mail@robinwieschendorf.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RobinTheHood\ModifiedModuleLoaderClient\Cli;

use RobinTheHood\ModifiedModuleLoaderClient\Loader\RemoteModuleLoader;

class CommandList
{
    public function __construct()
    {
    }

    public function run()
    {
        $remoteModuleLoader = RemoteModuleLoader::create();
        $modules = $remoteModuleLoader->loadAllLatestVersions();
        foreach ($modules as $module) {
            //echo $module->getArchiveName() . ' ' . $module->getVersion() . "\n";
            echo $module->getArchiveName() . "\n";
        }
    }
}