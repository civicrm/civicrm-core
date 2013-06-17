<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for processing a petition signature
 *
 */
class CRM_Campaign_Form_Petition_Signature extends CRM_Core_Form {
  CONST EMAIL_THANK = 1, EMAIL_CONFIRM = 2, MODE_CREATE = 4;

  protected $_mode;

  /**
   * the id of the contact associated with this signature
   *
   * @var int
   * @public
   */
  public $_contactId;

  /**
   * Is this a logged in user
   *
   * @var int
   */
  protected $_loggedIn = FALSE;

  /**
   * The contact type
   *
     * @var string ("Individual"/"Household"/"Organization"). Never been tested for something else than Individual
   */
  protected $_ctype = 'Individual';

  /**
   * The contact profile id attached with this petition
   *
   * @var int
   */
  protected $_contactProfileId;

  /**
   * the contact profile fields used for this petition
   *
   * @var array
   */
  public $_contactProfileFields;

  /**
   * The activity profile id attached with this petition
   *
   * @var int
   */
  protected $_activityProfileId;

  /**
   * the activity profile fields used for this petition
   *
   * @var array
   */
  public $_activityProfileFields;

  /**
   * the id of the survey (petition) we are proceessing
   *
   * @var int
   * @protected
   */
  public $_surveyId;

  /**
   * The tag id used to set against contacts with unconfirmed email
   *
   * @var int
   */
  protected $_tagId;

  /**
   * values to use for custom profiles
   *
   * @var array
   * @protected
   */
  public $_values;

  /**
   * The params submitted by the form
   *
   * @var array
   * @protected
   */
  protected $_params;

  /**
   * which email send mode do we use
   *
   * @var int
   * EMAIL_THANK = 1,
   *     connected user via login/pwd - thank you
   *      or dedupe contact matched who doesn't have a tag CIVICRM_TAG_UNCONFIRMED - thank you
   *     or login using fb connect - thank you + click to add msg to fb wall
   * EMAIL_CONFIRM = 2;
   *    send a confirmation request email
   */
  protected $_sendEmailMode;

  protected $_image_URL;

  protected $_defaults = NULL;

  function __construct() {
    parent::__construct();
    // this property used by civicrm_fb module and if true, forces thank you email to be sent
    // for users signing in via Facebook connect; also sets Fb email to check against
    $this->forceEmailConfirmed['flag'] = FALSE;
    $this->forceEmailConfirmed['email'] = '';
  }

