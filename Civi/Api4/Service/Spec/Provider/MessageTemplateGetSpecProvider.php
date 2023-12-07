<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class MessageTemplateGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('master_id', 'MessageTemplate', 'Integer');
    $field->setLabel(ts('Master ID'))
      ->setTitle(ts('Master ID'))
      ->setColumnName('id')
      ->setDescription(ts('MessageID that this could revert to'))
      ->setInputType('Select')
      ->setReadonly(TRUE)
      ->setFkEntity('MessageTemplate')
      ->setSqlRenderer(['\Civi\Api4\Service\Schema\Joiner', 'getExtraJoinSql']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'MessageTemplate' && $action === 'get';
  }

}
