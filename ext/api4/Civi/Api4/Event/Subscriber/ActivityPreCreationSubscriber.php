<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\DAOCreateAction;
use Civi\Api4\OptionValue;

class ActivityPreCreationSubscriber extends PreCreationSubscriber {
  /**
   * @param DAOCreateAction $request
   * @throws \API_Exception
   * @throws \Exception
   */
  protected function modify(DAOCreateAction $request) {
    $activityType = $request->getValue('activity_type');
    if ($activityType) {
      $result = OptionValue::get()
        ->setCheckPermissions(FALSE)
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
   * @param DAOCreateAction $request
   *
   * @return bool
   */
  protected function applies(DAOCreateAction $request) {
    return $request->getEntityName() === 'Activity';
  }

}
