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
 * This form is loaded when custom data is loaded by ajax.
 *
 * The forms ALSO need to call enough functions from Form_CustomData
 * to ensure the fields they need are added to the form or the values will be
 * ignored in post process (ie. quick form will filter them out).
 *
 * This form never submits & hence has no post process.
 */
class CRM_Custom_Form_CustomDataByType extends CRM_Core_Form {

  /**
   * @var array
   */
  protected $groupTree;

  /**
   * @var array
   */
  private $groupCount;

  /**
   * @var int|mixed|string|null
   */
  private $groupID;

  /**
   * Preprocess function.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {

    $customDataType = CRM_Utils_Request::retrieve('type', 'String', NULL, TRUE);
    $subType = CRM_Utils_Request::retrieve('subType', 'String');
    $this->groupCount = CRM_Utils_Request::retrieve('cgcount', 'Positive');
    $this->groupID = $groupID = CRM_Utils_Request::retrieve('groupID', 'Positive');
    $onlySubType = CRM_Utils_Request::retrieve('onlySubtype', 'Boolean');
    $this->_action = CRM_Utils_Request::retrieve('action', 'Alphanumeric');
    $this->assign('cdType', FALSE);
    $this->assign('cgCount', $this->groupCount);

    $contactTypes = CRM_Contact_BAO_ContactType::contactTypeInfo();
    if (array_key_exists($customDataType, $contactTypes)) {
      $this->assign('contactId', CRM_Utils_Request::retrieve('entityID', 'Positive'));
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree(CRM_Utils_Request::retrieve('type', 'String'),
      NULL,
      CRM_Utils_Request::retrieve('entityID', 'Positive'),
      $groupID,
      $subType,
      CRM_Utils_Request::retrieve('subName', 'String'),
      TRUE,
      $onlySubType
    );

    // we should use simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, $this->groupCount, $this);

    if (isset($this->groupTree) && is_array($this->groupTree)) {
      $keys = array_keys($groupTree);
      foreach ($keys as $key) {
        $this->groupTree[$key] = $groupTree[$key];
      }
    }
    else {
      $this->groupTree = $groupTree;
    }

    $this->assign('suppressForm', TRUE);
    $this->controller->_generateQFKey = FALSE;
  }

  /**
   * Set defaults.
   *
   * @return array
   */
  public function setDefaultValues(): array {
    $defaults = [];
    CRM_Core_BAO_CustomGroup::setDefaults($this->groupTree, $defaults, FALSE, FALSE, $this->get('action'));
    return $defaults;
  }

  /**
   * Build quick form.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addElement('hidden', 'hidden_custom', 1);
    $this->addElement('hidden', "hidden_custom_group_count[{$this->groupID}]", $this->groupCount);
    CRM_Core_BAO_CustomGroup::buildQuickForm($this, $this->groupTree);
  }

}
