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
 * This class generates form components for DedupeRules.
 */
class CRM_Contact_Form_DedupeFind extends CRM_Admin_Form {

  /**
   *  Indicate if this form should warn users of unsaved changes
   * @var bool
   */
  protected $unsavedChangesWarn = FALSE;

  /**
   * Pre processing.
   */
  public function preProcess() {
    $this->rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE, 0);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $groupList = ['' => ts('- All Contacts -')] + CRM_Core_PseudoConstant::nestedGroup();

    $this->add('select', 'group_id', ts('Select Group'), $groupList, FALSE, ['class' => 'crm-select2 huge']);
    if (Civi::settings()->get('dedupe_default_limit')) {
      $this->add('text', 'limit', ts('No of contacts to find matches for '));
    }
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Continue'),
        'isDefault' => TRUE,
      ],
      //hack to support cancel button functionality
      [
        'type' => 'submit',
        'class' => 'cancel',
        'icon' => 'fa-times',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Set the default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $this->_defaults['limit'] = Civi::settings()->get('dedupe_default_limit');
    return $this->_defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $values = $this->exportValues();
    if (!empty($_POST['_qf_DedupeFind_submit'])) {
      //used for cancel button
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/deduperules', 'reset=1'));
      return;
    }
    $url = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&action=update&rgid={$this->rgid}");
    if ($values['group_id']) {
      $url .= "&gid={$values['group_id']}";
    }

    if (!empty($values['limit'])) {
      $url .= '&limit=' . $values['limit'];
    }

    CRM_Utils_System::redirect($url);
  }

}
