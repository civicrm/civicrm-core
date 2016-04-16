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
 * $Id$
 *
 */

/**
 * This class contains the funtions for Friend
 *
 */
class CRM_Friend_BAO_Friend extends CRM_Friend_DAO_Friend {
  /**
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Takes an associative array and creates a friend object.
   *
   * the function extract all the params it needs to initialize the create a
   * friend object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Friend_BAO_Friend
   */
  public static function add(&$params) {
    $friend = CRM_Contact_BAO_Contact::createProfileContact($params, CRM_Core_DAO::$_nullArray);
    return $friend;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   *
   * @return array
   *   values
   */
  public static function retrieve(&$params, &$values) {
    $friend = new CRM_Friend_DAO_Friend();

    $friend->copyValues($params);

    $friend->find(TRUE);

    CRM_Core_DAO::storeValues($friend, $values);

    return $values;
  }

  /**
   * Takes an associative array and creates a friend object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return void
   */
  public static function create(&$params) {
    $transaction = new CRM_Core_Transaction();

    $mailParams = array();
    //create contact corresponding to each friend
    foreach ($params['friend'] as $key => $details) {
      if ($details["first_name"]) {
        $contactParams[$key] = array(
          'first_name' => $details["first_name"],
          'last_name' => $details["last_name"],
          'contact_source' => ts('Tell a Friend') . ": {$params['title']}",
          'email-Primary' => $details["email"],
        );

        $displayName = $details["first_name"] . " " . $details["last_name"];
        $mailParams['email'][$displayName] = $details["email"];
      }
    }

    $frndParams = array();
    $frndParams['entity_id'] = $params['entity_id'];
    $frndParams['entity_table'] = $params['entity_table'];
    self::getValues($frndParams);

    $activityTypeId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', 'Tell a Friend', 'value', 'name');

    //create activity
    $activityParams = array(
      'source_contact_id' => $params['source_contact_id'],
      'source_record_id' => NULL,
      'activity_type_id' => $activityTypeId,
      'title' => $params['title'],
      'activity_date_time' => date("YmdHis"),
      'subject' => ts('Tell a Friend') . ": {$params['title']}",
      'details' => $params['suggested_message'],
      'status_id' => 2,
      'is_test' => $params['is_test'],
      'campaign_id' => CRM_Utils_Array::value('campaign_id', $params),
    );

    //activity creation
    $activity = CRM_Activity_BAO_Activity::create($activityParams);
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    //friend contacts creation
    foreach ($contactParams as $key => $value) {

      //create contact only if it does not exits in db
      $value['email'] = $value['email-Primary'];
      $value['check_permission'] = FALSE;
      $contact = CRM_Core_BAO_UFGroup::findContact($value, NULL, 'Individual');

      if (!$contact) {
        $contact = self::add($value);
      }

      // attempt to save activity targets
      $targetParams = array(
        'activity_id' => $activity->id,
        'contact_id' => $contact,
        'record_type_id' => $targetID,
      );

      // See if it already exists
      $activityContact = new CRM_Activity_DAO_ActivityContact();
      $activityContact->activity_id = $activity->id;
      $activityContact->contact_id = $contact;
      $activityContact->find(TRUE);
      if (empty($activityContact->id)) {
        $resultTarget = CRM_Activity_BAO_ActivityContact::create($targetParams);
      }
    }

    $transaction->commit();

    //process sending of mails
    $mailParams['title'] = CRM_Utils_Array::value('title', $params);
    $mailParams['general_link'] = CRM_Utils_Array::value('general_link', $frndParams);
    $mailParams['message'] = CRM_Utils_Array::value('suggested_message', $params);

    // get domain
    $domainDetails = CRM_Core_BAO_Domain::getNameAndEmail();
    list($username, $mailParams['domain']) = explode('@', $domainDetails[1]);

    $default = array();
    $findProperties = array('id' => $params['entity_id']);

    if ($params['entity_table'] == 'civicrm_contribution_page') {

      $returnProperties = array('receipt_from_email', 'is_email_receipt');
      CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage',
        $findProperties,
        $default,
        $returnProperties
      );
      //if is_email_receipt is set then take receipt_from_email
      //as from_email
      if (!empty($default['is_email_receipt']) && !empty($default['receipt_from_email'])) {
        $mailParams['email_from'] = $default['receipt_from_email'];
      }

      $urlPath = 'civicrm/contribute/transact';
      $mailParams['module'] = 'contribute';
    }
    elseif ($params['entity_table'] == 'civicrm_event') {

      $returnProperties = array('confirm_from_email', 'is_email_confirm');
      CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event',
        $findProperties,
        $default,
        $returnProperties
      );

      $mailParams['email_from'] = $domainDetails['1'];

      //if is_email_confirm is set then take confirm_from_email
      //as from_email
      if (!empty($default['is_email_confirm']) && !empty($default['confirm_from_email'])) {
        $mailParams['email_from'] = $default['confirm_from_email'];
      }

      $urlPath = 'civicrm/event/info';
      $mailParams['module'] = 'event';
    }
    elseif ($params['entity_table'] == 'civicrm_pcp') {
      $mailParams['email_from'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $params['source_contact_id'],
        'email', 'contact_id'
      );
      $urlPath = 'civicrm/pcp/info';
      $mailParams['module'] = 'contribute';
    }

