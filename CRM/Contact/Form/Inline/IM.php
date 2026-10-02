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
 * Form helper class for an IM object.
 */
class CRM_Contact_Form_Inline_IM extends CRM_Contact_Form_Inline {
  use CRM_Contact_Form_Edit_IMBlockTrait;
  use CRM_Contact_Form_ContactFormTrait;

  /**
   * Is this the contact summary edit screen.
   *
   * @var bool
   */
  protected bool $isContactSummaryEdit = FALSE;

  /**
   * Ims of the contact that is been viewed.
   * @var array
   */
  private array $_ims = [];

  /**
   * No of im blocks for inline edit.
   * @var int
   */
  private int $_blockCount = 6;

  /**
   * Call preprocess.
   */
  public function preProcess(): void {
    parent::preProcess();
    // Get all the existing ims , The array historically starts
    // with 1 not 0 so we do something nasty to continue that.
    $this->_ims = $this->getExistingIMsReIndexed();
  }

  /**
   * Build the form object elements for im object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();

    $totalBlocks = $this->calculateAndAssignBlockCounts(count($this->_ims), $this->_blockCount);

    for ($blockId = 1; $blockId < $totalBlocks; $blockId++) {
      $this->addIMBlockFields($blockId);
    }

    $this->addFormRule(['CRM_Contact_Form_Inline_IM', 'formRule']);
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
    return self::validatePrimaryBlock($fields, 'im');
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues(): array {
    return $this->setBlockDefaultValues($this->_ims, 'im', $this->_blockCount, CRM_Core_BAO_LocationType::getDefault()->id);
  }

  /**
   * Process the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    $params = $this->getSubmittedValues();
    $this->mergeExistingBlockIds($params, $this->_ims, 'im');
    $this->saveIMs($params['im']);

    $this->log();
    $this->response();
  }

}
