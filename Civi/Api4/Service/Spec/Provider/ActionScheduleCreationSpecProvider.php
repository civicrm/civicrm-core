<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

class ActionScheduleCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('title')->setRequired(TRUE);
    $spec->getFieldByName('mapping_id')->setRequired(TRUE);
    $spec->getFieldByName('entity_value')->setRequired(TRUE);
    $spec->getFieldByName('start_action_date')->setRequiredIf('empty($values.absolute_date)');
    $spec->getFieldByName('absolute_date')->setRequiredIf('empty($values.start_action_date)');
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'ActionSchedule' && $action === 'create';
  }

}
