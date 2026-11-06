<?php

namespace Civi\Api4\Action\EmailMessage;

use CRM_Postbox_ExtensionUtil as E;

/**
 * Send an unsent email message record
 */
class Send extends \Civi\Api4\Generic\BasicBatchAction {

  protected function getSelect(): array {
    return [
      'id',
      'date_sent',
      'subject',
      'body',
      'from_site_email_address_id.email',
      'from_site_email_address_id.display_name',
      //'from_email',
      //'from_name',
      'location_type',
      'extra',
    ];
  }

  /**
   * @param array $record
   * @return array
   */
  protected function doTask($record): array {
    if ($record['date_sent']) {
      \Civi::log()->debug("Unable to send EmailMessage {$record['id']} - already sent!");
      return [
        'status' => 'sent',
        'message' => 'Already sent!',
      ];
    }

    try {
      $sendDetails = $this->sendMessage($record);
    }
    catch (\CRM_Core_Exception $e) {
      $error = $e->getMessage();

      \Civi\Api4\EmailMessage::update(FALSE)
        ->addWhere('id', '=', $record['id'])
        ->addValue('error_message', $error)
        ->execute();

      return [
        'status' => 'error',
        'message' => $error,
      ];
    }

    \Civi\Api4\EmailMessage::update(FALSE)
      ->addWhere('id', '=', $record['id'])
      ->addValue('date_sent', 'now')
      ->execute();

    // save final email addresses
    \Civi\Api4\EmailMessageRecipient::save(FALSE)
      ->setRecords(array_map(fn ($recipient) => [
        'id' => $recipient['id'],
        'email_address' => $recipient['email_address'],
      ], $sendDetails['recipients']))
      ->setMatch(['id'])
      ->execute();

    return [
      'id' => $record['id'],
      'status' => 'sent',
    ];
  }

  private function renderFromAddress(array $record) {
    // preserve non-null values passed to from_name / from_email
    // $fromName = $record['from_name'] ?: $record['from_site_email_address_id.display_name'];
    // $fromEmail = $record['from_email'] ?: $record['from_site_email_address_id.email'];

    $fromName = $record['from_site_email_address_id.display_name'];
    $fromEmail = $record['from_site_email_address_id.email'];

    if (!$fromName || !$fromEmail) {
      $default = \Civi\Api4\SiteEmailAddress::get(FALSE)
        ->addWhere('is_default', '=', TRUE)
        ->execute()
        ->single();

      $fromName = $fromName ?: $default['display_name'];
      $fromEmail = $fromEmail ?: $default['email'];

      if (!$fromName || !$fromEmail) {
        throw new \CRM_Core_Exception("Could not determine from name or email");
      }
    }

    return '"' . $fromName . '" <' . $fromEmail . '>';
  }

  private function checkDoNotEmail(array $recipient): void {
    if ($recipient['contact_id.do_not_email'] || $recipient['contact_id.is_deceased'] || $recipient['contact_id.is_deleted']) {
      throw new \CRM_Core_Exception("Recipient is deceased, deleted, or has opted out from all mailings");
    }
  }

  private function lookupEmailAddress(array $recipient, ?string $locationType = NULL): string {
    $email = $recipient['contact_id.email_primary.email'];
    $onHold = $recipient['contact_id.email_primary.on_hold'];

    // check for location type specific email
    if ($locationType) {
      $locationEmail = \Civi\Api4\Email::get(FALSE)
        ->addSelect('email')
        ->addWhere('location_type_id:name', '=', $locationType)
        ->addWhere('contact_id', '=', $recipient['contact_id'])
        ->addWhere('on_hold', '=', FALSE)
        ->addOrderBy('id', 'DESC')
        ->execute()
        ->first()['email'] ?? NULL;

      if ($locationEmail) {
        $email = $locationEmail;
        // we only fetched not on hold emails
        $onHold = FALSE;
      }
      else {
        \Civi::log('postbox')->debug("Postbox: No active email address for {$recipient['contact_id']} with location type {$locationType}. Falling back to primary email.");
      }
    }

    if (!$email || $onHold) {
      throw new \CRM_Core_Exception("Recipient email is blank or on hold so not sending.");
    }

    return $email;
  }

  private function renderMessage(array $record): array {
    $message = [
      'subject' => $record['subject'],
      'html' => $record['body'],
      'text' => \CRM_Utils_String::htmlToText($record['body']),
    ];
    // render the &amp; entities in text mode, so that the links work
    $message['text'] = str_replace('&amp;', '&', $message['text']);
    return $message;
  }

  /**
   * Attempt to send email for a given record
   *
   * @return array details of send
   * @throws \CRM_Core_Exception
   */
  private function sendMessage(array $record): array {

    $fromAddress = $this->renderFromAddress($record);

    $recipients = (array) \Civi\Api4\EmailMessageRecipient::get(FALSE)
      ->addWhere('email_message_id', '=', $record['id'])
      ->addSelect(
        'recipient_type',
        'email_address',
        'contact_id',
        'contact_id.display_name',
        'contact_id.email_primary.email',
        'contact_id.email_primary.on_hold',
        'contact_id.is_deceased',
        'contact_id.is_deleted',
        'contact_id.do_not_email'
      )
      ->execute();

    foreach ($recipients as &$recipient) {
      // check for do not emails
      $this->checkDoNotEmail($recipient);

      if (!$recipient['email_address']) {
        $recipient['email_address'] = $this->lookupEmailAddress($recipient, $record['location_type']);
      }

      // if email is specified directly, there may be no contact display name
      // so fall back to using the raw email address
      $recipient['display_name'] = $recipient['contact_id.display_name'] ?? $recipient['email_address'];
    }

    // NOTE: we currently only support a single TO recipient, due to the constraints
    // of CRM_Utils_Mail::send. Additional TO recipients will be ignored
    $toRecipient = array_find($recipients, fn ($recipient) => ($recipient['recipient_type'] === 'to'));

    if (!$toRecipient) {
      throw new \CRM_Core_Exception(E::ts('No TO recipients found for Email Message %1', [1 => $record['id']]));
    }

    $message = $this->renderMessage($record);

    // set up the parameters for CRM_Utils_Mail::send
    $mailParams = [
      // from details
      'groupName' => 'Postbox',
      'from' => $fromAddress,
      // recipient details
      'contactId' => $toRecipient['contact_id'],
      'toName' => $toRecipient['display_name'],
      'toEmail' => $toRecipient['email_address'],
      // message content
      'subject' => $message['subject'],
      'html' => $message['html'],
      'text' => $message['text'],
    ];

    $ccRecipients = array_filter($recipients, fn ($recipient) => ($recipient['recipient_type'] === 'cc'));
    if ($ccRecipients) {
      $mailParams['cc'] = \implode(',', \array_column($ccRecipients, 'email_address'));
    }
    $bccRecipients = array_filter($recipients, fn ($recipient) => ($recipient['recipient_type'] === 'bcc'));
    if ($bccRecipients) {
      $mailParams['bcc'] = \implode(',', \array_column($bccRecipients, 'email_address'));
    }

    // Try to send the email.
    $result = \CRM_Utils_Mail::send($mailParams);

    // Falsey return from CRM_Utils_Mail indicates an error
    if (!$result) {
      throw new \CRM_Core_Exception("Error sending email to {$toRecipient['name']} <{$toRecipient['email']}>");
    }

    return [
      'recipients' => $recipients,
    ];
  }

}
