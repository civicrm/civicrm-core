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
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
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
class CRM_Upgrade_Incremental_php_ThreeFour {
  /**
   * @param $errors
   *
   * @return bool
   */
  function verifyPreDBstate(&$errors) {
    return TRUE;
  }

  /**
   * @param $rev
   */
  function upgrade_3_4_alpha3($rev) {
    // CRM-7681, update report instance criteria.
    $modifiedReportIds = array('contact/summary', 'contact/detail', 'event/participantListing', 'member/summary', 'pledge/summary', 'pledge/pbnp', 'member/detail', 'member/lapse', 'grant/detail', 'contribute/bookkeeping', 'contribute/lybunt', 'contribute/summary', 'contribute/repeat', 'contribute/detail', 'contribute/organizationSummary', 'contribute/sybunt', 'contribute/householdSummary', 'contact/relationship', 'contact/currentEmployer', 'case/demographics', 'walklist', 'case/detail', 'contact/log', 'activitySummary', 'case/timespent', 'case/summary');

    $instances = CRM_Core_DAO::executeQuery("SELECT id, form_values, report_id FROM civicrm_report_instance WHERE report_id IN ('" . implode("','", $modifiedReportIds) . "')");

    while ($instances->fetch()) {
      $formValues = unserialize($instances->form_values);

      // replace display_name fields by sort_name
      if (!empty($formValues['fields']) && isset($formValues['fields']['display_name'])) {
        $formValues['fields']['sort_name'] = $formValues['fields']['display_name'];
        unset($formValues['fields']['display_name']);
      }

      // replace display_name filters by sort_name
      if (isset($formValues['display_name_op'])) {
        $formValues['sort_name_op'] = $formValues['display_name_op'];
        unset($formValues['display_name_op']);
      }
      if (isset($formValues['display_name_value'])) {
        $formValues['sort_name_value'] = $formValues['display_name_value'];
        unset($formValues['display_name_value']);
      }

      // for report id 'contact/log' replace field
      // display_name_touched by sort_name_touched
      if ($instances->report_id == 'contact/log' && isset($formValues['fields']['display_name_touched'])) {
        $formValues['fields']['sort_name_touched'] = $formValues['fields']['display_name_touched'];
        unset($formValues['fields']['display_name_touched']);
      }

      // for report id 'contact/relationship' replace field
      // display_name_a by sort_name_a and display_name_b by sort_name_b
      if ($instances->report_id == 'contact/relationship') {
        if (isset($formValues['fields']['display_name_a'])) {
          $formValues['fields']['sort_name_a'] = $formValues['fields']['display_name_a'];
          unset($formValues['fields']['display_name_a']);
        }

        if (isset($formValues['fields']['display_name_b'])) {
          $formValues['fields']['sort_name_b'] = $formValues['fields']['display_name_b'];
          unset($formValues['fields']['display_name_b']);
        }
      }

      // save updated instance criteria
      $dao              = new CRM_Report_DAO_ReportInstance();
      $dao->id          = $instances->id;
      $dao->form_values = serialize($formValues);
      $dao->save();
      $dao->free();
    }

    // Handled for typo in 3.3.2.mysql.tpl, rename column visibilty to
    // visibility in table civicrm_mailing
    $renameColumnVisibility = CRM_Core_DAO::checkFieldExists('civicrm_mailing', 'visibilty');

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->assign('renameColumnVisibility', $renameColumnVisibility);
    $upgrade->processSQL($rev);
  }

