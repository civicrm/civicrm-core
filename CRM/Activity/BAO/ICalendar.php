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
 * Generate ical invites for activities.
 */
class CRM_Activity_BAO_ICalendar {

  /**
   * The activity for which we're generating ical.
   *
   * @var CRM_Activity_BAO_Activity
   */
  protected $activity;

  /**
   * Path to temporary unique file,
   * to hold generated .ics file
   *
   * @var string
   */
  protected $icsfile;

  /**
   * Constructor.
   *
   * @param CRM_Activity_BAO_Activity $act
   *   Reference to an activity object.
   */
  public function __construct(&$act) {
    $this->activity = $act;
  }

  /**
   * Add an ics attachment to the input array.
   *
   * @param array $attachments
   *   Reference to array in same format returned from CRM_Core_BAO_File::getEntityFile().
   * @param array $contacts
   *   Array of contacts (attendees).
   *
   * @return string|null
   *   Array index of the added attachment in the $attachments array, else NULL.
   */
  public function addAttachment(&$attachments, $contacts) {
    // Check preferences setting
    if (Civi::settings()->get('activity_assignee_notification_ics')) {
      $this->icsfile = tempnam(CRM_Core_Config::singleton()->customFileUploadDir, 'ics');
      if ($this->icsfile !== FALSE) {
        rename($this->icsfile, $this->icsfile . '.ics');
        $this->icsfile .= '.ics';
        $icsFileName = basename($this->icsfile);

        // get logged in user's primary email
        // TODO: Is there a better way to do this?
        $organizer = $this->getPrimaryEmail();

        $template = CRM_Core_Smarty::singleton();
        $template->assign('activity', $this->activity);
        $template->assign('organizer', $organizer);
        $template->assign('contacts', $contacts);
        $template->assign('timezone', date_default_timezone_get());
        $calendar = $template->fetch('CRM/Activity/Calendar/ICal.tpl');
        if (file_put_contents($this->icsfile, $calendar) !== FALSE) {
          if (empty($attachments)) {
            $attachments = [];
          }
          $attachments['activity_ics'] = [
            'mime_type' => 'text/calendar',
            'fileName' => $icsFileName,
            'cleanName' => $icsFileName,
            'fullPath' => $this->icsfile,
          ];
          return 'activity_ics';
        }
      }
    }
    return NULL;
  }

  /**
   * Remove temp file.
   */
  public function cleanup() {
    if (!empty($this->icsfile)) {
      @unlink($this->icsfile);
    }
  }

  /**
   * @todo Is there a better way to do this?
   * @return string
   */
  private function getPrimaryEmail() {
    $uid = CRM_Core_Session::getLoggedInContactID();
    $primary = '';
    $emails = CRM_Core_BAO_Email::allEmails($uid);
    foreach ($emails as $eid => $e) {
      if ($e['is_primary']) {
        if ($e['email']) {
          $primary = $e['email'];
          break;
        }
      }

      if (count($emails) == 1) {
        $primary = $e['email'];
        break;
      }
    }
    return $primary;
  }

}
