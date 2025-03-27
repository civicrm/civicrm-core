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
class CRM_Upgrade_Incremental_UserJob {

  /**
   * Version we are upgrading to.
   *
   * @var string
   */
  protected $upgradeVersion;

  /**
   * @return string
   */
  public function getUpgradeVersion() {
    return $this->upgradeVersion;
  }

  /**
   * @param string $upgradeVersion
   */
  public function setUpgradeVersion($upgradeVersion) {
    $this->upgradeVersion = $upgradeVersion;
  }

  private array $jobs;

  /**
   * CRM_Upgrade_Incremental_MessageTemplates constructor.
   *
   * @param string $upgradeVersion
   */
  public function __construct($upgradeVersion, array $jobs) {
    $this->setUpgradeVersion($upgradeVersion);
    $this->jobs = $jobs;
  }

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
  public static function upgradeImportMappingFields(string $importType): bool {
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
      $userJob = new \CRM_Core_DAO_UserJob();
      $userJob->job_type = 'contribution_import';
      $userJob->find();
      while ($userJob->fetch()) {
        $metadata = json_decode($userJob->metadata, TRUE);
        foreach ($metadata['import_mappings'] as &$mapping) {
          if (isset($fieldsToConvert[$mapping['name']])) {
            $mapping['name'] = $fieldsToConvert[$mapping['name']];
          }
          $userJob->metadata = json_encode($metadata);
          $userJob->save();
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
  public function updateFieldMappingsForImport(): bool {
    foreach ($this->jobs as $importType) {
      self::upgradeImportMappingFields($importType);
    }
    return TRUE;
  }

}
