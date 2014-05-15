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
class CRM_Upgrade_Incremental_php_FourFive {
  const BATCH_SIZE = 5000;

  function verifyPreDBstate(&$errors) {
    return TRUE;
  }

  /**
   * Compute any messages which should be displayed beforeupgrade
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param $preUpgradeMessage
   * @param $rev string, a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'
   * @param null $currentVer
   *
   * @internal param string $postUpgradeMessage , alterable
   * @return void
   */
  function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
  }

  /**
   * Compute any messages which should be displayed after upgrade
   *
   * @param $postUpgradeMessage string, alterable
   * @param $rev string, an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs
   * @return void
   */
  function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.5.alpha1') {
      $postUpgradeMessage .= '<br /><br />' . ts('Default versions of the following System Workflow Message Templates have been modified to handle new functionality: <ul><li>Contributions - Receipt (off-line)</li><li>Contributions - Receipt (on-line)</li><li>Memberships - Receipt (on-line)</li><li>Pledges - Acknowledgement</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages). (<a href="%1">learn more...</a>)', array(1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Updating+System+Workflow+Message+Templates+after+Upgrades+-+method+1+-+kdiff'));
      $postUpgradeMessage .= '<br /><br />' . ts('This release allows you to view and edit multiple-record custom field sets in a table format which will be more usable in some cases. You can try out the format by navigating to Administer > Custom Data & Screens > Custom Fields. Click Settings for a custom field set and change Display Style to "Tab with Tables".');
      $postUpgradeMessage .= '<br /><br />' . ts('This release changes the way that anonymous event registrations match participants with existing contacts.  By default, all event participants will be matched with existing individuals using the Unsupervised rule, even if multiple registrations with the same email address are allowed.  However, you can now select a different matching rule to use for each event.  Please review your events to make sure you choose the appropriate matching rule and collect sufficient information for it to match contacts.');
    }
  }

  function upgrade_4_5_alpha1($rev) {
    // task to process sql
    $this->addTask(ts('Migrate honoree information to module_data'), 'migrateHonoreeInfo');
    $this->addTask(ts('Upgrade DB to 4.5.alpha1: SQL'), 'task_4_5_x_runSql', $rev);
    $this->addTask(ts('Set default for Individual name fields configuration'), 'addNameFieldOptions');

    // CRM-14522 - The below schema checking is done as foreign key name
    // for pdf_format_id column varies for different databases
    // if DB is been into upgrade for 3.4.2 version, it would have pdf_format_id name for FK
    // else FK_civicrm_msg_template_pdf_format_id
    $config = CRM_Core_Config::singleton();
    $dbUf = DB::parseDSN($config->dsn);
    $query = "
SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'civicrm_msg_template'
AND CONSTRAINT_TYPE = 'FOREIGN KEY'
AND TABLE_SCHEMA = %1
";
    $params = array(1 => array($dbUf['database'], 'String'));
    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, FALSE);
    if ($dao->fetch()) {
      if ($dao->CONSTRAINT_NAME == 'FK_civicrm_msg_template_pdf_format_id' ||
        $dao->CONSTRAINT_NAME == 'pdf_format_id') {
        $sqlDropFK = "ALTER TABLE `civicrm_msg_template`
DROP FOREIGN KEY `{$dao->CONSTRAINT_NAME}`,
DROP KEY `{$dao->CONSTRAINT_NAME}`";
        CRM_Core_DAO::executeQuery($sqlDropFK, CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);
      }
    }

    return TRUE;
  }

  /**
   * Add defaults for the newly introduced name fields configuration in 'contact_edit_options' setting
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool TRUE for success
   */
  static function addNameFieldOptions(CRM_Queue_TaskContext $ctx) {
    $query = "SELECT `value` FROM `civicrm_setting` WHERE `group_name` = 'CiviCRM Preferences' AND `name` = 'contact_edit_options'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $oldValue = unserialize($dao->value);

    $newValue = $oldValue . '1214151617';

    $query = "UPDATE `civicrm_setting` SET `value` = %1 WHERE `group_name` = 'CiviCRM Preferences' AND `name` = 'contact_edit_options'";
    $params = array(1 => array(serialize($newValue), 'String'));
    CRM_Core_DAO::executeQuery($query, $params);

    return TRUE;
  }

  /**
   * Migrate honoree information to uf_join.module_data as honoree columns (text and title) will be dropped
   * on DB upgrade
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool TRUE for success
   */
  static function migrateHonoreeInfo(CRM_Queue_TaskContext $ctx) {
    $query = "ALTER TABLE `civicrm_uf_join`
    ADD COLUMN `module_data` longtext COMMENT 'Json serialized array of data used by the ufjoin.module'";
      CRM_Core_DAO::executeQuery($query);

    $honorTypes = array_keys(CRM_Core_OptionGroup::values('honor_type'));
    $ufGroupDAO = new CRM_Core_DAO_UFGroup();
    $ufGroupDAO->name = 'new_individual';
    $ufGroupDAO->find(TRUE);

    $query = "SELECT * FROM civicrm_contribution_page";
    $dao = CRM_Core_DAO::executeQuery($query);

    if ($dao->N) {
      $domain = new CRM_Core_DAO_Domain;
      $domain->find(TRUE);
      while ($dao->fetch()) {
        $honorParams = array('soft_credit' => array('soft_credit_types' => $honorTypes));
        if ($domain->locales) {
          $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
          foreach ($locales as $locale) {
            $honor_block_title =  "honor_block_title_{$locale}";
            $honor_block_text =  "honor_block_text_{$locale}";
            $honorParams['soft_credit'] += array(
              $locale => array(
                'honor_block_title' => $dao->$honor_block_title,
                'honor_block_text' => $dao->$honor_block_text,
              ),
            );
          }
        }
        else {
          $honorParams['soft_credit'] += array(
            'default' => array(
              'honor_block_title' => $dao->honor_block_title,
              'honor_block_text' => $dao->honor_block_text,
            ),
          );
        }
        $ufJoinParam = array(
          'module' => 'soft_credit',
          'entity_table' => 'civicrm_contribution_page',
          'is_active' => $dao->honor_block_is_active,
          'entity_id' => $dao->id,
          'uf_group_id' => $ufGroupDAO->id,
          'module_data' => json_encode($honorParams),
        );
        CRM_Core_BAO_UFJoin::create($ufJoinParam);
      }
    }

    return TRUE;
  }

  /**
   * (Queue Task Callback)
   */
  static function task_4_5_x_runSql(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    return TRUE;
  }

  /**
   * Syntactic sugar for adding a task which (a) is in this class and (b) has
   * a high priority.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   */
  protected function addTask($title, $funcName) {
    $queue = CRM_Queue_Service::singleton()->load(array(
      'type' => 'Sql',
      'name' => CRM_Upgrade_Form::QUEUE_NAME,
    ));

    $args = func_get_args();
    $title = array_shift($args);
    $funcName = array_shift($args);
    $task = new CRM_Queue_Task(
      array(get_class($this), $funcName),
      $args,
      $title
    );
    $queue->createItem($task, array('weight' => -1));
  }
}
