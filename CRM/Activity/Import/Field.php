<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Activity_Import_Field {

  /**
   * @var string
   *   Name of the field
   */
  public $_name;

  /**
   * Title of the field to be used in display
   */
  public $_title;

  /**
   * Type of field
   * @var enum
   */
  public $_type;

  /**
   * Is this field required
   * @var boolean
   */
  public $_required;

  /**
   * Data to be carried for use by a derived class
   * @var object
   */
  public $_payload;

  /**
   * Regexp to match the CSV header of this column/field
   * @var string
   */
  public $_headerPattern;

  /**
   * Regexp to match the pattern of data from various column/fields
   * @var string
   */
  public $_dataPattern;

  /**
   * Value of this field
   * @var object
   */
  public $_value;

  /**
   * @param string $name
   * @param $title
   * @param int $type
   * @param string $headerPattern
   * @param string $dataPattern
   */
  public function __construct($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//') {
    $this->_name = $name;
    $this->_title = $title;
    $this->_type = $type;
    $this->_headerPattern = $headerPattern;
    $this->_dataPattern = $dataPattern;

    $this->_value = NULL;
  }

  public function resetValue() {
    $this->_value = NULL;
  }

  /**
   * The value is in string format. convert the value to the type of this field
   * and set the field value with the appropriate type
   * @param $value
   */
  public function setValue($value) {
    $this->_value = $value;
  }

  /**
   * @return bool
   */
  public function validate() {

    if (CRM_Utils_System::isNull($this->_value)) {
      return TRUE;
    }
    return TRUE;
  }

}
