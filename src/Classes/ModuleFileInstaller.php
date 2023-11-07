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

use Exception;
use RobinTheHood\ModifiedModuleLoaderClient\Helpers\FileHelper;
use RobinTheHood\ModifiedModuleLoaderClient\ModuleHasher\ModuleHashFileCreator;

class ModuleFileInstaller
{
    /**
     * (Re-) Installiert / überschreibt ein Modul (archiveName, Version) ohne dabei auf Abhänigkeiten und den
     * Modulstatus zu achten. Es wird nur auf Dateiebene kontrolliert, ob alle Dateien geschrieben werden konnten.
     * Die Autoload Datei wird NICHT erzeugt / erneuert.
     */
    public function install(Module $module): void
    {
        $this->installFiles($module);
        $this->createHashFile($module);
    }

    /**
     * Deinstalliert / entfernt ein Modul (archiveName, Version) ohne dabei auf Abhängigkeiten und den Modulstatus zu
     * achten. Es wird nur auf Dateiebene kontrolliert, ob alles Datien entfernt werden konnten. Die Autoload Datei
     * wird NICHT aktualisiert.
     */
    public function uninstall(Module $module): void
    {
        $this->uninstallFiles($module);
        $this->removeHashFile($module);
    }

    /**
     * (Re-) Installiert / Überschreibt nur die Datei zu einem Modul (archiveName, Version). Es wird nur auf Datei-Ebene
     * kontrolliert, ob alle Dateien geschrieben werden konnten. Die `modulehash.json` Datei wird NICHT erzeugt /
     * erneuert.
     */
    private function installFiles(Module $module): void
    {
        // Install Source Files to Shop Root
        $files = $module->getSrcFilePaths();

        foreach ($files as $file) {
            $src = $module->getLocalRootPath() . $module->getSrcRootPath() . '/' . $file;

            // TODO: Kontrollieren, könnte es Probleme machen, dass $files hier noch einmal zugewiesen / gesetzt wird?
            $files = $module->getTemplateFiles($file);
            foreach ($files as $file) {
                $overwrite = false;
                if (!FileInfo::isTemplateFile($file)) {
                    $overwrite = true;
                }

                $file = ModulePathMapper::moduleSrcToShopRoot($file);

                $dest = App::getShopRoot() . $file;
                $this->installFile($src, $dest, $overwrite);
            }
        }

        // Install Source Mmlc Files to shop vendor-mmlc
        $files = $module->getSrcMmlcFilePaths();
        foreach ($files as $file) {
            $src = $module->getLocalRootPath() . $module->getSrcMmlcRootPath() . '/' . $file;
            $file = ModulePathMapper::moduleSrcMmlcToShopVendorMmlc($file, $module->getArchiveName());
            $dest = App::getShopRoot() . '/' . $file;
            $this->installFile($src, $dest, true);
        }
    }

    private function installFile(string $src, string $dest, bool $overwrite = false): bool
    {
        if (!file_exists($src)) {
            throw new Exception("Can not install file $src - File not exists.");
        }

        if ($this->fileOrLinkExists($dest) && $overwrite === false) {
            // Die Datei existiert bereits und soll NICHT überschrieben werden.
            return false;
        } elseif ($this->fileOrLinkExists($dest) && $overwrite === true) {
            // Die Datei existiert bereits soll überschrieben.
            // Wir löschen die Datei zuerst, bevor wir sie überschreiben.
            $this->removeFile($dest);
        }

        FileHelper::makeDirIfNotExists($dest);

        // TODO: Kontrollieren ob hier eine Exception geworfen werden muss, wenn die Datei existiert.
        if ($this->fileOrLinkExists($dest)) {
            return false;
        }

        $this->copyFile($src, $dest);

        return true;
    }

    private function copyFile(string $srcPath, string $destPath): void
    {
        if (Config::getInstallMode() === 'link') {
            $result = symlink($srcPath, $destPath);
        } else {
            $result = copy($srcPath, $destPath);
        }

        if (!$result) {
            throw new Exception("Can not copy file $srcPath to $destPath");
        }
    }

    /**
     * Erzeugt / Überschreibt die `modulehash.json zu einem Modul (archive, Version)` Es wird nur auf Datei-Ebene
     * kontrolliert, ob alle Dateien geschrieben werden konnten.
     */
    private function createHashFile(Module $module): void
    {
        $moduleHashFileCreator = new ModuleHashFileCreator();
        $moduleHashFile = $moduleHashFileCreator->createHashFile($module);
        $moduleHashFile->writeTo($module->getHashPath());
    }

    private function uninstallFiles(Module $module): void
    {
        // Uninstall from shop-root
        $files = $module->getSrcFilePaths();
        foreach ($files as $file) {
            $file = ModulePathMapper::moduleSrcToShopRoot($file);
            $dest = App::getShopRoot() . $file;
            $this->removeIfFileExists($dest);
        }

        // Uninstall from shop-vendor-mmlc
        $files = $module->getSrcMmlcFilePaths();
        foreach ($files as $file) {
            $file = ModulePathMapper::moduleSrcMmlcToShopVendorMmlc($file, $module->getArchiveName());
            $dest = App::getShopRoot() . $file;
            $this->removeIfFileExists($dest);
            FileHelper::deletePathIsEmpty($dest);
        }
    }

    private function fileOrLinkExists(string $path): bool
    {
        return file_exists($path) || is_link($path);
    }

    private function removeIfFileExists(string $path): void
    {
        if (file_exists($path)) {
            $this->removeFile($path);
        }
    }

    private function removeFile(string $path): void
    {
        $result = unlink($path);
        if (!$result) {
            throw new Exception("Can not remove file $path");
        }
    }

    private function removeHashFile(Module $module): void
    {
        $path = $module->getHashPath();
        $this->removeIfFileExists($path);
    }
}
