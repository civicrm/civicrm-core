<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


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
