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
 * Form helper class for an OpenID object.
 */
class CRM_Contact_Form_Inline_OpenID extends CRM_Contact_Form_Inline {
  use CRM_Contact_Form_Edit_OpenIDBlockTrait;
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
  private $_openids = [];

  /**
   * No of openid blocks for inline edit.
   * @var int
   */
  private $_blockCount = 6;

  /**
   * Call preprocess.
   */
  public function preProcess() {
    parent::preProcess();
    // Get all the existing ims , The array historically starts
    // with 1 not 0.
    $this->_openids = $this->getExistingOpenIDsReIndexed();
  }

  /**
   * Build the form object elements for openID object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $totalBlocks = $this->_blockCount;
    $actualBlockCount = 1;
    if (count($this->_openids) > 1) {
      $actualBlockCount = $totalBlocks = count($this->_openids);
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
      $this->addOpenIDBlockFields($blockId);;
    }

    $this->addFormRule(['CRM_Contact_Form_Inline_OpenID', 'formRule']);
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
    if (!empty($fields['openid']) && is_array($fields['openid'])) {
      foreach ($fields['openid'] as $instance => $blockValues) {
        $dataExists = CRM_Contact_Form_Contact::blockDataExists($blockValues);

        if ($dataExists) {
          $hasData[] = $instance;
          if (!empty($blockValues['is_primary'])) {
            $hasPrimary[] = $instance;
            if (!$primaryID && !empty($blockValues['openid'])) {
              $primaryID = $blockValues['openid'];
            }
          }
        }
      }

      if (empty($hasPrimary) && !empty($hasData)) {
        $errors["openid[1][is_primary]"] = ts('One OpenID should be marked as primary.');
      }

      if (count($hasPrimary) > 1) {
        $errors["openid[" . array_pop($hasPrimary) . "][is_primary]"] = ts('Only one OpenID can be marked as primary.');
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
    if (!empty($this->_openids)) {
      foreach ($this->_openids as $id => $value) {
        $defaults['openid'][$id] = $value;
      }
    }
    else {
      // get the default location type
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $defaults['openid'][1]['location_type_id'] = $locationType->id;
    }
    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess(): void {
    $params = $this->getSubmittedValues();

    // Process / save openID
    foreach ($this->_openids as $count => $value) {
      if (!empty($value['id']) && isset($params['openid'][$count])) {
        $params['openid'][$count]['id'] = $value['id'];
      }
    }
    $this->saveOpenIDss($params['openid']);

    $this->log();
    $this->response();
  }

}
