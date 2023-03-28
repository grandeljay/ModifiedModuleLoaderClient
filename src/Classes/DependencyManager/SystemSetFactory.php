<?php

/*
 * This file is part of MMLC - ModifiedModuleLoaderClient.
 *
 * (c) Robin Wieschendorf <mail@robinwieschendorf.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace RobinTheHood\ModifiedModuleLoaderClient\DependencyManager;

use RobinTheHood\ModifiedModuleLoaderClient\App;
use RobinTheHood\ModifiedModuleLoaderClient\Loader\LocalModuleLoader;
use RobinTheHood\ModifiedModuleLoaderClient\ShopInfo as ModifiedModuleLoaderClientShopInfo;

class SystemSetFactory
{
    public function getSystemSet(): SystemSet
    {
        $systemSet = new SystemSet();
        $systemSet->add('modified', ModifiedModuleLoaderClientShopInfo::getModifiedVersion());
        $systemSet->add('php', phpversion());
        $systemSet->add('mmlc', App::getMmlcVersion());

        $moduleLoader = LocalModuleLoader::getModuleLoader();
        $modules = $moduleLoader->loadAllInstalledVersions();
        foreach ($modules as $module) {
            $systemSet->add($module->getArchiveName(), $module->getVersion());
        }
        return $systemSet;
    }
}