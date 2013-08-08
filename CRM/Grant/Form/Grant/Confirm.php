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
 * form to process actions on the group aspect of Custom Data
 */
class CRM_Grant_Form_Grant_Confirm extends CRM_Grant_Form_GrantBase {

  /**
   * the id of the contact associated with this grant application
   *
   * @var int
   * @public
   */
  public $_contactID;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
   
    parent::preProcess();

    // lineItem isn't set until Register postProcess
    $this->_lineItem = $this->get('lineItem');
    $this->_paymentProcessor = $this->get('paymentProcessor');

    $this->_params['amount'] = $this->get('default_amount_hidden');

    // we use this here to incorporate any changes made by folks in hooks
    $this->_params['currencyID'] = $config->defaultCurrency;
    $this->_params = $this->controller->exportValues('Main');
    

    $this->_params['ip_address'] = $_SERVER['REMOTE_ADDR'];
    // hack for safari
    if ($this->_params['ip_address'] == '::1') {
      $this->_params['ip_address'] = '127.0.0.1';
    }
    $this->_params['amount'] = $this->get('default_amount');

    $this->_useForMember = $this->get('useForMember');
      
    if (isset($this->_params['amount'])) {
      $this->_params['currencyID'] = $config->defaultCurrency;
    }

    $this->set('params', $this->_params);
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->assignToTemplate();

