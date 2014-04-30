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
    
    if (isset($this->_params['amount'])) {
      $this->_params['currencyID'] = $config->defaultCurrency;
    }

    // if onbehalf-of-organization
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $this->_params)) {
      if (CRM_Utils_Array::value('org_option', $this->_params) &&
        CRM_Utils_Array::value('organization_id', $this->_params)
      ) {
        if (CRM_Utils_Array::value('onbehalfof_id', $this->_params)) {
          $this->_params['organization_id'] = $this->_params['onbehalfof_id'];
        }
      }

      $this->_params['organization_name'] = $this->_params['onbehalf']['organization_name'];
      $addressBlocks = array(
        'street_address', 'city', 'state_province',
        'postal_code', 'country', 'supplemental_address_1',
        'supplemental_address_2', 'supplemental_address_3',
        'postal_code_suffix', 'geo_code_1', 'geo_code_2', 'address_name',
      );

      $blocks = array('email', 'phone', 'im', 'url', 'openid');
      foreach ($this->_params['onbehalf'] as $loc => $value) {
        $field = $typeId = NULL;
        if (strstr($loc, '-')) {
          list($field, $locType) = explode('-', $loc);
        }

        if (in_array($field, $addressBlocks)) {
          if ($locType == 'Primary') {
            $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
            $locType = $defaultLocationType->id;
          }

          if ($field == 'country') {
            $value = CRM_Core_PseudoConstant::countryIsoCode($value);
          }
          elseif ($field == 'state_province') {
            $value = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value);
          }

          $isPrimary = 1;
          if (isset($this->_params['onbehalf_location']['address'])
               && count($this->_params['onbehalf_location']['address']) > 0) {
            $isPrimary = 0;
          }

          $this->_params['onbehalf_location']['address'][$locType][$field] = $value;
          if (!CRM_Utils_Array::value('is_primary', $this->_params['onbehalf_location']['address'][$locType])) {
            $this->_params['onbehalf_location']['address'][$locType]['is_primary'] = $isPrimary;
        }
          $this->_params['onbehalf_location']['address'][$locType]['location_type_id'] = $locType;
        }
        elseif (in_array($field, $blocks)) {
          if (!$typeId || is_numeric($typeId)) {
            $blockName     = $fieldName = $field;
            $locationType  = 'location_type_id';
            if ( $locType == 'Primary' ) {
              $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
              $locationValue = $defaultLocationType->id;
            }
            else {
              $locationValue = $locType;
            }
            $locTypeId     = '';
            $phoneExtField = array();

            if ($field == 'url') {
              $blockName     = 'website';
              $locationType  = 'website_type_id';
              $locationValue = CRM_Utils_Array::value("{$loc}-website_type_id", $this->_params['onbehalf']);
            }
            elseif ($field == 'im') {
              $fieldName = 'name';
              $locTypeId = 'provider_id';
              $typeId    = $this->_params['onbehalf']["{$loc}-provider_id"];
            }
            elseif ($field == 'phone') {
              list($field, $locType, $typeId) = explode('-', $loc);
              $locTypeId = 'phone_type_id';

              //check if extension field exists
              $extField = str_replace('phone','phone_ext', $loc);
              if (isset($this->_params['onbehalf'][$extField])) {
                $phoneExtField = array('phone_ext' => $this->_params['onbehalf'][$extField]);
              }
            }

            $isPrimary = 1;
            if ( isset ($this->_params['onbehalf_location'][$blockName] )
              && count( $this->_params['onbehalf_location'][$blockName] ) > 0 ) {
                $isPrimary = 0;
            }
            if ($locationValue) {
              $blockValues = array(
                $fieldName    => $value,
                $locationType => $locationValue,
                'is_primary'  => $isPrimary,
              );

              if ($locTypeId) {
                $blockValues = array_merge($blockValues, array($locTypeId  => $typeId));
              }
              if (!empty($phoneExtField)) {
                $blockValues = array_merge($blockValues, $phoneExtField);
              }

              $this->_params['onbehalf_location'][$blockName][] = $blockValues;
            }
          }
        }
        elseif (strstr($loc, 'custom')) {
          if ($value && isset($this->_params['onbehalf']["{$loc}_id"])) {
            $value = $this->_params['onbehalf']["{$loc}_id"];
          }
          $this->_params['onbehalf_location']["{$loc}"] = $value;
        }
        else {
          if ($loc == 'contact_sub_type') {
            $this->_params['onbehalf_location'][$loc] = $value;
          }
          else {
            $this->_params['onbehalf_location'][$field] = $value;
          }
        }
      }
    }
    elseif (CRM_Utils_Array::value('is_for_organization', $this->_values)) {
      // no on behalf of an organization, CRM-5519
      // so reset loc blocks from main params.
      foreach (array(
        'phone', 'email', 'address') as $blk) {
        if (isset($this->_params[$blk])) {
          unset($this->_params[$blk]);
        }
      }
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
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $params)) {
      $ufJoinParams = array(
        'module' => 'onBehalf',
        'entity_table' => 'civicrm_grant_app_page',
        'entity_id' => $this->_id,
      );
      $OnBehalfProfile = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      $profileId = $OnBehalfProfile[0];

      $fieldTypes     = array('Contact', 'Organization');
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes     = array_merge($fieldTypes, $contactSubType);
      $fieldTypes = array_merge($fieldTypes, array('Grant'));


      $this->buildCustom($profileId, 'onbehalfProfile', TRUE, TRUE, $fieldTypes);
    }
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
      if ($name == 'onbehalf') {
        foreach ($dontCare as $key => $value) {
          $fields['onbehalf'][$key] = 1;
        }
      }
      else {
        $fields[$name] = 1;
      }
    }

    $contact = $this->_params;
    foreach ($fields as $name => $dontCare) {
      if ($name == 'onbehalf') {
        foreach ($dontCare as $key => $value) {
          if (isset($contact['onbehalf'][$key])) {
            $defaults[$key] = $contact['onbehalf'][$key];
          }
          if (isset($contact['onbehalf']["{$key}_id"])) {
            $defaults["{$key}_id"] = $contact['onbehalf']["{$key}_id"];
          }
        }
      }
      elseif (isset($contact[$name])) {
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
    $contactID = $this->getContactID();

    // add a description field at the very beginning
    $this->_params['description'] = ts('Online Grant Application') . ':' . $this->_values['title'];

    // fix currency ID
    $this->_params['currencyID'] = $config->defaultCurrency;

    $params = $this->_params;
    $fields = array();

    if (CRM_Utils_Array::value('image_URL', $params)) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    // set email for primary location.
    $fields['email-Primary'] = 1;

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

    // if onbehalf-of-organization grant application, take out
    // organization params in a separate variable, to make sure
    // normal behavior is continued. And use that variable to
    // process on-behalf-of functionality.
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $this->_params)) {
      $behalfOrganization = array();
      $orgFields = array('organization_name', 'organization_id', 'org_option');
      foreach ($orgFields as $fld) {
        if (array_key_exists($fld, $params)) {
          $behalfOrganization[$fld] = $params[$fld];
          unset($params[$fld]);
        }
      }

      if (is_array($params['onbehalf']) && !empty($params['onbehalf'])) {
        foreach ($params['onbehalf'] as $fld => $values) {
          if (strstr($fld, 'custom_')) {
            $behalfOrganization[$fld] = $values;
          }
          elseif (!(strstr($fld, '-'))) {
            $behalfOrganization[$fld] = $values;
            $this->_params[$fld] = $values;
          }
        }
      }

      if (array_key_exists('onbehalf_location', $params) && is_array($params['onbehalf_location'])) {
        foreach ($params['onbehalf_location'] as $block => $vals) {
          //fix for custom data (of type checkbox, multi-select)
          if ( substr($block, 0, 7) == 'custom_' ) {
            continue;
          }
          // fix the index of block elements
          if (is_array($vals) ) {
            foreach ( $vals as $key => $val ) {
              //dont adjust the index of address block as
              //it's index is WRT to location type
              $newKey = ($block == 'address') ? $key : ++$key;
              $behalfOrganization[$block][$newKey] = $val;
            }
          }
        }
        unset($params['onbehalf_location']);
      }
      if (CRM_Utils_Array::value('onbehalf[image_URL]', $params)) {
        $behalfOrganization['image_URL'] = $params['onbehalf[image_URL]'];
      }
    }
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

    if (empty($contactID)) {
      $dupeParams = $params;
      if (CRM_Utils_Array::value('onbehalf', $dupeParams)) {
        unset($dupeParams['onbehalf']);
      }

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

      $contactID = CRM_Contact_BAO_Contact::createProfileContact(
        $params,
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
      $contactID = CRM_Contact_BAO_Contact::createProfileContact(
        $params,
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
    //@todo consider handling this in $this->getContactID();
    $this->set('contactID', $contactID);
    $this->_contactID = $contactID;

    //get email primary first if exist
    $subscribtionEmail = array('email' => CRM_Utils_Array::value('email-Primary', $params));
    // subscribing contact to groups
    if (!empty($subscribeGroupIds) && $subscribtionEmail['email']) {
      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($subscribeGroupIds, $subscribtionEmail, $contactID);
    }
    // If onbehalf-of-organization grant application add organization
    // and it's location.
    if (isset($params['hidden_onbehalf_profile']) && isset($behalfOrganization['organization_name'])) {
      $ufFields = array();
      foreach ($this->_fields['onbehalf'] as $name => $value) {
        $ufFields[$name] = 1;
      }
      self::processOnBehalfOrganization($behalfOrganization, $contactID, $this->_values,
        $this->_params, $ufFields
      );
    }
    $grantTypeId = $this->_values['grant_type_id'];
    
    $fieldTypes = array();
    
    $grantParams = $this->_params;
    
    CRM_Grant_BAO_Grant_Utils::processConfirm($this, 
      $grantParams,
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
  static function processApplication(&$form,
    $params,
    $contactID,
    $grantTypeId,
    $online = TRUE
  ) {
    $transaction = new CRM_Core_Transaction();
  
    $className   = get_class($form);

    $params['is_email_receipt'] = CRM_Utils_Array::value('is_email_receipt', $form->_values);
        
    $config = CRM_Core_Config::singleton();
  
    $nonDeductibleAmount = isset($params['default_amount_hidden']) ? $params['default_amount_hidden'] : $params['amount_total'];
   
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

    // first create the grant record
    $grantParams = array(
      'contact_id' => $contactID,
      'grant_type_id' => $grantTypeId,
      'grant_page_id' => $grantPageId,
      'application_received_date' => (CRM_Utils_Array::value('receive_date', $params)) ? CRM_Utils_Date::processDate($params['receive_date']) : date('YmdHis'),
      'status_id' => 1,
      'amount_level' => CRM_Utils_Array::value('amount_level', $params),
      'currency' => $params['currencyID'],
      'source' => CRM_Utils_Array::value('description', $params),
      'thankyou_date' =>
      isset($params['thankyou_date']) ?
      CRM_Utils_Date::format($params['thankyou_date']) :
      NULL,
    );
 
    if (!$online && isset($params['thankyou_date'])) {
      $grantParams['thankyou_date'] = $params['thankyou_date'];
    }

    $grantParams['grant_status_id'] = CRM_Core_OptionGroup::getValue('grant_status', 'Submitted');
  
    $grantParams['is_test'] = 0;
     
    $ids = array();

    $grantParams['amount_requested'] = trim(CRM_Utils_Money::format($nonDeductibleAmount, ' '));
    $grantParams['amount_total'] = trim(CRM_Utils_Money::format($nonDeductibleAmount, ' '));

    if ($nonDeductibleAmount) {
        //add grant record
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
      $params['grant_id'] = $grant->id;
    
      if (CRM_Utils_Array::value('custom', $params) &&
        is_array($params['custom']) &&
        !is_a($grant, 'CRM_Core_Error')
      ) {
        CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_grant', $grant->id);
      }
    }

    $targetContactID = NULL;
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $params)) {
      $targetContactID = $grant->contact_id;
      $grant->contact_id = $contactID;
    }
    // add source 
    $grant->source = CRM_Utils_Array::value('description', $params);
    // create an activity record
    if ($grant) {
      CRM_Grant_BAO_GrantApplicationPage::addActivity($grant, 'Grant', $targetContactID);
    }
    // Re-using function defined in Contribution/Utils.php
    CRM_Contribute_BAO_Contribution_Utils::createCMSUser($params,
      $contactID,
      'email-Primary'
    );

    return $grant;
  }

/**
   * Function to add on behalf of organization and it's location
   *
   * @param $behalfOrganization array  array of organization info
   * @param $contactID          int    individual contact id. One
   * who is doing the process of applying for the grant.
   *
   * @param $values             array  form values array
   * @param $params
   * @param null $fields
   *
   * @return void
   * @access public
   */
  static function processOnBehalfOrganization(&$behalfOrganization, &$contactID, &$values, &$params, $fields = NULL) {
    $isCurrentEmployer = FALSE;
    $orgID = NULL;
    if (CRM_Utils_Array::value('organization_id', $behalfOrganization) &&
      CRM_Utils_Array::value('org_option', $behalfOrganization)
    ) {
      $orgID = $behalfOrganization['organization_id'];
      unset($behalfOrganization['organization_id']);
      $isCurrentEmployer = TRUE;
    }

    // formalities for creating / editing organization.
    $behalfOrganization['contact_type'] = 'Organization';

    // get the relationship type id
    $relType = new CRM_Contact_DAO_RelationshipType();
    $relType->name_a_b = 'Employee of';
    $relType->find(TRUE);
    $relTypeId = $relType->id;

    // keep relationship params ready
    $relParams['relationship_type_id'] = $relTypeId . '_a_b';
    $relParams['is_permission_a_b'] = 1;
    $relParams['is_active'] = 1;

    if (!$orgID) {
      // check if matching organization contact exists
      $dedupeParams = CRM_Dedupe_Finder::formatParams($behalfOrganization, 'Organization');
      $dedupeParams['check_permission'] = FALSE;
      $dupeIDs = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Organization', 'Unsupervised');

      // CRM-6243 says to pick the first org even if more than one match
      if (count($dupeIDs) >= 1) {
        $behalfOrganization['contact_id'] = $dupeIDs[0];
        // don't allow name edit
        unset($behalfOrganization['organization_name']);
      }
    }
    else {
      // if found permissioned related organization, allow location edit
      $behalfOrganization['contact_id'] = $orgID;
      // don't allow name edit
      unset($behalfOrganization['organization_name']);
    }

    // handling for image url
    if (CRM_Utils_Array::value('image_URL', $behalfOrganization)) {
      CRM_Contact_BAO_Contact::processImageParams($behalfOrganization);
    }

    // create organization, add location
    $orgID = CRM_Contact_BAO_Contact::createProfileContact($behalfOrganization, $fields, $orgID,
      NULL, NULL, 'Organization'
    );
    // create relationship
    $relParams['contact_check'][$orgID] = 1;
    $cid = array('contact' => $contactID);
    CRM_Contact_BAO_Relationship::create($relParams, $cid);

    // if multiple match - send a duplicate alert
    if ($dupeIDs && (count($dupeIDs) > 1)) {
      $values['onbehalf_dupe_alert'] = 1;
      // required for IPN
      $params['onbehalf_dupe_alert'] = 1;
    }

    // make sure organization-contact-id is considered for recording
    // grant application etc..
    if ($contactID != $orgID) {
      // take a note of contact-id, so we can send the
      // receipt to individual contact as well.

      // required for mailing/template display ..etc
      $values['related_contact'] = $contactID;
      // required for IPN
      $params['related_contact'] = $contactID;

      //make this employee of relationship as current
      //employer / employee relationship,  CRM-3532
      if ($isCurrentEmployer &&
        ($orgID != CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'employer_id'))
      ) {
        $isCurrentEmployer = FALSE;
      }

      if (!$isCurrentEmployer && $orgID) {
        //build current employer params
        $currentEmpParams[$contactID] = $orgID;
        CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($currentEmpParams);
      }

      // grant will be assigned to this
      // organization id.
      $contactID = $orgID;
    }
  }
}