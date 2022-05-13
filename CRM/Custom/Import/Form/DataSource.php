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

    $multipleCustomData = CRM_Core_BAO_CustomGroup::getMultipleFieldGroup();
    if (!$multipleCustomData) {
      CRM_Core_Error::statusBounce(ts('This import screen cannot be used because there are no Multi-value custom data groups'));
    }
    $this->add('select', 'multipleCustomData', ts('Multi-value Custom Data'), ['' => ts('- select -')] + $multipleCustomData, TRUE);
    // Assign an array of fields that are specific to this import to be included.
    $this->assign('import_options', ['multipleCustomData']);
    $this->addContactTypeSelector();
  }

  /**
   * Is the custom data import available for use.
   *
   * @return bool
   */
  public static function isAvailable(): bool {
    return CRM_Core_Permission::check('access CiviCRM') && CRM_Core_BAO_CustomGroup::getMultipleFieldGroup();
  }

  /**
   * Process the uploaded file.
   *
   * @return void
   */
  public function postProcess() {
    $this->storeFormValues([
      'contactType',
      'dateFormats',
      'savedMapping',
      'multipleCustomData',
    ]);

    $this->submitFileForMapping('CRM_Custom_Import_Parser_Api', 'multipleCustomData');
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
