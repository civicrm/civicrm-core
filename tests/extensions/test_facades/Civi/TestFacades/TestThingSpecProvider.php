<?php
declare(strict_types = 1);

namespace Civi\TestFacades;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * Adds a calculated field to TestThing, to check that it also applies to TestThing's facades.
 *
 * @service
 * @internal
 */
class TestThingSpecProvider extends \Civi\Core\Service\AutoService implements SpecProviderInterface {

  public function modifySpec(RequestSpec $spec): void {
    $field = new FieldSpec('name_upper', $spec->getEntity(), 'String');
    $field->setLabel('Name (uppercase)')
      ->setTitle('Name (uppercase)')
      ->setColumnName('name')
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'renderNameUpper']);
    $spec->addFieldSpec($field);
  }

  public function applies($entity, $action): bool {
    return $entity === 'TestThing' && $action === 'get';
  }

  public static function renderNameUpper(array $field): string {
    return "UPPER({$field['sql_name']})";
  }

}
