<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Generate ical invites for activities.
 */
class CRM_Activity_BAO_ICalendar {

  /**
   * @var object The activity for which we're generating ical.
   */
  protected $activity;

  /**
   * Constructor.
   *
   * @param object $act
   *   Reference to an activity object.
   *
   * @return \CRM_Activity_BAO_ICalendar
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
      $config = &CRM_Core_Config::singleton();
      $this->icsfile = tempnam($config->customFileUploadDir, 'ics');
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
