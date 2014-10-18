<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class is a container for legacy upgrade logic which predates
 * the current 'CRM/Incremental/php/*' structure.
 */
class CRM_Upgrade_Incremental_Legacy {

  /**
   * Compute any messages which should be displayed before upgrade
   *
   * @param $preUpgradeMessage string, alterable
   * @param $currentVer
   * @param $latestVer
   */
  static function setPreUpgradeMessage(&$preUpgradeMessage, $currentVer, $latestVer) {
    $upgrade = new CRM_Upgrade_Form();
    $template = CRM_Core_Smarty::singleton();

    if ((version_compare($currentVer, '3.3.alpha1') < 0 &&
        version_compare($latestVer, '3.3.alpha1') >= 0
      ) ||
      (version_compare($currentVer, '3.4.alpha1') < 0 &&
        version_compare($latestVer, '3.4.alpha1') >= 0
      )
    ) {
      $query = "
SELECT  id
  FROM  civicrm_mailing_job
 WHERE  status NOT IN ( 'Complete', 'Canceled' ) AND is_test = 0 LIMIT 1";
      $mjId = CRM_Core_DAO::singleValueQuery($query);
      if ($mjId) {
        $preUpgradeMessage = ts("There are one or more Scheduled or In Progress mailings in your install. Scheduled mailings will not be sent and In Progress mailings will not finish if you continue with the upgrade. We strongly recommend that all Scheduled and In Progress mailings be completed or cancelled and then upgrade your CiviCRM install.");
      }
    }

    //turning some tables to monolingual during 3.4.beta3, CRM-7869
    $upgradeTo = str_replace('4.0.', '3.4.', $latestVer);
    $upgradeFrom = str_replace('4.0.', '3.4.', $currentVer);

    // check for changed message templates
    self::checkMessageTemplate($template, $preUpgradeMessage, $upgradeTo, $upgradeFrom);

    $upgrade = new CRM_Upgrade_Form();
    if ($upgrade->multilingual &&
      version_compare($upgradeFrom, '3.4.beta3') == -1 &&
      version_compare($upgradeTo, '3.4.beta3') >= 0
    ) {
      $config = CRM_Core_Config::singleton();
      $preUpgradeMessage .= '<br />' . ts("As per <a href='%1'>the related blog post</a>, we are making contact names, addresses and mailings monolingual; the values entered for the default locale (%2) will be preserved and values for other locales removed.", array(1 => 'http://civicrm.org/blogs/shot/multilingual-civicrm-3440-making-some-fields-monolingual', 2 => $config->lcMessages));
    }

    if (version_compare($currentVer, '3.4.6') == -1 &&
      version_compare($latestVer, '3.4.6') >= 0
    ) {
      $googleProcessorExists = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_payment_processor WHERE payment_processor_type = 'Google_Checkout' AND is_active = 1 LIMIT 1;");

      if ($googleProcessorExists) {
        $preUpgradeMessage .= '<br />' . ts('To continue using Google Checkout Payment Processor with latest version of CiviCRM, requires updating merchant account settings. Please refer "Set API callback URL and other settings" section of <a href="%1" target="_blank"><strong>Google Checkout Configuration</strong></a> doc.', array(1 => 'http://wiki.civicrm.org/confluence/x/zAJTAg'));
      }
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
          1 => $ofcFile
        ));
      } else {
        $preUpgradeMessage .= '<br />' . ts('This system includes an outdated, insecure script (%1). Please delete it.', array(
          1 => $ofcFile
        ));
      }
    }

    if (CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME, 'enable_innodb_fts', NULL, FALSE)) {
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
   * @param $template
   * @param $message
   * @param $latestVer
   * @param $currentVer
   */
  static function checkMessageTemplate(&$template, &$message, $latestVer, $currentVer) {
    if (version_compare($currentVer, '3.1.alpha1') < 0) {
      return;
    }

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

    $dao = &CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $workflows[$dao->workflow_id] = $dao->title;
    }

    if (empty($workflows)) {
      return;
    }

    $html     = NULL;
    $pathName = dirname(dirname(__FILE__));
    $flag     = FALSE;
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

      $message .= '<br />' . ts("The default copies of the message templates listed below will be updated to handle new features or correct a problem. Your installation has customized versions of these message templates, and you will need to apply the updates manually after running this upgrade. <a href='%1' style='color:white; text-decoration:underline; font-weight:bold;' target='_blank'>Click here</a> for detailed instructions. %2", array(1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Message+Templates#MessageTemplates-UpgradesandCustomizedSystemWorkflowTemplates', 2 => $html));
    }
  }

  /**
   * Compute any messages which should be displayed after upgrade
   *
   * @param $postUpgradeMessage string, alterable
   * @param $rev string, an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs
   * @return void
   */
  static function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '3.2.alpha1') {
      $postUpgradeMessage .= '<br />' . ts("We have reset the COUNTED flag to false for the event participant status 'Pending from incomplete transaction'. This change ensures that people who have a problem during registration can try again.");
    }
    if ($rev == '3.2.beta3') {
      $subTypes = CRM_Contact_BAO_ContactType::subTypes();

      if (is_array($subTypes) && !empty($subTypes)) {
        $config = CRM_Core_Config::singleton();
        $subTypeTemplates = array();

        if (isset($config->customTemplateDir)) {
          foreach ($subTypes as $key => $subTypeName) {
            $customContactSubTypeEdit = $config->customTemplateDir . "CRM/Contact/Form/Edit/" . $subTypeName . ".tpl";
            $customContactSubTypeView = $config->customTemplateDir . "CRM/Contact/Page/View/" . $subTypeName . ".tpl";
            if (file_exists($customContactSubTypeEdit) || file_exists($customContactSubTypeView)) {
              $subTypeTemplates[$subTypeName] = $subTypeName;
            }
          }
        }

        foreach ($subTypes as $key => $subTypeName) {
          $customContactSubTypeEdit = $config->templateDir . "CRM/Contact/Form/Edit/" . $subTypeName . ".tpl";
          $customContactSubTypeView = $config->templateDir . "CRM/Contact/Page/View/" . $subTypeName . ".tpl";
          if (file_exists($customContactSubTypeEdit) || file_exists($customContactSubTypeView)) {
            $subTypeTemplates[$subTypeName] = $subTypeName;
          }
        }

        if (!empty($subTypeTemplates)) {
          $subTypeTemplates = implode(',', $subTypeTemplates);
          $postUpgradeMessage .= '<br />' . ts('You are using custom template for contact subtypes: %1.', array(1 => $subTypeTemplates)) . '<br />' . ts('You need to move these subtype templates to the SubType directory in %1 and %2 respectively.', array(1 => 'CRM/Contact/Form/Edit', 2 => 'CRM/Contact/Page/View'));
        }
      }
    }
    if ($rev == '3.2.beta4') {
      $statuses = array('New', 'Current', 'Grace', 'Expired', 'Pending', 'Cancelled', 'Deceased');
      $sql = "
SELECT  count( id ) as statusCount
  FROM  civicrm_membership_status
 WHERE  name IN ( '" . implode("' , '", $statuses) . "' ) ";
      $count = CRM_Core_DAO::singleValueQuery($sql);
      if ($count < count($statuses)) {
        $postUpgradeMessage .= '<br />' . ts("One or more Membership Status Rules was disabled during the upgrade because it did not match a recognized status name. if custom membership status rules were added to this site - review the disabled statuses and re-enable any that are still needed (Administer > CiviMember > Membership Status Rules).");
      }
    }
    if ($rev == '3.4.alpha1') {
      $renamedBinScripts = array(
        'ParticipantProcessor.php',
        'RespondentProcessor.php',
        'UpdateGreeting.php',
        'UpdateMembershipRecord.php',
        'UpdatePledgeRecord.php ',
      );
      $postUpgradeMessage .= '<br />' . ts('The following files have been renamed to have a ".php" extension instead of a ".php.txt" extension') . ': ' . implode(', ', $renamedBinScripts);
    }
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_2_2_alpha1($rev) {
    for ($stepID = 1; $stepID <= 4; $stepID++) {
      $formName = "CRM_Upgrade_TwoTwo_Form_Step{$stepID}";
      $form = new $formName();

      $error = NULL;
      if (!$form->verifyPreDBState($error)) {
        if (!isset($error)) {
          $error = "pre-condition failed for current upgrade step $stepID, rev $rev";
        }
        CRM_Core_Error::fatal($error);
      }

      if ($stepID == 4) {
        return;
      }

      $template = CRM_Core_Smarty::singleton();

      $eventFees = array();
      $query     = "SELECT og.id ogid FROM civicrm_option_group og WHERE og.name LIKE  %1";
      $params    = array(1 => array('civicrm_event_page.amount%', 'String'));
      $dao       = CRM_Core_DAO::executeQuery($query, $params);
      while ($dao->fetch()) {
        $eventFees[$dao->ogid] = $dao->ogid;
      }
      $template->assign('eventFees', $eventFees);

      $form->upgrade();

      if (!$form->verifyPostDBState($error)) {
        if (!isset($error)) {
          $error = "post-condition failed for current upgrade step $stepID, rev $rev";
        }
        CRM_Core_Error::fatal($error);
      }
    }
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_2_1_2($rev) {
    $formName = "CRM_Upgrade_TwoOne_Form_TwoOneTwo";
    $form = new $formName($rev);

    $error = NULL;
    if (!$form->verifyPreDBState($error)) {
      if (!isset($error)) {
        $error = "pre-condition failed for current upgrade for $rev";
      }
      CRM_Core_Error::fatal($error);
    }

    $form->upgrade();

    if (!$form->verifyPostDBState($error)) {
      if (!isset($error)) {
        $error = "post-condition failed for current upgrade for $rev";
      }
      CRM_Core_Error::fatal($error);
    }
  }

  /**
   * This function should check if if need to skip current sql file
   * Name of this function will change according to the latest release
   *
   */
  static function upgrade_2_2_alpha3($rev) {
    // skip processing sql file, if fresh install -
    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'mail_protocol', 'id', 'name')) {
      $upgrade = new CRM_Upgrade_Form();
      $upgrade->processSQL($rev);
    }
    return TRUE;
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_2_2_beta1($rev) {
    if (!CRM_Core_DAO::checkFieldExists('civicrm_pcp_block', 'notify_email')) {
      $template = CRM_Core_Smarty::singleton();
      $template->assign('notifyAbsent', TRUE);
    }
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_2_2_beta2($rev) {
    $template = CRM_Core_Smarty::singleton();
    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
        'CRM_Contact_Form_Search_Custom_ZipCodeRange', 'id', 'name'
      )) {
      $template->assign('customSearchAbsentAll', TRUE);
    }
    elseif (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
        'CRM_Contact_Form_Search_Custom_MultipleValues', 'id', 'name'
      )) {
      $template->assign('customSearchAbsent', TRUE);
    }
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_2_2_beta3($rev) {
    $template = CRM_Core_Smarty::singleton();
    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'custom_data_type', 'id', 'name')) {
      $template->assign('customDataType', TRUE);
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_3_0_alpha1($rev) {

    $threeZero = new CRM_Upgrade_ThreeZero_ThreeZero();

    $error = NULL;
    if (!$threeZero->verifyPreDBState($error)) {
      if (!isset($error)) {
        $error = 'pre-condition failed for current upgrade for 3.0.alpha2';
      }
      CRM_Core_Error::fatal($error);
    }

    $threeZero->upgrade($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_3_1_alpha1($rev) {

    $threeOne = new CRM_Upgrade_ThreeOne_ThreeOne();

    $error = NULL;
    if (!$threeOne->verifyPreDBState($error)) {
      if (!isset($error)) {
        $error = 'pre-condition failed for current upgrade for 3.0.alpha2';
      }
      CRM_Core_Error::fatal($error);
    }

    $threeOne->upgrade($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_2_2_7($rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
    $sql = "UPDATE civicrm_report_instance
                       SET form_values = REPLACE(form_values,'#',';') ";
    CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    // make report component enabled by default
    $domain = new CRM_Core_DAO_Domain();
    $domain->selectAdd();
    $domain->selectAdd('config_backend');
    $domain->find(TRUE);
    if ($domain->config_backend) {
      $defaults = unserialize($domain->config_backend);

      if (is_array($defaults['enableComponents'])) {
        $compId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviReport'");
        if ($compId) {
          $defaults['enableComponents'][] = 'CiviReport';
          $defaults['enableComponentIDs'][] = $compId;

          CRM_Core_BAO_ConfigSetting::add($defaults);
        }
      }
    }
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_3_0_2($rev) {

    $template = CRM_Core_Smarty::singleton();
    //check whether upgraded from 2.1.x or 2.2.x
    $inboundEmailID = CRM_Core_OptionGroup::getValue('activity_type', 'Inbound Email', 'name');

    if (!empty($inboundEmailID)) {
      $template->assign('addInboundEmail', FALSE);
    }
    else {
      $template->assign('addInboundEmail', TRUE);
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_3_0_4($rev) {
    //make sure 'Deceased' membership status present in db,CRM-5636
    $template = CRM_Core_Smarty::singleton();

    $addDeceasedStatus = FALSE;
    $sql = "SELECT max(id) FROM civicrm_membership_status where name = 'Deceased'";
    if (!CRM_Core_DAO::singleValueQuery($sql)) {
      $addDeceasedStatus = TRUE;
    }
    $template->assign('addDeceasedStatus', $addDeceasedStatus);

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_3_1_0($rev) {
    // upgrade all roles who have 'access CiviEvent' permission, to also have
    // newly added permission 'edit_all_events', CRM-5472
    $config = CRM_Core_Config::singleton();
    if (is_callable(array(
      $config->userSystem, 'replacePermission'))) {
      $config->userSystem->replacePermission('access CiviEvent', array('access CiviEvent', 'edit all events'));
    }

    //make sure 'Deceased' membership status present in db,CRM-5636
    $template = CRM_Core_Smarty::singleton();

    $addDeceasedStatus = FALSE;
    $sql = "SELECT max(id) FROM civicrm_membership_status where name = 'Deceased'";
    if (!CRM_Core_DAO::singleValueQuery($sql)) {
      $addDeceasedStatus = TRUE;
    }
    $template->assign('addDeceasedStatus', $addDeceasedStatus);

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_3_1_3($rev) {
    $threeOne = new CRM_Upgrade_ThreeOne_ThreeOne();
    $threeOne->upgrade_3_1_3();

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * Perform an incremental upgrade
   *
   * @param $rev string, the revision to which we are upgrading (Note: When processing a series of upgrades, this is the immediate upgrade - not the final)
   */
  static function upgrade_3_1_4($rev) {
    $threeOne = new CRM_Upgrade_ThreeOne_ThreeOne();
    $threeOne->upgrade_3_1_4();

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }
}