  function getContactID() {
    $tempID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    // force to ignore the authenticated user
    if ($tempID === '0') {
      return $tempID;
    }

    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if ($userChecksum) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        return $tempID;
      }
    }

    // check if the user is registered and we have a contact ID
    $session = CRM_Core_Session::singleton();
    return $session->get('userID');
  }

  public function preProcess() {
    $this->bao = new CRM_Campaign_BAO_Petition();
    $this->_mode = self::MODE_CREATE;

    //get the survey id
    $this->_surveyId = CRM_Utils_Request::retrieve('sid', 'Positive', $this);

    //some sanity checks
    if (!$this->_surveyId) {
      CRM_Core_Error::fatal('Petition id is not valid. (it needs a "sid" in the url).');
      return;
    }
    //check petition is valid and active
    $params['id'] = $this->_surveyId;
    $this->petition = array();
    CRM_Campaign_BAO_Survey::retrieve($params, $this->petition);
    if (empty($this->petition)) {
      CRM_Core_Error::fatal('Petition doesn\'t exist.');
    }
    if ($this->petition['is_active'] == 0) {
      CRM_Core_Error::fatal('Petition is no longer active.');
    }

    //get userID from session
    $session = CRM_Core_Session::singleton();

    //get the contact id for this user if logged in
    $this->_contactId = $this->getContactId();
    if (isset($this->_contactId)) {
      $this->_loggedIn = TRUE;
    }

    // add the custom contact and activity profile fields to the signature form

    $ufJoinParams = array(
      'entity_id' => $this->_surveyId,
      'entity_table' => 'civicrm_survey',
      'module' => 'CiviCampaign',
      'weight' => 2,
    );

    $this->_contactProfileId = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParams);
    if ($this->_contactProfileId) {
      $this->_contactProfileFields = CRM_Core_BAO_UFGroup::getFields($this->_contactProfileId, FALSE, CRM_Core_Action::ADD);
    }
    if (!isset($this->_contactProfileFields['email-Primary'])) {
      CRM_Core_Error::fatal('The contact profile needs to contain the primary email address field');
    }


    $ufJoinParams['weight'] = 1;
    $this->_activityProfileId = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParams);

    if ($this->_activityProfileId) {
      $this->_activityProfileFields = CRM_Core_BAO_UFGroup::getFields($this->_activityProfileId, FALSE, CRM_Core_Action::ADD);
    }

    $this->setDefaultValues();
    CRM_Utils_System::setTitle($this->petition['title']);
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $this->_defaults = array();
    if ($this->_contactId) {
      CRM_Core_BAO_UFGroup::setProfileDefaults($this->_contactId, $this->_contactProfileFields, $this->_defaults, TRUE);
      if ($this->_activityProfileId) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($this->_contactId, $this->_activityProfileFields, $this->_defaults, TRUE);
      }
    }

    //set custom field defaults

    foreach ($this->_contactProfileFields as $name => $field) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
        $htmlType = $field['html_type'];

        if (!isset($this->_defaults[$name])) {
          CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID,
            $name,
            $this->_defaults,
            $this->_contactId,
            $this->_mode
          );
        }
      }
    }

    if ($this->_activityProfileFields) {
      foreach ($this->_activityProfileFields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $htmlType = $field['html_type'];

          if (!isset($this->_defaults[$name])) {
            CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID,
              $name,
              $this->_defaults,
              $this->_contactId,
              $this->_mode
            );
          }
        }
      }
    }

    $this->setDefaults($this->_defaults);
    
    // add in all state country selectors for enabled countries
    CRM_Core_BAO_Address::fixAllStateSelects($this, $this->_defaults);
  }

  public function buildQuickForm() {
    $this->assign('survey_id', $this->_surveyId);
    $this->assign('petitionTitle', $this->petition['title']);
    if (isset($_COOKIE['signed_' . $this->_surveyId])) {
      if (isset($_COOKIE['confirmed_' . $this->_surveyId])) {
        $this->assign('duplicate', "confirmed");
      }
      else {
        $this->assign('duplicate', "unconfirmed");
      }
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    $this->buildCustom($this->_contactProfileId, 'petitionContactProfile');
    if ($this->_activityProfileId) {
      $this->buildCustom($this->_activityProfileId, 'petitionActivityProfile');
    }
    // add buttons
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Sign the Petition'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * This function is used to add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @return None
   * @access public
   * @see valid_date
   */

  static function formRule($fields, $files, $errors) {
    $errors = array();

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Form submission of petition signature
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $tag_name = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CAMPAIGN_PREFERENCES_NAME,
      'tag_unconfirmed'
    );


    if ($tag_name) {
      // Check if contact 'email confirmed' tag exists, else create one
      // This should be in the petition module initialise code to create a default tag for this
      $tag_params['name'] = $tag_name;
      $tag_params['version'] = 3;
      $tag = civicrm_api('tag', 'get', $tag_params);
      if ($tag['count'] == 0) {
        //create tag
        $tag_params['description'] = $tag_name;
        $tag_params['is_reserved'] = 1;
        $tag_params['used_for'] = 'civicrm_contact';
        $tag = civicrm_api('tag', 'create', $tag_params);
      }
      $this->_tagId = $tag['id'];
    }

    // export the field values to be used for saving the profile form
    $params = $this->controller->exportValues($this->_name);

    $session = CRM_Core_Session::singleton();
    // format params
    $params['last_modified_id'] = $session->get('userID');
    $params['last_modified_date'] = date('YmdHis');

    if ($this->_action & CRM_Core_Action::ADD) {
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }

    if (isset($this->_surveyId)) {
      $params['sid'] = $this->_surveyId;
    }

    if (isset($this->_contactId)) {
      $params['contactId'] = $this->_contactId;
    }

    // if logged in user, skip dedupe
    if ($this->_loggedIn) {
      $ids[0] = $this->_contactId;
    }
    else {
      // dupeCheck - check if contact record already exists
      // code modified from api/v2/Contact.php-function civicrm_contact_check_params()
      $params['contact_type'] = $this->_ctype;
      //TODO - current dedupe finds soft deleted contacts - adding param is_deleted not working
      // ignore soft deleted contacts
      //$params['is_deleted'] = 0;
      $dedupeParams = CRM_Dedupe_Finder::formatParams($params, $params['contact_type']);
      $dedupeParams['check_permission'] = '';

      //dupesByParams($params, $ctype, $level = 'Unsupervised', $except = array())
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, $params['contact_type']);
    }

        $petition_params['id'] = $this->_surveyId;
        $petition = array();
        CRM_Campaign_BAO_Survey::retrieve($petition_params, $petition);

    switch (count($ids)) {
      case 0:
        //no matching contacts - create a new contact
        // Add a source for this new contact
        $params['source'] = ts('Petition Signature') . ' ' . $this->petition['title'];

        if ($this->petition['bypass_confirm']) {
          // send thank you email directly, bypassing confirmation
          $this->_sendEmailMode = self::EMAIL_THANK;
          // Set status for signature activity to completed
          $params['statusId'] = 2;
        }
        else {
        $this->_sendEmailMode = self::EMAIL_CONFIRM;

        // Set status for signature activity to scheduled until email is verified
        $params['statusId'] = 1;
        }
        break;

      case 1:
        $this->_contactId = $params['contactId'] = $ids[0];

        // check if user has already signed this petition - redirects to Thank You if true
        $this->redirectIfSigned($params);

        if ($this->petition['bypass_confirm']) {
          // send thank you email directly, bypassing confirmation
          $this->_sendEmailMode = self::EMAIL_THANK;
          // Set status for signature activity to completed
          $params['statusId'] = 2;
          break;
        }

        // dedupe matched single contact, check for 'unconfirmed' tag
        if ($tag_name) {
          $tag            = new CRM_Core_DAO_EntityTag();
          $tag->entity_id = $this->_contactId;
          $tag->tag_id    = $this->_tagId;

          if (!($tag->find())) {
            // send thank you email directly, the user is known and validated
            $this->_sendEmailMode = self::EMAIL_THANK;
            // Set status for signature activity to completed
            $params['statusId'] = 2;
          }
          else {
            // send email verification email
            $this->_sendEmailMode = self::EMAIL_CONFIRM;
            // Set status for signature activity to scheduled until email is verified
            $params['statusId'] = 1;
          }
        }
        break;

      default:
        // more than 1 matching contact
        // for time being, take the first matching contact (not sure that's the best strategy, but better than creating another duplicate)
        $this->_contactId = $params['contactId'] = $ids[0];

        // check if user has already signed this petition - redirects to Thank You if true
        $this->redirectIfSigned($params);

        if ($this->petition['bypass_confirm']) {
          // send thank you email directly, bypassing confirmation
          $this->_sendEmailMode = self::EMAIL_THANK;
          // Set status for signature activity to completed
          $params['statusId'] = 2;
          break;
        }

        if ($tag_name) {
          $tag            = new CRM_Core_DAO_EntityTag();
          $tag->entity_id = $this->_contactId;
          $tag->tag_id    = $this->_tagId;

          if (!($tag->find())) {
            // send thank you email
            $this->_sendEmailMode = self::EMAIL_THANK;
            // Set status for signature activity to completed
            $params['statusId'] = 2;
          }
          else {
            // send email verification email
            $this->_sendEmailMode = self::EMAIL_CONFIRM;
            // Set status for signature activity to scheduled until email is verified
            $params['statusId'] = 1;
          }
        }
        break;
    }



    $transaction = new CRM_Core_Transaction();

    $addToGroupID = isset($this->_addToGroupID) ? $this->_addToGroupID : NULL;
    $this->_contactId = CRM_Contact_BAO_Contact::createProfileContact($params, $this->_contactProfileFields,
      $this->_contactId, $addToGroupID,
      $this->_contactProfileId, $this->_ctype,
      TRUE
    );

    // get additional custom activity profile field data
    // to save with new signature activity record
    $surveyInfo = $this->bao->getSurveyInfo($this->_surveyId);
    $customActivityFields = CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
      $surveyInfo['activity_type_id']
    );
    $customActivityFields = CRM_Utils_Array::crmArrayMerge($customActivityFields,
      CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
        NULL, NULL, TRUE
      )
    );

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customActivityFields,
      NULL,
      'Activity'
    );

    // create the signature activity record
    $params['contactId'] = $this->_contactId;
    $params['activity_campaign_id'] = CRM_Utils_Array::value('campaign_id', $this->petition);
    $result = $this->bao->createSignature($params);

    // send thank you or email verification emails

    // if logged in using Facebook connect and email on form matches Fb email,
    // no need for email confirmation, send thank you email
    if ($this->forceEmailConfirmed['flag'] &&
      ($this->forceEmailConfirmed['email'] == $params['email-Primary'])
    ) {
      $this->_sendEmailMode = self::EMAIL_THANK;
    }

    switch ($this->_sendEmailMode) {
      case self::EMAIL_THANK:
        // mark the signature activity as completed and set confirmed cookie
        $this->bao->confirmSignature($result->id, $this->_contactId, $this->_surveyId);
        break;

      case self::EMAIL_CONFIRM:
        // set 'Unconfirmed' tag for this new contact
        if ($tag_name) {
          unset($tag_params);
          $tag_params['contact_id'] = $this->_contactId;
          $tag_params['tag_id'] = $this->_tagId;
          $tag_value = civicrm_api('entity_tag', 'create', $tag_params);
        }
        break;
    }

    //send email
    $params['activityId'] = $result->id;
    $params['tagId'] = $this->_tagId;
    $this->bao->sendEmail($params, $this->_sendEmailMode);

    $transaction->commit();

    if ($result) {
      // call the hook before we redirect
      $this->postProcessHook();

      // set the template to thank you
      $url =
        CRM_Utils_System::url(
          'civicrm/petition/thankyou',
          'pid=' . $this->_surveyId . '&id=' . $this->_sendEmailMode . '&reset=1'
        );
      CRM_Utils_System::redirect($url);
    }
  }

  /**
   * Function to build the petition profile form
   *
   * @return None
   * @access public
   */
  function buildCustom($id, $name, $viewOnly = FALSE) {

    // create state country map array to hold selectors 
    $stateCountryMap = array();

    if ($id) {
      $session = CRM_Core_Session::singleton();
      $this->assign("petition", $this->petition);
      //$contactID = $this->_contactId;
      $contactID = NULL;
      $this->assign('contact_id', $this->_contactId);

      $fields = NULL;
      // TODO: contactID is never set (commented above)
      if ($contactID) {
        if (CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
          $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD);
        }
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD);
      }

      if ($fields) {
        /*
                // unset any email-* fields since we already collect it, CRM-2888
                foreach ( array_keys( $fields ) as $fieldName ) {
                    if ( substr( $fieldName, 0, 6 ) == 'email-' ) {
                        unset( $fields[$fieldName] );
                    }
                }
                */


        $this->assign($name, $fields);

        $addCaptcha = FALSE;
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            // ignore file upload fields
            continue;
          }
          
          // if state or country in the profile, create map 
          list($prefixName, $index) = CRM_Utils_System::explode('-', $key, 2);
          if ($prefixName == 'state_province' || $prefixName == 'country' || $prefixName == 'county') {
            if (!array_key_exists($index, $stateCountryMap)) {
              $stateCountryMap[$index] = array();
            }
            $stateCountryMap[$index][$prefixName] = $key;
          }  

          CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE, $contactID, TRUE);
          $this->_fields[$key] = $field;
          if ($field['add_captcha']) {
            $addCaptcha = TRUE;
          }
        }

        // initialize the state country map        
        CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);

        if ($addCaptcha &&
          !$viewOnly
        ) {
          $captcha = CRM_Utils_ReCAPTCHA::singleton();
          $captcha->add($this);
          $this->assign("isCaptcha", TRUE);
        }
      }
    }
  }

  function getTemplateFileName() {
    if (isset($this->thankyou)) {
      return ('CRM/Campaign/Page/Petition/ThankYou.tpl');
    }
    else {}
    return parent::getTemplateFileName();
  }

  // check if user has already signed this petition
  function redirectIfSigned($params) {
    $signature = $this->bao->checkSignature($this->_surveyId, $this->_contactId);
    //TODO: error case when more than one signature found for this petition and this contact
    if (!empty($signature) && (count($signature) == 1)) {
      $signature_id = array_keys($signature);
      switch ($signature[$signature_id[0]]['status_id']) {
        case 1:
          //status is scheduled - email is unconfirmed
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/petition/thankyou', 'pid=' . $this->_surveyId . '&id=4&reset=1'));
          break;

        case 2:
          //status is completed
          $this->bao->sendEmail($params, 1);
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/petition/thankyou', 'pid=' . $this->_surveyId . '&id=5&reset=1'));
          break;
      }
    }
  }
}



