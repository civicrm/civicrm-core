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

/**
 * @service
 * @internal
 */
class SearchDisplayCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('name')->setRequired(FALSE);

    $field = new FieldSpec('is_autocomplete_default', 'SearchDisplay', 'Boolean');
    $field->setLabel(ts('Autocomplete Default'))
      ->setTitle(ts('Autocomplete Default'))
      ->setColumnName('name')
      ->setDescription(ts('Is this the default autocomplete display for this entity'))
      ->setType('Extra')
      ->setSqlRenderer([__CLASS__, 'renderIsAutocompleteDefault']);
    $spec->addFieldSpec($field);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'SearchDisplay';
  }

  public static function renderIsAutocompleteDefault(array $nameField, Api4SelectQuery $query): string {
    $typeField = $query->getFieldSibling($nameField, 'type');
    $currentDomain = \CRM_Core_Config::domainID();
    return "{$typeField['sql_name']} = 'autocomplete'
      AND EXISTS (
        SELECT 1 FROM `civicrm_setting`
        WHERE `civicrm_setting`.name = 'autocomplete_displays'
        AND `civicrm_setting`.domain_id = $currentDomain
        AND `civicrm_setting`.value LIKE CONCAT('%:', {$nameField['sql_name']}, '\"%')
      )";
  }

}
