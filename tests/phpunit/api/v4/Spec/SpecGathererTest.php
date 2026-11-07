<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Spec;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Service\Spec\SpecGatherer;
use Civi\Api4\Utils\CoreUtil;
use api\v4\Traits\OptionCleanupTrait;
use api\v4\Api4TestBase;
use api\v4\Traits\TableDropperTrait;
use Civi\Test\Invasive;

/**
 * @group headless
 */
class SpecGathererTest extends Api4TestBase {

  use TableDropperTrait;
  use OptionCleanupTrait;

  public function testBasicFieldsGathering(): void {
    $gatherer = new SpecGatherer();
    $specs = Invasive::call([$gatherer, 'getSpec'], ['Contact', 'get']);
    $contactDAO = CoreUtil::getBAOFromApiName('Contact');
    $contactFields = $contactDAO::fields();
    $specFieldNames = $specs->getFieldNames();
    $contactFieldNames = array_column($contactFields, 'name');

    $this->assertEmpty(array_diff_key($contactFieldNames, $specFieldNames));
  }

  public function testWithSpecProvider(): void {
    $gather = new SpecGatherer();

    $gather->addSpecProvider(new class implements SpecProviderInterface {

      public function modifySpec(RequestSpec $spec): void {
        $spec->addFieldSpec(new FieldSpec('foo', 'Contact'));
      }

      public function applies(string $entity, string $action): bool {
        return $entity === 'Contact' && $action === 'create';
      }

    });

    $spec = Invasive::call([$gather, 'getSpec'], ['Contact', 'create']);
    $fieldNames = $spec->getFieldNames();

    $this->assertContains('foo', $fieldNames);
  }

}
