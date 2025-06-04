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
 * Upgrade logic for the 6.1.x series.
 *
 * Each minor version in the series is handled by either a `6.1.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_1_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixOne extends CRM_Upgrade_Incremental_Base {

  /**
   * @param string $importType
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public static function getMappingTypeID(string $importType): int {
    $mappingTypeID = (int) CRM_Core_DAO::singleValueQuery("
      SELECT option_value.value
      FROM civicrm_option_value option_value
        INNER JOIN civicrm_option_group option_group
        ON option_group.id = option_value.option_group_id
        AND option_group.name =  'mapping_type'
      WHERE option_value.name = '{$importType}'");
    return $mappingTypeID;
  }

  /**
   * @param \CRM_Queue_TaskContext|null $context
   * @param string $importType
   *
   * @return true
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function upgradeImportMappingFields($context, string $importType): bool {
    $mappingFields = CRM_Core_DAO::executeQuery('
      SELECT field.id, field.name FROM civicrm_mapping_field field
        INNER JOIN civicrm_mapping mapping
          ON field.mapping_id = mapping.id
          AND mapping_type_id = ' . self::getMappingTypeID($importType));
    // The only possible contact field for participant import is email
    // as only the email rule can be selected. However, keeping the 'set'
    // together (from Contribution convert in FiveFiftyFour) feels like
    // it has merit
    $contactPrefix = '';
    if ($importType === 'Import Membership' || $importType === 'Import Contribution' || $importType === 'Import Participant') {
      $contactPrefix = 'contact.';
    }
    if ($importType === 'Import Activity') {
      $contactPrefix = 'target_contact.';
    }
    $fieldsToConvert = [
      'email' => $contactPrefix . 'email_primary.email',
      'phone' => $contactPrefix . 'phone_primary.phone',
      'street_address' => $contactPrefix . 'address_primary.street_address',
      'supplemental_address_1' => $contactPrefix . 'address_primary.supplemental_address_1',
      'supplemental_address_2' => $contactPrefix . 'address_primary.supplemental_address_2',
      'supplemental_address_3' => $contactPrefix . 'address_primary.supplemental_address_3',
      'city' => $contactPrefix . 'address_primary.city',
      'county_id' => $contactPrefix . 'address_primary.county_id',
      'state_province_id' => $contactPrefix . 'address_primary.state_province_id',
      'country_id' => $contactPrefix . 'address_primary.country_id',
      'external_identifier' => $contactPrefix . 'external_identifier',
      'membership_id' => 'id',
      'membership_contact_id' => 'contact.id',
      'membership_start_date' => 'start_date',
      'membership_type_id' => 'membership_type_id',
      'membership_join_date' => 'join_date',
      'membership_end_date' => 'end_date',
      'membership_source' => 'source',
      'member_is_override' => 'is_override',
      'status_override_end_date' => 'status_override_end_date',
      'member_is_test' => 'is_test',
      'member_is_pay_later' => 'is_pay_later',
      'member_campaign_id' => 'campaign_id',
      'source_contact_id' => 'source_contact.id',
      'target_contact_id' => 'target_contact.id',
      'source_contact_external_identifier' => 'source_contact.external_identifier',
      'activity_id' => 'id',
      'activity_subject' => 'subject',
      'activity_type_id' => 'activity_type_id',
      'activity_date_time' => 'activity_date_time',
      'activity_duration' => 'duration',
      'activity_location' => 'location',
      'activity_details' => 'details',
      'activity_status_id' => 'status_id',
      'activity_priority_id' => 'priority_id',
      'priority_id' => 'priority_id',
      'activity_is_test' => 'is_test',
      'activity_is_deleted' => 'is_deleted',
      'activity_campaign_id' => 'campaign_id',
      'activity_engagement_level' => 'engagement_level',
      'activity_is_star' => 'is_star',
    ];

    if ($importType === 'Import Contribution') {
      $fieldsToConvert['source'] = 'contact.source';
      $fieldsToConvert['id'] = 'contact.id';
      $fieldsToConvert['contribution_source'] = 'source';
      $fieldsToConvert['contribution_id'] = 'id';
      $fieldsToConvert['contribution_contact_id'] = 'contact_id';
    }

    $customFields = CRM_Core_DAO::executeQuery('
      SELECT custom_field.id, custom_field.name, custom_group.name as custom_group_name, custom_group.extends
      FROM civicrm_custom_field custom_field INNER JOIN civicrm_custom_group custom_group
      ON custom_field.custom_group_id = custom_group.id
      WHERE extends IN ("Contact", "Individual", "Organization", "Household", "Participant", "Membership", "Activity")
    ');
    while ($customFields->fetch()) {
      $prefix = in_array($customFields->extends, ['Contact', 'Individual', 'Household', 'Organization']) ? $contactPrefix : '';
      $fieldsToConvert['custom_' . $customFields->id] = $prefix . $customFields->custom_group_name . '.' . $customFields->name;
    }
    while ($mappingFields->fetch()) {
      // Convert the field.
      if (isset($fieldsToConvert[$mappingFields->name])) {
        CRM_Core_DAO::executeQuery(' UPDATE civicrm_mapping_field SET name = %1 WHERE id = %2', [
          1 => [$fieldsToConvert[$mappingFields->name], 'String'],
          2 => [$mappingFields->id, 'Integer'],
        ]);
      }
    }
    if ($importType === 'Import Contribution') {
      CRM_Upgrade_Incremental_php_SixTwo::upgradeUserJobs('Contribution');
    }
    return TRUE;
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_1_alpha1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update afform tab names', 'updateAfformTabs');
    $this->addTask('Update import mappings', 'updateFieldMappingsForImport');
    $this->addTask('Replace Clear Caches & Reset Paths with Clear Caches in Nav Menu', 'updateUpdateConfigBackendNavItem');
    $this->addTask('Install ImportTemplateField entity', 'createEntityTable', '6.1.alpha1.ImportTemplateField.entityType.php');
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_1_beta1(string $rev): void {
    $this->addTask('Update import mappings', 'upgradeImportMappingFields', 'Import Contribution');
    $this->addTask('Increase site email display name length', 'alterSchemaField', 'SiteEmailAddress', 'display_name', [
      'title' => ts('Display Name'),
      'sql_type' => 'varchar(254)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Full name of the sender'),
      'add' => '6.0',
    ]);
  }

  /**
   * The updateConfigBackend page has been removed - so remove any nav items linking to it
   *
   * Add a new menu item to Clear Caches directly
   *
   * @return bool
   */
  public static function updateUpdateConfigBackendNavItem() {
    $domainID = CRM_Core_Config::domainID();

    // delete any entries to the path that no longer exists
    // doesn't seem necessary to restrict by domain?
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_navigation WHERE url = "civicrm/admin/setting/updateConfigBackend?reset=1"');

    $systemSettingsNavItem = CRM_Core_DAO::singleValueQuery("SELECT id
      FROM civicrm_navigation
      WHERE name = 'System Settings' AND domain_id = {$domainID}
    ");

    if (!$systemSettingsNavItem) {
      \Civi::log()->debug('Couldn\'t find System Settings Nav Menu Item to create new Clear Caches entry');
      return TRUE;
    }

    $exists = CRM_Core_DAO::singleValueQuery("SELECT id
      FROM civicrm_navigation
      WHERE name = 'cache_clear' AND domain_id = {$domainID}
    ");

    if ($exists) {
      // already exists, we can finish early
      return TRUE;
    }

    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_navigation
        (
          url, label, name,
          has_separator, parent_id, weight,
          permission, domain_id
        )
      VALUES
        (
          'civicrm/menu/rebuild?reset=1', 'Clear Caches', 'cache_clear',
          1, {$systemSettingsNavItem}, 0,
          'administer CiviCRM', {$domainID}
        )
    ");

    return TRUE;
  }

  /**
   * If the ContactLayout extension is installed, update its stored tab names to keep up
   * with core changes to Afform tabs.
   *
   * @see https://github.com/civicrm/civicrm-core/pull/27196
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateAfformTabs(CRM_Queue_TaskContext $ctx) {
    $convert = function($id) {
      if ($id === 'afsearchGrants') {
        return 'grant';
      }
      if (preg_match('#^(afform|afsearch)#i', $id)) {
        $tabId = CRM_Utils_String::convertStringToSnakeCase(preg_replace('#^(afformtab|afsearchtab|afform|afsearch)#i', '', $id));
        // @see afform_civicrm_tabset()
        if (str_starts_with($tabId, 'custom_')) {
          // custom group tab forms use name, but need to replace tabs using ID
          // remove 'afsearchTabCustom_' from the form name to get the group name
          $groupName = substr($id, 18);
          $groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $groupName, 'id', 'name');
          if ($groupId) {
            $tabId = 'custom_' . $groupId;
          }
        }
        return $tabId;
      }
      return $id;
    };

    $setting = \Civi::settings()->get('contactlayout_default_tabs');
    if ($setting && is_array($setting)) {
      foreach ($setting as $index => $tab) {
        $setting[$index]['id'] = $convert($tab['id']);
      }
      \Civi::settings()->set('contactlayout_default_tabs', $setting);
    }
    if (CRM_Core_DAO::checkTableExists('civicrm_contact_layout')) {
      // Can't use the api due to extension loading issues
      $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_contact_layout');
      while ($dao->fetch()) {
        if (!empty($dao->tabs)) {
          $tabs = CRM_Core_DAO::unSerializeField($dao->tabs, CRM_Core_DAO::SERIALIZE_JSON);
          foreach ($tabs as $index => $tab) {
            $tabs[$index]['id'] = $convert($tab['id']);
          }
          CRM_Core_DAO::executeQuery('UPDATE civicrm_contact_layout SET tabs = %1 WHERE id = %2', [
            1 => [CRM_Core_DAO::serializeField($tabs, CRM_Core_DAO::SERIALIZE_JSON), 'String'],
            2 => [$dao->id, 'Integer'],
          ]);
        }
      }
    }
    return TRUE;
  }

  /**
   * Update the fields that have been converted to apiv4 within the field mappings
   * - participant_import : email => email_primary.email (there are no other pre-existing contact fields)
   *
   * @return bool
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function updateFieldMappingsForImport(): bool {
    $importTypes = ['Import Participant', 'Import Membership', 'Import Activity'];
    foreach ($importTypes as $importType) {
      self::upgradeImportMappingFields(NULL, $importType);
    }
    return TRUE;
  }

}
