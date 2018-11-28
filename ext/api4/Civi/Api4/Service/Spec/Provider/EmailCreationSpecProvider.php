<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class EmailCreationSpecProvider implements SpecProviderInterface {
  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('contact_id')->setRequired(TRUE);
    $spec->getFieldByName('email')->setRequired(TRUE);
    $spec->getFieldByName('on_hold')->setRequired(FALSE);
    $spec->getFieldByName('is_bulkmail')->setRequired(FALSE);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Email' && $action === 'create';
  }

}
