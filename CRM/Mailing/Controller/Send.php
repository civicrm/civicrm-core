<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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
      $clone = civicrm_api3('Mailing', 'clone', array('id' => $mid));
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/mailing/' . $clone['id']));
    }
  }

}
