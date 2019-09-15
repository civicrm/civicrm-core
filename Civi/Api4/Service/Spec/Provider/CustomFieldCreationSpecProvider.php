<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class CustomFieldCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $optionField = new FieldSpec('option_values', $spec->getEntity(), 'Array');
    $optionField->setTitle(ts('Option Values'));
    $optionField->setDescription('Pass an array of options (value => label) to create this field\'s option values');
    $spec->addFieldSpec($optionField);
    $spec->getFieldByName('data_type')->setDefaultValue('String')->setRequired(FALSE);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'CustomField' && $action === 'create';
  }

}
