<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: Event.php 45499 2013-02-08 12:31:05Z kurund $
 *
 */

/**
 * This class provides the functionality to map
 * the address for group of
 * contacts.
 */
class CRM_Contact_Form_Task_Map_Event extends CRM_Contact_Form_Task_Map {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $ids = CRM_Utils_Request::retrieve('eid', 'Positive',
      $this, TRUE
    );
    $lid = CRM_Utils_Request::retrieve('lid', 'Positive',
      $this, FALSE
    );
    $type = 'Event';
    self::createMapXML($ids, $lid, $this, TRUE, $type);
    $this->assign('single', FALSE);
    $this->assign('skipLocationType', TRUE);
  }

  function getTemplateFileName() {
    return 'CRM/Contact/Form/Task/Map.tpl';
  }
}

