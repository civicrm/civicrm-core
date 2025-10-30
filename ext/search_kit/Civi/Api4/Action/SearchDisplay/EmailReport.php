<?php
namespace Civi\Api4\Action\SearchDisplay;

/**
 * Leverages the saveFile action to save the SearchDisplay to a file,
 * and then emails one or more contacts with the saved file as an attachment.
 *
 * @package Civi\Api4\Action\SearchDisplay
 */
class EmailReport extends SaveFile {

  /**
   * A single Contact ID or multiple Contact ID's separated by comma.
   * @var string
   * @required
   */
  protected $contactID;

  /**
   * The subject of the email.
   *
   * @var string
   * @required
   */
  protected $subject;

  /**
   * The ID of the message template to be used for the email.
   *
   * @var int
   * @required
   */
  protected $templateID;

  /**
   * Send copy to email address (cc)
   *
   * @var string
   */
  protected $cc;

  /**
   * Send blind copy to email address (bcc)
   *
   * @var string
   */
  protected $bcc;

  /**
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   *
   * @return void
   */
  protected function processResult(\Civi\Api4\Result\SearchDisplayRunResult $result) {
    parent::processResult($result);

    if (!empty($result['file']) && !empty($result['file']->uri)) {
      $attachment = [
        'fullPath' => $result['file']->uri,
        'mime_type' => $result['file']->mime_type,
        'cleanName' => $result['file']->description,
      ];

      if (!\CRM_Utils_Type::validate($this->contactID, 'CommaSeparatedIntegers')) {
        throw new \API_Exception('Parameter contact_id must be a unique id or a list of ids separated by comma');
      }

      $contactIDs = explode(',', $this->contactID);

      $messageTemplates = new \CRM_Core_DAO_MessageTemplate();
      $messageTemplates->id = $this->templateID;

      if (!$messageTemplates->find(TRUE)) {
        throw new \API_Exception('Could not find template with ID: ' . $this->templateID);
      }

      [$defaultFromName, $defaultFromEmail] = \CRM_Core_BAO_Domain::getNameAndEmail();
      $from = "\"$defaultFromName\" <$defaultFromEmail>";

      $returnValues = [];
      for ($i = 0; $i < count($contactIDs); $i++) {
        $contactId = $contactIDs[$i];
        $contact = \Civi\Api4\Contact::get(TRUE)
          ->addSelect('*', 'email_primary.email', 'email_primary.on_hold')
          ->addWhere('id', '=', $contactId)
          ->setLimit(1)
          ->execute()->first();
        if (!$contact || $contact['do_not_email'] || empty($contact['email_primary.email']) || \CRM_Utils_Array::value('is_deceased', $contact) || $contact['email_primary.on_hold'] || $contact['is_deleted']) {
          /*
           * Contact is deceased or has opted out from mailings so do not send the email
           */
          continue;
        }
        else {
          $toName = $contact['display_name'];
          $toEmail = $contact['email_primary.email'];
        }

        $tokenContext = [
          'contactId' => $contactId,
        ];

        // set up the parameters for CRM_Utils_Mail::send
        $mailParams = [
          'groupName' => 'SearchDisplay_EmailReport',
          'from' => $from,
          'toName' => $toName,
          'toEmail' => $toEmail,
          'messageTemplate' => [
            'msg_subject' => $this->subject,
          ],
          'messageTemplateID' => $messageTemplates->id,
          'contactId' => $contactId,
          'attachments' => [$attachment],
          'tokenContext' => $tokenContext,
        ];

        if (!empty($this->cc)) {
          $mailParams['cc'] = $this->cc;
        }
        if (!empty($this->bcc)) {
          $mailParams['bcc'] = $this->bcc;
        }

        // Try to send the email.
        [$emailResult] = \CRM_Core_BAO_MessageTemplate::sendTemplate($mailParams);
        if (!$emailResult) {
          throw new \API_Exception('Error sending email to ' . $contact['display_name'] . ' <' . $toEmail . '> ');
        }

        $result[$contactId] = [
          'contact_id' => $contactId,
          'send' => 1,
          'status_msg' => "Successfully sent email to {$toEmail}",
        ];
      }
    }
  }

}
