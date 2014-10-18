<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * Class CRM_Contact_Import_Field
 */
class CRM_Contact_Import_Field {

  /**#@+
   * @access protected
   * @var string
   */

  /**
   * name of the field
   */
  public $_name;

  /**
   * title of the field to be used in display
   */
  public $_title;

  /**
   * type of field
   * @var enum
   */
  public $_type;

  /**
   * is this field required
   * @var boolean
   */
  public $_required;

  /**
   * data to be carried for use by a derived class
   * @var object
   */
  public $_payload;

  /**
   * regexp to match the CSV header of this column/field
   * @var string
   */
  public $_columnPattern;

  /**
   * regexp to match the pattern of data from various column/fields
   * @var string
   */
  public $_dataPattern;

  /**
   * regexp to match the pattern of header from various column/fields
   * @var string
   */
  public $_headerPattern;

  /**
   * location type
   * @var int
   */
  public $_hasLocationType;

  /**
   * does this field have a phone type
   * @var string
   */
  public $_phoneType;

  /**
   * value of this field
   * @var object
   */
  public $_value;

  /**
   * does this field have a relationship info
   * @var string
   */
  public $_related;

  /**
   * does this field have a relationship Contact Type
   * @var string
   */
  public $_relatedContactType;

  /**
   * does this field have a relationship Contact Details
   * @var string
   */
  public $_relatedContactDetails;

  /**
   * does this field have a related Contact info of Location Type
   * @var int
   */
  public $_relatedContactLocType;

  /**
   * does this field have a related Contact info of Phone Type
   * @var string
   */
  public $_relatedContactPhoneType;

  /**
   * @param $name
   * @param $title
   * @param int $type
   * @param string $columnPattern
   * @param string $dataPattern
   * @param null $hasLocationType
   * @param null $phoneType
   * @param null $related
   * @param null $relatedContactType
   * @param null $relatedContactDetails
   * @param null $relatedContactLocType
   * @param null $relatedContactPhoneType
   */
  function __construct($name, $title, $type = CRM_Utils_Type::T_INT, $columnPattern = '//', $dataPattern = '//', $hasLocationType = NULL, $phoneType = NULL, $related = NULL, $relatedContactType = NULL, $relatedContactDetails = NULL, $relatedContactLocType = NULL, $relatedContactPhoneType = NULL) {
    $this->_name = $name;
    $this->_title = $title;
    $this->_type = $type;
    $this->_columnPattern = $columnPattern;
    $this->_dataPattern = $dataPattern;
    $this->_hasLocationType = $hasLocationType;
    $this->_phoneType = $phoneType;
    $this->_related = $related;
    $this->_relatedContactType = $relatedContactType;
    $this->_relatedContactDetails = $relatedContactDetails;
    $this->_relatedContactLocType = $relatedContactLocType;
    $this->_relatedContactPhoneType = $relatedContactPhoneType;

    $this->_value = NULL;
  }

  function resetValue() {
    $this->_value = NULL;
  }

  /**
   * the value is in string format. convert the value to the type of this field
   * and set the field value with the appropriate type
   */
  function setValue($value) {
    $this->_value = $value;
  }

  /**
   * @return bool
   */
  function validate() {
    //  echo $this->_value."===========<br>";
    $message = '';

    if ($this->_value === NULL) {
      return TRUE;
    }

    //     Commented due to bug CRM-150, internationalization/wew.
    //         if ( $this->_name == 'phone' ) {
    //            return CRM_Utils_Rule::phone( $this->_value );
    //         }

    if ($this->_name == 'email') {
      return CRM_Utils_Rule::email($this->_value);
    }
  }
}

