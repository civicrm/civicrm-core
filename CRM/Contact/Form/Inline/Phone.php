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
 * Form helper class for an Phone object.
 */
class CRM_Contact_Form_Inline_Phone extends CRM_Contact_Form_Inline {

  use CRM_Contact_Form_Edit_PhoneBlockTrait;
  use CRM_Contact_Form_ContactFormTrait;

  /**
   * Phones of the contact that is been viewed
   * @var array
   */
  private array $_phones = [];

  /**
   * No of phone blocks for inline edit
   * @var int
   */
  private int $_blockCount = 6;

  /**
   * Call preprocess.
   */
  public function preProcess(): void {
    parent::preProcess();
    // Get all the existing phones , The array historically starts
    // with 1 not 0.
    $this->_phones = $this->getExistingPhonesReIndexed();
  }

  /**
   * Build the form object elements for phone object.
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();

    $totalBlocks = $this->calculateAndAssignBlockCounts(count($this->_phones), $this->_blockCount);

    for ($blockId = 1; $blockId < $totalBlocks; $blockId++) {
      $this->addPhoneBlockFields($blockId);
    }

    $this->addFormRule(['CRM_Contact_Form_Inline_Phone', 'formRule']);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $errors
   *   List of errors to be posted back to the form.
   *
   * @return array
   */
  public static function formRule($fields, $errors) {
    return self::validatePrimaryBlock($fields, 'phone');
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues(): array {
    return $this->setBlockDefaultValues($this->_phones, 'phone', $this->_blockCount, CRM_Core_BAO_LocationType::getDefault()->id);
  }

  /**
   * Process the form.
   */
  public function postProcess(): void {
    $params = $this->getSubmittedValues();

    $this->mergeExistingBlockIds($params, $this->_phones, 'phone');
    $this->savePhones($params['phone']);

    $this->log();
    $this->response();
  }

}
