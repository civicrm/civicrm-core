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
 * This trait is used to track a PHP-style field which is mapped ot a entity-style field.
 * Thus, it has both PHP's `@var` (`$type`) and entity's `@dataType` (`$dataType`).
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
trait PhpDataTypeSpecTrait {

  // DataTypeSpecTrait: dataType, serialize, fkEntity
  use DataTypeSpecTrait;

  /**
   * The PHP
   *
   * @var string[]
   */
  public $type;

  /**
   * @param string|string[] $type
   * @return $this
   * @throws \Exception
   */
  public function setType($type) {
    $type = (array) $type;
    if (property_exists(static::CLASS, 'required') && preg_grep('/null/i', $type)) {
      $this->required = FALSE;
    }
    $type = preg_grep('/null/i', $type, PREG_GREP_INVERT);
    // If there is one `@var` type, then attempt to infer the `dataType` and `serialize` type.
    if (count($type) === 1) {
      switch ($type[0]) {
        case 'string[]':
        case 'int[]':
        case 'bool[]':
        case 'array':
          $autoDataType = 'Blob';
          $autoSerialize = \CRM_Core_DAO::SERIALIZE_JSON;
          break;

        case 'string':
        case 'int':
          $autoDataType = ucfirst($type[0]);
          $autoSerialize = NULL;
          break;

        case 'bool':
          $autoDataType = 'Boolean';
          $autoSerialize = NULL;
          break;
      }

      if ($this->dataType === NULL) {
        $this->setDataType($autoDataType);
      }
      if ($this->serialize === NULL) {
        $this->setSerialize($autoSerialize);
      }
    }
    $this->type = $type;
    return $this;
  }

  /**
   * @return string[]
   */
  public function getType(): array {
    return $this->type;
  }

}
