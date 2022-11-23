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
      ->setSqlRenderer([__CLASS__, 'revertible']);
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

  /**
   * Callback for finding id of template to revert to
   * Based on CRM_Admin_Page_MessageTemplates::__construct()
   *
   * @return string
   */
  public static function revertible(): string {
    return "(SELECT `id` FROM `civicrm_msg_template` `orig`
      WHERE `a`.`workflow_name` = `orig`.`workflow_name` AND `orig`.`is_reserved` = 1 AND
        ( `a`.`msg_subject` != `orig`.`msg_subject` OR
          `a`.`msg_text`    != `orig`.`msg_text`    OR
          `a`.`msg_html`    != `orig`.`msg_html`
        ))";
  }

}
