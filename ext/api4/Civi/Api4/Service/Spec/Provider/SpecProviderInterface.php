<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

interface SpecProviderInterface {
  /**
   * @param RequestSpec $spec
   *
   * @return void
   */
  public function modifySpec(RequestSpec $spec);

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action);

}
