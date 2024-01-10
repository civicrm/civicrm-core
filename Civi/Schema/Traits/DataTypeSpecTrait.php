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

namespace Civi\Schema\Traits;

/**
 * Describe what values are allowed to be stored in this field.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
trait DataTypeSpecTrait {

  /**
   * The type of data stored in this field.
   *
   * Ex: 'Integer', 'Boolean', 'Float', 'String', 'Text', 'Blob'
   *
   * @var string
   */
  public $dataType;

  /**
   * @var int
   * @see CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
   *   CRM_Core_DAO::SERIALIZE_JSON, etc
   */
  public $serialize;

  /**
   * @var string
   */
  public $fkEntity;

  /**
   * @var array
   */
  public $fkColumn;

  /**
   * @var string
   */
  public $dfkEntities;

  /**
   * Aliases for the valid data types
   *
   * @var array
   */
  public static $typeAliases = [
    'Int' => 'Integer',
    'Link' => 'Url',
    'Memo' => 'Text',
  ];

  /**
   * @return string
   */
  public function getDataType() {
    return $this->dataType;
  }

  /**
   * @param $dataType
   *
   * @return $this
   * @throws \CRM_Core_Exception
   */
  public function setDataType($dataType) {
    if (array_key_exists($dataType, self::$typeAliases)) {
      $dataType = self::$typeAliases[$dataType];
    }

    if (!in_array($dataType, $this->getValidDataTypes())) {
      throw new \CRM_Core_Exception(sprintf('Invalid data type "%s"', $dataType));
    }

    $this->dataType = $dataType;

    return $this;
  }

  /**
   * @return string
   */
  public function getFkEntity() {
    return $this->fkEntity;
  }

  /**
   * @param string $fkEntity
   *
   * @return $this
   */
  public function setFkEntity($fkEntity) {
    $this->fkEntity = $fkEntity;
    // If the field has a FK Entity, then FK Column also must be set.
    if ($fkEntity) {
      // Ensure a sensible default if not already set.
      $this->fkColumn ??= 'id';
    }
    return $this;
  }

  /**
   * @return string|null
   */
  public function getFkColumn(): ?string {
    return $this->fkColumn;
  }

  /**
   * @param string $fkColumn
   * @return $this
   */
  public function setFkColumn($fkColumn) {
    $this->fkColumn = $fkColumn;
    return $this;
  }

  /**
   * @return array|null
   */
  public function getDfkEntities(): ?array {
    return $this->dfkEntities;
  }

  /**
   * @param array|null $dfkEntities
   *
   * @return $this
   */
  public function setDfkEntities(?array $dfkEntities) {
    $this->dfkEntities = $dfkEntities;
    return $this;
  }

  /**
   * @return int
   */
  public function getSerialize() {
    return $this->serialize;
  }

  /**
   * @param int|string|null $serialize
   * @return $this
   */
  public function setSerialize($serialize) {
    if (is_string($serialize)) {
      $const = 'CRM_Core_DAO::SERIALIZE_' . $serialize;
      if (defined($const)) {
        $serialize = constant($const);
      }
    }
    $this->serialize = $serialize;
    return $this;
  }

  /**
   * Add valid types that are not not part of \CRM_Utils_Type::dataTypes
   *
   * @return array
   */
  protected function getValidDataTypes() {
    $extraTypes = [
      'Boolean',
      'Text',
      'Float',
      'Url',
      'Array',
      'Blob',
      'Mediumblob',
    ];
    $extraTypes = array_combine($extraTypes, $extraTypes);

    return array_merge(\CRM_Utils_Type::dataTypes(), $extraTypes);
  }

}
