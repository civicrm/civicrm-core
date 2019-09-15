<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class ContactTypeCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('label')->setRequired(TRUE);
    $spec->getFieldByName('name')->setRequired(TRUE);
    $spec->getFieldByName('parent_id')->setRequired(TRUE);

  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'ContactType' && $action === 'create';
  }

}
