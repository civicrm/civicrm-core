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

namespace Civi\Afform\Placement;

/**
 * Shared functions for Afform Placements
 *
 * A Placement is an existing place in which an Afform can be inserted,
 * e.g. the Contact Summary Screen.
 *
 * The list of placements is in the `afform_placement` option group.
 */
class PlacementUtils {

  public static function getPlacements(): array {
    if (!isset(\Civi::$statics[__CLASS__]['placements'])) {
      \Civi::$statics[__CLASS__]['placements'] = (array) \Civi\Api4\OptionValue::get(FALSE)
        ->addSelect('value', 'label', 'icon', 'description', 'grouping', 'filter')
        ->addWhere('is_active', '=', TRUE)
        ->addWhere('option_group_id:name', '=', 'afform_placement')
        ->addOrderBy('weight')
        ->execute()->indexBy('value');
      foreach (\Civi::$statics[__CLASS__]['placements'] as &$placement) {
        $placement['entities'] = [];
        if ($placement['grouping']) {
          foreach (explode(',', $placement['grouping']) as $entityName) {
            $placement['entities'][self::getEntityTypeId($entityName)] = $entityName;
          }
        }
      }
    }
    return \Civi::$statics[__CLASS__]['placements'];
  }

  public static function getEntityTypeId(string $entityName): string {
    return \CRM_Utils_String::convertStringToSnakeCase($entityName) . '_id';
  }

  public static function getEntityTypeFilterName(string $entityName): ?string {
    $filterField = self::getEntityTypeFilterFields($entityName)[0] ?? NULL;
    if ($filterField && str_ends_with($filterField, '_id')) {
      $filterField = substr($filterField, 0, -3);
    }
    return $filterField;
  }

  public static function getEntityTypeFilterLabel(string $entityName): ?string {
    $filterFieldName = self::getEntityTypeFilterFields($entityName)[0] ?? NULL;
    if ($filterFieldName) {
      $filterField = \Civi::entity($entityName)->getField($filterFieldName);
      return $filterField['input_attrs']['label'] ?? $filterField['title'];
    }
    return NULL;
  }

  public static function getEntityTypeFilterFields(string $entityName, bool $addSuffix = FALSE): array {
    // For contacts, these 2 fields get merged to a single "contact_type" value
    if ($entityName === 'Contact') {
      return ['contact_type', 'contact_sub_type'];
    }
    // All other entities it's just a single filter field (e.g. event_id)
    $fieldName = \CRM_Utils_String::convertStringToSnakeCase($entityName) . '_type_id';
    if (\Civi::entity($entityName)->getField($fieldName)) {
      return $addSuffix ? ["$fieldName:name"] : [$fieldName];
    }
    return [];
  }

  public static function matchesContextFilters(string $placement, array $afform, array &$context): bool {
    foreach (self::getPlacements()[$placement]['entities'] as $entityKey => $entityName) {
      $entityId = $context[$entityKey] ?? NULL;
      $filterName = self::getEntityTypeFilterName($entityName);
      // Look up filter values from entity id, and stash in the $context variable so we only do the lookup once
      if ($filterName && !empty($afform['placement_filters'][$filterName]) && !array_key_exists($filterName, $context) && $entityId) {
        $filterFields = self::getEntityTypeFilterFields($entityName, TRUE);
        $entityValues = civicrm_api4($entityName, 'get', [
          'where' => [['id', '=', $entityId]],
          'select' => $filterFields,
        ])->first();
        $context[$filterName] = [];
        foreach ($filterFields as $filterField) {
          $context[$filterName] = array_merge($context[$filterName], (array) ($entityValues[$filterField] ?? []));
        }
      }
      if (!empty($afform['placement_filters'][$filterName]) && !empty($context[$filterName]) && !array_intersect($afform['placement_filters'][$filterName], $context[$filterName])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public static function getAfformContextOptions(string $placement, array $context): array {
    return array_intersect_key($context, self::getPlacements()[$placement]['entities']);
  }

  public static function getEntityTypeFilterOptions(string $entityName): ?array {
    // The contact_type filter includes contact types and subtypes
    if ($entityName === 'Contact') {
      $contactTypes = \CRM_Contact_BAO_ContactType::basicTypeInfo();
      foreach ($contactTypes as &$contactType) {
        $contactType['children'] = \CRM_Contact_BAO_ContactType::subTypeInfo($contactType['name']);
      }
      return \CRM_Utils_Array::formatForSelect2($contactTypes, 'label', 'name');
    }
    // All other filters, e.g. activity_type
    $filterField = self::getEntityTypeFilterFields($entityName)[0] ?? NULL;
    if (\Civi::entity($entityName)->getField($filterField)) {
      $options = \Civi::entity($entityName)->getOptions($filterField);
      return \CRM_Utils_Array::formatForSelect2($options, 'label', 'name');
    }
    return NULL;
  }

  public static function getAfformsForPlacement(string $placement): array {
    return (array) \Civi\Api4\Afform::get()
      ->addSelect('name', 'title', 'icon', 'server_route', 'module_name', 'directive_name', 'placement_filters', 'placement_weight')
      ->addWhere('placement', 'CONTAINS', $placement)
      ->addOrderBy('placement_weight')
      ->addOrderBy('title')
      ->execute();
  }

  /**
   * Resolve a mixed list of contact types and sub-types into just top-level contact types (Individual, Organization, Household)
   */
  public static function filterContactTypes(array $mixedTypes): array {
    $allContactTypes = \CRM_Contact_BAO_ContactType::getAllContactTypes();
    $contactTypes = [];
    foreach ($mixedTypes as $name) {
      $parent = $allContactTypes[$name]['parent'] ?? $name;
      $contactTypes[$parent] = $parent;
    }
    return array_values($contactTypes);
  }

}
