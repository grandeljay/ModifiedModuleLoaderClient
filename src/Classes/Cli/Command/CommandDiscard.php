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
use RobinTheHood\ModifiedModuleLoaderClient\DependencyManager\DependencyException;
use RobinTheHood\ModifiedModuleLoaderClient\Loader\LocalModuleLoader;
use RobinTheHood\ModifiedModuleLoaderClient\ModuleInstaller;
use RobinTheHood\ModifiedModuleLoaderClient\ModuleManager\ModuleManager;
use RuntimeException;

class CommandDiscard implements CommandInterface
{
    public function __construct()
    {
    }

    public function getName(): string
    {
        return 'discard';
    }

    public function run(MmlcCli $cli): void
    {
        $archiveName = $cli->getFilteredArgument(0);

        if (!$archiveName) {
            $cli->writeLine($this->getHelp($cli));
            return;
        }

        $moduleLoader = LocalModuleLoader::createFromConfig();
        $module = $moduleLoader->loadInstalledVersionByArchiveName($archiveName);

        if (!$module) {
            $cli->writeLine(
                "Module " . TextRenderer::color($archiveName, TextRenderer::COLOR_GREEN) . " is not installed."
            );
            return;
        }

        $moduleText =
            "module " . TextRenderer::color($archiveName, TextRenderer::COLOR_GREEN)
            . " version " . TextRenderer::color($module->getVersion(), TextRenderer::COLOR_YELLOW);

        if (!$module->isChanged()) {
            $cli->writeLine("Can an not discard $moduleText, because the modul has no changes.\n");
            return;
        }

        $cli->writeLine("Discarding $moduleText ...");

        try {
            $moduleManager = ModuleManager::createFromConfig();
            $moduleManager->discard($module);
        } catch (RuntimeException $e) {
            $cli->writeLine(
                TextRenderer::color('Error:', TextRenderer::COLOR_RED)
                . " can not discard $moduleText."
                . " Message: " . $e->getMessage()
            );
            die();
        }

        $cli->writeLine(TextRenderer::color('ready', TextRenderer::COLOR_GREEN));
        return;
    }

    public function getHelp(MmlcCli $cli): string
    {
        return
            TextRenderer::renderHelpHeading('Description:')
            . "  Uninstall a module from your shop.\n"
            . "\n"

            . TextRenderer::renderHelpHeading('Usage:')
            . "  uninstall <archiveName>\n"
            . "\n"

            . TextRenderer::renderHelpHeading('Options:')
            . TextRenderer::renderHelpOption('f', 'force', 'Uninstall even if the module has changes.')
            . TextRenderer::renderHelpOption('h', 'help', 'Display help for the given command.')
            . "\n"

            . "Read more at https://module-loader.de/documentation.php";
    }
}