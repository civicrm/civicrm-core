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
 * Class CRM_Mailing_Event_BAO_Confirm
 */
class CRM_Mailing_Event_BAO_Confirm extends CRM_Mailing_Event_DAO_Confirm {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Confirm a pending subscription.
   *
   * @param int $contact_id
   *   The id of the contact.
   * @param int $subscribe_id
   *   The id of the subscription event.
   * @param string $hash
   *   The hash.
   *
   * @return bool
   *   True on success
   */
  public static function confirm($contact_id, $subscribe_id, $hash) {
    $se = &CRM_Mailing_Event_BAO_Subscribe::verify(
      $contact_id,
      $subscribe_id,
      $hash
    );

    if (!$se) {
      return FALSE;
    }

    // before we proceed lets just check if this contact is already 'Added'
    // if so, we should ignore this request and hence avoid sending multiple
    // emails - CRM-11157
    $details = CRM_Contact_BAO_GroupContact::getMembershipDetail($contact_id, $se->group_id);
    if ($details && $details->status == 'Added') {
      // This contact is already subscribed
      // lets return the group title
      return CRM_Core_DAO::getFieldValue(
        'CRM_Contact_DAO_Group',
        $se->group_id,
        'title'
      );
    }

    $transaction = new CRM_Core_Transaction();

    $ce = new CRM_Mailing_Event_BAO_Confirm();
    $ce->event_subscribe_id = $se->id;
    $ce->time_stamp = date('YmdHis');
    $ce->save();

    CRM_Contact_BAO_GroupContact::addContactsToGroup(
      [$contact_id],
      $se->group_id,
      'Email',
      'Added',
      $ce->id
    );

    $transaction->commit();

    $config = CRM_Core_Config::singleton();

    $domain = CRM_Core_BAO_Domain::getDomain();
    list($domainEmailName, $_) = CRM_Core_BAO_Domain::getNameAndEmail();

    list($display_name, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($se->contact_id);

    $group = new CRM_Contact_DAO_Group();
    $group->id = $se->group_id;
    $group->find(TRUE);

    $component = new CRM_Mailing_BAO_MailingComponent();
    $component->is_default = 1;
    $component->is_active = 1;
    $component->component_type = 'Welcome';

    $component->find(TRUE);

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
    $html = CRM_Utils_Token::replaceWelcomeTokens($html, $group->title, TRUE);

    $text = CRM_Utils_Token::replaceDomainTokens($text, $domain, FALSE, $tokens['text']);
    $text = CRM_Utils_Token::replaceWelcomeTokens($text, $group->title, FALSE);

    $mailParams = [
      'groupName' => 'Mailing Event ' . $component->component_type,
      'subject' => $component->subject,
      'from' => "\"$domainEmailName\" <" . CRM_Core_BAO_Domain::getNoReplyEmailAddress() . '>',
      'toEmail' => $email,
      'toName' => $display_name,
      'replyTo' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
      'returnPath' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
      'html' => $html,
      'text' => $text,
    ];
    // send - ignore errors because the desired status change has already been successful
    $unused_result = CRM_Utils_Mail::send($mailParams);

    return $group->title;
  }

}
