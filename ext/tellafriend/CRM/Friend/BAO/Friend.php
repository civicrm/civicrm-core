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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class contains the functions for Friend
 *
 */
class CRM_Friend_BAO_Friend extends CRM_Friend_DAO_Friend {

  /**
   * Tell a friend id in db.
   *
   * @var int
   */
  public $_friendId;

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
   * @return int
   */
  public static function add(&$params) {
    return CRM_Contact_BAO_Contact::createProfileContact($params);
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Takes an associative array and creates a friend object.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @throws \CRM_Core_Exception
   */
  public static function create(&$params) {
    $transaction = new CRM_Core_Transaction();

    $mailParams = [];
    $contactParams = [];

    // create contact corresponding to each friend
    foreach ($params['friend'] as $key => $details) {
      if ($details["first_name"]) {
        $contactParams[$key] = [
          'first_name' => $details["first_name"],
          'last_name' => $details["last_name"],
          'contact_source' => ts('Tell a Friend') . ": {$params['title']}",
          'email-Primary' => $details["email"],
        ];

        $displayName = $details["first_name"] . " " . $details["last_name"];
        $mailParams['email'][$displayName] = $details["email"];
      }
    }

    $friendParams = [
      'entity_id' => $params['entity_id'],
      'entity_table' => $params['entity_table'],
    ];
    self::getValues($friendParams);

    $activityTypeId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', 'Tell a Friend', 'value', 'name');

    // create activity
    $activityParams = [
      'source_contact_id' => $params['source_contact_id'],
      'source_record_id' => NULL,
      'activity_type_id' => $activityTypeId,
      'title' => $params['title'],
      'activity_date_time' => date("YmdHis"),
      'subject' => ts('Tell a Friend') . ": {$params['title']}",
      'details' => $params['suggested_message'],
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
      'is_test' => $params['is_test'],
      'campaign_id' => $params['campaign_id'] ?? NULL,
    ];

    // activity creation
    $activity = CRM_Activity_BAO_Activity::create($activityParams);
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // friend contacts creation
    foreach ($contactParams as $key => $value) {
      // create contact only if it does not exits in db
      $value['email'] = $value['email-Primary'];
      $contactID = CRM_Contact_BAO_Contact::getFirstDuplicateContact($value, 'Individual', 'Supervised', [], FALSE);

      if (!$contactID) {
        $contactID = self::add($value);
      }

      // attempt to save activity targets
      $targetParams = [
        'activity_id' => $activity->id,
        'contact_id' => $contactID,
        'record_type_id' => $targetID,
      ];

      // See if it already exists
      $activityContact = new CRM_Activity_DAO_ActivityContact();
      $activityContact->activity_id = $activity->id;
      $activityContact->contact_id = $contactID;
      $activityContact->find(TRUE);
      if (empty($activityContact->id)) {
        CRM_Activity_BAO_ActivityContact::create($targetParams);
      }
    }

    $transaction->commit();

    // Process sending of mails
    $mailParams['title'] = $params['title'] ?? NULL;
    $mailParams['general_link'] = $friendParams['general_link'] ?? NULL;
    $mailParams['message'] = $params['suggested_message'] ?? NULL;

    // Default "from email address" is default domain address.
    list($_, $mailParams['email_from']) = CRM_Core_BAO_Domain::getNameAndEmail();
    list($username, $mailParams['domain']) = explode('@', $mailParams['email_from']);

    $default = [];
    $findProperties = ['id' => $params['entity_id']];

    if ($params['entity_table'] == 'civicrm_contribution_page') {
      $returnProperties = ['receipt_from_email', 'is_email_receipt'];
      CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage',
        $findProperties,
        $default,
        $returnProperties
      );

      // if is_email_receipt is set then take receipt_from_email as from_email
      if (!empty($default['is_email_receipt']) && !empty($default['receipt_from_email'])) {
        $mailParams['email_from'] = $default['receipt_from_email'];
      }

      $urlPath = 'civicrm/contribute/transact';
      $mailParams['module'] = 'contribute';
    }
    elseif ($params['entity_table'] == 'civicrm_event') {
      $returnProperties = ['confirm_from_email', 'is_email_confirm'];
      CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event',
        $findProperties,
        $default,
        $returnProperties
      );

      // if is_email_confirm is set then take confirm_from_email as from_email
      if (!empty($default['is_email_confirm']) && !empty($default['confirm_from_email'])) {
        $mailParams['email_from'] = $default['confirm_from_email'];
      }

      $urlPath = 'civicrm/event/info';
      $mailParams['module'] = 'event';
    }
    elseif ($params['entity_table'] == 'civicrm_pcp') {
      if (Civi::settings()->get('allow_mail_from_logged_in_contact')) {
        $mailParams['email_from'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $params['source_contact_id'],
          'email', 'contact_id'
        );
      }
      $urlPath = 'civicrm/pcp/info';
      $mailParams['module'] = 'contribute';
    }

