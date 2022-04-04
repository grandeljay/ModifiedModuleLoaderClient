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

namespace RobinTheHood\ModifiedModuleLoaderClient\Helpers;

class Measurement
{
    public static function getTimeStamp(): string
    {
        $now = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        return $now->format("H:i:s.u");
    }
}
