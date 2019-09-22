<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class DefaultLocationTypeProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $locationField = $spec->getFieldByName('location_type_id')->setRequired(TRUE);
    $defaultType = \CRM_Core_BAO_LocationType::getDefault();
    if ($defaultType) {
      $locationField->setDefaultValue($defaultType->id);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'create' && in_array($entity, ['Address', 'Email', 'IM', 'OpenID', 'Phone']);
  }

}
