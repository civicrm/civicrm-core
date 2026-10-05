<?php
namespace Civi\Api4\Action\AfformSubmissionData;

use Civi\Api4\AfformSubmission;
use CRM_Afform_ExtensionUtil as E;

class GetFields extends \Civi\Api4\Generic\BasicGetFieldsAction {

  use AfformSubmissionDataTrait;

  /**
   * Name of the afform whose fields should be used.
   *
   * @var string
   * @dynamicFieldControl
   */
  protected $afformName;

  public function getRecords(): array {
    // 1. Get standard submission fields, excluding the raw data blob.
    $fields = (array) AfformSubmission::getFields(FALSE)
      ->setAction('get')
      ->setLoadOptions($this->getLoadOptions())
      ->addWhere('name', 'NOT IN', ['data', 'afform_name'])
      ->execute();

    // 2. Load layout fields for the given afform.
    $formDataModel = $this->getFormDataModel($this->afformName);
    if (!$formDataModel) {
      return $fields;
    }

    $entitySpecs = $this->loadEntitySpecs($formDataModel, $this->getLoadOptions());
    $entityPrimaryKeys = array_map(['\Civi\Api4\Utils\CoreUtil', 'getIdFieldName'], array_combine(array_keys($entitySpecs), array_keys($entitySpecs)));

    // 3. Add dynamic submission-data fields from the afform layout.
    foreach ($this->getLayoutFields($formDataModel) as $lf) {
      if ($lf['entity'] === 'extra') {
        $defn = $lf['props']['defn'] ?? $lf['props'];
        $label = $defn['label'] ?? $lf['field'];
        $fields[] = [
          'name' => $lf['name'],
          'title' => ($lf['label_prefix'] ?? '') . $label,
          'label' => ($lf['label_prefix'] ?? '') . $label,
          'data_type' => $defn['data_type'] ?? 'String',
          'input_type' => $defn['input_type'] ?? 'Text',
          'options' => $defn['options'] ?? FALSE,
        ];
      }
      else {
        $specs = $entitySpecs[$lf['entity']] ?? [];
        $fieldNameWithoutSuffix = explode(':', $lf['field'])[0];
        $fieldDef = $specs[$fieldNameWithoutSuffix] ?? NULL;
        if ($fieldDef) {
          $fieldDef['name'] = explode(':', $lf['name'])[0];
          $fieldDef['title'] = $lf['label_prefix'] . ($fieldDef['label'] ?? $fieldNameWithoutSuffix);
          $fieldDef['label'] = $lf['label_prefix'] . ($lf['props']['defn']['label'] ?? $fieldDef['label'] ?? $fieldNameWithoutSuffix);
          if ($fieldNameWithoutSuffix === $entityPrimaryKeys[$lf['entity']]) {
            $fieldDef['fk_entity'] = $lf['entity'];
            $fieldDef['fk_column'] = $fieldNameWithoutSuffix;
          }
          $fields[] = $fieldDef;
        }
      }
    }

    foreach ($fields as &$field) {
      $field['entity'] = 'AfformSubmissionData';
    }
    return $fields;
  }

}
