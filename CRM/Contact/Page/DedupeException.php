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
 * $Id$
 *
 */

/**
 * Main page for viewing contact.
 *
 */
class CRM_Contact_Page_DedupeException extends CRM_Core_Page {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    //fetch the dedupe exception contacts.
    $dedupeExceptions = array();

    $exception = new CRM_Dedupe_DAO_Exception();
    $exception->find();
    $contactIds = array();
    while ($exception->fetch()) {
      $key = "{$exception->contact_id1}_{$exception->contact_id2}";
      $contactIds[$exception->contact_id1] = $exception->contact_id1;
      $contactIds[$exception->contact_id2] = $exception->contact_id2;
      $dedupeExceptions[$key] = array('main' => array('id' => $exception->contact_id1),
        'other' => array('id' => $exception->contact_id2),
      );
    }
    //get the dupe contacts display names.
    if (!empty($dedupeExceptions)) {
      $sql          = 'select id, display_name from civicrm_contact where id IN ( ' . implode(', ', $contactIds) . ' )';
      $contact      = CRM_Core_DAO::executeQuery($sql);
      $displayNames = array();
      while ($contact->fetch()) {
        $displayNames[$contact->id] = $contact->display_name;
      }
      foreach ($dedupeExceptions as $key => & $values) {
        $values['main']['name'] = CRM_Utils_Array::value($values['main']['id'], $displayNames);
        $values['other']['name'] = CRM_Utils_Array::value($values['other']['id'], $displayNames);
      }
    }
    $this->assign('dedupeExceptions', $dedupeExceptions);
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->preProcess();
    return parent::run();
  }
}

