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
 * Class CRM_Upgrade_Incremental_SqlStep
 *
 * This classes requests any *.mysql.tpl steps.
 */
class CRM_Upgrade_Incremental_SqlStep implements CRM_Upgrade_Incremental_Interface {

  private $file;

  private $name;

  /**
   * CRM_Upgrade_Incremental_SqlStep constructor.
   * @param $file
   * @param $name
   */
  public function __construct($file, $name) {
    $this->file = $file;
    $this->name = $name;
  }

  public function createPreUpgradeMessage($startVer, $endVer) {
    return NULL;
  }

  public function getName() {
    return $this->name;
  }

  public function buildQueue(CRM_Queue_Queue $queue, $postUpgradeMessageFile, $startVer, $endVer) {
    $task = new CRM_Queue_Task(
      array('CRM_Upgrade_Incremental_SqlStep', 'doSqlFile'),
      array($this->file),
      ts('Execute SQL: %1', array(
        1 => $this->file,
      ))
    );
    $queue->createItem($task);
  }

  public static function doSqlFile(CRM_Queue_TaskContext $ctx, $sqlFile) {
    $upgrade = new CRM_Upgrade_Form();
    // FIXME: Multilingual and $rev
    // $upgrade->setSchemaStructureTables($rev);
    // $upgrade->processLocales($sqlFile, $rev);
    // return TRUE;
    throw new RuntimeException(sprintf("Not implemented: %s::%s for %s", __CLASS__, __FUNCTION__, $sqlFile));
  }

}
