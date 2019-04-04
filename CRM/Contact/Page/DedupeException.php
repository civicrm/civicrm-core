<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Main page for viewing contact.
 */
class CRM_Contact_Page_DedupeException extends CRM_Core_Page {
  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->initializePager();
    $this->assign('exceptions', $this->getExceptions());
    return parent::run();
  }

  /**
   * Method to initialize pager
   *
   * @access protected
   */
  protected function initializePager() {
    $params = array();

    $contactOneQ = CRM_Utils_Request::retrieve('crmContact1Q', 'String');

    if ($contactOneQ) {
      $params['contact_id1.display_name'] = array('LIKE' => '%' . $contactOneQ . '%');
      $params['contact_id2.display_name'] = array('LIKE' => '%' . $contactOneQ . '%');

      $params['options']['or'] = [["contact_id1.display_name", "contact_id2.display_name"]];
    }

    $totalitems = civicrm_api3('Exception', "getcount", $params);
    $params           = array(
      'total' => $totalitems,
      'rowCount' => CRM_Utils_Pager::ROWCOUNT,
      'status' => ts('Dedupe Exceptions %%StatusMessage%%'),
      'buttonBottom' => 'PagerBottomButton',
      'buttonTop' => 'PagerTopButton',
      'pageID' => $this->get(CRM_Utils_Pager::PAGE_ID),
    );
    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  /**
   * Function to get the exceptions
   *
   * @return array $exceptions
   * @access protected
   */
  protected function getExceptions() {
    list($offset, $limit) = $this->_pager->getOffsetAndRowCount();
    $contactOneQ = CRM_Utils_Request::retrieve('crmContact1Q', 'String');

    if (!$contactOneQ) {
      $contactOneQ = '';
    }

    $this->assign('searchcontact1', $contactOneQ);

    $params = array(
      "options"     => array('limit' => $limit, 'offset' => $offset),
      'return' => ["contact_id1.display_name", "contact_id2.display_name", "contact_id1", "contact_id2"],
    );

    if ($contactOneQ != '') {
      $params['contact_id1.display_name'] = array('LIKE' => '%' . $contactOneQ . '%');
      $params['contact_id2.display_name'] = array('LIKE' => '%' . $contactOneQ . '%');

      $params['options']['or'] = [["contact_id1.display_name", "contact_id2.display_name"]];
    }

    $exceptions = civicrm_api3("Exception", "get", $params);
    $exceptions = $exceptions["values"];
    return $exceptions;
  }

}
