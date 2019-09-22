<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class ACLCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('entity_table')->setDefaultValue('civicrm_acl_role');
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'ACL' && $action === 'create';
  }

}
