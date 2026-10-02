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
 * Parent class for inline contact forms.
 */
abstract class CRM_Contact_Form_Inline extends CRM_Core_Form {

  /**
   * Id of the contact that is being edited
   * @var int
   *
   * @internal - use getContactID()
   */
  public $_contactId;

  /**
   * Type of contact being edited
   * @var string
   */
  public $_contactType;

  /**
   * Sub type of contact being edited
   * @var string
   */
  public $_contactSubType;

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Contact';
  }

  /**
   * Common preprocess: fetch contact ID and contact type
   */
  public function preProcess() {
    $this->assign('contactId', $this->getContactID());

    // get contact type and subtype
    if (empty($this->_contactType)) {
      $contactTypeInfo = CRM_Contact_BAO_Contact::getContactTypes($this->getContactID());
      $this->_contactType = $contactTypeInfo[0];

      // check if subtype is set
      if (isset($contactTypeInfo[1])) {
        // unset contact type which is 0th element
        unset($contactTypeInfo[0]);
        $this->_contactSubType = $contactTypeInfo;
      }
    }

    $this->assign('contactType', $this->_contactType);

    $this->setAction(CRM_Core_Action::UPDATE);
  }

  /**
   * Get the contact ID.
   *
   * Override this for more complex retrieval as required by the form.
   *
   * @return int|null
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getContactID(): ?int {
    if (!isset($this->_contactId)) {
      $this->_contactId = (int) CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    }
    return $this->_contactId;
  }

  /**
   * Common form elements.
   */
  public function buildQuickForm() {
    CRM_Contact_Form_Inline_Lock::buildQuickForm($this, $this->getContactID());

    $buttons = [
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];
    $this->addButtons($buttons);
  }

  /**
   * Override default cancel action.
   */
  public function cancelAction() {
    $response = ['status' => 'cancel'];
    CRM_Utils_JSON::output($response);
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    CRM_Contact_BAO_Contact::getValues(['id' => $this->getContactID()], $defaults);

    return $defaults;
  }

  /**
   * Build default values for a repeated block inline form (email, phone, im, openid, website).
   *
   * Populates defaults from existing values and sets the default location type id on hidden empty blocks.
   *
   * @param array $blocks Existing saved blocks, keyed 1-n.
   * @param string $key Field key (e.g. 'email', 'phone').
   * @param int $blockCount Total number of blocks to fill.
   * @param int $defaultLocationTypeId Default location type ID for hidden empty blocks.
   *
   * @return array
   */
  protected function setBlockDefaultValues(array $blocks, string $key, int $blockCount, int $defaultLocationTypeId): array {
    $defaults = [];
    foreach ($blocks as $id => $value) {
      $defaults[$key][$id] = $value;
    }
    $defaultTypeKey = ($key === 'website') ? 'website_type_id' : 'location_type_id';
    for ($i = 1; $i <= $blockCount; $i++) {
      if (empty($defaults[$key][$i])) {
        $defaults[$key][$i][$defaultTypeKey] = $defaultLocationTypeId;
      }
    }
    return $defaults;
  }

  /**
   * Calculate and assign total and actual block counts for repeated block forms.
   *
   * @param int $existingCount Number of existing saved blocks.
   * @param int $blockCount Target / maximum block count.
   *
   * @return int Total number of blocks.
   */
  protected function calculateAndAssignBlockCounts(int $existingCount, int $blockCount): int {
    $totalBlocks = $blockCount;
    $actualBlockCount = 1;
    if ($existingCount > 1) {
      $actualBlockCount = $totalBlocks = $existingCount;
      if ($totalBlocks < $blockCount) {
        $totalBlocks += ($blockCount - $totalBlocks);
      }
      else {
        $actualBlockCount++;
        $totalBlocks++;
      }
    }
    $this->assign('actualBlockCount', $actualBlockCount);
    $this->assign('totalBlocks', $totalBlocks);
    return $totalBlocks;
  }

  /**
   * Merge IDs of existing blocks into submitted values prior to saving.
   *
   * @param array $submittedValues Form submitted values (modified in-place).
   * @param array $existingBlocks Existing blocks keyed by index.
   * @param string $key Block field key (e.g. 'email', 'phone', 'im', 'openid', 'website').
   */
  protected function mergeExistingBlockIds(array &$submittedValues, array $existingBlocks, string $key): void {
    foreach ($existingBlocks as $count => $value) {
      if (!empty($value['id']) && isset($submittedValues[$key][$count])) {
        $submittedValues[$key][$count]['id'] = $value['id'];
      }
    }
  }

  /**
   * Validate that at most one block is marked as primary.
   *
   * @param array $fields Submitted form fields.
   * @param string $key Block field key (e.g. 'email', 'phone', 'im', 'openid').
   *
   * @return array Array of validation errors.
   */
  public static function validatePrimaryBlock(array $fields, string $key): array {
    $errors = [];
    $hasPrimary = [];
    foreach ($fields[$key] ?? [] as $blockId => $blockValues) {
      if (CRM_Contact_Form_Contact::blockDataExists($blockValues) && !empty($blockValues['is_primary'])) {
        $hasPrimary[] = $blockId;
      }
    }

    if (count($hasPrimary) > 1) {
      // Set error on last block marked primary
      $lastBlockId = end($hasPrimary);
      $errors["{$key}[$lastBlockId][is_primary]"] = ts('Only one can be marked as primary.');
    }
    return $errors;
  }

  /**
   * Check if any submitted blocks for the given key contain actual data.
   *
   * @param array $fields Submitted form fields.
   * @param string $key Block field key (e.g. 'email', 'phone', 'im', 'openid', 'website').
   *
   * @return bool
   */
  public static function hasBlockData(array $fields, string $key): bool {
    if (!empty($fields[$key]) && is_array($fields[$key])) {
      foreach ($fields[$key] as $blockValues) {
        if (CRM_Contact_Form_Contact::blockDataExists($blockValues)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Get parameters for updating contact fields from submitted form values.
   *
   * @return array
   */
  protected function getContactUpdateParams(): array {
    $params = $this->getSubmittedValues();
    $params['contact_type'] = $this->_contactType ?? 'Individual';
    $params['contact_id'] = $this->getContactID();
    if (!empty($this->_contactSubType)) {
      $params['contact_sub_type'] = $this->_contactSubType;
    }
    if ($this->elementExists('is_deceased') && empty($params['is_deceased'])) {
      $params['is_deceased'] = FALSE;
      $params['deceased_date'] = '';
    }
    return $params;
  }

  /**
   * Save contact using parameters from the submitted form.
   *
   * @param array $params Optional parameters to override or augment.
   *
   * @return CRM_Contact_DAO_Contact
   */
  protected function saveContact(array $params = []): CRM_Contact_DAO_Contact {
    $params = array_merge($this->getContactUpdateParams(), $params);
    return CRM_Contact_BAO_Contact::create($params);
  }

  /**
   * Add entry to log table.
   */
  protected function log() {
    CRM_Core_BAO_Log::register($this->getContactID(),
      'civicrm_contact',
      $this->getContactID()
    );
  }

  /**
   * Common function for all inline contact edit forms.
   *
   * Prepares ajaxResponse
   */
  protected function response() {
    $this->ajaxResponse = array_merge(
      self::renderFooter($this->getContactID()),
      $this->ajaxResponse,
      CRM_Contact_Form_Inline_Lock::getResponse($this->getContactID())
    );
    // Note: Post hooks will be called by CRM_Core_Form::mainProcess
  }

  /**
   * Render change log footer markup for a contact and supply count.
   *
   * Needed for refreshing the contact summary screen
   *
   * @param int $cid
   * @param bool $includeCount
   * @return array
   */
  public static function renderFooter($cid, $includeCount = TRUE) {
    // Load change log footer from template.
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('contactId', $cid);
    $smarty->assign('external_identifier', CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'external_identifier'));
    $smarty->assign('created_date', CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'created_date'));
    $smarty->assign('lastModified', CRM_Core_BAO_Log::lastModified($cid, 'civicrm_contact'));
    $viewOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_view_options', TRUE
    );
    $smarty->assign('changeLog', $viewOptions['log']);
    $smarty->ensureVariablesAreAssigned(['action']);
    $ret = ['markup' => $smarty->fetch('CRM/common/contactFooter.tpl')];
    if ($includeCount) {
      $ret['count'] = CRM_Contact_BAO_Contact::getCountComponent('log', $cid);
    }
    return ['changeLog' => $ret];
  }

}
