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
class CRM_Case_Page_CaseDetails extends CRM_Core_Page {

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->_action  = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $type           = CRM_Utils_Request::retrieve('type', 'String', CRM_Core_DAO::$_nullObject);

    $this->assign('action', $this->_action);
    $this->assign('context', $this->_context);

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    $caseId = CRM_Utils_Request::retrieve('caseId', 'Positive', $this);

    CRM_Case_Page_Tab::setContext();

    $params = array('date_range' => 0);

    $caseDetails = array();
    if (CRM_Case_BAO_Case::accessCiviCase()) {
      $caseDetails = CRM_Case_BAO_Case::getCaseActivity($caseId, $params, $this->_contactId, NULL, NULL, $type);
    }

    $this->assign('rows', $caseDetails);
    $this->assign('caseId', $caseId);
    $this->assign('contactId', $this->_contactId);

    // Make it easy to refresh this table
    $params = array(
      'caseId' => $caseId,
      'type' => $type,
      'context' => $this->_context,
      'cid' => $this->_contactId,
      'action' => $this->_action,
      'snippet' => 4,
    );
    $this->assign('data_params', json_encode($params));

    return parent::run();
  }
}

