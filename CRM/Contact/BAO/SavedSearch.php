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
use Civi\Api4\Event\AuthorizeRecordEvent;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Business object for Saved searches.
 */
class CRM_Contact_BAO_SavedSearch extends CRM_Contact_DAO_SavedSearch implements \Civi\Core\HookInterface {

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults = []) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Given an id, extract the formValues of the saved search.
   *
   * @param int $id
   *   The id of the saved search.
   *
   * @return array
   *   the values of the posted saved search used as default values in various Search Form
   *
   * @throws \CRM_Core_Exception
   */
  public static function getFormValues($id) {
    $specialDateFields = [
      'event_start_date_low' => 'event_date_low',
      'event_end_date_high' => 'event_date_high',
    ];

    $fv = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $id, 'form_values');
    $result = [];
    if ($fv) {
      // make sure u CRM_Utils_String::unserialize - since it's stored in serialized form
      $result = CRM_Utils_String::unserialize($fv);
      if ($result === FALSE) {
        // See https://github.com/civicrm/civicrm-core/pull/33638
        Civi::log()->error("Invalid saved_search.form_values for search ID $id. Failed to unserialize. Risk of unexpected search results.");
        return [];
      }
    }

    $specialFields = ['contact_type', 'group', 'contact_tags', 'member_membership_type_id', 'member_status_id'];
    foreach ($result as $element => $value) {
      if (CRM_Contact_BAO_Query::isAlreadyProcessedForQueryFormat($value)) {
        $id = $value[0] ?? NULL;
        $value = $value[2] ?? NULL;
        if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $op = key($value);
          $value = $value[$op] ?? NULL;
          if (in_array($op, ['BETWEEN', '>=', '<='])) {
            self::decodeRelativeFields($result, $id, $op, $value);
            unset($result[$element]);
            continue;
          }
        }
        // Check for a date range field, which might be a standard date
        // range or a relative date.
        if (str_contains($id, '_date_low') || str_contains($id, '_date_high')) {
          $entityName = strstr($id, '_date', TRUE);

          // This is the default, for non relative dates. We will overwrite
          // it if we determine this is a relative date.
          $result[$id] = $value;
          $result["{$entityName}_date_relative"] = 0;

          if (!empty($result['relative_dates'])) {
            if (array_key_exists($entityName, $result['relative_dates'])) {
              // We have a match from a regular field.
              $result[$id] = NULL;
              $result["{$entityName}_date_relative"] = $result['relative_dates'][$entityName];
            }
            elseif (!empty($specialDateFields[$id])) {
              // We may have a match on a special date field.
              $entityName = strstr($specialDateFields[$id], '_date', TRUE);
              if (array_key_exists($entityName, $result['relative_dates'])) {
                $result[$id] = NULL;
                $result["{$entityName}_relative"] = $result['relative_dates'][$entityName];
              }
            }
          }
        }
        else {
          $result[$id] = $value;
        }
        unset($result[$element]);
        continue;
      }
      if (!empty($value) && is_array($value)) {
        if (in_array($element, $specialFields)) {
          // Remove the element to minimise support for legacy formats. It is stored in $value
          // so will be re-set with the right name.
          unset($result[$element]);
          $element = str_replace('member_membership_type_id', 'membership_type_id', $element);
          $element = str_replace('member_status_id', 'membership_status_id', $element);
          CRM_Contact_BAO_Query::legacyConvertFormValues($element, $value);
          $result[$element] = $value;
        }
        // As per the OK (Operator as Key) value format, value array may contain key
        // as an operator so to ensure the default is always set actual value
        elseif (in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $result[$element] = $value[key($value)] ?? NULL;
          if (is_string($result[$element])) {
            $result[$element] = str_replace("%", '', $result[$element]);
          }
        }
      }
      // We should only set the relative key for custom date fields if it is not already set in the array.
      $realField = str_replace(['_relative', '_low', '_high', '_to', '_high'], '', $element);
      if (substr($element, 0, 7) == 'custom_' && CRM_Contact_BAO_Query::isCustomDateField($realField)) {
        if (!isset($result[$realField . '_relative'])) {
          $result[$realField . '_relative'] = 0;
        }
      }
      // check to see if we need to convert the old privacy array
      // CRM-9180
      if (!empty($result['privacy'])) {
        if (is_array($result['privacy'])) {
          $result['privacy_operator'] = 'AND';
          $result['privacy_toggle'] = 1;
          if (isset($result['privacy']['do_not_toggle'])) {
            if ($result['privacy']['do_not_toggle']) {
              $result['privacy_toggle'] = 2;
            }
            unset($result['privacy']['do_not_toggle']);
          }

          $result['privacy_options'] = [];
          foreach ($result['privacy'] as $name => $val) {
            if ($val) {
              $result['privacy_options'][] = $name;
            }
          }
        }
        unset($result['privacy']);
      }
    }

    return $result;
  }

  /**
   * Get search parameters.
   *
   * @param int $id
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function getSearchParams($id) {
    $savedSearch = \Civi\Api4\SavedSearch::get(FALSE)
      ->addWhere('id', '=', $id)
      ->execute()
      ->first();
    if ($savedSearch['api_entity']) {
      return $savedSearch;
    }
    $fv = self::getFormValues($id);
    //check if the saved search has mapping id
    if ($savedSearch['mapping_id']) {
      return CRM_Core_BAO_Mapping::formattedFields($fv);
    }
    elseif (!empty($fv['customSearchID'])) {
      return $fv;
    }
    else {
      return CRM_Contact_BAO_Query::convertFormValues($fv);
    }
  }

  /**
   * Get the where clause for a saved search.
   *
   * @param int $id
   *   Saved search id.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the where clause for this saved search
   */
  public static function whereClause($id, &$tables, &$whereTables) {
    $params = self::getSearchParams($id);
    if ($params) {
      if (!empty($params['customSearchID'])) {
        // this has not yet been implemented
      }
      else {
        return CRM_Contact_BAO_Query::getWhereClause($params, NULL, $tables, $whereTables);
      }
    }
    return NULL;
  }

  /**
   * Deprecated function, gets a value from Group entity
   *
   * @deprecated
   * @param int $id
   * @param string $value
   *
   * @return string|null
   */
  public static function getName($id, $value = 'name') {
    return parent::getFieldValue('CRM_Contact_DAO_Group', $id, $value, 'saved_search_id');
  }

  /**
   * @deprecated
   * @param array $params
   * @return CRM_Contact_DAO_SavedSearch
   */
  public static function create($params) {
    return self::writeRecord($params);
  }

  /**
   * Callback for hook_civicrm_pre().
   *
   * @param \Civi\Core\Event\PreEvent $event
   *
   * @throws \CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event): void {
    if ($event->action === 'create' || $event->action === 'edit') {
      $event->params['modified_id'] ??= CRM_Core_Session::getLoggedInContactID();
      // Set by mysql
      unset($event->params['modified_date']);

      // Delete empty form values and save as null if completely empty
      if (isset($event->params['form_values']) && is_array($event->params['form_values'])) {
        // Exclude legacy smart groups by checking if api_entity is set
        if (!empty($event->params['api_entity'])) {
          foreach ($event->params['form_values'] as $key => $value) {
            if (is_array($value) && !$value) {
              unset($event->params['form_values'][$key]);
            }
          }
          if (!$event->params['form_values']) {
            $event->params['form_values'] = '';
          }
        }
      }

      // Flush angular caches to refresh search displays
      if (isset($event->params['api_params'])) {
        Civi::container()->get('angular')->clear();
      }
    }
  }

  /**
   * Assign test value.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    if ($fieldName == 'form_values') {
      // A dummy value for form_values.
      $this->{$fieldName} = serialize(
          ['sort_name' => "SortName{$counter}"]);
    }
    else {
      parent::assignTestValues($fieldName, $fieldDef, $counter);
    }
  }

  /**
   * Store search variables in $queryParams which were skipped while processing query params,
   * precisely at CRM_Contact_BAO_Query::fixWhereValues(...). But these variable are required in
   * building smart group criteria otherwise it will cause issues like CRM-18585,CRM-19571
   *
   * @param array $queryParams
   * @param array $formValues
   */
  public static function saveSkippedElement(&$queryParams, $formValues) {
    // these are elements which are skipped in a smart group criteria
    $specialElements = [
      'operator',
      'component_mode',
      'display_relationship_type',
      'uf_group_id',
    ];
    foreach ($specialElements as $element) {
      if (!empty($formValues[$element])) {
        $queryParams[] = [$element, '=', $formValues[$element], 0, 0];
      }
    }
  }

  /**
   * Decode relative custom fields (converted by CRM_Contact_BAO_Query->convertCustomRelativeFields(...))
   *  into desired formValues
   *
   * @param array $formValues
   * @param string $fieldName
   * @param string $op
   * @param array|string|int $value
   *
   * @throws \CRM_Core_Exception
   */
  public static function decodeRelativeFields(&$formValues, $fieldName, $op, $value) {
    // check if its a custom date field, if yes then 'searchDate' format the value
    if (CRM_Contact_BAO_Query::isCustomDateField($fieldName)) {
      return;
    }

    switch ($op) {
      case 'BETWEEN':
        [$formValues[$fieldName . '_from'], $formValues[$fieldName . '_to']] = $value;
        break;

      case '>=':
        $formValues[$fieldName . '_from'] = $value;
        break;

      case '<=':
        $formValues[$fieldName . '_to'] = $value;
        break;
    }
  }

  /**
   * Generate a url to the appropriate search form for a given savedSearch
   *
   * @param int $id
   *   Saved search id
   * @return string
   */
  public static function getEditSearchUrl($id) {
    $savedSearch = self::retrieve(['id' => $id]);
    // APIv4 search
    if (!empty($savedSearch->api_entity)) {
      return CRM_Utils_System::url('civicrm/admin/search', NULL, FALSE, "/edit/$id");
    }
    // Classic search builder
    if (!empty($savedSearch->mapping_id)) {
      $path = 'civicrm/contact/search/builder';
    }
    // Classic custom search
    elseif (!empty($savedSearch->search_custom_id)) {
      $path = 'civicrm/contact/search/custom';
    }
    // Classic advanced search
    else {
      $path = 'civicrm/contact/search/advanced';
    }
    return CRM_Utils_System::url($path, ['reset' => 1, 'ssID' => $id]);
  }

  /**
   * Retrieve pseudoconstant options for $this->api_entity field
   * @return array
   */
  public static function getApiEntityOptions() {
    return Civi\Api4\Entity::get(FALSE)
      ->addSelect('name', 'title_plural')
      ->addOrderBy('title_plural')
      ->execute()
      ->column('title_plural', 'name');
  }

  /**
   * Gets all smart groups that filter based on groupID.
   *
   * @param int $groupID
   *     Group Id to search for.
   * @return array
   */
  public static function getSmartGroupsUsingGroup(int $groupID) {
    $groups = \Civi\Api4\Group::get(FALSE)
      ->addSelect('id', 'title', 'saved_search_id', 'saved_search_id.form_values')
      ->addWhere('saved_search_id', 'IS NOT NULL')
      ->addWhere('saved_search_id.form_values', 'CONTAINS', 'group')
      ->setLimit(0)
      ->execute();
    $smartGroups = [];
    foreach ($groups as $group) {
      // Filter out arrays in Form Values which are group searchs.
      $groupSearches = array_filter(
        $group['saved_search_id.form_values'] ?: [],
        function($v) {
          return ($v[0] == 'group');
        }
      );
      // Check each group search for valid groups.
      foreach ($groupSearches as $groupSearch) {
        $groupFormValues = (array) ($groupSearch[2]['IN'] ?? $groupSearch[2] ?? []);
        if (!empty($groupFormValues) && in_array($groupID, (array) $groupFormValues)) {
          $smartGroups[$group['id']] = [
            'title' => $group['title'],
            'editSearchURL' => self::getEditSearchUrl($group['saved_search_id']),
          ];
        }
      }
    }
    return $smartGroups;
  }

  /**
   * Check SavedSearch access.
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    if (CRM_Core_Permission::check('administer search_kit')) {
      // User has  access to manage all records.
      return;
    }

    $record = $e->getRecord();
    $action = $e->getActionName();
    if (!in_array($action, ['delete', 'update'], TRUE)) {
      // We only care about these actions.
      return;
    }

    try {
      self::checkManageOwnPermission($record, $e->getUserID());
    }
    catch (\Civi\API\Exception\UnauthorizedException) {
      $e->setAuthorized(FALSE);
    }
  }

  /**
   * @inheritDoc
   */
  public static function writeRecord(array $record): CRM_Contact_DAO_SavedSearch {
    if (!empty($record['check_permission']) && !CRM_Core_Permission::check('administer search_kit') && !empty($record['id'])) {
      self::checkManageOwnPermission($record, CRM_Core_Session::getLoggedInContactID());
    }
    return parent::writeRecord($record);
  }

  /**
   * @inheritDoc
   */
  public static function deleteRecord(array $record): CRM_Contact_DAO_SavedSearch {
    if (!empty($record['check_permission']) && !CRM_Core_Permission::check('administer search_kit')) {
      self::checkManageOwnPermission($record, CRM_Core_Session::getLoggedInContactID());
    }
    return parent::deleteRecord($record);
  }

  /**
   * Ensure that the current user has permission to manage their own SavedSearch records.
   *
   * @param array $record The record in which the permission is to be checked.
   * @param int $userID The user ID to check permissions against.
   * @return void
   * @throws CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function checkManageOwnPermission(array $record, int $userID): void {
    // If user doesn't have base permission, block action.
    if (!CRM_Core_Permission::check('manage own search_kit')) {
      throw new \Civi\API\Exception\UnauthorizedException('You do not have permission to manage this SavedSearch.');
    }

    $created_id = empty($record['created_id']) ? self::getFieldValue(parent::class, $record['id'], 'created_id') : $record['created_id'];
    // When we have a created_id, we need to compare against user to see if they are the owner.
    if (!empty($created_id)) {

      // IDs must match in order to manage.
      if ($userID !== (int) $created_id) {
        throw new \Civi\API\Exception\UnauthorizedException('You do not have permission to manage this SavedSearch.');
      }
    }
    else {
      // No created_id and user can't manage all records, so block access.
      throw new \Civi\API\Exception\UnauthorizedException('You do not have permission to manage this SavedSearch.');
    }
  }

}
