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
class CampaignFilterSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    foreach ($spec->getFields() as $field) {
      if ($field->getFkEntity() === 'Campaign') {
        $field->addSqlFilter([__CLASS__, 'getCampaignFilterSql']);
      }
    }
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    return $action === 'get' && \CRM_Core_Component::isEnabled('CiviCampaign');
  }

  /**
   * @param array $field
   * @param string $fieldAlias
   * @param string $operator
   * @param mixed $value
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @param int $depth
   * @return string|null
   */
  public static function getCampaignFilterSql(array $field, string $fieldAlias, string $operator, $value, Api4SelectQuery $query, int $depth): ?string {
    if (!in_array($operator, ['=', '!=', '<>', 'IN', 'NOT IN'], TRUE)) {
      return NULL;
    }
    if ($value === NULL || $value === '' || $value === []) {
      return NULL;
    }
    $allIds = \CRM_Campaign_BAO_Campaign::getDescendantIds($value);
    if (!$allIds) {
      return in_array($operator, ['=', 'IN'], TRUE) ? '1=0' : '1';
    }
    if (count($allIds) === 1) {
      $val = reset($allIds);
      if ($operator === '=') {
        return \CRM_Core_DAO::createSQLFilter($fieldAlias, ['=' => $val]);
      }
      if ($operator === '!=' || $operator === '<>') {
        return \CRM_Core_DAO::createSQLFilter($fieldAlias, ['!=' => $val]);
      }
    }
    $sqlOp = in_array($operator, ['=', 'IN'], TRUE) ? 'IN' : 'NOT IN';
    return \CRM_Core_DAO::createSQLFilter($fieldAlias, [$sqlOp => $allIds]);
  }

}
