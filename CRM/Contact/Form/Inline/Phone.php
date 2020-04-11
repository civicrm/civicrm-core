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

  /**
   * Phones of the contact that is been viewed
   * @var array
   */
  private $_phones = [];

  /**
   * No of phone blocks for inline edit
   * @var int
   */
  private $_blockCount = 6;

  /**
   * Call preprocess.
   */
  public function preProcess() {
    parent::preProcess();

    //get all the existing phones
    $phone = new CRM_Core_BAO_Phone();
    $phone->contact_id = $this->_contactId;

    $this->_phones = CRM_Core_BAO_Block::retrieveBlock($phone, NULL);
  }

  /**
   * Build the form object elements for phone object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $totalBlocks = $this->_blockCount;
    $actualBlockCount = 1;
    if (count($this->_phones) > 1) {
      $actualBlockCount = $totalBlocks = count($this->_phones);
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
      CRM_Contact_Form_Edit_Phone::buildQuickForm($this, $blockId, TRUE);
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
    $hasData = $hasPrimary = $errors = [];
    if (!empty($fields['phone']) && is_array($fields['phone'])) {
      $primaryID = NULL;
      foreach ($fields['phone'] as $instance => $blockValues) {
        $dataExists = CRM_Contact_Form_Contact::blockDataExists($blockValues);

        if ($dataExists) {
          $hasData[] = $instance;
          if (!empty($blockValues['is_primary'])) {
            $hasPrimary[] = $instance;
            if (!$primaryID && !empty($blockValues['phone'])) {
              $primaryID = $blockValues['phone'];
            }
          }
        }
      }

      if (empty($hasPrimary) && !empty($hasData)) {
        $errors["phone[1][is_primary]"] = ts('One phone should be marked as primary.');
      }

      if (count($hasPrimary) > 1) {
        $errors["phone[" . array_pop($hasPrimary) . "][is_primary]"] = ts('Only one phone can be marked as primary.');
      }
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
    if (!empty($this->_phones)) {
      foreach ($this->_phones as $id => $value) {
        $defaults['phone'][$id] = $value;
      }
    }
    else {
      // get the default location type
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $defaults['phone'][1]['location_type_id'] = $locationType->id;
    }
    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    // Process / save phones
    $params['contact_id'] = $this->_contactId;
    $params['updateBlankLocInfo'] = TRUE;
    $params['phone']['isIdSet'] = TRUE;
    foreach ($this->_phones as $count => $value) {
      if (!empty($value['id']) && isset($params['phone'][$count])) {
        $params['phone'][$count]['id'] = $value['id'];
      }
    }
    CRM_Core_BAO_Block::create('phone', $params);

    $this->log();
    $this->response();
  }

}
