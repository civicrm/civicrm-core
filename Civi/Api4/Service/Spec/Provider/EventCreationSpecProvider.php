<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class EventCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('event_type_id')->setRequiredIf('empty($values.template_id)');
    $spec->getFieldByName('title')->setRequiredIf('empty($values.is_template)');
    $spec->getFieldByName('start_date')->setRequiredIf('empty($values.is_template)');
    $spec->getFieldByName('template_title')->setRequiredIf('!empty($values.is_template)');

    $template_id = new FieldSpec('template_id', 'Event', 'Integer');
    $template_id
      ->setTitle('Template Id')
      ->setDescription('Template on which to base this new event');
    $spec->addFieldSpec($template_id);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Event' && $action === 'create';
  }

}
