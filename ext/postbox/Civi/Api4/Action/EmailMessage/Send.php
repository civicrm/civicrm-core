<?php

namespace Civi\Api4\Action\EmailMessage;

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
      'to_contact_id',
      "to_contact_id.display_name",
      "to_contact_id.email_primary.email",
      "to_contact_id.email_primary.on_hold",
      "to_contact_id.do_not_email",
      "to_contact_id.is_deceased",
      "to_contact_id.is_deleted",
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

  private function renderRecipient(array $contactRecord, ?string $locationType = NULL): array {
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

    // remove join prefix from to contact record details
    $toContactRecord = [
      'id' => $record['to_contact_id'],
      'display_name' => $record["to_contact_id.display_name"],
      'email_primary.email' => $record["to_contact_id.email_primary.email"],
      'email_primary.on_hold' => $record["to_contact_id.email_primary.on_hold"],
      'do_not_email' => $record["to_contact_id.do_not_email"],
      'is_deceased' => $record["to_contact_id.is_deceased"],
      'is_deleted' => $record["to_contact_id.is_deleted"],
    ];

    $recipient = $this->renderRecipient($toContactRecord, $record['location_type']);
    $message = $this->renderMessage($record);

    // set up the parameters for CRM_Utils_Mail::send
    $mailParams = [
      // from details
      'groupName' => 'Postbox',
      'from' => $fromAddress,
      // recipient details
      'contactId' => $recipient['id'],
      'toName' => $recipient['name'],
      'toEmail' => $recipient['email'],
      // message content
      'subject' => $message['subject'],
      'html' => $message['html'],
      'text' => $message['text'],
    ];

    // TODO: support additional contacts in cc / bcc
    // if (!empty($record['extra']) && !empty($record['extra']['cc_contacts'])) {
    //   $mailParams['cc'] = $record['cc_id.email_primary.email'];
    // }

    // Try to send the email.
    $result = \CRM_Utils_Mail::send($mailParams);

    // Falsey return from CRM_Utils_Mail indicates an error
    if (!$result) {
      throw new \CRM_Core_Exception("Error sending email to {$recipient['name']} <{$recipient['email']}>");
    }
  }

}
