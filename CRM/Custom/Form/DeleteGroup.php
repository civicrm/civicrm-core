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
 * This class is to build the form for Deleting Group
 */
class CRM_Custom_Form_DeleteGroup extends CRM_Core_Form {

  /**
   * The group id.
   *
   * @var int
   */
  protected $_id;

  /**
   * The title of the group being deleted.
   *
   * @var string
   */
  protected $_title;

  /**
   * Set up variables to build the form.
   *
   * @return void
   * @access protected
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

    $defaults = [];
    $params = ['id' => $this->_id];
    CRM_Core_BAO_CustomGroup::retrieve($params, $defaults);
    $this->_title = $defaults['title'];

    //check if this contains any custom fields
    $customField = new CRM_Core_DAO_CustomField();
    $customField->custom_group_id = $this->_id;

    if ($customField->find(TRUE)) {
      CRM_Core_Error::statusBounce(ts("The Group '%1' cannot be deleted! You must Delete all custom fields in this group prior to deleting the group.", [1 => $this->_title]),
        CRM_Utils_System::url('civicrm/admin/custom/group', "reset=1"));
      return TRUE;
    }
    $this->assign('title', $this->_title);

    $this->setTitle(ts('Confirm Custom Group Delete'));
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Delete Custom Group'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Process the form when submitted.
   *
   * @return void
   */
  public function postProcess() {
    $group = new CRM_Core_DAO_CustomGroup();
    $group->id = $this->_id;
    $group->find(TRUE);

    $wt = CRM_Utils_Weight::delWeight('CRM_Core_DAO_CustomGroup', $this->_id);
    CRM_Core_BAO_CustomGroup::deleteGroup($group);
    CRM_Core_Session::setStatus(ts("The Group '%1' has been deleted.", [1 => $group->title]), '', 'success');
  }

}
