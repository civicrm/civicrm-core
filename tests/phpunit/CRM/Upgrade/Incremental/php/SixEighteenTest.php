<?php

/**
 * Class CRM_Upgrade_Incremental_php_SixEighteenTest
 * @group headless
 */
class CRM_Upgrade_Incremental_php_SixEighteenTest extends CiviUnitTestCase {

  public function testContributionPageTableAlter(): void {
    $pageID = $this->contributionPageCreate()['id'];
    $sqlModes = CRM_Utils_SQL::getSqlModes();
    $changedSQLModes = $sqlModes;
    if (!empty($sqlModes) && in_array('NO_ZERO_DATE', $changedSQLModes)) {
      if ($key = array_search('NO_ZERO_DATE', $changedSQLModes)) {
        unset($changedSQLModes[$key]);
        CRM_Core_DAO::executeQuery("SET SESSION sql_mode = '" . implode(',', $changedSQLModes) . "'");
      }
    }
    elseif (!empty($sqlModes)) {
      $sqlModes[] = 'NO_ZERO_DATE';
    }
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_contribution_page DROP CONSTRAINT `FK_civicrm_contribution_page_currency`");
    CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_page SET created_date = '0000-00-00 00:00:00' WHERE id = %1", [1 => [$pageID, 'Positive']]);
    CRM_Core_DAO::executeQuery("SET SESSION sql_mode = '" . implode(',', $sqlModes) . "'");
    CRM_Upgrade_Incremental_php_SixEighteen::addCurrencyFk(NULL, 'ContributionPage', 'currency');
  }

}