    $mailParams['page_url'] = CRM_Utils_System::url($urlPath, "reset=1&id={$params['entity_id']}", TRUE, NULL, FALSE, TRUE);

    // Send the email
    self::sendMail($params['source_contact_id'], $mailParams);
  }

  /**
   * Build the form object.
   *
   * @param CRM_Friend_Form $form
   *   Form object.
   *
   * @return void
   */
  public static function buildFriendForm($form) {
    $form->addElement('checkbox', 'tf_is_active', ts('Tell a Friend enabled?'), NULL, ['onclick' => "friendBlock(this)"]);
    // name
    $form->add('text', 'tf_title', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'title'), TRUE);

    // intro-text and thank-you text
    $form->add('wysiwyg', 'intro', ts('Introduction'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'intro') + ['class' => 'collapsed']);

    $form->add('textarea', 'suggested_message', ts('Suggested Message'),
      CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'suggested_message'), FALSE
    );

    $form->add('text', 'general_link', ts('Info Page Link'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'general_link'));

    $form->add('text', 'tf_thankyou_title', ts('Thank-you Title'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'thankyou_title'), TRUE);

    $form->add('wysiwyg', 'tf_thankyou_text', ts('Thank-you Message'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'thankyou_text') + ['class' => 'collapsed']);

    if ($form->_friendId) {
      // CRM-14200 the i18n dialogs need this for translation
      $form->assign('friendId', $form->_friendId);
    }
  }

  /**
   * The function sets the default values of the form.
   *
   * @param array $defaults
   *   (reference) the default values.
   *
   * @return bool
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
   * Process that sends tell a friend e-mails
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

    if (Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      // use contact email, CRM-4963
      if (empty($values['email_from'])) {
        $values['email_from'] = $email;
      }
    }

    // If we have no "email_from" when we get to here, explicitly set it to the default domain email.
    if (empty($values['email_from'])) {
      list($domainFromName, $domainEmail) = CRM_Core_BAO_Domain::getNameAndEmail();
      $values['email_from'] = $domainEmail;
      $values['domain'] = $domainFromName;
    }

    $templateParams = [
      'groupName' => 'msg_tpl_workflow_friend',
      'workflow' => 'friend',
      'contactId' => $contactID,
      'tplParams' => [
        $values['module'] => $values['module'],
        'senderContactName' => $fromName,
        'title' => $values['title'],
        'generalLink' => $values['general_link'],
        'pageURL' => $values['page_url'],
        'senderMessage' => $values['message'],
      ],
      'from' => "$fromName (via {$values['domain']}) <{$values['email_from']}>",
      'replyTo' => $email,
    ];

    foreach ($values['email'] as $displayName => $emailTo) {
      if ($emailTo) {
        $templateParams['toName'] = $displayName;
        $templateParams['toEmail'] = $emailTo;
        CRM_Core_BAO_MessageTemplate::sendTemplate($templateParams);
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
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Friend_DAO_Friend
   */
  public static function addTellAFriend(&$params) {
    $friendDAO = new CRM_Friend_DAO_Friend();
    $friendDAO->copyValues($params);
    $friendDAO->is_active = $params['is_active'] ?? FALSE;
    $friendDAO->save();

    return $friendDAO;
  }

}
