<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This class generates form components for processing a grant application
 *
 */
class CRM_Grant_Form_Grant_Main extends CRM_Grant_Form_GrantBase {

  protected $_defaults;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();
 

    // Make the contributionPageID avilable to the template
    $this->assign('contributionPageID', $this->_id);
   
    $this->assign('isConfirmEnabled', 1) ;

    // make sure we have right permission to edit this user
    $csContactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, $this->_userID);
    $reset       = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $mainDisplay = CRM_Utils_Request::retrieve('_qf_Main_display', 'Boolean', CRM_Core_DAO::$_nullObject);
   
    if ($csContactID != $this->_userID) {
      if (CRM_Contact_BAO_Contact_Permission::validateChecksumContact($csContactID, $this)) {
        $session = CRM_Core_Session::singleton();
        $session->set('userID', $csContactID);
        $this->_userID = $csContactID;
      }
    }

    if ($reset) {
      $this->assign('reset', $reset);
    }

    if ($mainDisplay) {
      $this->assign('mainDisplay', $mainDisplay);
    }

    if (CRM_Utils_Array::value('intro_text', $this->_values)) {
      $this->assign('intro_text', $this->_values['intro_text']);
    }

    $qParams = "reset=1&amp;id={$this->_id}";
    
    $this->assign('qParams' , $qParams);

    if (CRM_Utils_Array::value('footer_text', $this->_values)) {
      $this->assign('footer_text', $this->_values['footer_text']);
    }

  }

  function setDefaultValues() {
    // process defaults only once
    if (!empty($this->_defaults)) {
 
    }
 
    // check if the user is registered and we have a contact ID
    $session = CRM_Core_Session::singleton();
   
    $contactID = $this->_userID;

    if ($contactID) {
      $options = array();
      $fields = array();
      $removeCustomFieldTypes = array('Contribution', 'Membership', 'Activity', 'Participant', 'Grant');
      $grantFields = CRM_Grant_BAO_Grant::getGrantFields(FALSE);
     
      // remove component related fields
      foreach ($this->_fields as $name => $dontCare) {
        //don't set custom data Used for Grants (CRM-1344)
        if (substr($name, 0, 7) == 'custom_') {
          $id = substr($name, 7);
          if (!CRM_Core_BAO_CustomGroup::checkCustomField($id, $removeCustomFieldTypes)) {
            continue;
          }
          // ignore component fields
        }
        elseif ( array_key_exists($name, $grantFields) || (stristr($name, 'amount_requested') )) {
          continue;
        }
        $fields[$name] = 1;
      }

      $names = array(
                     'first_name', 'middle_name', 'last_name', "street_address-{$this->_bltID}", "city-{$this->_bltID}",
                     "postal_code-{$this->_bltID}", "country_id-{$this->_bltID}", "state_province_id-{$this->_bltID}",
                     );
      foreach ($names as $name) {
        $fields[$name] = 1;
      }
      $fields["state_province-{$this->_bltID}"] = 1;
      $fields["country-{$this->_bltID}"] = 1;
      $fields["email-{$this->_bltID}"] = 1;
      $fields['email-Primary'] = 1;
     
      CRM_Core_BAO_UFGroup::setProfileDefaults($contactID, $fields, $this->_defaults);

      // use primary email address if billing email address is empty
      if (empty($this->_defaults["email-{$this->_bltID}"]) &&
          !empty($this->_defaults['email-Primary'])
          ) {
        $this->_defaults["email-{$this->_bltID}"] = $this->_defaults['email-Primary'];
      }
    }

    //set custom field defaults set by admin if value is not set
    if (!empty($this->_fields)) {
      //set custom field defaults
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          if (!isset($this->_defaults[$name])) {
            CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $this->_defaults,
              NULL, CRM_Profile_Form::MODE_REGISTER
            );
          }
        }
      }
    }
   
    // to process Custom data that are appended to URL
    $getDefaults = CRM_Core_BAO_CustomGroup::extractGetParams($this, "'Contact', 'Individual', 'Grant'");
    if (!empty($getDefaults)) {
      $this->_defaults = array_merge($this->_defaults, $getDefaults);
    }

    $config = CRM_Core_Config::singleton();
    // set default country from config if no country set
 

    // now fix all state country selectors
    CRM_Core_BAO_Address::fixAllStateSelects($this, $this->_defaults);

    return $this->_defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
   
    $config = CRM_Core_Config::singleton();
 
    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', "email-{$this->_bltID}",
               ts('Email Address'), array(
                                          'size' => 30, 'maxlength' => 60), TRUE
               );
 
    $this->addRule("email-{$this->_bltID}", ts('Email is not valid.'), 'email');
 
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre');
    $this->buildCustom($this->_values['custom_post_id'], 'customPost');
    
    if (!CRM_Utils_Array::value('amount_requested', $this->_fields) && CRM_Utils_Array::value('default_amount', $this->_values)) {
      $this->assign('defaultAmount', $this->_values['default_amount']);
      $this->add('hidden', "default_amount_hidden",
                 $this->_values['default_amount'] ? $this->_values['default_amount'] : '0', '', FALSE
                 );
    } 
    else if (!CRM_Utils_Array::value('default_amount', $this->_fields) && !CRM_Utils_Array::value('amount_requested', $this->_fields)) {
      $this->assign('defaultAmount', '0.00');
      $this->add('hidden', "default_amount_hidden",
                 '0.00', '', FALSE
                 );
    }
    if (CRM_Utils_Array::value('amount_requested', $this->_fields)) {
      $this->addRule('amount_requested', ts('Please enter a valid amount (numbers and decimal point only).'), 'money');
    }

    if (!empty($this->_fields)) {
      $profileAddressFields = array();
      foreach ($this->_fields as $key => $value) {
        CRM_Core_BAO_UFField::assignAddressField($key, $profileAddressFields);
        $this->set('profileAddressFields', $profileAddressFields);
      }
    }

    //to create an cms user
    if (!$this->_userID) {
      $createCMSUser = FALSE;

      if ($this->_values['custom_pre_id']) {
        $profileID = $this->_values['custom_pre_id'];
        $createCMSUser = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'is_cms_user');
      }

      if (!$createCMSUser &&
          $this->_values['custom_post_id']
          ) {
        if (!is_array($this->_values['custom_post_id'])) {
          $profileIDs = array($this->_values['custom_post_id']);
        }
        else {
          $profileIDs = $this->_values['custom_post_id'];
        }
        foreach ($profileIDs as $pid) {
          if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $pid, 'is_cms_user')) {
            $profileID = $pid;
            $createCMSUser = TRUE;
            break;
          }
        }
      }

      if ($createCMSUser) {
        CRM_Core_BAO_CMSUser::buildForm($this, $profileID, TRUE);
      }
    }
    $this->addButtons(array(
      array(
        'type' => 'upload',
        'name' => ts('Confirm Grant Application'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ),
     )
    );
    $this->addFormRule(array('CRM_Grant_Form_Grant_Main', 'formRule'), $this);
  }
  
  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
  
    if (array_key_exists('grant_amount_requested', $fields)) {
      if (!CRM_Utils_Array::value('grant_amount_requested', $fields) ||  CRM_Utils_Array::value('grant_amount_requested', $fields) < 0) {
        $errors['grant_amount_requested'] = ts('Requested amount has to be greater than zero.');
      }
    }
    $config = CRM_Core_Config::singleton();

    foreach ($self->_fields as $name => $fld) {
      if ($fld['is_required'] &&
          CRM_Utils_System::isNull(CRM_Utils_Array::value($name, $fields))
          ) {
        $errors[$name] = ts('%1 is a required field.', array(1 => $fld['title']));
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $config = CRM_Core_Config::singleton();

    // we first reset the confirm page so it accepts new values
    $this->controller->resetPage('Confirm');

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
   
    if (CRM_Utils_Array::value('default_amount_hidden', $params) > 0 && !CRM_Utils_Array::value('amount_requested', $params)) {  
      $this->set('default_amount', $params['default_amount_hidden']);
    } 
    elseif (CRM_Utils_Array::value('amount_requested', $params))  {
      $this->set('default_amount', $params['amount_requested']);
    }
  }
}