    $params = $this->_params;
       
    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));

    $config = CRM_Core_Config::singleton();
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);
   
    $grantButton = ts('Continue >>');
    $this->assign('button', ts('Continue'));
    
    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => $grantButton,
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
        'js' => array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');"),
      ),
      array(
        'type' => 'back',
        'name' => ts('Go Back'),
      ),
     )
    );
    $defaults = array();
    $options = array();
    $fields = array();
    $removeCustomFieldTypes = array('Grant');
    foreach ($this->_fields as $name => $dontCare) {
      $fields[$name] = 1;
    }

    $contact = $this->_params;
    foreach ($fields as $name => $dontCare) {
      if (isset($contact[$name])) {
        $defaults[$name] = $contact[$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
          if (isset($contact["{$name}_id"])) {
            $defaults["{$name}_id"] = $contact["{$name}_id"];
          }
        }
        elseif (in_array($name, array('addressee', 'email_greeting', 'postal_greeting'))
                && CRM_Utils_Array::value($name . '_custom', $contact)
                ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
      }
    }
    // now fix all state country selectors
    CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults);

    $this->setDefaults($defaults);

    $this->freeze();
  }

  /**
   * overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   * @access public
   */
  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {}

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $config = CRM_Core_Config::singleton();
    $contactID = $this->_userID;

    // add a description field at the very beginning
    $this->_params['description'] = ts('Online Grant Application') . ':' .$this->_values['title'];

    // fix currency ID
    $this->_params['currencyID'] = $config->defaultCurrency;

    $premiumParams = $membershipParams = $tempParams = $params = $this->_params;

    //carry payment processor id.

    $now = date('YmdHis');
    $fields = array();

    if (CRM_Utils_Array::value('image_URL', $params)) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    // set email for primary location.
    $fields['email-Primary'] = 1;

    // don't create primary email address, just add it to billing location
    //$params["email-Primary"] = $params["email-{$this->_bltID}"];

    // get the add to groups
    $addToGroups = array();

    // now set the values for the billing location.
    foreach ($this->_fields as $name => $value) {
      $fields[$name] = 1;

      // get the add to groups for uf fields
      if (CRM_Utils_Array::value('add_to_group_id', $value)) {
        $addToGroups[$value['add_to_group_id']] = $value['add_to_group_id'];
      }
    }

    if (!array_key_exists('first_name', $fields)) {
      $nameFields = array('first_name', 'middle_name', 'last_name');
      foreach ($nameFields as $name) {
        $fields[$name] = 1;
      }
    }

    // billing email address
    $fields["email-{$this->_bltID}"] = 1;

    // check for profile double opt-in and get groups to be subscribed
    $subscribeGroupIds = CRM_Core_BAO_UFGroup::getDoubleOptInGroupIds($params, $contactID);

    // since we are directly adding contact to group lets unset it from mailing
    if (!empty($addToGroups)) {
      foreach ($addToGroups as $groupId) {
        if (isset($subscribeGroupIds[$groupId])) {
          unset($subscribeGroupIds[$groupId]);
        }
      }
    }


    foreach ($addToGroups as $k) {
      if (array_key_exists($k, $subscribeGroupIds)) {
        unset($addToGroups[$k]);
      }
    }

    if (!isset($contactID)) {
      $dupeParams = $params;

      $dedupeParams = CRM_Dedupe_Finder::formatParams($dupeParams, 'Individual');
      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');

      // if we find more than one contact, use the first one
      $contact_id = CRM_Utils_Array::value(0, $ids);

      // Fetch default greeting id's if creating a contact
      if (!$contact_id) {
        foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
          if (!isset($params[$greeting])) {
            $params[$greeting] = CRM_Contact_BAO_Contact_Utils::defaultGreeting('Individual', $greeting);
          }
        }
      }

      $contactID = &CRM_Contact_BAO_Contact::createProfileContact($params,
        $fields,
        $contact_id,
        $addToGroups,
        NULL,
        NULL,
        TRUE
      );
    }
    else {
      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type');
      $contactID = &CRM_Contact_BAO_Contact::createProfileContact($params,
        $fields,
        $contactID,
        $addToGroups,
        NULL,
        $ctype,
        TRUE
      );
    }

    // Make the contact ID associated with the grant application available at the Class level.
    // Also make available to the session.
    $this->set('contactID', $contactID);
    $this->_contactID = $contactID;

    //get email primary first if exist
    $subscribtionEmail = array('email' => CRM_Utils_Array::value('email-Primary', $params));
    if (!$subscribtionEmail['email']) {
      $subscribtionEmail['email'] = CRM_Utils_Array::value("email-{$this->_bltID}", $params);
    }
    // subscribing contact to groups
    if (!empty($subscribeGroupIds) && $subscribtionEmail['email']) {
      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($subscribeGroupIds, $subscribtionEmail, $contactID);
    }

    // lets store the contactID in the session
    // for things like tell a friend
    $session = CRM_Core_Session::singleton();
    if (!$session->get('userID')) {
      $session->set('transaction.userID', $contactID);
    }
    else {
      $session->set('transaction.userID', NULL);
    }
    // at this point we've created a contact and stored its address etc
    // all the payment processors expect the name and address to be in the
    // so we copy stuff over to first_name etc.
    $paymentParams = $this->_params;
     
    $grantTypeId = $this->_values['grant_type_id'];
     
    $fieldTypes = array();
        
    CRM_Grant_BAO_Grant_Utils::processConfirm($this, $paymentParams,
      $premiumParams, 
      $contactID,
      $grantTypeId,
      'grant',
      $fieldTypes
    );
     
    
    
  }

  /**
   * Process the grant application
   *
   * @return void
   * @access public
   */
  static function processContribution(&$form,
    $params,
    $result,
    $contactID,
    $grantTypeId,
    $deductibleMode = TRUE,
    $pending = FALSE,
    $online = TRUE
    ) {
    $transaction = new CRM_Core_Transaction();
  
    $className   = get_class($form);

    $params['is_email_receipt'] = CRM_Utils_Array::value( 'is_email_receipt', $form->_values );
        
    $config = CRM_Core_Config::singleton();
  
    $nonDeductibleAmount = isset($params['default_amount_hidden']) ? $params['default_amount_hidden'] : $params['amount_requested'];
   
    $now = date('YmdHis');
    $receiptDate = CRM_Utils_Array::value('receipt_date', $params);
    if (CRM_Utils_Array::value('is_email_receipt', $form->_values)) {
      $receiptDate = $now;
    }

    //get the grant page id.
    $grantPageId = NULL;
   
    if ($online) {
      $grantPageId = $form->_id;
    }
    else {
      //also for offline we do support - CRM-7290
      $grantPageId = CRM_Utils_Array::value('contribution_page_id', $params);
      $campaignId = CRM_Utils_Array::value('campaign_id', $params);
    }

    // first create the grant record
    $grantParams = array(
      'contact_id' => $contactID,
      'grant_type_id' => $grantTypeId,
      'contribution_page_id' => $grantPageId,
      'application_received_date' => (CRM_Utils_Array::value('receive_date', $params)) ? CRM_Utils_Date::processDate($params['receive_date']) : date('YmdHis'),
      'status_id' => 1,
      'amount_level' => CRM_Utils_Array::value('amount_level', $params),
      'currency' => $params['currencyID'],
      'source' =>
      (!$online || CRM_Utils_Array::value('source', $params)) ?
      CRM_Utils_Array::value('source', $params) :
      CRM_Utils_Array::value('description', $params),
      'thankyou_date' =>
      isset($params['thankyou_date']) ?
      CRM_Utils_Date::format($params['thankyou_date']) :
      NULL,
    );
 
    if (!$online && isset($params['thankyou_date'])) {
      $grantParams['thankyou_date'] = $params['thankyou_date'];
    }

    $grantParams['contribution_status_id'] = $pending ? 2 : 1;
  
    $grantParams['is_test'] = 0;
     
    $ids = array();

    $grantParams['amount_requested'] = trim(CRM_Utils_Money::format($nonDeductibleAmount, ' '));
    $grantParams['amount_total'] = trim(CRM_Utils_Money::format($nonDeductibleAmount, ' '));

    if ($nonDeductibleAmount) {
      //add contribution record
      $grant = &CRM_Grant_BAO_Grant::add($grantParams, $ids);
    }
    if ($online && $grant) {
      CRM_Core_BAO_CustomValueTable::postProcess($form->_params,
        CRM_Core_DAO::$_nullArray,
        'civicrm_grant',
        $grant->id,
        'Grant'
      );
    }
    elseif ($grant) {
      //handle custom data.
      $params['contribution_id'] = $grant->id;
    
      if (CRM_Utils_Array::value('custom', $params) &&
          is_array($params['custom']) &&
          !is_a($grant, 'CRM_Core_Error')
          ) {
        CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_grant', $grant->id);
      }
    }
  
    if (isset($params['related_contact'])) {
      $contactID = $params['related_contact'];
    }
    elseif (isset($params['cms_contactID'])) {
      $contactID = $params['cms_contactID'];
    }
    CRM_Contribute_BAO_Contribution_Utils::createCMSUser($params,
      $contactID,
      'email-' . $form->_bltID
    );

    // return if pending
    if ($pending) {
      return $grant;
    }
    return $grant;
  }
}