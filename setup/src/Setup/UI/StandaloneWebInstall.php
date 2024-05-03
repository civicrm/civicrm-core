<?php

namespace Civi\Setup\UI;

class StandaloneWebInstall {

  public static function invoke() {
    \Civi\Setup::assertProtocolCompatibility(1.0);

    \Civi\Setup::init([
      // This is just enough information to get going.
      'cms'     => 'Standalone',
      'srcPath' => \Civi\Standalone\AppSettings::getPath('core'),
    ]);

    $baseUrl = \Civi\Standalone\AppSettings::getUrl('web_root');
    $setupUrl = \Civi\Standalone\AppSettings::getUrl('setup');
    $bowerUrl = \Civi\Standalone\AppSettings::getUrl('bower');

    $ctrl = \Civi\Setup::instance()->createController()->getCtrl();
    $ctrl->setUrls([
      // The URL of this setup controller. May be used for POST-backs
      'ctrl'             => $baseUrl . '/civicrm',
      // The base URL for loading resource files (images/javascripts) for this project. Includes trailing slash.
      'res'              => $setupUrl . '/res/',
      'jquery.js'        => $bowerUrl . '/jquery/dist/jquery.min.js',
      'font-awesome.css' => $bowerUrl . '/font-awesome/css/font-awesome.min.css',
    ]);
    \Civi\Setup\BasicRunner::run($ctrl);
    exit();
  }
}