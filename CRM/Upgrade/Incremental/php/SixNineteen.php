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

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    parent::setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
    if ($rev === '6.19.alpha1') {
      $nullCount = (int) CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_case_type WHERE definition IS NULL OR definition = ''");
      if ($nullCount > 0) {
        $preUpgradeMessage .= '<p>' .
          ts('Case type configuration XML files will be copied into the database and no longer read from disk.') .
          '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_19_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop OptionValue.domain_id column', 'dropColumn', 'civicrm_option_value', 'domain_id');
    $this->addTask(ts('Copy CaseType XML configuration files into database'), 'copyCaseTypeXmlToDatabase');
  }

  /**
   * Copy XML configuration files for case types into database definition column if NULL or empty.
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function copyCaseTypeXmlToDatabase(CRM_Queue_TaskContext $ctx): bool {
    $dao = CRM_Core_DAO::executeQuery("SELECT id, name FROM civicrm_case_type WHERE definition IS NULL OR definition = ''");
    $caseTypesViaHook = [];
    CRM_Utils_Hook::caseTypes($caseTypesViaHook);
    $config = CRM_Core_Config::singleton();

    while ($dao->fetch()) {
      $candidates = [$dao->name];
      $munged = CRM_Case_XMLProcessor::mungeCaseType($dao->name);
      if ($munged !== $dao->name) {
        $candidates[] = $munged;
      }

      $fileName = NULL;
      foreach ($candidates as $name) {
        if (isset($caseTypesViaHook[$name]['file']) && file_exists($caseTypesViaHook[$name]['file'])) {
          $fileName = $caseTypesViaHook[$name]['file'];
          break;
        }
        if (!empty($config->customTemplateDir)) {
          $customFile = implode(DIRECTORY_SEPARATOR, [$config->customTemplateDir, 'CRM', 'Case', 'xml', 'configuration', "$name.xml"]);
          if (file_exists($customFile)) {
            $fileName = $customFile;
            break;
          }
        }
        $coreFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'Case', 'xml', 'configuration', "$name.xml"]);
        if (file_exists($coreFile)) {
          $fileName = $coreFile;
          break;
        }
        $sampleFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'Case', 'xml', 'configuration.sample', "$name.xml"]);
        if (file_exists($sampleFile)) {
          $fileName = $sampleFile;
          break;
        }
      }

      if ($fileName && file_exists($fileName)) {
        $dom = new DOMDocument();
        $xmlString = file_get_contents($fileName);
        $dom->loadXML($xmlString);
        $dom->documentURI = $fileName;
        $dom->xinclude();
        $fileXml = simplexml_import_dom($dom);
        if ($fileXml) {
          $xmlOutput = $fileXml->asXML();
          CRM_Core_DAO::executeQuery("UPDATE civicrm_case_type SET definition = %1 WHERE id = %2", [
            1 => [$xmlOutput, 'String'],
            2 => [$dao->id, 'Integer'],
          ]);
        }
      }
    }

    return TRUE;
  }

}
