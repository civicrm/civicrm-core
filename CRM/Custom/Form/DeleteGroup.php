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
 * This class is to build the form for Deleting Group
 */
class CRM_Custom_Form_DeleteGroup extends CRM_Core_Form {

  /**
   * the group id
   *
   * @var int
   */
  protected $_id;

  /**
   * The title of the group being deleted
   *
   * @var string
   */
  protected $_title;

  /**
   * set up variables to build the form
   *
   * @return void
   * @acess protected
   */ 
  function preProcess() {
    $this->_id = $this->get('id');

    $defaults = array();
    $params = array('id' => $this->_id);
    CRM_Core_BAO_CustomGroup::retrieve($params, $defaults);
    $this->_title = $defaults['title'];

    //check wheter this contain any custom fields
    $customField = new CRM_Core_DAO_CustomField();
    $customField->custom_group_id = $this->_id;

    if ($customField->find(TRUE)) {
      CRM_Core_Session::setStatus(ts("The Group '%1' cannot be deleted! You must Delete all custom fields in this group prior to deleting the group.", array(1 => $this->_title)), ts('Deletion Error'), 'error');
      $url = CRM_Utils_System::url('civicrm/admin/custom/group', "reset=1");
      CRM_Utils_System::redirect($url);
      return TRUE;
    }
    $this->assign('title', $this->_title);

    CRM_Utils_System::setTitle(ts('Confirm Custom Group Delete'));
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Delete Custom Group'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $group = new CRM_Core_DAO_CustomGroup();
    $group->id = $this->_id;
    $group->find(TRUE);

    $wt = CRM_Utils_Weight::delWeight('CRM_Core_DAO_CustomGroup', $this->_id);
    CRM_Core_BAO_CustomGroup::deleteGroup($group);
    CRM_Core_Session::setStatus(ts("The Group '%1' has been deleted.", array(1 => $group->title)), '', 'success');
  }
}

