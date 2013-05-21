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
 * This class contains the funtions for Friend
 *
 */
class CRM_Friend_BAO_Friend extends CRM_Friend_DAO_Friend {
  function __construct() {
    parent::__construct();
  }

  /**
   * takes an associative array and creates a friend object
   *
   * the function extract all the params it needs to initialize the create a
   * friend object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Friend_BAO_Friend object
   * @access public
   * @static
   */
  static function add(&$params) {
    $friend = CRM_Contact_BAO_Contact::createProfileContact($params, CRM_Core_DAO::$_nullArray);
    return $friend;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array  $params input parameters to find object
   * @param array  $values output values of the object
   *
   * @return array $values values
   * @access public
   * @static
   */
  static function retrieve(&$params, &$values) {
    $friend = new CRM_Friend_DAO_Friend();

    $friend->copyValues($params);

    $friend->find(TRUE);

    CRM_Core_DAO::storeValues($friend, $values);

    return $values;
  }

  /**
   * takes an associative array and creates a friend object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Contact_BAO_Contact object
   * @access public
   * @static
   */
  static function create(&$params) {
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
        'target_contact_id' => $contact,
      );
      // See if it already exists
      $activity_target = new CRM_Activity_DAO_ActivityTarget();
      $activity_target->activity_id = $activity->id;
      $activity_target->target_contact_id = $contact;
      $activity_target->find(TRUE);
      if (empty($activity_target->id)) {
        $resultTarget = CRM_Activity_BAO_ActivityTarget::create($targetParams);
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
      if (CRM_Utils_Array::value('is_email_receipt', $default) && CRM_Utils_Array::value('receipt_from_email', $default)) {
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
      if (CRM_Utils_Array::value('is_email_confirm', $default) && CRM_Utils_Array::value('confirm_from_email', $default)) {
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
   * Function to build the form
   *
   * @param object $form form object
   *
   * @return None
   * @access public
   */
  static function buildFriendForm($form) {
    $form->addElement('checkbox', 'tf_is_active', ts('Tell a Friend enabled?'), NULL, array('onclick' => "friendBlock(this)"));
    // name
    $form->add('text', 'tf_title', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'title'), TRUE);

    // intro-text and thank-you text
    $form->addWysiwyg('intro', ts('Introduction'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'intro'), TRUE);

    $form->add('textarea', 'suggested_message', ts('Suggested Message'),
      CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'suggested_message'), FALSE
    );

    $form->add('text', 'general_link', ts('Info Page Link'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'general_link'));

    $form->add('text', 'tf_thankyou_title', ts('Thank-you Title'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'thankyou_title'), TRUE);

    $form->addWysiwyg('tf_thankyou_text', ts('Thank-you Message'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'thankyou_text'), TRUE);
  }

  /**
   * The function sets the deafult values of the form.
   *
   * @param array   $defaults (reference) the default values.
   *
   * @return booelan  whether anything was found
   */
  static function getValues(&$defaults) {
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
   * @params int     $contactId      contact id
   * @params array   $values         associative array of name/value pair
   *
   * @return void
   * @access public
   */
  static function sendMail($contactID, &$values) {
    list($fromName, $email) = CRM_Contact_BAO_Contact::getContactDetails($contactID);
    // if no $fromName (only email collected from originating contact) - list returns single space
    if (trim($fromName) == '') {
      $fromName = $email;
    }

    // use contact email, CRM-4963
    if (!CRM_Utils_Array::value('email_from', $values)) {
      $values['email_from'] = $email;
    }

    foreach ($values['email'] as $displayName => $emailTo) {
      if ($emailTo) {
        // FIXME: factor the below out of the foreach loop
        CRM_Core_BAO_MessageTemplates::sendTemplate(
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
   * takes an associative array and creates a tell a friend object
   *
   * the function extract all the params it needs to initialize the create/edit a
   * friend object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Friend_BAO_Friend object
   * @access public
   * @static
   */
  static function addTellAFriend(&$params) {
    $friendDAO = new CRM_Friend_DAO_Friend();

    $friendDAO->copyValues($params);
    $friendDAO->is_active = CRM_Utils_Array::value('is_active', $params, FALSE);

    $friendDAO->save();

    return $friendDAO;
  }
}

