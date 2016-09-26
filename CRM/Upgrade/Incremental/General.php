<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This class contains generic upgrade logic which runs regardless of version.
 */
class CRM_Upgrade_Incremental_General {

  /**
   * The recommended PHP version.
   */
  const MIN_RECOMMENDED_PHP_VER = '5.5';

  /**
   * The minimum PHP version required to install Civi.
   *
   * @see install/index.php
   */
  const MIN_INSTALL_PHP_VER = '5.3.4';

  /**
   * The minimum PHP version required to avoid known
   * limits or defects.
   */
  const MIN_DEFECT_PHP_VER = '5.3.23';

  /**
   * Compute any messages which should be displayed before upgrade.
   *
   * @param string $preUpgradeMessage
   *   alterable.
   * @param $currentVer
   * @param $latestVer
   */
  public static function setPreUpgradeMessage(&$preUpgradeMessage, $currentVer, $latestVer) {
    if (version_compare(phpversion(), self::MIN_RECOMMENDED_PHP_VER) < 0) {
      $preUpgradeMessage .= '<p>' .
        ts('This webserver is running an outdated version of PHP (%1). It is strongly recommended to upgrade to PHP %2 or later, as older versions can present a security risk.', array(
          1 => phpversion(),
          2 => self::MIN_RECOMMENDED_PHP_VER,
        )) .
        '</p>';
    }

    // http://issues.civicrm.org/jira/browse/CRM-13572
    // Depending on how the code was upgraded, some sites may still have copies of old
    // source files left behind. This is often a forgivable offense, but it's quite
    // dangerous for CIVI-SA-2013-001.
    global $civicrm_root;
    $ofcFile = "$civicrm_root/packages/OpenFlashChart/php-ofc-library/ofc_upload_image.php";
    if (file_exists($ofcFile)) {
      if (@unlink($ofcFile)) {
        $preUpgradeMessage .= '<br />' . ts('This system included an outdated, insecure script (%1). The file was automatically deleted.', array(
            1 => $ofcFile,
          ));
      }
      else {
        $preUpgradeMessage .= '<br />' . ts('This system includes an outdated, insecure script (%1). Please delete it.', array(
            1 => $ofcFile,
          ));
      }
    }

    if (Civi::settings()->get('enable_innodb_fts')) {
      // The FTS indexing feature dynamically manipulates the schema which could
      // cause conflicts with other layers that manipulate the schema. The
      // simplest thing is to turn it off and back on.

      // It may not always be necessary to do this -- but I doubt we're going to test
      // systematically in future releases.  When it is necessary, one could probably
      // ignore the matter and simply run CRM_Core_InnoDBIndexer::fixSchemaDifferences
      // after the upgrade.  But that's speculative.  For now, we'll leave this
      // advanced feature in the hands of the sysadmin.
      $preUpgradeMessage .= '<br />' . ts('This database uses InnoDB Full Text Search for optimized searching. The upgrade procedure has not been tested with this feature. You should disable (and later re-enable) the feature by navigating to "Administer => System Settings => Miscellaneous".');
    }
  }

  /**
   * @param $message
   * @param $latestVer
   * @param $currentVer
   */
  public static function checkMessageTemplate(&$message, $latestVer, $currentVer) {

    $sql = "SELECT orig.workflow_id as workflow_id,
             orig.msg_title as title
            FROM civicrm_msg_template diverted JOIN civicrm_msg_template orig ON (
                diverted.workflow_id = orig.workflow_id AND
                orig.is_reserved = 1                    AND (
                    diverted.msg_subject != orig.msg_subject OR
                    diverted.msg_text    != orig.msg_text    OR
                    diverted.msg_html    != orig.msg_html
                )
            )";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $workflows[$dao->workflow_id] = $dao->title;
    }

    if (empty($workflows)) {
      return;
    }

    $html = NULL;
    $pathName = dirname(dirname(__FILE__));
    $flag = FALSE;
    foreach ($workflows as $workflow => $title) {
      $name = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
        $workflow,
        'name',
        'id'
      );

      // check if file exists locally
      $textFileName = implode(DIRECTORY_SEPARATOR,
        array(
          $pathName,
          "{$latestVer}.msg_template",
          'message_templates',
          "{$name}_text.tpl",
        )
      );

      $htmlFileName = implode(DIRECTORY_SEPARATOR,
        array(
          $pathName,
          "{$latestVer}.msg_template",
          'message_templates',
          "{$name}_html.tpl",
        )
      );

      if (file_exists($textFileName) ||
        file_exists($htmlFileName)
      ) {
        $flag = TRUE;
        $html .= "<li>{$title}</li>";
      }
    }

    if ($flag == TRUE) {
      $html = "<ul>" . $html . "<ul>";

      $message .= '<br />' . ts("The default copies of the message templates listed below will be updated to handle new features or correct a problem. Your installation has customized versions of these message templates, and you will need to apply the updates manually after running this upgrade. <a href='%1' style='color:white; text-decoration:underline; font-weight:bold;' target='_blank'>Click here</a> for detailed instructions. %2", array(
            1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Message+Templates#MessageTemplates-UpgradesandCustomizedSystemWorkflowTemplates',
            2 => $html,
          ));
    }
  }

}
