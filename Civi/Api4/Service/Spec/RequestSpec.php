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


namespace Civi\Api4\Service\Spec;

class RequestSpec {

  /**
   * @var string
   */
  protected $entity;

  /**
   * @var string
   */
  protected $action;

  /**
   * @var FieldSpec[]
   */
  protected $fields = [];

  /**
   * @param string $entity
   * @param string $action
   */
  public function __construct($entity, $action) {
    $this->entity = $entity;
    $this->action = $action;
  }

  public function addFieldSpec(FieldSpec $field) {
    $this->fields[] = $field;
  }

  /**
   * @param $name
   *
   * @return FieldSpec|null
   */
  public function getFieldByName($name) {
    foreach ($this->fields as $field) {
      if ($field->getName() === $name) {
        return $field;
      }
    }

    return NULL;
  }

  /**
   * @return array
   *   Gets all the field names currently part of the specification
   */
  public function getFieldNames() {
    return array_map(function(FieldSpec $field) {
      return $field->getName();
    }, $this->fields);
  }

  /**
   * @return array|FieldSpec[]
   */
  public function getRequiredFields() {
    return array_filter($this->fields, function (FieldSpec $field) {
      return $field->isRequired();
    });
  }

  /**
   * @return array|FieldSpec[]
   */
  public function getConditionalRequiredFields() {
    return array_filter($this->fields, function (FieldSpec $field) {
      return $field->getRequiredIf();
    });
  }

  /**
   * @param array $fieldNames
   *   Optional array of fields to return
   * @return FieldSpec[]
   */
  public function getFields($fieldNames = NULL) {
    if (!$fieldNames) {
      return $this->fields;
    }
    $fields = [];
    foreach ($this->fields as $field) {
      if (in_array($field->getName(), $fieldNames)) {
        $fields[] = $field;
      }
    }
    return $fields;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @return string
   */
  public function getAction() {
    return $this->action;
  }

}
