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

namespace RobinTheHood\ModifiedModuleLoaderClient\Cli\Command;

use RobinTheHood\ModifiedModuleLoaderClient\Cli\MmlcCli;
use RobinTheHood\ModifiedModuleLoaderClient\Cli\TextRenderer;
use RobinTheHood\ModifiedModuleLoaderClient\Loader\RemoteModuleLoader;

class CommandInfo implements CommandInterface
{
    public function __construct()
    {
    }

    public function getName(): string
    {
        return 'info';
    }

    public function run(MmlcCli $cli): void
    {
        $archiveName = $cli->getFilteredArgument(0);
        if (!$archiveName) {
            echo "No archiveName specified. \n";
            return;
        }

        $remoteModuleLoader = RemoteModuleLoader::create();
        $module = $remoteModuleLoader->loadLatestVersionByArchiveName($archiveName);

        if (!$module) {
            echo "Module $archiveName not found. \n";
            return;
        }

        echo "name:              " . $module->getName() . "\n";
        echo "archiveName:       " . $module->getArchiveName() . "\n";
        echo "latestVersion:     " . $module->getVersion() . "\n";
        echo "shortDescription:  " . $module->getShortDescription() . "\n";
    }

    public function runHelp(MmlcCli $cli): void
    {
        TextRenderer::renderHelpHeading('Description:');
        echo "  Display information and details for a specific module.\n";
        echo "\n";

        TextRenderer::renderHelpHeading('Usage:');
        echo "  info <archiveName>\n";
        echo "\n";

        TextRenderer::renderHelpHeading('Options:');
        TextRenderer::renderHelpOption('h', 'help', 'Display help for the given command.');
        echo "\n";

        echo "Read more at https://module-loader.de/documentation.php\n";
    }
}
