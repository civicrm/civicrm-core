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
 * Indicate that a CiviMail message has been opened
 *
 * General Usage: civicrm/mailing/open?qid={event_queue_id}
 *
 * NOTE: The parameter name has changed slightly from 'extern/open.php?q={event_queue_id}`.
 */
class CRM_Mailing_Page_Open extends CRM_Core_Page {

  /**
   * Mark the mailing as opened
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $queue_id = CRM_Utils_Request::retrieveValue('qid', 'Positive', NULL, FALSE, 'GET');
    if (!$queue_id) {
      // Deprecated: "?q=" is problematic in Drupal integrations, but we'll accept if igiven
      $queue_id = CRM_Utils_Request::retrieveValue('q', 'Positive', NULL, FALSE, 'GET');
    }
    if (!$queue_id) {
      CRM_Utils_System::sendInvalidRequestResponse(ts("Missing input parameters"));
    }

    CRM_Mailing_Event_BAO_MailingEventOpened::open($queue_id);

    $filename = Civi::paths()->getPath('[civicrm.root]/i/tracker.gif');

    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-type: image/gif');
    header('Content-Length: ' . filesize($filename));
    header('Content-Disposition: inline; filename=tracker.gif');

    readfile($filename);

    CRM_Utils_System::civiExit();
  }

}
