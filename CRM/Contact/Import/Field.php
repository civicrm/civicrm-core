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

/**
 * Class CRM_Contact_Import_Field
 */
class CRM_Contact_Import_Field {

  /**
   * #@+
   * @var string
   */

  /**
   * Name of the field
   * @var string
   */
  public $_name;

  /**
   * Title of the field to be used in display
   * @var string
   */
  public $_title;

  /**
   * Type of field
   * @var enum
   */
  public $_type;

  /**
   * Is this field required
   * @var bool
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
  public $_columnPattern;

  /**
   * Regexp to match the pattern of data from various column/fields
   * @var string
   */
  public $_dataPattern;

  /**
   * Regexp to match the pattern of header from various column/fields
   * @var string
   */
  public $_headerPattern;

  /**
   * Location type
   * @var int
   */
  public $_hasLocationType;

  /**
   * Does this field have a phone type
   * @var string
   */
  public $_phoneType;

  /**
   * Value of this field
   * @var object
   */
  public $_value;

  /**
   * Does this field have a relationship info
   * @var string
   */
  public $_related;

  /**
   * Does this field have a relationship Contact Type
   * @var string
   */
  public $_relatedContactType;

  /**
   * Does this field have a relationship Contact Details
   * @var string
   */
  public $_relatedContactDetails;

  /**
   * Does this field have a related Contact info of Location Type
   * @var int
   */
  public $_relatedContactLocType;

  /**
   * Does this field have a related Contact info of Phone Type
   * @var string
   */
  public $_relatedContactPhoneType;

  /**
   * @param string $name
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
  public function __construct($name, $title, $type = CRM_Utils_Type::T_INT, $columnPattern = '//', $dataPattern = '//', $hasLocationType = NULL, $phoneType = NULL, $related = NULL, $relatedContactType = NULL, $relatedContactDetails = NULL, $relatedContactLocType = NULL, $relatedContactPhoneType = NULL) {
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

  public function resetValue() {
    $this->_value = NULL;
  }

  /**
   * The value is in string format.
   *
   * Convert the value to the type of this field
   * and set the field value with the appropriate type
   *
   * @param mixed $value
   */
  public function setValue($value) {
    $this->_value = $value;
  }

  /**
   * Validate something we didn't document.
   *
   * @return bool
   */
  public function validate() {
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
