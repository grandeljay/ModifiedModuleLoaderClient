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

namespace RobinTheHood\ModifiedModuleLoaderClient;

use RobinTheHood\ModifiedModuleLoaderClient\Module;
use RobinTheHood\ModifiedModuleLoaderClient\Helpers\Hasher;

class ModuleHasher extends Hasher
{
    public function hashModule(Module $module): void
    {
        $hashFilePath = $module->getHashPath();
        $hashes = $this->createModuleHashes($module);
        $this->createHashFile($hashFilePath, $hashes);
    }

    public function unhashModule(Module $module): void
    {
        $hashFilePath = $module->getHashPath();
        $this->deleteHashFile($hashFilePath);
    }

    public function createModuleHashes(Module $module, bool $moduleDir = false): array
    {
        // hash src
        $files = $module->getSrcFilePaths();

        if ($moduleDir) {
            $root = $module->getLocalRootPath() . $module->getSrcRootPath() . '/';
        } else {
            $root = App::getShopRoot();
            $files = ModulePathMapper::mmlcPathsToShopPaths($files);
        }

        $hashesSrc = $this->createFileHashes($files, $root);

        if (!$moduleDir) {
            $hashesSrc = $this->mapHashesShopToMmlc($hashesSrc);
        }

        // hash src-mmlc
        $files = $module->getSrcFileMmlcPaths();
        $files = ModulePathMapper::srcMmlcToVendorMmlcPaths($files, $module->getArchiveName());
        $hashesSrcMmlc = $this->createFileHashes($files, $root);

        if (!$moduleDir) {
            $hashesSrcMmlc = $this->mapHashesVendorMmlcToSrcMmlc($hashesSrcMmlc, $module->getArchiveName());
        }

        return $hashesSrc + $hashesSrcMmlc;
    }

    public function mapHashesShopToMmlc(array $hashes): array
    {
        $mappedHashes = [];
        foreach ($hashes as $file => $hash) {
            $file = ModulePathMapper::shopToMmlc($file);
            $mappedHashes[$file] = $hash;
        }
        return $mappedHashes;
    }

    public function mapHashesVendorMmlcToSrcMmlc(array $hashes, $archiveName): array
    {
        $mappedHashes = [];
        foreach ($hashes as $file => $hash) {
            $file = ModulePathMapper::vendorMmlcToSrcMmlc($file, $archiveName);
            $mappedHashes[$file] = $hash;
        }
        return $mappedHashes;
    }

    public function loadeModuleHashes(Module $module): array
    {
        $hashFilePath = $module->getHashPath();
        $hashes = $this->loadHashes($hashFilePath);
        return $hashes;
    }

    public function getModuleChanges(Module $module)
    {
        $hashesLoaded = $this->loadeModuleHashes($module);
        $hashesCreatedA = $this->createModuleHashes($module);
        $hashesCreatedB = $this->createModuleHashes($module, true);

        var_dump($hashesLoaded['scopes']['src']['hashes']);
        var_dump($hashesCreatedA);
        var_dump($hashesCreatedB);
        //die();
        $result = $this->getChanges($hashesLoaded['scopes']['src']['hashes'], $hashesCreatedA, $hashesCreatedB);

        var_dump($result);
        die();
        return $result;
    }

    public static function getFileChanges(Module $module, string $path, string $mode = 'changed')
    {
        if ($mode != 'changed') {
            return '';
        }

        $moduleFilePath = $module->getLocalRootPath() . $module->getSrcRootPath() . '/' . $path;
        $installedFilePath = App::getShopRoot() . '/' . ModulePathMapper::mmlcToShop($path);

        if (file_exists($installedFilePath) && is_link($installedFilePath)) {
            return "No line by line diff available for linked files, because they have always equal content.";
        }

        $moduleFileContent = '';
        if (file_exists($moduleFilePath)) {
            $moduleFileContent = file_get_contents($moduleFilePath);
        }

        $installedFileContent = '';
        if (file_exists($installedFilePath)) {
            $installedFileContent = file_get_contents($installedFilePath);
        }

        $builder = new \SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder(
            "--- Original\n+++ New\n",  // custom header
            true                        // show line numbers
        );

        $differ = new \SebastianBergmann\Diff\Differ($builder);
        return $differ->diff($moduleFileContent, $installedFileContent);
    }
}
