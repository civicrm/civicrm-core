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

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class FinancialItemCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  // I'm not sure it makes sense to have a default `entity_table`... actually, I don't even know if it makes
  // sense to expose `FinancialItem` as a public API, for what that's worth. But it's there, so clearly it does.
  //  And the ConformanceTests require that you be able to create (and read-back) a record using metadata.

  const DEFAULT_TABLE = 'civicrm_line_item';
  const DEFAULT_ENTITY = 'LineItem';

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('entity_table')->setRequired(TRUE);
    $spec->getFieldByName('entity_id')->setRequired(TRUE);
    $spec->getFieldByName('entity_table')->setDefaultValue(self::DEFAULT_TABLE);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'FinancialItem' && $action === 'create';
  }

}
