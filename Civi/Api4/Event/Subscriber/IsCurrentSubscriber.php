<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Event\PrepareEvent;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Process $current api param for Get actions
 *
 * @see \Civi\Api4\Generic\Traits\IsCurrentTrait
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
