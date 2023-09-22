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
 * This class is for building event(participation) block on user dashboard
 */
class CRM_Event_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * List participations for the UF user.
   *
   */
  public function listParticipations() {
    $event_rows = [];

    $participants = \Civi\Api4\Participant::get(FALSE)
      ->addSelect('id', 'contact_id', 'status_id:name', 'status_id:label', 'event_id', 'event_id.title', 'event_id.start_date', 'event_id.end_date')
      ->addWhere('contact_id', '=', $this->_contactId)
      ->addOrderBy('event_id.start_date', 'DESC')
      ->execute()
      ->indexBy('id');

    // Flatten the results in the format expected by the template
    foreach ($participants as $p) {
      $p['participant_id'] = $p['id'];
      $p['status'] = $p['status_id:name'];
      $p['participant_status'] = $p['status_id:label'];
      $p['event_id'] = $p['event_id'];
      $p['event_title'] = $p['event_id.title'];
      $p['event_start_date'] = $p['event_id.start_date'];
      $p['event_end_date'] = $p['event_id.end_date'];

      $event_rows[] = $p;
    }

    $this->assign('event_rows', $event_rows);
  }

  /**
   * the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   *
   */
  public function run() {
    parent::preProcess();
    $this->listParticipations();
  }

}
