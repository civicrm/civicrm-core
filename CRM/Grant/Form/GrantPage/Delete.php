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
 * This class is to build the form for Deleting Group
 */
class CRM_Grant_Form_GrantPage_Delete extends CRM_Grant_Form_GrantPage {

  /**
   * page title
   *
   * @var string
   * @protected
   */
  protected $_title;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //Check if there are contributions related to Contribution Page

    parent::preProcess();

    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviGrant', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
  
    $this->_title = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $this->_id, 'title');
    $this->assign('title', $this->_title);

    $buttons = array();
    $buttons[] = array(
      'type' => 'next',
      'name' => ts('Delete Grant Application Page'),
      'isDefault' => TRUE,
    );

    $buttons[] = array(
      'type' => 'cancel',
      'name' => ts('Cancel'),
    );

    $this->addButtons($buttons);
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $transaction = new CRM_Core_Transaction();

    // first delete the join entries associated with this grant application page
    $dao = new CRM_Core_DAO_UFJoin();

    $params = array(
      'entity_table' => 'civicrm_grant_app_page',
      'entity_id' => $this->_id,
    );
    $dao->copyValues($params);
    $dao->delete();
           
    // finally delete the grant application page
    $dao = new CRM_Grant_DAO_GrantApplicationPage();
    $dao->id = $this->_id;
    $dao->delete();

    $transaction->commit();

    CRM_Core_Session::setStatus(ts('The Grant Application page \'%1\' has been deleted.', array(1 => $this->_title)));
  }
}

