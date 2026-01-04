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
 * This class provides the functionality to map the address for group of contacts.
 */
class CRM_Contact_Form_Task_Map_Event extends CRM_Contact_Form_Task_Map {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $ids = CRM_Utils_Request::retrieve('eid', 'Positive',
      $this, TRUE
    );
    $lid = CRM_Utils_Request::retrieve('lid', 'Positive',
      $this, FALSE
    );
    $type = 'Event';
    $this->assign('profileGID');
    $this->assign('showDirectly', FALSE);
    self::createMapXML($ids, $lid, $this, TRUE, $type);
    $this->assign('single', FALSE);
    $this->assign('skipLocationType', TRUE);
    $is_public = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $ids, 'is_public');
    if ($is_public == 0) {
      CRM_Utils_System::setNoRobotsFlag();
    }
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    return 'CRM/Contact/Form/Task/Map.tpl';
  }

}
