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

namespace Civi\Api4\Service\Spec;

class CustomFieldSpec extends FieldSpec {
  /**
   * @var int
   */
  public $customFieldId;

  /**
   * @var int
   */
  public $customGroup;

  /**
   * @var string
   */
  public $type = 'Custom';

  /**
   * @inheritDoc
   */
  public function setDataType($dataType) {
    switch ($dataType) {
      case 'ContactReference':
        $this->setFkEntity('Contact');
        $dataType = 'Integer';
        break;

      case 'EntityReference':
        $dataType = 'Integer';
        break;

      case 'File':
      case 'StateProvince':
      case 'Country':
        $this->setFkEntity($dataType);
        $dataType = 'Integer';
        break;
    }
    return parent::setDataType($dataType);
  }

  /**
   * @return int
   */
  public function getCustomFieldId() {
    return $this->customFieldId;
  }

  /**
   * @param int $customFieldId
   *
   * @return $this
   */
  public function setCustomFieldId($customFieldId) {
    $this->customFieldId = $customFieldId;

    return $this;
  }

  /**
   * @return int
   */
  public function getCustomGroupName() {
    return $this->customGroup;
  }

  /**
   * @param string $customGroupName
   *
   * @return $this
   */
  public function setCustomGroupName($customGroupName) {
    $this->customGroup = $customGroupName;

    return $this;
  }

}
