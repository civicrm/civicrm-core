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
 * This class gets the name of the file to upload
 */
class CRM_Custom_Import_Form_DataSource extends CRM_Import_Form_DataSource {

  const PATH = 'civicrm/import/custom';

  const IMPORT_ENTITY = 'Multi value custom data';

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'custom_field_import';
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
   */
  public function setDefaultValues() {
    $config = CRM_Core_Config::singleton();
    $defaults = [
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'fieldSeparator' => $config->fieldSeparator,
      'multipleCustomData' => $this->_id,
    ];

    $loadedMapping = $this->get('loadedMapping');
    if ($loadedMapping) {
      $defaults['savedMapping'] = $loadedMapping;
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    // Perhaps never used, but permits url passing of the group.
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    $multipleCustomData = CRM_Core_BAO_CustomGroup::getMultipleFieldGroup();
    $this->assign('fieldGroups', $multipleCustomData);
    $this->add('select', 'multipleCustomData', ts('Multi-value Custom Data'), ['' => ts('- select -')] + $multipleCustomData, TRUE);

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
