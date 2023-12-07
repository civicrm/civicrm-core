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
    $group = ['' => ts('- select group -')] + CRM_Core_PseudoConstant::nestedGroup();
    $groupElement = $this->add('select', 'group_id', ts('Select Group'), $group, TRUE, ['class' => 'crm-select2 huge']);

    $this->setTitle(ts('Remove Contacts from Group'));
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
    $defaults = [];

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

    $status = [
      ts("%count contact removed from '%2'", [
        'count' => $removed,
        'plural' => "%count contacts removed from '%2'",
        2 => $group[$groupId],
      ]),
    ];
    if ($notRemoved) {
      $status[] = ts('1 contact was already not in this group', [
        'count' => $notRemoved,
        'plural' => '%count contacts were already not in this group',
      ]);
    }
    $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
    CRM_Core_Session::setStatus($status, ts("Removed Contact From Group", [
      'plural' => "Removed Contacts From Group",
      'count' => $removed,
    ]), 'success');
  }

}
