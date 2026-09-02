<?php

/**
 * Class CRM_Upgrade_Incremental_php_SixEighteenTest
 * @group headless
 */
class CRM_Upgrade_Incremental_php_SixEighteenTest extends CiviUnitTestCase {

  public function testContributionPageTableAlter(): void {
    $pageID = $this->contributionPageCreate()['id'];
    $sqlModes = CRM_Utils_SQL::getSqlModes();
    if (in_array('NO_ZERO_DATE', $sqlModes)) {
      CRM_Utils_Sql::setSqlModes(array_diff($sqlModes, ['NO_ZERO_DATE']));
    }
    elseif (!empty($sqlModes)) {
      $sqlModes[] = 'NO_ZERO_DATE';
    }
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_contribution_page', 'FK_civicrm_contribution_page_currency');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_page SET created_date = '0000-00-00 00:00:00' WHERE id = %1", [1 => [$pageID, 'Positive']]);
    CRM_Utils_Sql::setSqlModes($sqlModes);
    CRM_Upgrade_Incremental_php_SixEighteen::addCurrencyFk(NULL, 'ContributionPage', 'currency');
  }

}
