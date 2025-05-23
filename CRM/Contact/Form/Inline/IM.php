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

    $totalBlocks = $this->_blockCount;
    $actualBlockCount = 1;
    if (count($this->_ims) > 1) {
      $actualBlockCount = $totalBlocks = count($this->_ims);
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
    $hasData = $hasPrimary = $errors = [];
    if (!empty($fields['im']) && is_array($fields['im'])) {
      foreach ($fields['im'] as $instance => $blockValues) {
        $dataExists = CRM_Contact_Form_Contact::blockDataExists($blockValues);

        if ($dataExists) {
          $hasData[] = $instance;
          if (!empty($blockValues['is_primary'])) {
            $hasPrimary[] = $instance;
            if (!$primaryID && !empty($blockValues['im'])) {
              $primaryID = $blockValues['im'];
            }
          }
        }
      }

      if (empty($hasPrimary) && !empty($hasData)) {
        $errors["im[1][is_primary]"] = ts('One IM should be marked as primary.');
      }

      if (count($hasPrimary) > 1) {
        $errors["im[" . array_pop($hasPrimary) . "][is_primary]"] = ts('Only one IM can be marked as primary.');
      }
    }
    return $errors;
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues(): array {
    $defaults = [];
    if (!empty($this->_ims)) {
      foreach ($this->_ims as $id => $value) {
        $defaults['im'][$id] = $value;
      }
    }
    else {
      // get the default location type
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $defaults['im'][1]['location_type_id'] = $locationType->id;
    }
    return $defaults;
  }

  /**
   * Process the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    $params = $this->getSubmittedValues();
    foreach ($this->_ims as $count => $value) {
      if (!empty($value['id']) && isset($params['im'][$count])) {
        $params['im'][$count]['id'] = $value['id'];
      }
    }
    $this->saveIMs($params['im']);

    $this->log();
    $this->response();
  }

}
