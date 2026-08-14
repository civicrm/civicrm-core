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

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class CaseTypeGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('definition')
      ->setSqlRenderer([__CLASS__, 'renderSqlForDefinition'])
      ->addOutputFormatter(['CRM_Case_BAO_CaseType', 'formatOutputDefinition']);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'CaseType' && $action === 'get';
  }

  /**
   * Fallback when definition is NULL.
   *
   * If the definition isn't stored in the database, provide the CaseType.name for file-based lookup
   * @see \CRM_Case_BAO_CaseType::formatOutputDefinition
   */
  public static function renderSqlForDefinition(array $field, Api4SelectQuery $query): string {
    return "IFNULL({$field['sql_name']}, `name`)";
  }

}
