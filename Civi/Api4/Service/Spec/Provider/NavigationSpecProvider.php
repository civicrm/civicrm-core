<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class NavigationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * This runs for both create and get actions
   *
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('domain_id')->setRequired(FALSE)->setDefaultValue('current_domain');
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Navigation' && in_array($action, ['create', 'get']);
  }

}
