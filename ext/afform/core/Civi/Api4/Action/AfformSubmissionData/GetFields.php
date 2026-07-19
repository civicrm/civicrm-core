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

    // 3. Add dynamic submission-data fields from the afform layout.
    foreach ($this->getLayoutFields($formDataModel) as $lf) {
      if ($lf['entity'] === 'extra') {
        $fields[] = [
          'name' => $lf['name'],
          'title' => $lf['label_prefix'] . ($lf['props']['label'] ?? $lf['field']),
          'label' => $lf['label_prefix'] . ($lf['props']['label'] ?? $lf['field']),
          'data_type' => $lf['props']['data_type'] ?? 'String',
          'input_type' => $lf['props']['input_type'] ?? 'Text',
          'options' => $lf['props']['options'] ?? FALSE,
        ];
      }
      else {
        $specs = $entitySpecs[$lf['entity']] ?? [];
        $fieldNameWithoutSuffix = explode(':', $lf['field'])[0];
        $fieldDef = $specs[$fieldNameWithoutSuffix] ?? NULL;
        if ($fieldDef) {
          $fieldDef['name'] = explode(':', $lf['name'])[0];
          $fieldDef['title'] = $lf['label_prefix'] . ($fieldDef['label'] ?? $fieldNameWithoutSuffix);
          $fieldDef['label'] = $lf['label_prefix'] . ($fieldDef['label'] ?? $fieldNameWithoutSuffix);
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
