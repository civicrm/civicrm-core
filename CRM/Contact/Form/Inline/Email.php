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
    // with 1 not 0 so we do something nasty to continue that.
    $this->_emails = array_merge([0 => 1], (array) $this->getExistingEmails());
    unset($this->_emails[0]);

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

    $totalBlocks = $this->_blockCount;
    $actualBlockCount = 1;
    if (count($this->_emails) > 1) {
      $actualBlockCount = $totalBlocks = count($this->_emails);
      if ($totalBlocks < $this->_blockCount) {
        $additionalBlocks = $this->_blockCount - $totalBlocks;
        $totalBlocks += $additionalBlocks;
      }
      else {
        $actualBlockCount++;
        $totalBlocks++;
      }
    }

    $this->assign('actualBlockCount', $actualBlockCount);
    $this->assign('totalBlocks', $totalBlocks);

    $this->applyFilter('__ALL__', 'trim');

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
    $hasData = $hasPrimary = $errors = [];
    if (!empty($fields['email']) && is_array($fields['email'])) {
      foreach ($fields['email'] as $instance => $blockValues) {
        $dataExists = CRM_Contact_Form_Contact::blockDataExists($blockValues);

        if ($dataExists) {
          $hasData[] = $instance;
          if (!empty($blockValues['is_primary'])) {
            $hasPrimary[] = $instance;
          }
        }
      }

      if (empty($hasPrimary) && !empty($hasData)) {
        $errors["email[1][is_primary]"] = ts('One email should be marked as primary.');
      }

      if (count($hasPrimary) > 1) {
        $errors["email[" . array_pop($hasPrimary) . "][is_primary]"] = ts('Only one email can be marked as primary.');
      }
    }
    if (!$hasData && !$form->contactHasName) {
      $errors["email[1][email]"] = ts('Contact with no name must have an email.');
    }
    return $errors;
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if (!empty($this->_emails)) {
      foreach ($this->_emails as $id => $value) {
        $defaults['email'][$id] = $value;
      }
    }
    else {
      // get the default location type
      $defaults['email'][1]['location_type_id'] = CRM_Core_BAO_LocationType::getDefault()->id;
    }

    return $defaults;
  }

  /**
   * Process the form.
   *
   * @throws CRM_Core_Exception
   */
  public function postProcess(): void {
    $params = $this->exportValues();

    // Process / save emails
    foreach ($this->_emails as $count => $value) {
      if (!empty($value['id']) && isset($params['email'][$count])) {
        $params['email'][$count]['id'] = $value['id'];
      }
    }
    $this->saveEmails($params['email']);

    // Changing email might change a contact's display_name so refresh name block content
    if (!$this->contactHasName) {
      $this->ajaxResponse['reloadBlocks'] = ['#crm-contactname-content'];
    }

    $this->log();
    $this->response();
  }

}
