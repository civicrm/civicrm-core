<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Core_DAOTest
 */
class CRM_Export_BAO_ExportTest extends CiviUnitTestCase {

  /**
   * Basic test to ensure the exportComponents function completes without error.
   */
  function testExportComponentsNull() {
    CRM_Export_BAO_Export::exportComponents(
      TRUE,
      array(),
      array(),
      NULL,
      NULL,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      NULL,
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => 1,
        'suppress_csv_for_testing' => TRUE,
      )
    );
  }
}
