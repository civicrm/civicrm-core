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
      $this->sendMessage($record);
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

    return [
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

  private function renderRecipient(array $record, ?string $locationType = NULL): array {
    // remove join prefix from contact details
    $contactRecord = [
      'id' => $record['contact_id'],
      'display_name' => $record['contact_id.display_name'],
      'email_primary.email' => $record['contact_id.email_primary.email'],
      'email_primary.on_hold' => $record['contact_id.email_primary.on_hold'],
      'do_not_email' => $record['contact_id.do_not_email'],
      'is_deceased' => $record['contact_id.is_deceased'],
      'is_deleted' => $record['contact_id.is_deleted'],
    ];

    if ($contactRecord["do_not_email"] || $contactRecord["is_deceased"] || $contactRecord["is_deleted"]) {
      throw new \CRM_Core_Exception("Recipient is deceased, deleted, or has opted out from all mailings");
    }

    $name = $contactRecord["display_name"];
    $email = $contactRecord["email_primary.email"];
    $onHold = $contactRecord["email_primary.on_hold"];

    // check for location type specific email
    if ($locationType) {
      $locationEmail = \Civi\Api4\Email::get(FALSE)
        ->addSelect('email')
        ->addWhere('location_type_id:name', '=', $locationType)
        ->addWhere('contact_id', '=', $contactRecord["id"])
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
        \Civi::log('postbox')->debug("Postbox: No active email address for {$contactRecord['id']} with location type {$locationType}. Falling back to primary email.");
      }
    }

    if (!$email || $onHold) {
      throw new \CRM_Core_Exception("Recipient email is blank or on hold so not sending.");
    }

    return [
      'id' => $contactRecord['id'],
      'email' => $email,
      'name' => ($name !== '') ? $name : $email,
      'type' => $record['recipient_type'],
    ];
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
   * @throws \CRM_Core_Exception
   */
  private function sendMessage(array $record): void {

    $fromAddress = $this->renderFromAddress($record);

    $recipientRecords = (array) \Civi\Api4\EmailMessageRecipient::get(FALSE)
      ->addWhere('email_message_id', '=', $record['id'])
      ->addSelect('recipient_type', 'contact_id', 'contact_id.display_name', 'contact_id.email_primary.email', 'contact_id.email_primary.on_hold', 'contact_id.is_deceased', 'contact_id.is_deleted')
      ->execute();

    if (!$recipientRecords) {
      throw new \CRM_Core_Exception(E::ts('No recipients for Email Message %1', [1 => $record['id']]));
    }

    $recipients = array_map(fn ($recipientRecord) => $this->renderRecipient($recipientRecord, $record['location_type']), $recipientRecords);

    $toRecipient = array_find($recipients, fn ($recipient) => ($recipient['type'] === 'to'));

    $message = $this->renderMessage($record);

    // set up the parameters for CRM_Utils_Mail::send
    $mailParams = [
      // from details
      'groupName' => 'Postbox',
      'from' => $fromAddress,
      // recipient details
      'contactId' => $toRecipient['id'],
      'toName' => $toRecipient['name'],
      'toEmail' => $toRecipient['email'],
      // message content
      'subject' => $message['subject'],
      'html' => $message['html'],
      'text' => $message['text'],
    ];

    $ccRecipients = array_filter($recipients, fn ($recipient) => ($recipient['type'] === 'cc'));
    if ($ccRecipients) {
      $mailParams['cc'] = \array_column($ccRecipients, 'email');
    }
    $bccRecipients = array_filter($recipients, fn ($recipient) => ($recipient['type'] === 'bcc'));
    if ($bccRecipients) {
      $mailParams['bcc'] = \array_column($bccRecipients, 'email');
    }

    // Try to send the email.
    $result = \CRM_Utils_Mail::send($mailParams);

    // Falsey return from CRM_Utils_Mail indicates an error
    if (!$result) {
      throw new \CRM_Core_Exception("Error sending email to {$toRecipient['name']} <{$toRecipient['email']}>");
    }
  }

}
