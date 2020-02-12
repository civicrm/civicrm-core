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


require_once 'Mail/mime.php';

/**
 * Class CRM_Mailing_Event_BAO_Subscribe
 */
class CRM_Mailing_Event_BAO_Subscribe extends CRM_Mailing_Event_DAO_Subscribe {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Register a subscription event.  Create a new contact if one does not
   * already exist.
   *
   * @param int $group_id
   *   The group id to subscribe to.
   * @param string $email
   *   The email address of the (new) contact.
   * @param int $contactId
   *   Currently used during event registration/contribution.
   *   Specifically to avoid linking group to wrong duplicate contact
   *   during event registration.
   * @param string $context
   *
   * @return int|null
   *   $se_id      The id of the subscription event, null on failure
   */
  public static function &subscribe($group_id, $email, $contactId = NULL, $context = NULL) {
    // CRM-1797 - allow subscription only to public groups
    $params = ['id' => (int) $group_id];
    $defaults = [];
    $contact_id = NULL;
    $success = NULL;

    $bao = CRM_Contact_BAO_Group::retrieve($params, $defaults);
    if ($bao && substr($bao->visibility, 0, 6) != 'Public' && $context != 'profile') {
      return $success;
    }

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $email = $strtolower($email);

    // process the query only if no contactId
    if ($contactId) {
      $contact_id = $contactId;
    }
    else {
      // First, find out if the contact already exists.

      $query = "
   SELECT DISTINCT contact_a.id as contact_id
     FROM civicrm_contact contact_a
LEFT JOIN civicrm_email      ON contact_a.id = civicrm_email.contact_id
    WHERE civicrm_email.email = %1 AND contact_a.is_deleted = 0";

      $params = [1 => [$email, 'String']];
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      // lets just use the first contact id we got
      if ($dao->fetch()) {
        $contact_id = $dao->contact_id;
      }
    }

    $transaction = new CRM_Core_Transaction();

    if (!$contact_id) {
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $formatted = [
        'contact_type' => 'Individual',
        'email' => $email,
        'location_type_id' => $locationType->id,
      ];

      $formatted['onDuplicate'] = CRM_Import_Parser::DUPLICATE_SKIP;
      $formatted['fixAddress'] = TRUE;
      $contact = civicrm_api3('contact', 'create', $formatted);
      if (civicrm_error($contact)) {
        return $success;
      }
      $contact_id = $contact['id'];
    }
    elseif (!is_numeric($contact_id) &&
      (int ) $contact_id > 0
    ) {
      // make sure contact_id is numeric
      return $success;
    }

    // Get the primary email id from the contact to use as a hash input.
    $query = "
SELECT     civicrm_email.id as email_id
  FROM     civicrm_email
     WHERE civicrm_email.email = %1
       AND civicrm_email.contact_id = %2";
    $params = [
      1 => [$email, 'String'],
      2 => [$contact_id, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if (!$dao->fetch()) {
      throw new CRM_Core_Exception('Please file an issue with the backtrace');
      return $success;
    }

    $se = new CRM_Mailing_Event_BAO_Subscribe();
    $se->group_id = $group_id;
    $se->contact_id = $contact_id;
    $se->time_stamp = date('YmdHis');
    $se->hash = substr(sha1("{$group_id}:{$contact_id}:{$dao->email_id}:" . time()),
      0, 16
    );
    $se->save();

    $contacts = [$contact_id];
    CRM_Contact_BAO_GroupContact::addContactsToGroup($contacts, $group_id,
      'Email', 'Pending', $se->id
    );

    $transaction->commit();
    return $se;
  }

  /**
   * Verify the hash of a subscription event.
   *
   * @param int $contact_id
   *   ID of the contact.
   * @param int $subscribe_id
   *   ID of the subscription event.
   * @param string $hash
   *   Hash to verify.
   *
   * @return object|null
   *   The subscribe event object, or null on failure
   */
  public static function &verify($contact_id, $subscribe_id, $hash) {
    $success = NULL;
    $se = new CRM_Mailing_Event_BAO_Subscribe();
    $se->contact_id = $contact_id;
    $se->id = $subscribe_id;
    $se->hash = $hash;
    if ($se->find(TRUE)) {
      $success = $se;
    }
    return $success;
  }

  /**
   * Ask a contact for subscription confirmation (opt-in)
   *
   * @param string $email
   *   The email address.
   */
  public function send_confirm_request($email) {
    $config = CRM_Core_Config::singleton();

    $domain = CRM_Core_BAO_Domain::getDomain();

    //get the default domain email address.
    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

    $confirm = implode($config->verpSeparator,
        [
          $localpart . 'c',
          $this->contact_id,
          $this->id,
          $this->hash,
        ]
      ) . "@$emailDomain";

    $group = new CRM_Contact_BAO_Group();
    $group->id = $this->group_id;
    $group->find(TRUE);

    $component = new CRM_Mailing_BAO_MailingComponent();
    $component->is_default = 1;
    $component->is_active = 1;
    $component->component_type = 'Subscribe';

    $component->find(TRUE);

    $headers = [
      'Subject' => $component->subject,
      'From' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
      'To' => $email,
      'Reply-To' => $confirm,
      'Return-Path' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
    ];

    $url = CRM_Utils_System::url('civicrm/mailing/confirm',
      "reset=1&cid={$this->contact_id}&sid={$this->id}&h={$this->hash}",
      TRUE, NULL, TRUE, TRUE
    );

    $html = $component->body_html;

    if ($component->body_text) {
      $text = $component->body_text;
    }
    else {
      $text = CRM_Utils_String::htmlToText($component->body_html);
    }

    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->body_text = $text;
    $bao->body_html = $html;
    $tokens = $bao->getTokens();

    $html = CRM_Utils_Token::replaceDomainTokens($html, $domain, TRUE, $tokens['html']);
    $html = CRM_Utils_Token::replaceSubscribeTokens($html,
      $group->title,
      $url, TRUE
    );

    $text = CRM_Utils_Token::replaceDomainTokens($text, $domain, FALSE, $tokens['text']);
    $text = CRM_Utils_Token::replaceSubscribeTokens($text,
      $group->title,
      $url, FALSE
    );
    // render the &amp; entities in text mode, so that the links work
    $text = str_replace('&amp;', '&', $text);

    $message = new Mail_mime("\n");

    $message->setHTMLBody($html);
    $message->setTxtBody($text);
    $b = CRM_Utils_Mail::setMimeParams($message);
    $h = $message->headers($headers);
    CRM_Mailing_BAO_Mailing::addMessageIdHeader($h, 's',
      $this->contact_id,
      $this->id,
      $this->hash
    );
    $mailer = \Civi::service('pear_mail');

    if (is_object($mailer)) {
      $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
      $mailer->send($email, $h, $b);
      unset($errorScope);
    }
  }

  /**
   * Get the domain object given a subscribe event.
   *
   * @param int $subscribe_id
   *   ID of the subscribe event.
   *
   * @return object
   *   $domain       The domain owning the event
   */
  public static function &getDomain($subscribe_id) {
    return CRM_Core_BAO_Domain::getDomain();
  }

  /**
   * Get the group details to which given email belongs.
   *
   * @param string $email
   *   Email of the contact.
   * @param int $contactID
   *   ContactID if we want an exact match.
   *
   * @return array
   *   array of group ids
   */
  public static function getContactGroups($email, $contactID = NULL) {
    if ($contactID) {
      $query = "
                 SELECT DISTINCT group_a.group_id, group_a.status, civicrm_group.title
                 FROM civicrm_group_contact group_a
                 LEFT JOIN civicrm_group ON civicrm_group.id = group_a.group_id
                 LEFT JOIN civicrm_contact ON ( group_a.contact_id = civicrm_contact.id )
                 WHERE civicrm_contact.id = %1";

      $params = [1 => [$contactID, 'Integer']];
    }
    else {
      $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
      $email = $strtolower($email);

      $query = "
                 SELECT DISTINCT group_a.group_id, group_a.status, civicrm_group.title
                 FROM civicrm_group_contact group_a
                 LEFT JOIN civicrm_group ON civicrm_group.id = group_a.group_id
                 LEFT JOIN civicrm_contact ON ( group_a.contact_id = civicrm_contact.id ) AND civicrm_contact.is_deleted = 0
                 LEFT JOIN civicrm_email ON civicrm_contact.id = civicrm_email.contact_id
                 WHERE civicrm_email.email = %1";

      $params = [1 => [$email, 'String']];
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $groups = [];
    while ($dao->fetch()) {
      $groups[$dao->group_id] = [
        'id' => $dao->group_id,
        'title' => $dao->title,
        'status' => $dao->status,
      ];
    }

    return $groups;
  }

  /**
   * Send subscribe mail.
   *
   * @param array $groups
   *   The list of group ids for subscribe.
   * @param array $params
   *   The list of email.
   * @param int $contactId
   *   Currently used during event registration/contribution.
   *   Specifically to avoid linking group to wrong duplicate contact
   *   during event registration.
   * @param string $context
   */
  public static function commonSubscribe(&$groups, &$params, $contactId = NULL, $context = NULL) {
    $contactGroups = CRM_Mailing_Event_BAO_Subscribe::getContactGroups($params['email'], $contactId);
    $group = [];
    $success = NULL;
    foreach ($groups as $groupID) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $groupID, 'title');
      if (array_key_exists($groupID, $contactGroups) && $contactGroups[$groupID]['status'] != 'Removed') {
        $group[$groupID]['title'] = $contactGroups[$groupID]['title'];

        $group[$groupID]['status'] = $contactGroups[$groupID]['status'];
        $status = ts('You are already subscribed in %1, your subscription is %2.', [
          1 => $group[$groupID]['title'],
          2 => ts($group[$groupID]['status']),
        ]);
        CRM_Utils_System::setUFMessage($status);
        continue;
      }

      $se = self::subscribe($groupID,
        $params['email'], $contactId, $context
      );
      if ($se !== NULL) {
        $success = TRUE;
        $groupAdded[] = $title;

        // Ask the contact for confirmation
        $se->send_confirm_request($params['email']);
      }
      else {
        $success = FALSE;
        $groupFailed[] = $title;
      }
    }
    if ($success) {
      $groupTitle = implode(', ', $groupAdded);
      CRM_Utils_System::setUFMessage(ts('Your subscription request has been submitted for %1. Check your inbox shortly for the confirmation email(s). If you do not see a confirmation email, please check your spam/junk mail folder.', [1 => $groupTitle]));
    }
    elseif ($success === FALSE) {
      $groupTitle = implode(',', $groupFailed);
      CRM_Utils_System::setUFMessage(ts('We had a problem processing your subscription request for %1. You have tried to subscribe to a private group and/or we encountered a database error. Please contact the site administrator.', [1 => $groupTitle]));
    }
  }

}
