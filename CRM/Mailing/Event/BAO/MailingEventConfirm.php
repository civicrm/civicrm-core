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

use Civi\Token\TokenProcessor;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Mailing_Event_BAO_Confirm
 */
class CRM_Mailing_Event_BAO_MailingEventConfirm extends CRM_Mailing_Event_DAO_MailingEventConfirm {

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
   * @return bool|string
   *   FALSE on failure, group frontend title on success.
   * @throws \CRM_Core_Exception
   */
  public static function confirm(int $contact_id, int $subscribe_id, string $hash) {
    $se = CRM_Mailing_Event_BAO_MailingEventSubscribe::verify(
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
    if ($details && $details->status === 'Added') {
      // This contact is already subscribed
      // lets return the group title
      return CRM_Core_DAO::getFieldValue(
        'CRM_Contact_DAO_Group',
        $se->group_id,
        'frontend_title'
      );
    }

    $transaction = new CRM_Core_Transaction();

    $ce = new CRM_Mailing_Event_BAO_MailingEventConfirm();
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

    [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail();

    [$display_name, $email] = CRM_Contact_BAO_Contact_Location::getEmailDetails($se->contact_id);

    $group = new CRM_Contact_DAO_Group();
    $group->id = $se->group_id;
    $group->find(TRUE);

    $component = new CRM_Mailing_BAO_MailingComponent();
    $component->is_default = 1;
    $component->is_active = 1;
    $component->component_type = 'Welcome';

    // we should return early if welcome email temaplate is disabled
    // this means confirmation email will not be sent
    if (!$component->find(TRUE)) {
      return $group->frontend_title;
    }

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
    $templates = $bao->getTemplates();

    // We can stop doing this here once it has been done in an upgrade script.
    $html = str_replace('{welcome.group}', '{group.frontend_title}', $templates['html']);
    $text = str_replace('{welcome.group}', '{group.frontend_title}', $templates['text']);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['contactId', 'groupId'],
    ]);

    $tokenProcessor->addMessage('body_html', $html, 'text/html');
    $tokenProcessor->addMessage('body_text', $text, 'text/plain');
    $tokenProcessor->addRow(['contactId' => $contact_id, 'groupId' => $group->id]);
    $tokenProcessor->evaluate();
    $html = $tokenProcessor->getRow(0)->render('body_html');
    $text = $tokenProcessor->getRow(0)->render('body_text');

    $mailParams = [
      'groupName' => 'Mailing Event ' . $component->component_type,
      'subject' => $component->subject,
      'from' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
      'toEmail' => $email,
      'toName' => $display_name,
      'replyTo' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
      'returnPath' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
      'html' => $html,
      'text' => $text,
      'contactId' => $contact_id,
    ];
    // send - ignore errors because the desired status change has already been successful
    CRM_Utils_Mail::send($mailParams);

    return $group->frontend_title;
  }

}
