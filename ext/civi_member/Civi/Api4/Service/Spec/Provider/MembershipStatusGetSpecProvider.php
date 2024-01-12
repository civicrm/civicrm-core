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
class MembershipStatusGetSpecProvider extends AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $spec->addFieldSpec(new FieldSpec('is_new', 'Membership', 'Integer'));
    $field = (new FieldSpec('is_new', 'Membership', 'Boolean'))
      ->setTitle(ts('Is new membership status'))
      ->setDescription(ts('Is this the status for new members'))
      ->setInputType('Number')
      ->setType('Extra')
      ->setColumnName('id')
      ->setSqlRenderer([__CLASS__, 'isNewMembership']);
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
    return $entity === 'MembershipStatus' && $action === 'get';
  }

  /**
   * Determine if the membership status is the status used for new memberships.
   *
   * @param array $fieldSpec
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * return string
   *
   * @return string
   * @throws \CRM_Core_Exception
   * @noinspection PhpUnusedParameterInspection
   */
  public static function isNewMembership(array $fieldSpec, Api4SelectQuery $query): string {
    $newID = \CRM_Member_BAO_MembershipStatus::getNewMembershipTypeID();
    if ($newID) {
      return "IF ({$fieldSpec['sql_name']} = $newID, 1, 0)";
    }
    return '0';
  }

}
