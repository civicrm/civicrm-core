<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class CustomValueSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();
    if ($action !== 'create') {
      $idField = new FieldSpec('id', $spec->getEntity(), 'Integer');
      $idField->setTitle(ts('Custom Value ID'));
      $spec->addFieldSpec($idField);
    }
    $entityField = new FieldSpec('entity_id', $spec->getEntity(), 'Integer');
    $entityField->setTitle(ts('Entity ID'));
    $entityField->setRequired($action === 'create');
    $entityField->setFkEntity('Contact');
    $spec->addFieldSpec($entityField);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return strstr($entity, 'Custom_');
  }

}
