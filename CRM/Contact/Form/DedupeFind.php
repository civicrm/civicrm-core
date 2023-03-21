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
   * Indicate if this form should warn users of unsaved changes
   * @var bool
   */
  protected $unsavedChangesWarn = FALSE;

  /**
   * Dedupe rule group ID.
   *
   * @var int
   */
  protected $dedupeRuleGroupID;

  /**
   * Pre processing.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    $this->dedupeRuleGroupID = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE, 0);
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {

    $groupList = ['' => ts('- All Contacts -')] + CRM_Core_PseudoConstant::nestedGroup();

    $this->add('select', 'group_id', ts('Select Group'), $groupList, FALSE, ['class' => 'crm-select2 huge']);
    $this->add('text', 'limit', ts('No of contacts to find matches for '));

    // To improve usability for smaller sites, we don't show the limit field unless a default limit has been set.
    $this->assign('limitShown', (bool) Civi::settings()->get('dedupe_default_limit'));

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
  public function setDefaultValues(): array {
    $this->_defaults['limit'] = Civi::settings()->get('dedupe_default_limit');
    return $this->_defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess(): void {
    $values = $this->exportValues();
    if (!empty($_POST['_qf_DedupeFind_submit'])) {
      //used for cancel button
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/deduperules', 'reset=1'));
      return;
    }
    $url = CRM_Utils_System::url('civicrm/contact/dedupefind', 'reset=1&action=update&rgid=' . $this->getDedupeRuleGroupID());
    if ($values['group_id']) {
      $url .= "&gid={$values['group_id']}";
    }

    if (!empty($values['limit'])) {
      $url .= '&limit=' . $values['limit'];
    }

    CRM_Utils_System::redirect($url);
  }

  /**
   * Get the rule group ID passed in by the url.
   *
   * @todo  - could this ever really be NULL - the retrieveValue does not
   * use $abort so maybe.
   *
   * @return int|null
   */
  public function getDedupeRuleGroupID(): ?int {
    return $this->dedupeRuleGroupID;
  }

}
