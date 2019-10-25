<?php

/*
 * This file is part of Composer Extra Files Plugin.
 *
 * (c) 2017 Last Call Media, Rob Bayliss <rob@lastcallmedia.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LastCall\DownloadsPlugin;

use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;

/**
 * Class Subpackage
 * @package LastCall\DownloadsPlugin
 *
 * A subpackage is simulated package which lives beneath some parent package.
 */
class Subpackage extends Package
{

    /**
     * @var PackageInterface
     */
    private $parent;

    public function __construct(PackageInterface $parent, $id, $url, $type, $path, $version = NULL, $prettyVersion = NULL)
    {
        parent::__construct(
            sprintf('%s:%s', $parent->getName(), $id),
            $version ? $version : $parent->getVersion(),
            $prettyVersion ? $prettyVersion : $parent->getPrettyVersion()
        );
        $this->parent = $parent;
        $this->id = $id;
        $this->setDistUrl($url);
        $this->setDistType($type);
        $this->setTargetDir($path);
        $this->setInstallationSource('dist');
    }

}