  /**
   * @param $rev
   */
  function upgrade_3_4_beta2($rev) {
    $addPetitionOptionGroup = !(boolean) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'msg_tpl_workflow_petition', 'id', 'name');
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->assign('addPetitionOptionGroup', $addPetitionOptionGroup);
    $upgrade->processSQL($rev);
  }

  /**
   * @param $rev
   */
  function upgrade_3_4_beta3($rev) {
    // do the regular upgrade
    $upgrade = new CRM_Upgrade_Form;
    $upgrade->processSQL($rev);

    if ($upgrade->multilingual) {

      // rebuild schema, because due to a CRM-7854 mis-fix some indices might be missing
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($upgrade->locales, $rev);

      // turn a set of columns singlelingual
      $config = CRM_Core_Config::singleton();
      $tables = array('civicrm_address', 'civicrm_contact', 'civicrm_mailing', 'civicrm_mailing_component');
      $triggers = array(array('when' => 'before', 'event' => 'update'), array('when' => 'before', 'event' => 'insert'));

      // FIXME: Doing require_once is a must here because a call like CRM_Core_I18n_SchemaStructure_3_4_beta2 makes
      // class loader look for file like - CRM/Core/I18n/SchemaStructure/3/4/beta2.php which is not what we want to be loaded
      require_once "CRM/Core/I18n/SchemaStructure_3_4_beta2.php";
      foreach ($tables as $table) {
        CRM_Core_I18n_Schema::makeSinglelingualTable($config->lcMessages, $table, 'CRM_Core_I18n_SchemaStructure_3_4_beta2', $triggers);
      }
    }
  }

  /**
   * @param $rev
   */
  function upgrade_3_4_3($rev) {
    // CRM-8147, update group_type for uf groups, check and add component field types
    $ufGroups = new CRM_Core_DAO_UFGroup();
    $ufGroups->find();
    $skipGroupTypes = array('Individual,Contact', 'Organization,Contact', 'Household,Contact', 'Contact', 'Individual', 'Organization', 'Household');
    while ($ufGroups->fetch()) {
      if (!in_array($ufGroups->group_type, $skipGroupTypes)) {
        $groupTypes = CRM_Core_BAO_UFGroup::calculateGroupType($ufGroups->id, TRUE);
        CRM_Core_BAO_UFGroup::updateGroupTypes($ufGroups->id, $groupTypes);
      }
    }
    $ufGroups->free();

    // CRM-8134 add phone_ext column if it wasn't already added for this site in 3.3.7 upgrade (3.3.7 was released after 3.4.0)
    $dao = new CRM_Contact_DAO_Contact();
    $dbName = $dao->_database;

    $chkExtQuery = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %1
                        AND TABLE_NAME = 'civicrm_phone' AND COLUMN_NAME = 'phone_ext'";
    $extensionExists = CRM_Core_DAO::singleValueQuery($chkExtQuery,
      array(1 => array($dbName, 'String')),
      TRUE, FALSE
    );

    if (!$extensionExists) {
      $colQuery = 'ALTER TABLE `civicrm_phone` ADD `phone_ext` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `phone` ';
      CRM_Core_DAO::executeQuery($colQuery);
    }

    $sql = "SELECT id FROM civicrm_location_type WHERE name = 'Main'";
    if (!CRM_Core_DAO::singleValueQuery($sql)) {
      $query = "
INSERT INTO civicrm_location_type ( name, description, is_reserved, is_active )
     VALUES ( 'Main', 'Main office location', 0, 1 );";
      CRM_Core_DAO::executeQuery($query);
    }

    $upgrade = new CRM_Upgrade_Form;
    $upgrade->processSQL($rev);
  }

  /**
   * @param $rev
   */
  function upgrade_3_4_4($rev) {
    // CRM-8315, update report instance criteria.
    $modifiedReportIds = array('member/summary', 'member/detail');

    $instances = CRM_Core_DAO::executeQuery("SELECT id, form_values, report_id FROM civicrm_report_instance WHERE report_id IN ('" . implode("','", $modifiedReportIds) . "')");

    while ($instances->fetch()) {
      $formValues = unserialize($instances->form_values);

      // replace display_name fields by sort_name
      if (!isset($formValues['membership_start_date_relative']) &&
        !isset($formValues['membership_end_date_relative'])
      ) {
        $formValues['membership_start_date_relative'] = '0';
        $formValues['membership_start_date_from'] = '';
        $formValues['membership_start_date_to'] = '';
        $formValues['membership_end_date_relative'] = '0';
        $formValues['membership_end_date_from'] = '';
        $formValues['membership_end_date_to'] = '';
      }

      // save updated instance criteria
      $dao              = new CRM_Report_DAO_ReportInstance();
      $dao->id          = $instances->id;
      $dao->form_values = serialize($formValues);
      $dao->save();
      $dao->free();
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  /**
   * @param $rev
   */
  function upgrade_3_4_5($rev) {
    // handle db changes done for CRM-8218
    $alterContactDashboard = FALSE;
    $dao = new CRM_Contact_DAO_DashboardContact();
    $dbName = $dao->_database;

    $chkContentQuery = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %1
                        AND TABLE_NAME = 'civicrm_dashboard_contact' AND COLUMN_NAME = 'content'";
    $contentExists = CRM_Core_DAO::singleValueQuery($chkContentQuery,
      array(1 => array($dbName, 'String')),
      TRUE, FALSE
    );
    if (!$contentExists) {
      $alterContactDashboard = TRUE;
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->assign('alterContactDashboard', $alterContactDashboard);
    $upgrade->processSQL($rev);
  }

  /**
   * @param $rev
   */
  function upgrade_3_4_6($rev) {
    $modifiedReportIds = array('event/summary', 'activity', 'Mailing/bounce', 'Mailing/clicks', 'Mailing/opened');

    $instances = CRM_Core_DAO::executeQuery("SELECT id, form_values, report_id FROM civicrm_report_instance WHERE report_id IN ('" . implode("','", $modifiedReportIds) . "')");
    while ($instances->fetch()) {
      $formValues = unserialize($instances->form_values);

      switch ($instances->report_id) {
        case 'event/summary':
          $eventDates = array('event_start_date_from', 'event_start_date_to', 'event_end_date_from', 'event_end_date_to');
          foreach ($eventDates as $date) {
            if (isset($formValues[$date]) && $formValues[$date] == ' ') {
              $formValues[$date] = '';
            }
          }
          break;

        case 'activity':
          if (isset($formValues['group_bys'])) {
            if (is_array($formValues['group_bys'])) {
              $orderBy = array();
              $count = 0;
              foreach ($formValues['group_bys'] as $col => $isSet) {
                if (!$isSet) {
                  continue;
                }

                $orderBy[++$count] = array(
                  'column' => $col,
                  'order' => 'ASC',
                );
              }
              if (!empty($orderBy)) {
                $formValues['order_bys'] = $orderBy;
              }
            }
            unset($formValues['group_bys']);
          }
          break;

        case 'Mailing/bounce':
        case 'Mailing/clicks':
        case 'Mailing/opened':
          $formValues['fields']['mailing_name'] = 1;
          break;
      }

      // save updated instance criteria
      $dao              = new CRM_Report_DAO_ReportInstance();
      $dao->id          = $instances->id;
      $dao->form_values = serialize($formValues);
      $dao->save();
      $dao->free();
    }

    $bulkEmailActivityType = CRM_Core_DAO::singleValueQuery("
SELECT v.id
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.name      = %1
  AND  g.is_active = 1
  AND  v.name      = %2", array(1 => array('activity_type', 'String'),
        2 => array('Bulk Email', 'String'),
      ));

    // CRM-8852, reset contact field cache
    CRM_Core_BAO_Cache::deleteGroup('contact fields');

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->assign('bulkEmailActivityType', $bulkEmailActivityType);

    $upgrade->processSQL($rev);
  }

  /**
   * @param $rev
   *
   * @throws Exception
   */
  function upgrade_3_4_7($rev) {
    $onBehalfProfileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', 'on_behalf_organization', 'id', 'name');
    if (!$onBehalfProfileId) {
      CRM_Core_Error::fatal();
    }

    $pages = CRM_Core_DAO::executeQuery("
SELECT    civicrm_contribution_page.id
FROM      civicrm_contribution_page
LEFT JOIN civicrm_uf_join ON entity_table = 'civicrm_contribution_page' AND entity_id = civicrm_contribution_page.id AND module = 'OnBehalf'
WHERE     is_for_organization = 1
AND       civicrm_uf_join.id IS NULL
");

    while ($pages->fetch()) {
      $query = "
INSERT INTO civicrm_uf_join
    (is_active, module, entity_table, entity_id, weight, uf_group_id)
VALUES
    (1, 'OnBehalf', 'civicrm_contribution_page', %1, 1, %2)";

      $params = array(1 => array($pages->id, 'Integer'),
        2 => array($onBehalfProfileId, 'Integer'),
      );
      CRM_Core_DAO::executeQuery($query, $params);
    }

    // CRM-8774
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Drupal' || $config->userFramework == 'Drupal6') {
      db_query("UPDATE {system} SET weight = 100 WHERE name = 'civicrm'");
      drupal_flush_all_caches();
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }
}

