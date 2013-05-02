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
 * This class helps to print the labels for contacts
 *
 */
class CRM_Event_Form_Task_Badge extends CRM_Event_Form_Task {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var boolean
   */
  public $_single = FALSE;

  /**
   * build all the data structures needed to build the form
   *
   * @param
   *
   * @return void
   * @access public
   */ function preProcess() {
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    if ($this->_context == 'view') {
      $this->_single = TRUE;

      $participantID = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
      $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
      $this->_participantIds = array($participantID);
      $this->_componentClause = " civicrm_participant.id = $participantID ";
      $this->assign('totalSelectedParticipants', 1);

      // also set the user context to send back to view page
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/participant',
          "reset=1&action=view&id={$participantID}&cid={$contactID}"
        ));
    }
    else {
      parent::preProcess();
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Make Name Badges'));

    //add select for label
    $label = CRM_Core_OptionGroup::values('event_badge');

    $this->add('select',
      'badge_id',
      ts('Name Badge Format'),
      array(
        '' => ts('- select -')) + $label, TRUE
    );

    $next = 'next';
    $back = $this->_single ? 'cancel' : 'back';
    $this->addDefaultButtons(ts('Make Name Badges'), $next, $back);
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $config = CRM_Core_Config::singleton();


    $returnProperties = CRM_Event_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_EVENT);
    $additionalFields = array('first_name', 'last_name', 'middle_name', 'current_employer');
    foreach ($additionalFields as $field) {
      $returnProperties[$field] = 1;
    }

    if ($this->_single) {
      $queryParams = NULL;
    }
    else {
      $queryParams = $this->get('queryParams');
    }

    $query = new CRM_Contact_BAO_Query($queryParams, $returnProperties, NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_EVENT
    );

    list($select, $from, $where, $having) = $query->query();
    if (empty($where)) {
      $where = "WHERE {$this->_componentClause}";
    }
    else {
      $where .= " AND {$this->_componentClause}";
    }

    $sortOrder = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ORDER)) {
      $sortOrder = $this->get(CRM_Utils_Sort::SORT_ORDER);
      if (!empty($sortOrder)) {
        $sortOrder = " ORDER BY $sortOrder";
      }
    }
    $queryString = "$select $from $where $having $sortOrder";

    $dao = CRM_Core_DAO::executeQuery($queryString);
    $rows = array();
    while ($dao->fetch()) {
      $rows[$dao->participant_id] = array();
      foreach ($returnProperties as $key => $dontCare) {
        $rows[$dao->participant_id][$key] = isset($dao->$key) ? $dao->$key : NULL;
      }
    }

    // get the class name from the participantListingID
    $className = CRM_Core_OptionGroup::getValue('event_badge',
      $params['badge_id'],
      'value',
      'Integer',
      'name'
    );

    $classFile = str_replace('_',
      DIRECTORY_SEPARATOR,
      $className
    ) . '.php';
    $error = include_once ($classFile);
    if ($error == FALSE) {
      CRM_Core_Error::fatal('Event Badge code file: ' . $classFile . ' does not exist. Please verify your custom event badge settings in CiviCRM administrative panel.');
    }

    eval("\$eventBadgeClass = new $className( );");


    $eventBadgeClass->run($rows);
  }
}

