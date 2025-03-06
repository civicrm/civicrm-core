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
 * This is page is for Event Dashboard
 */
class CRM_Event_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   */
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviEvent'));

    $eventSummary = CRM_Event_BAO_Event::getEventSummary();
    $eventSummary['tab'] = CRM_Event_Page_ManageEvent::tabs();

    $actionColumn = FALSE;
    if (!empty($eventSummary) &&
      isset($eventSummary['events']) &&
      is_array($eventSummary['events'])
    ) {
      foreach ($eventSummary['events'] as $e) {
        if (isset($e['isMap']) || isset($e['configure'])) {
          $actionColumn = TRUE;
          break;
        }
      }
    }

    $this->assign('actionColumn', $actionColumn);
    $this->assign('eventSummary', $eventSummary);
    $this->assign('iCal', CRM_Event_BAO_Event::getICalLinks());
    $this->assign('isShowICalIconsInline', FALSE);
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    $controller = new CRM_Core_Controller_Simple('CRM_Event_Form_Search', ts('events'), NULL);
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('limit', 10);
    $controller->set('force', 1);
    $controller->set('context', 'dashboard');
    // last 7 days including today
    $_GET['participant_register_date_relative'] = 'ending.week';
    $controller->process();
    $controller->run();

    return parent::run();
  }

}
