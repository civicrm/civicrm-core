<?php

namespace Civi\PhpStorm;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generate simple/static mappings.
 *
 * This could be committed directly, but generating via PhpStormMetadata means that we get the same
 * install/upgrade/uninstall workflow as the others. (No special carve-outs per-file.)
 *
 * @service civi.phpstorm.static
 */
class StaticGenerator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return ['civi.phpstorm.flush' => 'generate'];
  }

  public function generate() {
    $builder = new PhpStormMetadata('static', __CLASS__);
    $builder->addExitPoint('\CRM_Utils_System::civiExit()');
    $builder->write();
  }

}
