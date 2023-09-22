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
 * Form helper class for an Website object,
 */
class CRM_Contact_Form_Inline_Website extends CRM_Contact_Form_Inline {

  /**
   * Websitess of the contact that is been viewed.
   * @var array
   */
  private $_websites = [];

  /**
   * No of website blocks for inline edit.
   * @var int
   */
  private $_blockCount = 26;

  /**
   * Call preprocess.
   */
  public function preProcess() {
    parent::preProcess();

    //get all the existing websites
    $params = ['contact_id' => $this->_contactId];
    $values = [];
    $this->_websites = CRM_Core_BAO_Website::getValues($params, $values);
  }

  /**
   * Build the form object elements for website object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $totalBlocks = $this->_blockCount;
    $actualBlockCount = 1;
    if (count($this->_websites) > 1) {
      $actualBlockCount = $totalBlocks = count($this->_websites);
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
      CRM_Contact_Form_Edit_Website::buildQuickForm($this, $blockId, TRUE);
    }

    $this->addFormRule(['CRM_Contact_Form_Inline_Website', 'formRule'], $this);

  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if (!empty($this->_websites)) {
      foreach ($this->_websites as $id => $value) {
        $defaults['website'][$id] = $value;
      }
    }
    else {
      // set the default website type
      $defaults['website'][1]['website_type_id'] = key(CRM_Core_OptionGroup::values('website_type',
        FALSE, FALSE, FALSE, ' AND is_default = 1'
      ));
    }
    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    foreach ($this->_websites as $count => $value) {
      if (!empty($value['id']) && isset($params['website'][$count])) {
        $params['website'][$count]['id'] = $value['id'];
      }
    }
    // Process / save websites
    CRM_Core_BAO_Website::process($params['website'], $this->_contactId, TRUE);

    $this->log();
    $this->response();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $errors
   *   List of errors to be posted back to the form.
   * @param CRM_Contact_Form_Inline_Website $form
   *
   * @return array
   */
  public static function formRule($fields, $errors, $form) {
    $hasData = $errors = [];
    if (!empty($fields['website']) && is_array($fields['website'])) {
      $types = [];
      foreach ($fields['website'] as $instance => $blockValues) {
        $dataExists = CRM_Contact_Form_Contact::blockDataExists($blockValues);

        if ($dataExists) {
          $hasData[] = $instance;
          if (!empty($blockValues['website_type_id'])) {
            if (empty($types[$blockValues['website_type_id']])) {
              $types[$blockValues['website_type_id']] = $blockValues['website_type_id'];
            }
            else {
              $errors["website[" . $instance . "][website_type_id]"] = ts('Contacts may only have one website of each type at most.');
            }
          }
        }
      }
    }
    return $errors;
  }

}
