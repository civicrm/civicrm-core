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
 * Upgrade logic for the 6.2.x series.
 *
 * Each minor version in the series is handled by either a `6.2.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_2_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_2_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column "civicrm_managed.checksum"', 'alterSchemaField', 'Managed', 'checksum', [
      'title' => ts('Checksum'),
      'sql_type' => 'varchar(45)',
      'input_type' => 'Text',
      'required' => FALSE,
      'description' => ts('Configuration of the managed-entity when last stored'),
    ]);
    $this->addTask('Add in domain_id column to the civicrm_acl_contact_cache_table', 'alterSchemaField', 'ACLContactCache', 'domain_id', [
      'title'  => ts('Domain'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Implicit FK to civicrm_domain'),
      'required' => TRUE,
      'default' => 1,
    ]);
    $this->addTask('Set upload_date in file table', 'setFileUploadDate');
    $this->addTask('Set default for upload_date in file table', 'alterSchemaField', 'File', 'upload_date', [
      'title' => ts('File Upload Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'readonly' => TRUE,
      'default' => 'CURRENT_TIMESTAMP',
      'description' => ts('Date and time that this attachment was uploaded or written to server.'),
    ]);
    $this->addTask('CustomGroup: Make "name" required', 'alterSchemaField', 'CustomGroup', 'name', [
      'title' => ts('Custom Group Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Variable name/programmatic handle for this group.'),
      'required' => TRUE,
      'add' => '1.1',
    ]);
    $this->addTask('CustomGroup: Make "extends" required', 'alterSchemaField', 'CustomGroup', 'extends', [
      'title' => ts('Custom Group Extends'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('Type of object this group extends (can add other options later e.g. contact_address, etc.).'),
      'add' => '1.1',
      'default' => 'Contact',
      'required' => TRUE,
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_CustomGroup', 'getCustomGroupExtendsOptions'],
      ],
    ]);
    $this->addTask('CustomGroup: Make "style" required', 'alterSchemaField', 'CustomGroup', 'style', [
      'title' => ts('Custom Group Style'),
      'sql_type' => 'varchar(15)',
      'input_type' => 'Select',
      'description' => ts('Visual relationship between this form and its parent.'),
      'add' => '1.1',
      'required' => TRUE,
      'default' => 'Inline',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'customGroupStyle'],
      ],
    ]);
    $this->addExtensionTask('Enable CiviImport extension', ['civiimport']);
    $this->addTask('Fix Unique index on acl cache table with domain id', 'fixAclUniqueIndex');
    $this->addTask('Update Activity mappings', 'upgradeImportMappingFields', 'Activity');
    $this->addTask('Update Membership mappings', 'upgradeImportMappingFields', 'Membership');
    $this->addTask('Update Contribution mappings', 'upgradeImportMappingFields', 'Contribution');
    $this->addTask('Update Participant mappings', 'upgradeImportMappingFields', 'Participant');
  }

  public static function setFileUploadDate(): bool {
    $sql = 'SELECT id, uri FROM civicrm_file WHERE upload_date IS NULL';
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dir = CRM_Core_Config::singleton()->customFileUploadDir;
    while ($dao->fetch()) {
      $fileCreatedDate = time();
      if ($dao->uri) {
        $filePath = $dir . $dao->uri;
        // Get created date from file if possible
        if (is_file($filePath)) {
          $fileCreatedDate = filectime($filePath);
        }
      }
      CRM_Core_DAO::executeQuery('UPDATE civicrm_file SET upload_date = %1 WHERE id = %2', [
        1 => [date('YmdHis', $fileCreatedDate), 'Date'],
        2 => [$dao->id, 'Integer'],
      ]);
    }

    return TRUE;
  }

  public static function fixAclUniqueIndex(): bool {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_acl_contact_cache', 'FK_civicrm_acl_contact_cache_user_id');
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_acl_contact_cache', 'UI_user_contact_operation');
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_acl_contact_cache ADD UNIQUE INDEX `UI_user_contact_operation` (`domain_id`, `user_id`, `contact_id`, `operation`)");
    return TRUE;
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
   * @param string $entity
   *
   * @return true
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function upgradeImportMappingFields($context, string $entity): bool {
    $mappingFields = self::getMappingFields($entity);
    $fieldsToConvert = [];
    while ($mappingFields->fetch()) {
      $fieldsToConvert[$mappingFields->name] = self::getConvertedName((string) $mappingFields->name, $entity);
      // Convert the field.
      CRM_Core_DAO::executeQuery(' UPDATE civicrm_mapping_field SET name = %1 WHERE id = %2', [
        1 => [$fieldsToConvert[$mappingFields->name], 'String'],
        2 => [$mappingFields->id, 'Integer'],
      ]);
    }

    $userJob = new \CRM_Core_DAO_UserJob();
    $userJob->job_type = strtolower($entity) . '_import';
    $userJob->find();
    while ($userJob->fetch()) {
      $metadata = json_decode($userJob->metadata, TRUE);
      if (empty($metadata['import_mappings']) && !empty($metadata['Template']['mapping_id'])) {
        $mappingByID = self::getImportMappings($entity, $metadata['Template']['mapping_id']);
        $metadata['import_mappings'] = reset($mappingByID);
      }
      foreach ($metadata['import_mappings'] as &$mapping) {
        if (!empty($mapping['name'])) {
          $convertedName = self::getConvertedName($mapping['name'], $entity);
          if ($convertedName === 'do_not_import') {
            $convertedName = '';
          }
          $mapping['name'] = $convertedName;
        }
        else {
          $mapping['name'] = '';
        }
      }
      $userJob->metadata = json_encode($metadata);
      $userJob->save();
    }
    self::ensureTemplateJobsExist($entity);
    return TRUE;
  }

  public static function ensureTemplateJobsExist(string $entity) {
    $mappings = self::getImportMappings($entity);
    foreach ($mappings as $mappingName => $mapping) {
      if (!CRM_Core_DAO::singleValueQuery('
       SELECT id FROM civicrm_user_job WHERE name = %1', [1 => ['import_' . $mappingName, 'String']])) {
        // Create a User Job.
        $userJob = new \CRM_Core_DAO_UserJob();
        $userJob->name = 'import_' . $mappingName;
        $userJob->is_template = TRUE;
        $userJob->job_type = strtolower($entity) . '_import';
        $userJob->status_id = 2;
        $userJob->metadata = json_encode([
          'import_mappings' => $mapping,
        ]);
        $userJob->save();
      }
    }
  }

  /**
   * @param string $mappingFieldsName
   * @param string $type
   *
   * @return string
   */
  public static function getConvertedName(string $mappingFieldsName, string $type): string {
    $prefixMap = [
      'target_contact' => 'TargetContact',
      'source_contact' => 'SourceContact',
      'contact' => 'Contact',
      'soft_credit.contact' => 'SoftCreditContact',
    ];
    if (empty($mappingFieldsName) || $mappingFieldsName === 'do_not_import') {
      return 'do_not_import';
    }
    $parts = explode('.', $mappingFieldsName);
    // For contribution imports we may have failed to convert these fields in 6.1
    // as they are not generally available for other imports.
    $contactFields = [
      'first_name',
      'last_name',
      'middle_name',
      'email_primary.email',
      'nick_name',
      'do_not_trade',
      'do_not_email',
      'do_not_mail',
      'do_not_sms',
      'do_not_phone',
      'is_opt_out',
      'external_identifier',
      'legal_identifier',
      'legal_name',
      'preferred_communication_method',
      'preferred_language',
      'gender_id',
      'prefix_id',
      'suffix_id',
      'job_title',
      'birth_date',
      'deceased_date',
      'household_name',
    ];
    if (in_array($mappingFieldsName, $contactFields)) {
      // This would happen for Contribution fields that were not correctly updated in 6.1.
      return 'Contact.' . $mappingFieldsName;
    }
    if (!isset($prefixMap[$parts[0]])) {
      // This is a 'native' mapping, add a prefix.
      return $type . '.' . $mappingFieldsName;
    }
    else {
      return str_replace($parts[0], $prefixMap[$parts[0]], $mappingFieldsName);
    }
  }

  /**
   * @param string $entity
   *
   * @return \CRM_Core_DAO|object
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getMappingFields(string $entity) {
    $importType = 'Import ' . $entity;
    $mappingFields = CRM_Core_DAO::executeQuery('
      SELECT field.id, field.name, mapping.id as mapping_id, mapping.name as mapping_name FROM civicrm_mapping_field field
        INNER JOIN civicrm_mapping mapping
          ON field.mapping_id = mapping.id
          AND mapping_type_id = ' . self::getMappingTypeID($importType));
    return $mappingFields;
  }

  /**
   * @param string $entity
   * @param int|null $id
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getImportMappings(string $entity, ?int $id = NULL): array {
    $mappingFields = self::getMappingFields($entity);
    $mappings = [];
    while ($mappingFields->fetch()) {
      if ($id && $mappingFields->mapping_id != $id) {
        continue;
      }
      $mappings[$mappingFields->mapping_name][] = ['name' => ($mappingFields->name === 'do_not_import' ? '' : $mappingFields->name)];
    }
    return $mappings;
  }

}
