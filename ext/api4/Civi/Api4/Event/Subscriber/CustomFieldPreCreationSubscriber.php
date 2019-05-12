<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\DAOCreateAction;

class CustomFieldPreCreationSubscriber extends PreCreationSubscriber {

  const OPTION_TYPE_NEW = 1;
  const OPTION_STATUS_ACTIVE = 1;

  /**
   * @param DAOCreateAction $request
   */
  public function modify(DAOCreateAction $request) {
    $this->formatOptionParams($request);
    $this->setDefaults($request);
  }

  /**
   * @param DAOCreateAction $request
   *
   * @return bool
   */
  protected function applies(DAOCreateAction $request) {
    return $request->getEntityName() === 'CustomField';
  }

  /**
   * Sets defaults required for option group and value creation
   * @see CRM_Core_BAO_CustomField::create()
   *
   * @param DAOCreateAction $request
   */
  protected function formatOptionParams(DAOCreateAction $request) {
    $options = $request->getValue('options');

    if (!is_array($options)) {
      return;
    }

    $dataTypeKey = 'data_type';
    $optionLabelKey = 'option_label';
    $optionWeightKey = 'option_weight';
    $optionStatusKey = 'option_status';
    $optionValueKey = 'option_value';
    $optionTypeKey = 'option_type';

    $dataType = $request->getValue($dataTypeKey);
    $optionLabel = $request->getValue($optionLabelKey);
    $optionWeight = $request->getValue($optionWeightKey);
    $optionStatus = $request->getValue($optionStatusKey);
    $optionValue = $request->getValue($optionValueKey);
    $optionType = $request->getValue($optionTypeKey);

    if (!$optionType) {
      $request->addValue($optionTypeKey, self::OPTION_TYPE_NEW);
    }

    if (!$dataType) {
      $request->addValue($dataTypeKey, 'String');
    }

    if (!$optionLabel) {
      $request->addValue($optionLabelKey, array_values($options));
    }

    if (!$optionValue) {
      $request->addValue($optionValueKey, array_keys($options));
    }

    if (!$optionStatus) {
      $statuses = array_fill(0, count($options), self::OPTION_STATUS_ACTIVE);
      $request->addValue($optionStatusKey, $statuses);
    }

    if (!$optionWeight) {
      $request->addValue($optionWeightKey, range(1, count($options)));
    }
  }

  /**
   * @param DAOCreateAction $request
   */
  private function setDefaults(DAOCreateAction $request) {
    if (!$request->getValue('option_type')) {
      $request->addValue('option_type', NULL);
    }
  }

}
