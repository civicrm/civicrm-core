<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class provides the functionality to delete a group of
 * contacts. This class provides functionality for the actual
 * addition of contacts to groups.
 */
class CRM_Contact_Form_Task_RemoveFromGroup extends CRM_Contact_Form_Task {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // add select for groups
    $group = array('' => ts('- select group -')) + CRM_Core_PseudoConstant::nestedGroup();
    $groupElement = $this->add('select', 'group_id', ts('Select Group'), $group, TRUE, array('class' => 'crm-select2 huge'));

    CRM_Utils_System::setTitle(ts('Remove Contacts from Group'));
    $this->addDefaultButtons(ts('Remove from Group'));
  }

  /**
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = array();

    if ($this->get('context') === 'smog') {
      $defaults['group_id'] = $this->get('gid');
    }
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $groupId = $this->controller->exportValue('RemoveFromGroup', 'group_id');
    $group = CRM_Core_PseudoConstant::group();

    list($total, $removed, $notRemoved) = CRM_Contact_BAO_GroupContact::removeContactsFromGroup($this->_contactIds, $groupId);

    $status = array(
      ts("%count contact removed from '%2'", array(
        'count' => $removed,
        'plural' => "%count contacts removed from '%2'",
        2 => $group[$groupId],
      )),
    );
    if ($notRemoved) {
      $status[] = ts('1 contact was already not in this group', array(
          'count' => $notRemoved,
          'plural' => '%count contacts were already not in this group',
        ));
    }
    $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
    CRM_Core_Session::setStatus($status, ts("Removed Contact From Group", array(
          'plural' => "Removed Contacts From Group",
          'count' => $removed,
        )), 'success', array('expires' => 0));
  }

}
