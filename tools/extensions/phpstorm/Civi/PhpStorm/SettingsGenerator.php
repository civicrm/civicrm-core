<?php

namespace Civi\PhpStorm;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.settings
 */
class SettingsGenerator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return ['civi.phpstorm.flush' => 'generate'];
  }

  public function generate() {
    $metadata = \Civi\Core\SettingsMetadata::getMetadata();
    $methods = ['get', 'getDefault', 'getExplicit', 'getMandatory', 'hasExplicit', 'revert', 'set'];
    $builder = new PhpStormMetadata('settings', __CLASS__);
    $builder->registerArgumentsSet('settingNames', ...array_keys($metadata));
    foreach ($methods as $method) {
      $builder->addExpectedArguments('\Civi\Core\SettingsBag::' . $method . '()', 0, 'settingNames');
    }
    $builder->write();
  }

}
