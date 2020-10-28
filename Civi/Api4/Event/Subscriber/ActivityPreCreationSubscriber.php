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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\DAOCreateAction;
use Civi\Api4\OptionValue;

class ActivityPreCreationSubscriber extends Generic\PreCreationSubscriber {

  /**
   * @param \Civi\Api4\Generic\DAOCreateAction $request
   * @throws \API_Exception
   * @throws \Exception
   */
  protected function modify(DAOCreateAction $request) {
    $activityType = $request->getValue('activity_type');
    if ($activityType) {
      \CRM_Core_Error::deprecatedFunctionWarning('Use activity_type_id:name instead of activity_type in APIv4');
      $result = OptionValue::get(FALSE)
        ->addWhere('name', '=', $activityType)
        ->addWhere('option_group.name', '=', 'activity_type')
        ->execute();

      if ($result->count() !== 1) {
        throw new \Exception('Activity type must match a *single* type');
      }

      $request->addValue('activity_type_id', $result->first()['value']);
    }
  }

  /**
   * @param \Civi\Api4\Generic\DAOCreateAction $request
   *
   * @return bool
   */
  protected function applies(DAOCreateAction $request) {
    return $request->getEntityName() === 'Activity';
  }

}
