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

    $this->assign('events', $info);

    return parent::run();
  }

}
