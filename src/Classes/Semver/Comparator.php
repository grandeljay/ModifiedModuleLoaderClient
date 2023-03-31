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

namespace RobinTheHood\ModifiedModuleLoaderClient\Semver;

use RobinTheHood\ModifiedModuleLoaderClient\Semver\Parser;

class Comparator
{
    public const CARET_MODE_LAX = 0;
    public const CARET_MODE_STRICT = 1;

    /** @var Parser */
    protected $parser;

    /** @var int */
    private $mode = self::CARET_MODE_STRICT;

    public function __construct(Parser $parser, int $mode = self::CARET_MODE_STRICT)
    {
        $this->parser = $parser;
    }

    public function greaterThan(string $versionString1, string $versionString2): bool
    {
        if ($versionString1 == 'auto' && $versionString2 != 'auto') {
            return true;
        }

        if ($versionString2 == 'auto' && $versionString1 != 'auto') {
            return false;
        }

        if ($versionString1 == 'auto' && $versionString2 == 'auto') {
            return true;
        }

        $version1 = $this->parser->parse($versionString1);
        $version2 = $this->parser->parse($versionString2);

        if ($version1->getMajor() > $version2->getMajor()) {
            return true;
        }

        if (
            $version1->getMajor() == $version2->getMajor() &&
            $version1->getMinor() > $version2->getMinor()
        ) {
            return true;
        }

        if (
            $version1->getMajor() == $version2->getMajor() &&
            $version1->getMinor() == $version2->getMinor() &&
            $version1->getPatch() > $version2->getPatch()
        ) {
            return true;
        }

        if (
            $version1->getMajor() == $version2->getMajor() &&
            $version1->getMinor() == $version2->getMinor() &&
            $version1->getPatch() == $version2->getPatch() &&
            (new TagComparator())->greaterThan($version1->getTag(), $version2->getTag())
        ) {
            return true;
        }

        return false;
    }

    public function equalTo(string $versionString1, string $versionString2): bool
    {
        if ($versionString1 == 'auto' && $versionString2 == 'auto') {
            return true;
        } elseif ($versionString1 == 'auto' && $versionString2 != 'auto') {
            return false;
        } elseif ($versionString1 != 'auto' && $versionString2 == 'auto') {
            return false;
        }

        $version1 = $this->parser->parse($versionString1);
        $version2 = $this->parser->parse($versionString2);

        if ($version1->getMajor() !== $version2->getMajor()) {
            return false;
        }

        if ($version1->getMinor() !== $version2->getMinor()) {
            return false;
        }

        if ($version1->getPatch() !== $version2->getPatch()) {
            return false;
        }

        if ($version1->getTag() !== $version2->getTag()) {
            return false;
        }

        return true;
    }

    public function greaterThanOrEqualTo(string $versionString1, string $versionString2): bool
    {
        if ($this->greaterThan($versionString1, $versionString2)) {
            return true;
        }

        if ($this->equalTo($versionString1, $versionString2)) {
            return true;
        }

        return false;
    }

    public function lessThan(string $versionString1, string $versionString2): bool
    {
        if (!$this->greaterThanOrEqualTo($versionString1, $versionString2)) {
            return true;
        }

        return false;
    }

    public function lessThanOrEqualTo(string $versionString1, string $versionString2): bool
    {
        if (!$this->greaterThan($versionString1, $versionString2)) {
            return true;
        }

        return false;
    }

    public function notEqualTo(string $versionString1, string $versionString2): bool
    {
        if (!$this->equalTo($versionString1, $versionString2)) {
            return true;
        }

        return false;
    }

    // Testet ob Version1 mindestens das kann, was auch Version2 kann.
    // Version1 darf auch mehr können als das was Version2 kann,
    // aber nicht weniger.
    public function isCompatible(string $versionString1, string $versionString2): bool
    {
        if ($versionString1 == 'auto') {
            return true;
        }

        $version1 = $this->parser->parse($versionString1);
        $version2 = $this->parser->parse($versionString2);

        $majorCheck = $version1->getMajor() == $version2->getMajor();
        $minorCheck = $version1->getMinor() == $version2->getMinor();
        $patchCheck = $version1->getPatch() == $version2->getPatch();

        $strict = $this->mode === self::CARET_MODE_STRICT;

        if ($version1->getMajor() >= 1) { // ^1.0.0
            if (!$majorCheck) {
                return false;
            }
        } elseif ($strict && $version1->getMajor() == 0 && $version1->getMinor() >= 1) { // ^0.1.0
            if (!$majorCheck || !$minorCheck) {
                return false;
            }
        } elseif ($strict && $version1->getMajor() == 0 && $version1->getMinor() == 0) { // ^0.0.0
            if (!$majorCheck || !$minorCheck || !$patchCheck) {
                return false;
            }
        }

        return $this->greaterThanOrEqualTo($versionString1, $versionString2);
    }

    public function satisfies(string $versionString1, string $constraint): bool
    {
        if (strpos($constraint, '||')) {
            return $this->satisfiesOr($versionString1, $constraint);
        }

        if (strpos($constraint, ',')) {
            return $this->satisfiesAnd($versionString1, $constraint);
        }

        if (strpos($constraint, '<=') === 0) {
            $versionString2 = str_replace('<=', '', $constraint);
            return $this->lessThanOrEqualTo($versionString1, $versionString2);
        } elseif (strpos($constraint, '<') === 0) {
            $versionString2 = str_replace('<', '', $constraint);
            return $this->lessThan($versionString1, $versionString2);
        } elseif (strpos($constraint, '>=') === 0) {
            $versionString2 = str_replace('>=', '', $constraint);
            return $this->greaterThanOrEqualTo($versionString1, $versionString2);
        } elseif (strpos($constraint, '>') === 0) {
            $versionString2 = str_replace('>', '', $constraint);
            return $this->greaterThan($versionString1, $versionString2);
        } elseif (strpos($constraint, '^') === 0) {
            $versionString2 = str_replace('^', '', $constraint);
            return $this->isCompatible($versionString1, $versionString2);
        } else {
            $versionString2 = $constraint;
            return $this->equalTo($versionString1, $versionString2);
        }
    }

    /**
     * Can satisfy multiple constraints with OR (||)
     *
     * Example: ^7.4 || ^8.0
     */
    public function satisfiesOr(string $versionString1, string $constraintOrExpression): bool
    {
        $constraints = explode('||', $constraintOrExpression);
        foreach ($constraints as $constraint) {
            $constraint = trim($constraint);
            if ($this->satisfies($versionString1, $constraint)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Can satisfy multiple constraints with AND (,)
     *
     * Example: ^7.4, ^8.0
     */
    public function satisfiesAnd(string $versionString1, string $constraintOrExpression): bool
    {
        $constraints = explode(',', $constraintOrExpression);
        foreach ($constraints as $constraint) {
            $constraint = trim($constraint);
            if (!$this->satisfies($versionString1, $constraint)) {
                return false;
            }
        }
        return true;
    }
}
