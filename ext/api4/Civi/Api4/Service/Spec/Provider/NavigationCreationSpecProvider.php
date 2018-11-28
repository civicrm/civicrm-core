<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class NavigationCreationSpecProvider implements SpecProviderInterface {
  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('domain_id')->setRequired(FALSE);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Navigation' && $action === 'create';
  }

}
