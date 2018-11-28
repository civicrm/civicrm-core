<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class CustomValueSpecProvider implements SpecProviderInterface {
  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();
    $extraFields = [
      'id' => [
        'required' => ($action === 'update'),
        'title' => ts('Custom Table Unique ID'),
        'fk_entity' => NULL,
      ],
      'entity_id' => [
        'required' => ($action === 'create'),
        'title' => ts('Entity ID'),
        'fk_entity' => 'Contact',
      ],
    ];
    foreach ($extraFields as $name => $field) {
      // Do not add id field on create action
      if ('create' === $action && 'id' === $name) {
        continue;
      }
      $fieldSpec = new FieldSpec($name, $spec->getEntity(), 'Integer');
      $fieldSpec->setTitle($field['title']);
      $fieldSpec->setRequired($field['required']);
      if (!empty($field['fk_entity'])) {
        $fieldSpec->setFkEntity($field['fk_entity']);
      }

      $spec->addFieldSpec($fieldSpec);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return strstr($entity, 'Custom_');
  }

}
