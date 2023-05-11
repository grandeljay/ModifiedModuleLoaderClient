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

namespace RobinTheHood\ModifiedModuleLoaderClient\Logger;

use RobinTheHood\ModifiedModuleLoaderClient\App;
use RobinTheHood\ModifiedModuleLoaderClient\Config;

class StaticLogger
{
    public static function log(string $logLevel, string $message, array $context = []): void
    {
        if (!Config::getLogging()) {
            return;
        }

        $logger = new Logger();
        $logger->setLogDir(App::getLogsRoot() . '/');
        $logger->log($logLevel, $message);
    }
}
