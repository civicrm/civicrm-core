<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * State machine for managing different states of the Import process.
 *
 */
class CRM_Mailing_StateMachine_Send extends CRM_Core_StateMachine {

  /**
   * class constructor
   *
   * @param object $controller
   * @param \const|int $action
   *
   * @internal param \CRM_Mailing_Controller $object
   * @return \CRM_Mailing_StateMachine_Send CRM_Mailing_StateMachine
   */
  function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = array(
      'CRM_Mailing_Form_Group' => NULL,
      'CRM_Mailing_Form_Settings' => NULL,
      'CRM_Mailing_Form_Upload' => NULL,
      'CRM_Mailing_Form_Test' => NULL,
    );

    if (CRM_Mailing_Info::workflowEnabled()) {
      if (CRM_Core_Permission::check('schedule mailings')) {
        $this->_pages['CRM_Mailing_Form_Schedule'] = NULL;
      }

      if (CRM_Core_Permission::check('approve mailings')) {
        $this->_pages['CRM_Mailing_Form_Approve'] = NULL;
      }
    }
    else {
      $this->_pages['CRM_Mailing_Form_Schedule'] = NULL;
    }

    $this->addSequentialPages($this->_pages, $action);
  }
}

