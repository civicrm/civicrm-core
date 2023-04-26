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

use Civi\Api4\CustomGroup;

/**
 * This class gets the name of the file to upload
 */
class CRM_Custom_Import_Form_DataSource extends CRM_Import_Form_DataSource {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'custom_field_import';
  }

  /**
   * Multiple field custom groups.
   *
   * @var array
   */
  protected $customFieldGroups;

  /**
   * Get multi-field custom groups.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getCustomGroups(): array {
    if (isset($this->customFieldGroups)) {
      return $this->customFieldGroups;
    }
    $this->customFieldGroups = [];
    // If we make the permission TRUE is it too restrictive?
    $fields = CustomGroup::get(FALSE)->addSelect('id', 'title')
      ->addWhere('is_multiple', '=', TRUE)
      ->addWhere('is_active', '=', TRUE)->execute();
    foreach ($fields as $field) {
      $this->customFieldGroups[$field['id']] = $field['title'];
    }
    return $this->customFieldGroups;
  }

  /**
   * Get an error message to assign to the template.
   *
   * @return string
   */
  protected function getErrorMessage(): string {
    return empty($this->getCustomGroups()) ? ts('This import screen cannot be used because there are no Multi-value custom data groups.') : '';
  }

  /**
   * Get the import entity (translated).
   *
   * Used for template layer text.
   *
   * @return string
   */
  protected function getTranslatedEntity(): string {
    return ts('Multi-value Custom Data');
  }

  /**
   * Get the import entity plural (translated).
   *
   * Used for template layer text.
   *
   * @return string
   */
  protected function getTranslatedEntities(): string {
    return ts('multi-value custom data records');
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues(): array {
    return array_merge(parent::setDefaultValues(), [
      'contactType' => 'Individual',
      // Perhaps never used, but permits url passing of the group.
      'multipleCustomData' => CRM_Utils_Request::retrieve('id', 'Positive', $this),
    ]);
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();
    $this->add('select', 'multipleCustomData', ts('Multi-value Custom Data'), ['' => ts('- select -')] + $this->getCustomGroups(), TRUE);
    $this->addContactTypeSelector();
  }

  /**
   * @return CRM_Custom_Import_Parser_Api
   */
  protected function getParser(): CRM_Custom_Import_Parser_Api {
    if (!$this->parser) {
      $this->parser = new CRM_Custom_Import_Parser_Api();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
