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
