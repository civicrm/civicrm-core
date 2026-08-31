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
 * Upgrade logic for the 6.19.x series.
 *
 * Each minor version in the series is handled by either a `6.19.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_19_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixNineteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_19_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop OptionValue.domain_id column', 'dropColumn', 'civicrm_option_value', 'domain_id');
    $this->addTask('Update Localization menu label', 'updateLocalizationMenuLabels');
    $this->addTask('Increase Tag.name length to 128', 'alterSchemaField', 'Tag', 'name', [
      'title' => ts('Tag Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Unique machine name'),
      'add' => '1.1',
    ]);
    $this->addTask('Increase Tag.label length to 128', 'alterSchemaField', 'Tag', 'label', [
      'title' => ts('Tag Label'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('User-facing tag name'),
      'add' => '5.68',
    ]);
    $this->addTask('Update UFGroup.is_cms_user', 'alterSchemaField', 'UFGroup', 'is_cms_user', [
      'title' => ts('User account registration'),
      'sql_type' => 'tinyint',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Should we create a cms user for this profile'),
      'add' => '1.8',
      'default' => 0,
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'profileUserRegistrationMode'],
      ],
    ]);
    $this->addTask('Increase CustomGroup.title length to 128', 'alterSchemaField', 'CustomGroup', 'title', [
      'title' => ts('Custom Group Title'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'required' => TRUE,
      'localizable' => TRUE,
      'description' => ts('Friendly Name.'),
      'add' => '1.1',
    ]);
    $this->addTask('Decode Mailing.template_options HTML entities', 'decodeMailingTemplateOptions');
  }

  /**
   * Rename Localization > 'Date Formats' to 'Date and Time'
   * And 'Languages, Currency, Locations' to 'Language and Region'
   */
  public static function updateLocalizationMenuLabels(CRM_Queue_TaskContext $ctx): bool {
    $changes = [
      'Date Formats' => 'Date and Time',
      'Languages, Currency, Locations' => 'Language and Region',
    ];
    foreach ($changes as $old => $new) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_navigation SET label = %1 WHERE name = %2 AND label = %3', [
        1 => [$new, 'String'],
        2 => [$old, 'String'],
        3 => [$old, 'String'],
      ]);
    }
    return TRUE;
  }

  /**
   * Fixes https://lab.civicrm.org/extensions/mosaico/-/work_items/710
   */
  public static function decodeMailingTemplateOptions(CRM_Queue_TaskContext $ctx): bool {
    $coder = CRM_Utils_API_HTMLInputCoder::singleton();
    $dao = CRM_Core_DAO::executeQuery("
      SELECT id, template_options
      FROM civicrm_mailing
      WHERE template_options LIKE '%&lt;%' OR template_options LIKE '%&gt;%'
    ");

    while ($dao->fetch()) {
      $options = CRM_Core_DAO::unSerializeField($dao->template_options, CRM_Core_DAO::SERIALIZE_JSON);
      if (is_array($options)) {
        $coder->decodeOutput($options);
        $cleaned = CRM_Core_DAO::serializeField($options, CRM_Core_DAO::SERIALIZE_JSON);
        CRM_Core_DAO::executeQuery('UPDATE civicrm_mailing SET template_options = %1 WHERE id = %2', [
          1 => [$cleaned, 'String'],
          2 => [$dao->id, 'Integer'],
        ]);
      }
    }
    return TRUE;
  }

}
