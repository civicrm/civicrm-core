<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class MappingCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * This function runs for both Mapping and MappingField entities
   *
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('name')->setRequired(TRUE);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return strpos($entity, 'Mapping') === 0 && $action === 'create';
  }

}
