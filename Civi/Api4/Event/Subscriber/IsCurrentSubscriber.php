<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Event\PrepareEvent;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * @deprecated
 * @see \Civi\Api4\Generic\Traits\IsCurrentTrait
 * @service civi.api4.isCurrent
 */
class IsCurrentSubscriber extends Generic\AbstractPrepareSubscriber {

  public function onApiPrepare(PrepareEvent $event) {
    /** @var \Civi\Api4\Generic\AbstractQueryAction $action */
    $action = $event->getApiRequest();
    if ($action['version'] == 4 && method_exists($action, 'getCurrent')
      && in_array('Civi\Api4\Generic\Traits\IsCurrentTrait', ReflectionUtils::getTraits($action))
    ) {
      $fields = $action->entityFields();
      if ($action->getCurrent()) {
        if (isset($fields['is_active'])) {
          $action->addWhere('is_active', '=', '1');
        }
        $action->addClause('OR', ['start_date', 'IS NULL'], ['start_date', '<=', 'now']);
        $action->addClause('OR', ['end_date', 'IS NULL'], ['end_date', '>=', 'now']);
      }
      elseif ($action->getCurrent() === FALSE) {
        $conditions = [['end_date', '<', 'now'], ['start_date', '>', 'now']];
        if (isset($fields['is_active'])) {
          $conditions[] = ['is_active', '=', '0'];
        }
        $action->addClause('OR', $conditions);
      }
    }
  }

}
