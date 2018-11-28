<?php

namespace Civi\Api4\Service\Spec;

class CustomFieldSpec extends FieldSpec {
  /**
   * @var int
   */
  protected $customFieldId;

  /**
   * @var int
   */
  protected $customGroup;

  /**
   * @var string
   */
  protected $tableName;

  /**
   * @var string
   */
  protected $columnName;

  /**
   * @inheritDoc
   */
  public function setDataType($dataType) {
    switch ($dataType) {
      case 'ContactReference':
        $this->setFkEntity('Contact');
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

  /**
   * @return string
   */
  public function getCustomTableName() {
    return $this->tableName;
  }

  /**
   * @param string $customFieldColumnName
   *
   * @return $this
   */
  public function setCustomTableName($customFieldColumnName) {
    $this->tableName = $customFieldColumnName;

    return $this;
  }

  /**
   * @return string
   */
  public function getCustomFieldColumnName() {
    return $this->columnName;
  }

  /**
   * @param string $customFieldColumnName
   *
   * @return $this
   */
  public function setCustomFieldColumnName($customFieldColumnName) {
    $this->columnName = $customFieldColumnName;

    return $this;
  }

}
