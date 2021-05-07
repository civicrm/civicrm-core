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
 * Display a list of events on a page
 */
class CRM_Event_Page_List extends CRM_Core_Page {

  public function run() {
    $id = CRM_Utils_Request::retrieveValue('id', 'Positive', NULL, FALSE, 'GET');
    $type = CRM_Utils_Request::retrieveValue('type', 'Positive', 0);
    $start = CRM_Utils_Request::retrieveValue('start', 'Positive', 0);
    $end = CRM_Utils_Request::retrieveValue('end', 'Positive', 0);

    $info = CRM_Event_BAO_Event::getCompleteInfo($start, $type, $id, $end);

    foreach ($info as &$event) {
      $event['start_date_utc'] = CRM_Utils_Date::convertTimeZone($event['start_date'], 'UTC');
      $event['start_date'] = CRM_Utils_Date::convertTimeZone($event['start_date'], $event['tz']);

      $event['end_date_utc'] = !empty($event['end_date']) ? CRM_Utils_Date::convertTimeZone($event['end_date'], 'UTC') : NULL;
      $event['end_date'] = !empty($event['end_date']) ? CRM_Utils_date::convertTimeZone($event['end_date'], $event['tz']) : NULL;

      $event['registration_start_date_utc'] = !empty($event['registration_start_date']) ? CRM_Utils_Date::convertTimeZone($event['registration_start_date'], 'UTC') : NULL;
      $event['registration_start_date'] = !empty($event['registration_start_date']) ? CRM_Utils_date::convertTimeZone($event['registration_start_date'], $event['tz']) : NULL;

      $event['registration_end_date_utc'] = !empty($event['registration_end_date']) ? CRM_Utils_Date::convertTimeZone($event['registration_end_date'], 'UTC') : NULL;
      $event['registration_end_date'] = !empty($event['registration_end_date']) ? CRM_Utils_date::convertTimeZone($event['registration_end_date'], $event['tz']) : NULL;
    }

    $this->assign('events', $info);

    // @todo Move this to eventcart extension
    // check if we're in shopping cart mode for events
    if ((bool) Civi::settings()->get('enable_cart')) {
      $this->assign('registration_links', TRUE);
    }

    return parent::run();
  }

}
