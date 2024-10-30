<?php

namespace Civi\PhpStorm;

use Civi\Api4\Entity;
use Civi\Api4\Route;
use Civi\Core\Service\AutoService;
use Civi\Test\Invasive;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.path
 */
class PathGenerator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.phpstorm.flush' => 'generate',
    ];
  }

  public function generate() {
    $pathVars = array_keys(Invasive::get([\Civi::paths(), 'variableFactory']));
    $pathVarExprs = [];
    foreach ($pathVars as $pathVar) {
      $pathVarExprs[] = "[$pathVar]/.";
    }

    $builder = new PhpStormMetadata('paths', __CLASS__);

    $builder->registerArgumentsSet('pathVars', ...$pathVars);
    $builder->addExpectedArguments('\Civi\Core\Paths::getPath()', 0, 'pathVarExprs');
    $builder->addExpectedArguments('\Civi\Core\Paths::getUrl()', 0, 'pathVarExprs');

    $builder->registerArgumentsSet('pathVarExprs', ...$pathVarExprs);
    $builder->registerArgumentsSet('pathVarAttrs', 'path', 'url');
    $builder->addExpectedArguments('\Civi\Core\Paths::hasVariable()', 0, 'pathVars');
    $builder->addExpectedArguments('\Civi\Core\Paths::getVariable()', 0, 'pathVars');
    $builder->addExpectedArguments('\Civi\Core\Paths::getVariable()', 1, 'pathVarAttrs');

    $builder->write();
  }

}
