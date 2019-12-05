<?php

namespace LastCall\DownloadsPlugin\Handler;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Platform;


class PharHandler extends FileHandler
{

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function download(Composer $composer, IOInterface $io) {
        parent::download($composer, $io);

        if (Platform::isWindows()) {
            // TODO make .bat or .cmd
        } else {
            chmod($this->getTargetPath(), 0777 ^ umask());
        }
    }


}