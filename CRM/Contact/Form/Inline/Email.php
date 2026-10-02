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
 * Form helper class for an Email object.
 */
class CRM_Contact_Form_Inline_Email extends CRM_Contact_Form_Inline {
  use CRM_Contact_Form_Edit_EmailBlockTrait;
  use CRM_Contact_Form_ContactFormTrait;

  /**
   * Email addresses of the contact that is been viewed.
   * @var array
   */
  private array $_emails = [];

  /**
   * No of email blocks for inline edit.
   * @var int
   */
  private int $_blockCount = 6;

  /**
   * Whether this contact has a first/last/organization/household name
   *
   * @var bool
   */
  public $contactHasName;

  /**
   * Call preprocess.
   * @throws CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();
    $this->_contactId = $this->getContactID();

    // Get all the existing email addresses, The array historically starts
    // with 1 not 0.
    $this->_emails = $this->getExistingEmailsReIndexed();

    // Check if this contact has a first/last/organization/household name
    if ($this->getContactValue('contact_type') === 'Individual') {
      $this->contactHasName = (bool) ($this->getContactValue('last_name')
        || $this->getContactValue('first_name'));
    }
    else {
      $this->contactHasName = (bool) $this->getContactValue(strtolower($this->getContactValue('contact_type')) . '_name');
    }
  }

  /**
   * Build the form object elements for an email object.
   * @throws CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();

    $totalBlocks = $this->calculateAndAssignBlockCounts(count($this->_emails), $this->_blockCount);

    for ($blockId = 1; $blockId < $totalBlocks; $blockId++) {
      $this->addEmailBlockFields($blockId);
    }

    $this->addFormRule(['CRM_Contact_Form_Inline_Email', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $errors
   *   List of errors to be posted back to the form.
   * @param CRM_Contact_Form_Inline_Email $form
   *
   * @return array
   */
  public static function formRule($fields, $errors, $form) {
    $errors = self::validatePrimaryBlock($fields, 'email');
    if (!$form->contactHasName && !self::hasBlockData($fields, 'email')) {
      $errors['email[1][email]'] = ts('Contact with no name must have an email.');
    }
    return $errors;
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    return $this->setBlockDefaultValues($this->_emails, 'email', $this->_blockCount, CRM_Core_BAO_LocationType::getDefault()->id);
  }

  /**
   * Process the form.
   *
   * @throws CRM_Core_Exception
   */
  public function postProcess(): void {
    $params = $this->getSubmittedValues();

    $this->mergeExistingBlockIds($params, $this->_emails, 'email');
    $this->saveEmails($params['email']);

    // Changing email might change a contact's display_name so refresh name block content
    if (!$this->contactHasName) {
      $this->ajaxResponse['reloadBlocks'] = ['#crm-contactname-content'];
    }

    $this->log();
    $this->response();
  }

}
