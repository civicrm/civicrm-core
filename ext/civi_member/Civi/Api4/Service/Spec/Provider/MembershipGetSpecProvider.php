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
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Core\Service\AutoService;

/**
 * @service
 * @internal
 */
class MembershipGetSpecProvider extends AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $spec->addFieldSpec(new FieldSpec('is_primary_member', 'Membership', 'Boolean'));
    $field = (new FieldSpec('is_primary_member', 'Membership', 'Boolean'))
      ->setTitle(ts('Is Primary Member?'))
      ->setDescription(ts('Is this a primary membership?'))
      ->setInputType('Radio')
      ->setType('Extra')
      ->setColumnName('owner_membership_id')
      ->setSqlRenderer([__CLASS__, 'isPrimaryMembership']);
    $spec->addFieldSpec($field);
  }

  /**
   * When does this apply.
   *
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies(string $entity, string $action): bool {
    return $entity === 'Membership' && $action === 'get';
  }

  /**
   * Determine if the membership is a primary membership.
   *
   * @param array $fieldSpec
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * return string
   *
   * @return string
   * @throws \CRM_Core_Exception
   * @noinspection PhpUnusedParameterInspection
   */
  public static function isPrimaryMembership(array $fieldSpec, Api4SelectQuery $query): string {
    return "IF({$fieldSpec['sql_name']}, 0, 1)";
  }

}
