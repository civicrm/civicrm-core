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

/**
 * This class generates form components for processing a ontribution
 *
 */
class CRM_Grant_Form_GrantBase extends CRM_Core_Form {

  /**
   * the id of the grant application page that we are proceessing
   *
   * @var int
   * @public
   */
  public $_id;

  /**
   * the mode that we are in
   *
   * @var string
   * @protect
   */
  public $_mode;
  /**
   * the values for the grant db object
   *
   * @var array
   * @protected
   */
  public $_values;
  /**
   * the default values for the form
   *
   * @var array
   * @protected
   */
  protected $_defaults;

  /**
   * The params submitted by the form and computed by the app
   *
   * @var array
   * @public
   */
  public $_params;

  /**
   * The fields involved in this grant application page
   *
   * @var array
   * @public
   */
  public $_fields;

  /**
   * Cache the amount to make things easier
   *
   * @var float
   * @public
   */
  public $_amount;

  protected $_userID;

  public $_action;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();

    // current grant application page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this
    );
   
    if (!$this->_id) {
      $pastGrantID = $session->get('pastGrantID');
      if (!$pastGrantID) {
        CRM_Core_Error::fatal(ts('We can\'t load the requested web page due to an incomplete link. This can be caused by using your browser\'s Back button or by using an incomplete or invalid link.'));
      }
      else {
        CRM_Core_Error::fatal(ts('This grant application has already been submitted. Click <a href=\'%1\'>here</a> if you want to apply for another grant.', array(1 => CRM_Utils_System::url('civicrm/grant/transact', 'reset=1&id=' . $pastGrantID))));
      }
    }
    else {
      $session->set('pastGrantID', $this->_id);
    }

    $this->_userID = $session->get('userID');

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);
    // Grant Application page values are cleared from session, so can't use normal Printer Friendly view.
    // Use Browser Print instead.
    $this->assign('browserPrint', TRUE);

    // action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'add'
    );
    $this->assign('action', $this->_action);
  
    // current mode
    $this->_mode = ($this->_action == 1024) ? 'test' : 'live';

    $this->_values = $this->get('values');
    $this->_fields = $this->get('fields');
    $this->assign('title', $this->_values['title']);
    CRM_Utils_System::setTitle($this->_values['title']);
 if (!$this->_values) {
      // get all the values from the dao object
      $this->_values = array();
      $this->_fields = array();

      CRM_Grant_BAO_GrantApplicationPage::setValues($this->_id, $this->_values);
      $this->assign('title', $this->_values['title']);

      CRM_Utils_System::setTitle($this->_values['title']);
      // check if form is active
      if (!CRM_Utils_Array::value('is_active', $this->_values)) {
        // form is inactive, die a fatal death
        CRM_Core_Error::fatal(ts('The page you requested is currently unavailable.'));
      }
      
      if ($this->_values['custom_pre_id']) {
        $preProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_pre_id']);
      }

      if ($this->_values['custom_post_id']) {
        $postProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_post_id']);
      }

      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);
    }
      
    $this->assign('is_email_receipt', $this->_values['is_email_receipt']);

    //assign cancelSubscription URL to templates
    $this->assign('cancelSubscriptionUrl',
      CRM_Utils_Array::value('cancelSubscriptionUrl', $this->_values)
    );
  
    $this->_defaults = array();

    $this->_amount = $this->get('amount');

    //CRM-6907
    $config = CRM_Core_Config::singleton();
    $config->defaultCurrency = CRM_Utils_Array::value('currency',
      $this->_values,
      $config->defaultCurrency
    );
  }

  /**
   * set the default values
   *
   * @return void
   * @access public
   */
  function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * assign the minimal set of variables to the template
   *
   * @return void
   * @access public
   */
  function assignToTemplate() {
      $vars = array(
      'default_amount_hidden'
    );

    $config = CRM_Core_Config::singleton();
 
    if (CRM_Utils_Array::value('default_amount_hidden', $this->_params)) {
        $this->assign('default_amount_hidden', $this->_params['default_amount_hidden']);
    }

    // assign the address formatted up for display
    $addressParts = array(
      "street_address-Primary",
      "city-Primary",
      "postal_code-Primary",
      "state_province-Primary",
      "country-Primary",
    );

    $addressFields = array();
    foreach ($addressParts as $part) {
      list($n, $id) = explode('-', $part);
      $addressFields[$n] = CRM_Utils_Array::value('billing_' . $part, $this->_params);
    }

    $this->assign('address', CRM_Utils_Address::format($addressFields));
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $this->_params)) {
      $this->assign('onBehalfName', $this->_params['organization_name']);
      $locTypeId = array_keys($this->_params['onbehalf_location']['email']);
      $this->assign('onBehalfEmail', $this->_params['onbehalf_location']['email'][$locTypeId[0]]['email']);
    }
    $this->assign('email',
      $this->controller->exportValue('Main', "email-Primary")
    );

    // also assign the receipt_text
    if (isset($this->_values['receipt_text'])) {
      $this->assign('receipt_text', $this->_values['receipt_text']);
    }
  }

  /**
   * Function to add the custom fields
   *
   * @return None
   * @access public
   */
  function buildCustom($id, $name, $viewOnly = FALSE, $onBehalf = FALSE, $fieldTypes = NULL) {
    $stateCountryMap = array();

    if ($id) {
      $session = CRM_Core_Session::singleton();
      $contactID = $this->_userID;

      // we don't allow conflicting fields to be
      // configured via profile - CRM 2100
      $fieldsToIgnore = array(
        'amount_granted' => 1,
        'application_received_date' => 1,
        'decision_date' => 1,
        'grant_money_transfer_date' => 1,
        'grant_due_date' => 1,
        'grant_report_received' => 1,
        'grant_type_id' => 1,
        'currency' => 1,
        'rationale' => 1,
        'status_id' => 1
      );

      $fields = NULL;
      if ($contactID && CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
          NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
        );
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
          NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
        );
      }

      if ($fields) {
        // unset any email-* fields since we already collect it, CRM-2888
        foreach (array_keys($fields) as $fieldName) {
          if (substr($fieldName, 0, 6) == 'email-') {
            unset($fields[$fieldName]);
          }
        }

        if (array_intersect_key($fields, $fieldsToIgnore)) {
          $fields = array_diff_key($fields, $fieldsToIgnore);
          CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'));
        }

        $fields = array_diff_assoc($fields, $this->_fields);

        CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
        $addCaptcha = FALSE;
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            // ignore file upload fields
            continue;
          }

          list($prefixName, $index) = CRM_Utils_System::explode('-', $key, 2);
          if ($prefixName == 'state_province' || $prefixName == 'country' || $prefixName == 'county') {
            if (!array_key_exists($index, $stateCountryMap)) {
              $stateCountryMap[$index] = array();
            }
            $stateCountryMap[$index][$prefixName] = $key;
          }

          if ($onBehalf) {
            if (!empty($fieldTypes) && in_array($field['field_type'], $fieldTypes)) {
              CRM_Core_BAO_UFGroup::buildProfile(
                $this,
                $field,
                CRM_Profile_Form::MODE_CREATE,
                $contactID,
                TRUE
              );
              $this->_fields['onbehalf'][$key] = $field;
            }
            else {
              unset($fields[$key]);
            }
          }
          else {
            CRM_Core_BAO_UFGroup::buildProfile(
              $this,
              $field,
              CRM_Profile_Form::MODE_CREATE,
              $contactID,
              TRUE
            );
            $this->_fields[$key] = $field;
          }
          // CRM-11316 Is ReCAPTCHA enabled for this profile AND is this an anonymous visitor
          if ($field['add_captcha'] && !$this->_userID) {
            $addCaptcha = TRUE;
          }
        }

        $this->assign($name, $fields);

        CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);

        if ($addCaptcha &&
          !$viewOnly
        ) {
          $captcha = CRM_Utils_ReCAPTCHA::singleton();
          $captcha->add($this);
          $this->assign('isCaptcha', TRUE);
        }
      }
    }
  }

  function checkTemplateFileExists($suffix = NULL) {
    if ($this->_id) {
      $templateFile = "CRM/Grant/Form/Grant/{$this->_id}/{$this->_name}.{$suffix}tpl";
      $template = CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return NULL;
  }

  function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }
}

