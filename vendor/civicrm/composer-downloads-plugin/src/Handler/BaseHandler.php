<?php
/**
 * Created by PhpStorm.
 * User: totten
 * Date: 8/21/19
 * Time: 6:31 PM
 */

namespace LastCall\DownloadsPlugin\Handler;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use LastCall\DownloadsPlugin\Subpackage;


abstract class BaseHandler
{
    const FAKE_VERSION = 'dev-master';
    const DOT_DIR = '.composer-downloads';

    /**
     * @var array
     *   File specification from composer.json, with defaults/substitutions applied.
     */
    protected $extraFile;

    /**
     * @var PackageInterface
     */
    protected $parent;

    /**
     * @var string
     *   Path to the parent package.
     */
    protected $parentPath;

    /**
     * @var Subpackage
     */
    protected $subpackage;

    /**
     * BaseHandler constructor.
     * @param PackageInterface $parent
     * @param string $parentPath
     * @param array $extraFile
     */
    public function __construct(PackageInterface $parent, $parentPath, $extraFile)
    {
        $this->parent = $parent;
        $this->parentPath = $parentPath;
        $this->extraFile = $extraFile;
    }

    public function getSubpackage() {
        if ($this->subpackage === NULL) {
            $this->subpackage = $this->createSubpackage();
        }
        return $this->subpackage;
    }

    /**
     * @return Subpackage
     */
    public function createSubpackage()
    {
        $versionParser = new VersionParser();
        $extraFile = $this->extraFile;
        $parent = $this->parent;

        $package = new Subpackage(
            $parent,
            $extraFile['id'],
            $extraFile['url'],
            NULL,
            $extraFile['path'],
            $parent instanceof RootPackageInterface ? $versionParser->normalize(self::FAKE_VERSION) : $parent->getVersion(),
            $parent instanceof RootPackageInterface ? self::FAKE_VERSION : $parent->getPrettyVersion()
        );

        return $package;
    }

    public function createTrackingData() {
        return [
            'name' => $this->getSubpackage()->getName(),
            'url' => $this->getSubpackage()->getDistUrl(),
        ];
    }

    /**
     * @return string
     */
    public function getTargetPath()
    {
        return $this->parentPath . '/' . $this->extraFile['path'];
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    abstract public function download(Composer $composer, IOInterface $io);

    /**
     * @return string
     */
    abstract public function getTrackingFile();

}