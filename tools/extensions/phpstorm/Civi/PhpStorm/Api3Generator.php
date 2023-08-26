<?php

namespace Civi\PhpStorm;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.api3
 */
class Api3Generator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.phpstorm.flush' => 'generate',
    ];
  }

  public function generate() {
    /*
     * FIXME: PHPSTORM_META doesn't seem to support compound dynamic arguments
     * so even if you give it separate lists like
     * ```
     * expectedArguments(\civicrm_api4('Contact'), 1, 'a', 'b');
     * expectedArguments(\civicrm_api4('Case'), 1, 'c', 'd');
     * ```
     * It doesn't differentiate them and always offers a,b,c,d for every entity.
     * If they ever fix that upstream we could fetch a different list of actions per entity,
     * but for now there's no point.
     */

    $entities = \civicrm_api3('entity', 'get', []);
    $actions = ['create', 'delete', 'get', 'getactions', 'getcount', 'getfield', 'getfields', 'getlist', 'getoptions', 'getrefcount', 'getsingle', 'getunique', 'getvalue', 'replace', 'validate'];

    $builder = new PhpStormMetadata('api3', __CLASS__);
    $builder->registerArgumentsSet('api3Entities', ...$entities['values']);
    $builder->registerArgumentsSet('api3Actions', ...$actions);
    $builder->addExpectedArguments('\civicrm_api3()', 0, 'api3Entities');
    $builder->addExpectedArguments('\civicrm_api3()', 1, 'api3Actions');
    $builder->write();
  }

}
