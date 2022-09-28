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


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class IsCurrentFieldSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('is_current', $spec->getEntity(), 'Boolean');
    $field->setLabel(ts('Is Current'))
      ->setTitle(ts('Current'))
      // This pseudo-field is like is_active with some extra criteria
      ->setColumnName('is_current')
      ->setDescription(ts('Is active with a non-past end-date'))
      ->setType('Extra')
      ->setSqlRenderer([__CLASS__, 'renderIsCurrentSql']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    if ($action !== 'get') {
      return FALSE;
    }
    // TODO: If we wanted this to not be a hard-coded list, we could always return TRUE here
    // and then in the `modifySpec` function check for the 3 fields `is_active`, `start_date`, and `end_date`
    return in_array($entity, ['Relationship', 'RelationshipCache', 'Event', 'Campaign'], TRUE);
  }

  /**
   * @param array $field
   * return string
   */
  public static function renderIsCurrentSql(array $field): string {
    $startDate = substr_replace($field['sql_name'], 'start_date', -11, -1);
    $endDate = substr_replace($field['sql_name'], 'end_date', -11, -1);
    $isActive = substr_replace($field['sql_name'], 'is_active', -11, -1);
    $todayStart = date('Ymd', strtotime('now'));
    $todayEnd = date('Ymd', strtotime('now'));
    return "IF($isActive = 1 AND ($startDate <= '$todayStart' OR $startDate IS NULL) AND ($endDate >= '$todayEnd' OR $endDate IS NULL), '1', '0')";
  }

}
