<?php

use Civi\Import\ImportParser;

class CRM_CiviImport_Form_MapField extends CRM_Import_Form_MapField {

  public function preProcess(): void {
    if ($this->isStandalone() && !$this->getUserJob()['is_template'] && !$this->hasValidDataSource()) {
      $job = $this->getUserJob();
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/import/' . str_replace('_import', '', $job['job_type']), [
        'id' => $job['id'],
      ]));
    }
    parent::preProcess();
    // Add import-ui app
    Civi::service('angularjs.loader')->addModules('crmCiviimport');
    $this->getUserJobID();
    $this->assignCiviimportVariables();

    $templateJob = $this->getTemplateJob();
    if ($templateJob) {
      Civi::resources()->addVars('crmImportUi', ['savedMapping' => ['name' => substr($templateJob['name'], 7)]]);
    }
  }

  private function hasValidDataSource(): bool {
    $table = $this->getUserJob()['metadata']['DataSource']['table_name'] ?? '';
    return $table && CRM_Core_DAO::checkTableExists($table);
  }

  /**
   * Assign values for civiimport.
   *
   * I wanted to put this in the extension - but there are a lot of protected functions
   * we would need to revisit and make public - do we want to?
   *
   * @throws \CRM_Core_Exception
   */
  private function assignCiviimportVariables(): void {
    $contactTypes = CRM_Utils_Array::formatForSelect2(CRM_Contact_BAO_ContactType::basicTypeInfo(), 'name', 'label');
    $parser = $this->getParser();
    $this->isQuickFormMode = FALSE;
    Civi::resources()->addVars('crmImportUi', [
      'defaults' => $this->getDefaults(),
      'rows' => $this->getDataRows([], 2),
      'contactTypes' => $contactTypes,
      'entityMetadata' => $this->getFieldOptions(),
      'dedupeRules' => $parser->getAllDedupeRules(),
      'userJob' => $this->getUserJob(),
      'columnHeaders' => $this->getColumnHeaders(),
      'dateFormats' => $this->getDateFormats(),
      'isTemplate' => $this->getUserJob()['is_template'],
      'isStandalone' => $this->isStandalone(),
      'bundledActions' => ImportParser::getBundledActions(),
    ]);
  }

  /**
   * This transforms the lists of fields for each contact type & component
   * into a single unified list suitable for select2.
   *
   * @return array
   */
  private function getFieldOptions(): array {
    $fields = $this->getFields();
    $entity = $this->getBaseEntity();
    $categories = $this->getAvailableImportEntities();
    $highlightedFields = $this->getParser()->getRequiredFieldsForEntity($entity, $this->getParser()->getActionForEntity($entity));
    foreach ($fields as $fieldName => $field) {
      if ($fieldName === '') {
        // @todo stop setting 'do not import' in the first place.
        continue;
      }
      $childField = [
        'text' => $field['label'] ?? ($field['html']['label'] ?? $field['title']),
        'id' => $fieldName,
        'has_location' => !empty($field['hasLocationType']),
        'default_value' => $field['default_value'] ?? '',
        'contact_type' => $field['contact_type'] ?? NULL,
        'match_rule' => $field['match_rule'] ?? NULL,
      ];
      if (in_array($fieldName, $highlightedFields, TRUE)) {
        $childField['text'] .= '*';
      }
      $category = ($childField['has_location'] || $field['name'] === 'contact_id') ? 'Contact' : $field['entity_instance'] ?? ($field['entity'] ?? $entity);
      if (empty($categories[$category])) {
        $category = $entity;
      }
      $categories[$category]['children'][$fieldName] = $childField;
    }

    foreach ($categories as $index => $category) {
      if (empty($category['children'])) {
        unset($categories[$index]);
      }
      else {
        $categories[$index]['children'] = array_values($category['children']);
      }
    }
    return array_values($categories);
  }

  /**
   * Get the fields available for import selection.
   *
   * @return array
   *   e.g ['first_name' => 'First Name', 'last_name' => 'Last Name'....
   *
   */
  protected function getAvailableImportEntities(): array {
    return $this->getParser()->getAvailableImportEntities();
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName(): string {
    return 'CRM/Import/MapField.tpl';
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function getFieldMappings(): array {
    return $this->getUserJob()['metadata']['import_mappings'] ?? [];
  }

  /**
   * Get default values for the mapping.
   *
   * This looks up any saved mapping or derives them from the headers if possible.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDefaults(): array {
    $defaults = [];
    $fieldMappings = $this->getFieldMappings();
    foreach ($this->getColumnHeaders() as $i => $columnHeader) {
      $defaults["mapper[$i]"] = [];
      if ($fieldMappings) {
        $fieldMapping = $fieldMappings[$i] ?? [];
        if (!empty($fieldMapping['name']) && $fieldMapping['name'] !== ts('do_not_import')) {
          $this->addMappingToDefaults($defaults, $fieldMapping, $i);
        }
      }
    }
    if (empty($defaults) && $this->getSubmittedValue('skipColumnHeader')) {
      foreach ($this->getColumnHeaders() as $i => $columnHeader) {
        $defaults["mapper[$i]"][0] = $this->guessMappingBasedOnColumns($columnHeader);
      }
    }

    return $defaults;
  }

  /**
   * Add the saved mapping to the defaults.
   *
   * @param array $defaults
   * @param array $fieldMapping
   * @param int $rowNumber
   *
   * @return void
   */
  public function addMappingToDefaults(array &$defaults, array $fieldMapping, int $rowNumber): void {
    $fieldName = $fieldMapping['name'];
    $defaults["mapper[$rowNumber]"] = [$fieldName];
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param array $files
   * @param CRM_CiviImport_Form_MapField $self
   *
   * @return array|true
   *   list of errors to be posted back to the form
   */
  public static function validateMapping(array $fields, array $files, CRM_CiviImport_Form_MapField $self): bool|array {
    $mapperError = [];
    try {
      $parser = $self->getParser();
      $mappings = $self->getFieldMappings();
      $parser->validateMapping($mappings);
      $mapperError = $self->getMissingContactFields('Contact', $self->getFieldMappings());
    }
    catch (CRM_Core_Exception $e) {
      $mapperError[] = $e->getMessage();
    }
    if (!empty($mapperError)) {
      return ['_qf_default' => implode('<br/>', $mapperError)];
    }
    return TRUE;
  }

  /**
   * @param string $entity
   * @param array $mapper
   * @return array
   * @throws CRM_Core_Exception
   */
  public function getMissingContactFields(string $entity, array $mapper): array {
    $parser = $this->getParser();
    $rules = $this->getUserJob()['metadata']['entity_configuration'][$entity]['dedupe_rule'] ?? ['unique_identifier_match'];
    $missingError = [];
    foreach ($rules as $rule) {
      $rule = $parser->getDedupeRule($this->getContactType(), $rule);
      $missingError = array_merge($missingError, $this->validateContactFields($rule, $this->getImportKeys($mapper), ['external_identifier', 'contact_id', 'id']));
    }
    return array_filter($missingError);
  }

  /**
   * @param string $entity
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function validateRequiredContactFields(string $entity = 'Contact'): array {
    $mapper = [];
    $fields = $this->getUserJob()['metadata']['import_mappings'];
    foreach ($fields as $field) {
      if (!isset($field['name'])) {
        continue;
      }
      if (str_starts_with($field['name'], $entity . '.') || str_starts_with($field['name'], $this->getBaseEntity() . '.')) {
        $mapper[] = [$field['name']];
      }
    }
    $missing = $this->getMissingContactFields($entity, $mapper);
    if (empty($missing)) {
      return [];
    }
    return ['_qf_default' => implode(' + ', $missing)];
  }

  /**
   * Do nothing on post process.
   *
   * The angular form has already done this work & validation is now done on preview.
   */
  public function postProcess(): void {}

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $this->addFormButtons();
  }

}
