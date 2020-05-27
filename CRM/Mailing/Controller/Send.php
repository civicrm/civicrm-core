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
class CRM_Mailing_Controller_Send extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param null $title
   * @param bool|int $action
   * @param bool $modal
   *
   * @throws \Exception
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {
    parent::__construct($title, $modal, NULL, FALSE, TRUE);

    // New:            civicrm/mailing/send?reset=1
    // Re-use:         civicrm/mailing/send?reset=1&mid=%%mid%%
    // Continue:       civicrm/mailing/send?reset=1&mid=%%mid%%&continue=true
    $mid = CRM_Utils_Request::retrieve('mid', 'Positive');
    $continue = CRM_Utils_Request::retrieve('continue', 'String');
    if (!$mid) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/mailing/new'));
    }
    if ($mid && $continue) {
      //CRM-15979 - check if abtest exist for mailing then redirect accordingly
      $abtest = CRM_Mailing_BAO_MailingAB::getABTest($mid);
      if (!empty($abtest) && !empty($abtest->id)) {
        $redirect = CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/abtest/' . $abtest->id);
      }
      else {
        $redirect = CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/mailing/' . $mid);
      }
      CRM_Utils_System::redirect($redirect);
    }
    if ($mid && !$continue) {
      $clone = civicrm_api3('Mailing', 'clone', ['id' => $mid]);
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/mailing/' . $clone['id']));
    }
  }

}
