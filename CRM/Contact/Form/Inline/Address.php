<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Form helper class for address section.
 */
class CRM_Contact_Form_Inline_Address extends CRM_Contact_Form_Inline {

  /**
   * Location block no
   */
  private $_locBlockNo;

  /**
   * Do we want to parse street address.
   */
  public $_parseStreetAddress;

  /**
   * Store address values
   */
  public $_values;

  /**
   * Form action
   */
  public $_action;

  /**
   * Address id
   */
  public $_addressId;

  /**
   * Class constructor.
   *
   * Since we are using same class / code to generate multiple instances
   * of address block, we need to generate unique form name for each,
   * hence calling parent constructor
   */
  public function __construct() {
    $locBlockNo = CRM_Utils_Request::retrieve('locno', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, $_REQUEST);
    $name = "Address_{$locBlockNo}";

    parent::__construct(NULL, CRM_Core_Action::NONE, 'post', $name);
  }

  /**
   * Call preprocess.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_locBlockNo = CRM_Utils_Request::retrieve('locno', 'Positive', $this, TRUE, NULL, $_REQUEST);
    $this->assign('blockId', $this->_locBlockNo);

    $addressSequence = CRM_Core_BAO_Address::addressSequence();
    $this->assign('addressSequence', $addressSequence);

    $this->_values = array();
    $this->_addressId = CRM_Utils_Request::retrieve('aid', 'Positive', $this, FALSE, NULL, $_REQUEST);

    $this->_action = CRM_Core_Action::ADD;
    if ($this->_addressId) {
      $params = array('id' => $this->_addressId);
      $address = CRM_Core_BAO_Address::getValues($params, FALSE, 'id');
      $this->_values['address'][$this->_locBlockNo] = array_pop($address);
      $this->_action = CRM_Core_Action::UPDATE;
    }
    else {
      $this->_addressId = 0;
    }

    $this->assign('action', $this->_action);
    $this->assign('addressId', $this->_addressId);

    // parse street address, CRM-5450
    $this->_parseStreetAddress = $this->get('parseStreetAddress');
    if (!isset($this->_parseStreetAddress)) {
      $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'address_options'
      );
      $this->_parseStreetAddress = FALSE;
      if (!empty($addressOptions['street_address']) && !empty($addressOptions['street_address_parsing'])) {
        $this->_parseStreetAddress = TRUE;
      }
      $this->set('parseStreetAddress', $this->_parseStreetAddress);
    }
    $this->assign('parseStreetAddress', $this->_parseStreetAddress);
  }

  /**
   * Build the form object elements for an address object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    CRM_Contact_Form_Edit_Address::buildQuickForm($this, $this->_locBlockNo, TRUE, TRUE);
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    $config = CRM_Core_Config::singleton();
    //set address block defaults
    if (!empty($defaults['address'])) {
      CRM_Contact_Form_Edit_Address::setDefaultValues($defaults, $this);
    }
    else {
      // get the default location type
      $locationType = CRM_Core_BAO_LocationType::getDefault();

      if ($this->_locBlockNo == 1) {
        $address['is_primary'] = TRUE;
        $address['location_type_id'] = $locationType->id;
      }

      $address['country_id'] = $config->defaultContactCountry;
      $address['state_province_id'] = $config->defaultContactStateProvince;
      $defaults['address'][$this->_locBlockNo] = $address;
    }

    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    // Process / save address
    $params['contact_id'] = $this->_contactId;
    $params['updateBlankLocInfo'] = TRUE;

    // process shared contact address.
    CRM_Contact_BAO_Contact_Utils::processSharedAddress($params['address']);

    if ($this->_parseStreetAddress) {
      CRM_Contact_Form_Contact::parseAddress($params);
    }

    if ($this->_addressId > 0) {
      $params['address'][$this->_locBlockNo]['id'] = $this->_addressId;
    }

    // save address changes
    $address = CRM_Core_BAO_Address::create($params, TRUE);

    $this->log();
    $this->ajaxResponse['addressId'] = $address[0]->id;
    $this->response();
  }

}
