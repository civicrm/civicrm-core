<?php

namespace Civi\PhpStorm;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.api4
 */
class Api4Generator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.phpstorm.flush' => 'generate',
      'hook_civicrm_post::CustomGroup' => 'generate',
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

    $entities = \Civi\Api4\Entity::get(FALSE)->addSelect('name')->execute()->column('name');
    $actions = ['get', 'save', 'create', 'update', 'delete', 'replace', 'revert', 'export', 'autocomplete', 'getFields', 'getActions', 'checkAccess'];

    $builder = new PhpStormMetadata('api4', __CLASS__);
    $builder->registerArgumentsSet('api4Entities', ...$entities);
    $builder->registerArgumentsSet('api4Actions', ...$actions);
    $builder->addExpectedArguments('\civicrm_api4()', 0, 'api4Entities');
    $builder->addExpectedArguments('\civicrm_api4()', 1, 'api4Actions');
    $builder->write();
  }

}
