<?php
namespace Civi\FlexMailer\API;

use Civi\FlexMailer\FlexMailer;
use Civi\FlexMailer\FlexMailerTask;

class MailingPreview {

  /**
   * Generate a preview of how a mailing would look.
   *
   * @param array $apiRequest
   *  - entity: string
   *  - action: string
   *  - params: array
   *     - id: int
   *     - contact_id: int
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function preview($apiRequest) {
    $params = $apiRequest['params'];

    /** @var \CRM_Mailing_BAO_Mailing $mailing */
    $mailing = new \CRM_Mailing_BAO_Mailing();
    $mailingID = $params['id'] ?? NULL;
    if ($mailingID) {
      $mailing->id = $mailingID;
      $mailing->find(TRUE);
    }
    else {
      $mailing->copyValues($params);
    }

    $contactID = \CRM_Utils_Array::value('contact_id', $params,
      \CRM_Core_Session::singleton()->get('userID'));

    $job = new class extends \CRM_Mailing_BAO_MailingJob {

      public function insert() {
        throw new \RuntimeException('MailingJob is just a preview. It cannot be saved.');
      }

      public function update($dataObject = FALSE) {
        throw new \RuntimeException('MailingJob is just a preview. It cannot be saved.');
      }

      public function save($hook = TRUE) {
        throw new \RuntimeException('MailingJob is just a preview. It cannot be saved.');
      }

    };
    $job->mailing_id = $mailing->id ?: NULL;
    $job->status = 'Complete';

    $flexMailer = new FlexMailer([
      'is_preview' => TRUE,
      'mailing' => $mailing,
      'job' => $job,
      'attachments' => \CRM_Core_BAO_File::getEntityFile('civicrm_mailing',
        $mailing->id),
    ]);

    if (count($flexMailer->validate()) > 0) {
      throw new \CRM_Core_Exception("FlexMailer cannot execute: invalid context");
    }

    $task = new FlexMailerTask($job->id, $contactID, 'fakehash',
      'placeholder@example.com');

    $flexMailer->fireComposeBatch([$task]);

    return civicrm_api3_create_success([
      'id' => isset($params['id']) ? $params['id'] : NULL,
      'contact_id' => $contactID,
      'subject' => $task->getMailParam('Subject'),
      'body_html' => $task->getMailParam('html'),
      'body_text' => $task->getMailParam('text'),
      // Flag our role in processing this - to support tests.
      '_rendered_by_' => 'flexmailer',
    ]);
  }

}
