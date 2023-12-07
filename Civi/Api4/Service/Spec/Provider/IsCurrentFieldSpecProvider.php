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
  public function modifySpec(RequestSpec $spec): void {
    $field = new FieldSpec('is_current', $spec->getEntity(), 'Boolean');
    $field->setLabel(ts('Is Current'))
      ->setTitle(ts('Current'))
      // This pseudo-field is like is_active with some extra criteria
      ->setColumnName('is_current')
      ->setDescription(ts('Is active with a non-past end-date'))
      ->setType('Extra')
      ->setSqlRenderer([__CLASS__, $this->getRenderer($field->getEntity())]);
    $spec->addFieldSpec($field);
  }

  /**
   * Get the function to render the sql.
   *
   * @param string $entity
   *
   * @return string
   */
  private function getRenderer(string $entity): string {
    if (in_array($entity, ['UserJob', 'SavedSearch'])) {
      return 'renderNonExpiredSql';
    }
    return 'renderIsCurrentSql';
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    if ($action !== 'get') {
      return FALSE;
    }
    // TODO: If we wanted this to not be a hard-coded list, we could always return TRUE here
    // and then in the `modifySpec` function check for the 3 fields `is_active`, `start_date`, and `end_date`
    return in_array($entity, ['Relationship', 'RelationshipCache', 'Event', 'Campaign', 'SavedSearch', 'UserJob'], TRUE);
  }

  /**
   * @param array $field
   *
   * return string
   */
  public static function renderIsCurrentSql(array $field): string {
    $startDate = substr_replace($field['sql_name'], 'start_date', -11, -1);
    $endDate = substr_replace($field['sql_name'], 'end_date', -11, -1);
    $isActive = substr_replace($field['sql_name'], 'is_active', -11, -1);
    $today = date('Ymd');
    return "IF($isActive = 1 AND ($startDate <= '$today' OR $startDate IS NULL) AND ($endDate >= '$today' OR $endDate IS NULL), '1', '0')";
  }

  /**
   * Render the sql clause to filter on expires date.
   *
   * @param array $field
   *
   * return string
   */
  public static function renderNonExpiredSql(array $field): string {
    $endDate = substr_replace($field['sql_name'], 'expires_date', -11, -1);
    $today = date('Ymd');
    return "IF($endDate >= '$today' OR $endDate IS NULL, 1, 0)";
  }

}
