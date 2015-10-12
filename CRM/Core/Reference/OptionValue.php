<?php

/**
 * Description of a one-way link between an option-value and an entity
 */
class CRM_Core_Reference_OptionValue extends CRM_Core_Reference_Basic {
  /**
   * @var string option-group-name
   */
  protected $targetOptionGroupName;

  /**
   * @var int|NULL null if not yet loaded
   */
  protected $targetOptionGroupId;

  /**
   * @param $refTable
   * @param $refKey
   * @param null $targetTable
   * @param string $targetKey
   * @param null $optionGroupName
   */
  public function __construct($refTable, $refKey, $targetTable = NULL, $targetKey = 'id', $optionGroupName) {
    parent::__construct($refTable, $refKey, $targetTable, $targetKey, NULL);
    $this->targetOptionGroupName = $optionGroupName;
  }

  /**
   * @param CRM_Core_DAO $targetDao
   *
   * @return null|Object
   * @throws CRM_Core_Exception
   */
  public function findReferences($targetDao) {
    if (!($targetDao instanceof CRM_Core_DAO_OptionValue)) {
      throw new CRM_Core_Exception("Mismatched reference: expected OptionValue but received " . get_class($targetDao));
    }
    if ($targetDao->option_group_id == $this->getTargetOptionGroupId()) {
      return parent::findReferences($targetDao);
    }
    else {
      return NULL;
    }
  }

  /**
   * @param CRM_Core_DAO $targetDao
   *
   * @return array|null
   * @throws CRM_Core_Exception
   */
  public function getReferenceCount($targetDao) {
    if (!($targetDao instanceof CRM_Core_DAO_OptionValue)) {
      throw new CRM_Core_Exception("Mismatched reference: expected OptionValue but received " . get_class($targetDao));
    }
    if ($targetDao->option_group_id == $this->getTargetOptionGroupId()) {
      return parent::getReferenceCount($targetDao);
    }
    else {
      return NULL;
    }
  }

  /**
   * @return int|NULL
   */
  public function getTargetOptionGroupId() {
    if ($this->targetOptionGroupId === NULL) {
      $this->targetOptionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->targetOptionGroupName, 'id', 'name');
    }
    return $this->targetOptionGroupId;
  }

}