    $mailParams['page_url'] = CRM_Utils_System::url($urlPath, "reset=1&id={$params['entity_id']}", TRUE, NULL, FALSE, TRUE);

    //send mail
    self::sendMail($params['source_contact_id'], $mailParams);
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   *   Form object.
   *
   * @return void
   */
  public static function buildFriendForm($form) {
    $form->addElement('checkbox', 'tf_is_active', ts('Tell a Friend enabled?'), NULL, array('onclick' => "friendBlock(this)"));
    // name
    $form->add('text', 'tf_title', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'title'), TRUE);

    // intro-text and thank-you text
    $form->add('wysiwyg', 'intro', ts('Introduction'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'intro') + array('class' => 'collapsed'));

    $form->add('textarea', 'suggested_message', ts('Suggested Message'),
      CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'suggested_message'), FALSE
    );

    $form->add('text', 'general_link', ts('Info Page Link'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'general_link'));

    $form->add('text', 'tf_thankyou_title', ts('Thank-you Title'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'thankyou_title'), TRUE);

    $form->add('wysiwyg', 'tf_thankyou_text', ts('Thank-you Message'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'thankyou_text') + array('class' => 'collapsed'));

    if ($form->_friendId) {
      // CRM-14200 the i18n dialogs need this for translation
      $form->assign('friendId', $form->_friendId);
    }
  }

  /**
   * The function sets the deafult values of the form.
   *
   * @param array $defaults
   *   (reference) the default values.
   *
   * @return booelan
   *   whether anything was found
   */
  public static function getValues(&$defaults) {
    if (empty($defaults)) {
      return NULL;
    }
    $friend = new CRM_Friend_BAO_Friend();
    $friend->copyValues($defaults);
    $found = $friend->find(TRUE);
    CRM_Core_DAO::storeValues($friend, $defaults);
    return $found;
  }

  /**
   * Process that send tell a friend e-mails
   *
   * @param int $contactID
   * @param array $values
   *
   * @return void
   */
  public static function sendMail($contactID, &$values) {
    list($fromName, $email) = CRM_Contact_BAO_Contact::getContactDetails($contactID);
    // if no $fromName (only email collected from originating contact) - list returns single space
    if (trim($fromName) == '') {
      $fromName = $email;
    }

    // use contact email, CRM-4963
    if (empty($values['email_from'])) {
      $values['email_from'] = $email;
    }

    foreach ($values['email'] as $displayName => $emailTo) {
      if ($emailTo) {
        // FIXME: factor the below out of the foreach loop
        CRM_Core_BAO_MessageTemplate::sendTemplate(
          array(
            'groupName' => 'msg_tpl_workflow_friend',
            'valueName' => 'friend',
            'contactId' => $contactID,
            'tplParams' => array(
              $values['module'] => $values['module'],
              'senderContactName' => $fromName,
              'title' => $values['title'],
              'generalLink' => $values['general_link'],
              'pageURL' => $values['page_url'],
              'senderMessage' => $values['message'],
            ),
            'from' => "$fromName (via {$values['domain']}) <{$values['email_from']}>",
            'toName' => $displayName,
            'toEmail' => $emailTo,
            'replyTo' => $email,
          )
        );
      }
    }
  }

  /**
   * Takes an associative array and creates a tell a friend object.
   *
   * the function extract all the params it needs to initialize the create/edit a
   * friend object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Friend_BAO_Friend
   */
  public static function addTellAFriend(&$params) {
    $friendDAO = new CRM_Friend_DAO_Friend();

    $friendDAO->copyValues($params);
    $friendDAO->is_active = CRM_Utils_Array::value('is_active', $params, FALSE);

    $friendDAO->save();

    return $friendDAO;
  }

}
