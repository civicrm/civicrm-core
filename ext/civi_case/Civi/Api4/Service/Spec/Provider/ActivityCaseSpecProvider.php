<?php

namespace Civi\Api4\Service\Spec\Provider;

use CRM_Case_ExtensionUtil as E;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class ActivityCaseSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();

    $field = new FieldSpec('case_id', 'Activity', 'Integer');
    $field->setTitle(E::ts('Case ID'));
    $field->setLabel($action === 'get' ? E::ts('Filed on Case') : E::ts('File on Case'));
    $field->setDescription(E::ts('CiviCase this activity belongs to.'));
    $field->setFkEntity('Case');
    $field->setInputType('EntityRef');
    $field->setColumnName('id');
    // @see CaseSchemaMapSubscriber for the source of the join sql
    $field->setSqlRenderer(['Civi\Api4\Service\Schema\Joiner', 'getExtraJoinSql']);
    $spec->addFieldSpec($field);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Activity';
  }

}
