<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Upgrade_Incremental_php_FourOne {
  // This was changed in 4.3 so we define it locally for compatibility with older dbs
  const NAVIGATION_NAME = "Navigation Menu";

  /**
   * @param $errors
   *
   * @return bool
   */
  public function verifyPreDBstate(&$errors) {
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviCase', $config->enableComponents)) {
      if (!CRM_Core_DAO::checkTriggerViewPermission(TRUE, FALSE)) {
        $errors[] = 'CiviCase now requires CREATE VIEW and DROP VIEW permissions for the database user.';
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.1.alpha1') {
      $postUpgradeMessage .= '<br />' .
        ts('WARNING! CiviCRM 4.1 introduces an improved way of handling cron jobs. However the new method is NOT backwards compatible. <strong>Please notify your system administrator that all CiviCRM related cron jobs will cease to work, and will need to be re-configured (this includes sending CiviMail mailings, updating membership statuses, etc.).</strong> Refer to the <a href="%1">online documentation</a> for detailed instructions.', array(1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC41/Managing+Scheduled+Jobs'));
      $postUpgradeMessage .= '<br />' .
        ts('The CiviCRM Administration menu structure has been re-organized during this upgrade to make it easier to find things and reduce the number of keystrokes. If you have customized this portion of the navigation menu - you should take a few minutes to review the changes. You may need to reimplement or move your customizations.');

      $postUpgradeMessage .= '<br />Yahoo recently discontinued their geocoding and mapping API service. If you previously used Yahoo, you will need to select and configure an alternate service in order to continue using geocoding/mapping tools.';

      $postUpgradeMessage .= '<br />' .
        ts('We have integrated KCFinder with CKEditor and TinyMCE, which enables user to upload images. Note that all the images uploaded using KCFinder will be public.');
    }
  }

  /**
   * @param $rev
   */
  public function upgrade_4_1_alpha1($rev) {
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviCase', $config->enableComponents)) {
      if (!CRM_Case_BAO_Case::createCaseViews()) {
        $template = CRM_Core_Smarty::singleton();
        $afterUpgradeMessage = '';
        if ($afterUpgradeMessage = $template->get_template_vars('afterUpgradeMessage')) {
          $afterUpgradeMessage .= "<br/><br/>";
        }
        $afterUpgradeMessage .=
          '<div class="crm-upgrade-case-views-error" style="background-color: #E43D2B; padding: 10px;">' .
          ts("There was a problem creating CiviCase database views. Please create the following views manually before using CiviCase:");
        $afterUpgradeMessage .=
          '<div class="crm-upgrade-case-views-query"><div>' .
          CRM_Case_BAO_Case::createCaseViewsQuery('upcoming') . '</div><div>' .
          CRM_Case_BAO_Case::createCaseViewsQuery('recent') . '</div>' .
          '</div></div>';
        $template->assign('afterUpgradeMessage', $afterUpgradeMessage);
      }
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    $this->transferPreferencesToSettings();
    $this->createNewSettings();

    // now modify the config so that the directories are now stored in the settings table
    // CRM-8780
    $params = array();
    CRM_Core_BAO_ConfigSetting::add($params);

    // also reset navigation
    CRM_Core_BAO_Navigation::resetNavigation();
  }

  public function transferPreferencesToSettings() {
    // first transfer system preferences
    $domainColumnNames = array(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME => array(
        'contact_view_options',
        'contact_edit_options',
        'advanced_search_options',
        'user_dashboard_options',
        'address_options',
        'address_format',
        'mailing_format',
        'display_name_format',
        'sort_name_format',
        'editor_id',
        'contact_autocomplete_options',
      ),
      CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME => array(
        'address_standardization_provider',
        'address_standardization_userid',
        'address_standardization_url',
      ),
      CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME => array(
        'mailing_backend',
      ),
    );

    $userColumnNames = array(
      self::NAVIGATION_NAME => array(
        'navigation',
      ),
    );

    $sql = "
SELECT *
FROM   civicrm_preferences
WHERE  domain_id = %1
";
    $params = array(1 => array(CRM_Core_Config::domainID(), 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $domainID = CRM_Core_Config::domainID();
    $createdDate = date('YmdHis');

    while ($dao->fetch()) {
      if ($dao->is_domain) {
        $values = array();
        foreach ($domainColumnNames as $groupName => $settingNames) {
          foreach ($settingNames as $settingName) {
            if (empty($dao->$settingName)) {
              $value = NULL;
            }
            else {
              if ($settingName == 'mailing_backend') {
                $value = $dao->$settingName;
              }
              else {
                $value = serialize($dao->$settingName);
              }
            }

            if ($value) {
              $value = addslashes($value);
            }
            $value = $value ? "'{$value}'" : 'null';
            $values[] = " ('{$groupName}','{$settingName}', {$value}, {$domainID}, null, 1, '{$createdDate}', null )";
          }
        }
      }
      else {
        // this is a user setting
        foreach ($userColumnNames as $groupName => $settingNames) {
          foreach ($settingNames as $settingName) {
            $value = empty($dao->$settingName) ? NULL : serialize($dao->$settingName);

            if ($value) {
              $value = addslashes($value);
            }
            $value = $value ? "'{$value}'" : 'null';
            $values[] = " ('{$groupName}', '{$settingName}', {$value}, {$domainID}, {$dao->contact_id}, 0, '{$createdDate}', null )";
          }
        }
      }
    }

    $sql = "
INSERT INTO civicrm_setting( group_name, name, value, domain_id, contact_id, is_domain, created_date, created_id )
VALUES
";

    $sql .= implode(",\n", $values);
    CRM_Core_DAO::executeQuery($sql);

    // now drop the civicrm_preferences table
    $sql = "DROP TABLE civicrm_preferences";
    CRM_Core_DAO::executeQuery($sql);
  }

  public function createNewSettings() {
    $domainColumns = array(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME => array(
        array('contact_ajax_check_similar', 1),
        array('activity_assignee_notification', 1),
      ),
      CRM_Core_BAO_Setting::CAMPAIGN_PREFERENCES_NAME => array(
        array('tag_unconfirmed', 'Unconfirmed'),
        array('petition_contacts', 'Petition Contacts'),
      ),
      CRM_Core_BAO_Setting::EVENT_PREFERENCES_NAME => array(
        array('enable_cart', 0),
      ),
      CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME => array(
        array('profile_double_optin', 1),
        array('profile_add_to_group_double_optin', 0),
        array('track_civimail_replies', 0),
        array('civimail_workflow', 0),
        array('civimail_server_wide_lock', 0),
      ),
      CRM_Core_BAO_Setting::MEMBER_PREFERENCES_NAME => array(
        array('default_renewal_contribution_page', NULL),
      ),
      CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME => array(
        array('is_enabled', 0),
        array('uniq_email_per_site', 0),
        array('domain_group_id', 0),
        array('event_price_set_domain_id', 0),
      ),
      CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME => array(
        array('uploadDir', NULL),
        array('imageUploadDir', NULL),
        array('customFileUploadDir', NULL),
        array('customTemplateDir', NULL),
        array('customPHPPathDir', NULL),
        array('extensionsDir', NULL),
      ),
      CRM_Core_BAO_Setting::URL_PREFERENCES_NAME => array(
        array('userFrameworkResourceURL', NULL),
        array('imageUploadURL', NULL),
        array('customCSSURL', NULL),
      ),
    );

    $domainID = CRM_Core_Config::domainID();
    $createdDate = date('YmdHis');

    $dbSettings = array();
    self::retrieveDirectoryAndURLPaths($dbSettings);

    foreach ($domainColumns as $groupName => $settings) {
      foreach ($settings as $setting) {

        if (isset($dbSettings[$groupName][$setting[0]]) &&
          !empty($dbSettings[$groupName][$setting[0]])
        ) {
          $setting[1] = $dbSettings[$groupName][$setting[0]];
        }

        $value = $setting[1] === NULL ? NULL : serialize($setting[1]);

        if ($value) {
          $value = addslashes($value);
        }

        $value = $value ? "'{$value}'" : 'null';
        $values[] = "( '{$groupName}', '{$setting[0]}', {$value}, {$domainID}, null, 0, '{$createdDate}', null )";
      }
    }
    $sql = "
INSERT INTO civicrm_setting( group_name, name, value, domain_id, contact_id, is_domain, created_date, created_id )
VALUES
";
    $sql .= implode(",\n", $values);
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * @param array $params
   */
  public static function retrieveDirectoryAndURLPaths(&$params) {

    $sql = "
SELECT v.name as valueName, v.value, g.name as optionName
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  ( g.name = 'directory_preferences'
OR       g.name = 'url_preferences' )
AND    v.option_group_id = g.id
AND    v.is_active = 1
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (!$dao->value) {
        continue;
      }

      $groupName = CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME;
      if ($dao->optionName == 'url_preferences') {
        $groupName = CRM_Core_BAO_Setting::URL_PREFERENCES_NAME;
      }
      $params[$groupName][$dao->valueName] = $dao->value;
    }
  }

  /**
   * @param $rev
   */
  public function upgrade_4_1_alpha2($rev) {
    $dao = new CRM_Core_DAO_Setting();
    $dao->group_name = 'Directory Preferences';
    $dao->name = 'customTemplateDir';
    if (!($dao->find(TRUE))) {
      $dao->domain_id = CRM_Core_Config::domainID();
      $dao->created_date = date('YmdHis');
      $dao->is_domain = 0;
      $dao->save();
    }
    $dao->free();

    // Do the regular upgrade
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_1_beta1($rev) {
    //CRM-9311
    $groupNames = array('directory_preferences', 'url_preferences');
    foreach ($groupNames as $groupName) {
      CRM_Core_OptionGroup::deleteAssoc($groupName);
    }

    $domainCols = array(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME => array(
        'contact_ajax_check_similar',
        'activity_assignee_notification',
      ),
      CRM_Core_BAO_Setting::CAMPAIGN_PREFERENCES_NAME => array(
        'tag_unconfirmed',
        'petition_contacts',
      ),
      CRM_Core_BAO_Setting::EVENT_PREFERENCES_NAME => array(
        'enable_cart',
      ),
      CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME => array(
        'profile_double_optin',
        'profile_add_to_group_double_optin',
        'track_civimail_replies',
        'civimail_workflow',
        'civimail_server_wide_lock',
      ),
      CRM_Core_BAO_Setting::MEMBER_PREFERENCES_NAME => array(
        'default_renewal_contribution_page',
      ),
      CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME => array(
        'is_enabled',
        'uniq_email_per_site',
        'domain_group_id',
        'event_price_set_domain_id',
      ),
      CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME => array(
        'uploadDir',
        'imageUploadDir',
        'customFileUploadDir',
        'customTemplateDir',
        'customPHPPathDir',
        'extensionsDir',
      ),
      CRM_Core_BAO_Setting::URL_PREFERENCES_NAME => array(
        'userFrameworkResourceURL',
        'imageUploadURL',
        'customCSSURL',
      ),
    );

    $arrGroupNames = array_keys($domainCols);
    $groupNames = implode("','", $arrGroupNames);
    $arrNames = array();
    foreach ($domainCols as $groupName => $names) {
      $arrNames[] = implode("','", $names);
    }
    $name = implode("','", $arrNames);

    $sql = "
        update civicrm_setting set is_domain = 1 where is_domain = 0 and group_name in ( '{$groupNames}' ) and name in ('{$name}')";

    CRM_Core_DAO::executeQuery($sql);

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->assign('addWightForActivity', !(CRM_Core_DAO::checkFieldExists('civicrm_activity', 'weight')));
    $upgrade->processSQL($rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_1_1($rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->assign('addDedupeEmail', !(CRM_Core_DAO::checkFieldExists('civicrm_mailing', 'dedupe_email')));

    $sql = "SELECT id FROM civicrm_worldregion LIMIT 1";
    $upgrade->assign('worldRegionEmpty', !CRM_Core_DAO::singleValueQuery($sql));

    $upgrade->processSQL($rev);
  }

  /**
   * @return string
   */
  public function getTemplateMessage() {
    return "Blah";
  }

}
