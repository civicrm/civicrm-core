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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
require_once 'CRM/Core/Form.php';

/**
 * This class generates form components for adding an account if not already exists
 *
 */
class CRM_Auction_Form_ItemAccount extends CRM_Core_Form {

  /**
   * the id of the auction for which item needs to be updated/added.
   *
   * @var int
   * @protected
   */
  public $_aid = NULL;

  /**
   * the id of the item we are processing
   *
   * @var int
   * @protected
   */
  public $_id = NULL;

  public function preProcess() {
    $session       = CRM_Core_Session::singleton();
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->_aid    = CRM_Utils_Request::retrieve('aid', 'Positive', $this);

    if ($session->get('userID')) {
      $this->_donorID = $session->get('userID');
    }

    if (!$this->_aid) {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
      $this->_aid = CRM_Core_DAO::getFieldValue('CRM_Auction_DAO_Item', $this->_id, 'auction_id');
    }

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);
  }

  function setDefaultValues() {
    if (!$this->_donorID) {
      return;
    }

    foreach ($this->_fields as $name => $dontcare) {
      $fields[$name] = 1;
    }

    require_once "CRM/Core/BAO/UFGroup.php";
    CRM_Core_BAO_UFGroup::setProfileDefaults($this->_donorID, $fields, $this->_defaults);

    //set custom field defaults
    require_once "CRM/Core/BAO/CustomField.php";
    foreach ($this->_fields as $name => $field) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
        if (!isset($this->_defaults[$name])) {
          CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $this->_defaults,
            NULL, CRM_Profile_Form::MODE_REGISTER
          );
        }
      }
    }

    return $this->_defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $profileID = CRM_Core_DAO::getFieldValue('CRM_Auction_DAO_Auction',
      $this->_aid, 'donor_profile_id'
    );
    if (!$profileID) {
      CRM_Core_Error::fatal('Profile not configured for this auction.');
    }

    require_once 'CRM/Auction/BAO/Item.php';
    if (CRM_Auction_BAO_Item::isEmailInProfile($profileID)) {
      $this->assign('profileDisplay', TRUE);
    }

    $fields = NULL;
    require_once "CRM/Core/BAO/UFGroup.php";
    if ($this->_donorID) {
      if (CRM_Core_BAO_UFGroup::filterUFGroups($profileID, $this->_donorID)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($profileID, FALSE, CRM_Core_Action::ADD);
      }
      $this->addFormRule(array('CRM_Auction_Form_ItemAccount', 'formRule'), $this);
    }
    else {
      require_once 'CRM/Core/BAO/CMSUser.php';
      CRM_Core_BAO_CMSUser::buildForm($this, $profileID, TRUE);

      $fields = CRM_Core_BAO_UFGroup::getFields($profileID, FALSE, CRM_Core_Action::ADD);
    }

    if ($fields) {
      $this->assign('fields', $fields);
      $addCaptcha = FALSE;
      foreach ($fields as $key => $field) {
        if (isset($field['data_type']) && $field['data_type'] == 'File') {
          // ignore file upload fields
          continue;
        }
        require_once "CRM/Core/BAO/UFGroup.php";
        require_once "CRM/Profile/Form.php";
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE);
        $this->_fields[$key] = $field;
        if ($field['add_captcha']) {
          $addCaptcha = TRUE;
        }
      }

      if ($addCaptcha) {
        require_once 'CRM/Utils/ReCAPTCHA.php';
        $captcha = &CRM_Utils_ReCAPTCHA::singleton();
        $captcha->add($this);
        $this->assign("isCaptcha", TRUE);
      }
    }

    $button[] = array('type' => 'next',
      'name' => ts('Continue >>'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      'isDefault' => TRUE,
    );

    $this->addButtons($button);
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
  static
  function formRule($fields, $files, $self) {
    $errors = array();
    require_once "CRM/Utils/Rule.php";
    foreach ($fields as $key => $value) {
      if (strpos($key, 'email-') !== FALSE) {
        $ufContactId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFMatch', $value, 'contact_id', 'uf_name');
        if ($ufContactId && $ufContactId != $self->_donorID) {
          $errors[$key] = ts('There is already an user associated with this email address. Please enter different email address.');
        }
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
    $params = $this->controller->exportValues($this->getName());
    if (!$this->_donorID) {
      foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'email' && !empty($value)) {
          $params['email'] = $value;
        }
      }
    }
    $donorID = &CRM_Contact_BAO_Contact::createProfileContact($params, $this->_fields,
      $this->_donorID, $addToGroups
    );
    $this->set('donorID', $donorID);

    require_once "CRM/Contribute/BAO/Contribution/Utils.php";
    CRM_Contribute_BAO_Contribution_Utils::createCMSUser($params, $donorID, 'email');
  }
}

