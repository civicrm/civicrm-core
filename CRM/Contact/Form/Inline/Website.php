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
  use CRM_Contact_Form_Edit_WebsiteBlockTrait;

  /**
   * Websites of the contact that is being viewed.
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

    $this->_websites = $this->getExistingWebsitesReIndexed();
  }

  /**
   * Build the form object elements for website object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $totalBlocks = $this->calculateAndAssignBlockCounts(count($this->_websites), $this->_blockCount);

    for ($blockId = 1; $blockId < $totalBlocks; $blockId++) {
      $this->addWebsiteBlockFields($blockId);
    }

    $this->addFormRule(['CRM_Contact_Form_Inline_Website', 'formRule'], $this);

  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaultType = key(CRM_Core_OptionGroup::values('website_type', FALSE, FALSE, FALSE, ' AND is_default = 1'));
    return $this->setBlockDefaultValues($this->_websites, 'website', $this->_blockCount, $defaultType);
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->getSubmittedValues();

    $this->mergeExistingBlockIds($params, $this->_websites, 'website');
    $this->saveWebsites($params['website']);

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
    $errors = [];
    if (!empty($fields['website']) && is_array($fields['website'])) {
      $types = [];
      foreach ($fields['website'] as $instance => $blockValues) {
        if (CRM_Contact_Form_Contact::blockDataExists($blockValues) && !empty($blockValues['website_type_id'])) {
          if (empty($types[$blockValues['website_type_id']])) {
            $types[$blockValues['website_type_id']] = $blockValues['website_type_id'];
          }
          else {
            $errors["website[" . $instance . "][website_type_id]"] = ts('Contacts may only have one website of each type at most.');
          }
        }
      }
    }
    return $errors;
  }

}
