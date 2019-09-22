<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\DAOCreateAction;
use Civi\Api4\OptionGroup;

class OptionValuePreCreationSubscriber extends Generic\PreCreationSubscriber {

  /**
   * @param \Civi\Api4\Generic\DAOCreateAction $request
   */
  protected function modify(DAOCreateAction $request) {
    $this->setOptionGroupId($request);
  }

  /**
   * @param \Civi\Api4\Generic\DAOCreateAction $request
   *
   * @return bool
   */
  protected function applies(DAOCreateAction $request) {
    return $request->getEntityName() === 'OptionValue';
  }

  /**
   * @param \Civi\Api4\Generic\DAOCreateAction $request
   * @throws \API_Exception
   * @throws \Exception
   */
  private function setOptionGroupId(DAOCreateAction $request) {
    $optionGroupName = $request->getValue('option_group');
    if (!$optionGroupName || $request->getValue('option_group_id')) {
      return;
    }

    $optionGroup = OptionGroup::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', $optionGroupName)
      ->execute();

    if ($optionGroup->count() !== 1) {
      throw new \Exception('Option group name must match only a single group');
    }

    $request->addValue('option_group_id', $optionGroup->first()['id']);
  }

}
