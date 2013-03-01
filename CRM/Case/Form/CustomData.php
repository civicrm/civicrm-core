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
 * This class generates form components for custom data
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Case_Form_CustomData extends CRM_Core_Form {

  /**
   * The entity id, used when editing/creating custom data
   *
   * @var int
   */
  protected $_entityID;

  /**
   * The custom data type
   *
   * @var int
   */
  protected $_cdType;

  /**
   * entity sub type of the table id
   *
   * @var string
   * @access protected
   */
  protected $_subTypeID;

  /**
   * pre processing work done here.
   *
   * gets session variables for table name, id of entity in table, type of entity and stores them.
   *
   * @param
   *
   * @return void
   *
   * @access public
   *
   */
  function preProcess() {
    $this->_groupID   = CRM_Utils_Request::retrieve('groupID', 'Positive', $this, TRUE);
    $this->_entityID  = CRM_Utils_Request::retrieve('entityID', 'Positive', $this, TRUE);
    $this->_subTypeID = CRM_Utils_Request::retrieve('subType', 'Positive', $this, TRUE);
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Case',
      $this,
      $this->_entityID,
      $this->_groupID,
      $this->_subTypeID
    );
    // simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $this);
    foreach ($groupTree as $groupValues) {
      $this->_customTitle = $groupValues['title'];
    }

    $this->_defaults = array();
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $this->_defaults);
    $this->setDefaults($this->_defaults);

    CRM_Core_BAO_CustomGroup::buildQuickForm($this, $groupTree, FALSE, 1);

    //need to assign custom data type and subtype to the template
    $this->assign('entityID', $this->_entityID);
    $this->assign('groupID', $this->_groupID);
    $this->assign('subType', $this->_subTypeID);
    $this->assign('contactID', $this->_contactID);
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc
    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
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
   * Process the user submitted custom data values.
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $fields = array();

    $transaction = new CRM_Core_Transaction();

    CRM_Core_BAO_CustomValueTable::postProcess($params,
      $fields,
      'civicrm_case',
      $this->_entityID,
      'Case'
    );

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/case', "reset=1&id={$this->_entityID}&cid={$this->_contactID}&action=view"));

    $session        = CRM_Core_Session::singleton();
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type', 'Change Custom Data', 'name');
    $activityParams = array(
      'activity_type_id' => $activityTypeID,
      'source_contact_id' => $session->get('userID'),
      'is_auto' => TRUE,
      'subject' => $this->_customTitle . " : change data",
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
        'Completed',
        'name'
      ),
      'target_contact_id' => $this->_contactID,
      'details' => json_encode($this->_defaults),
      'activity_date_time' => date('YmdHis'),
    );
    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    $caseParams = array(
      'activity_id' => $activity->id,
      'case_id' => $this->_entityID,
    );
    CRM_Case_BAO_Case::processCaseActivity($caseParams);

    $transaction->commit();
  }
}

