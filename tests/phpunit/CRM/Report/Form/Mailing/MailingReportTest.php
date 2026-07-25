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
 * Test class for Mailing Report forms extending CRM_Report_Form_Mailing_Base.
 *
 * @group headless
 */
class CRM_Report_Form_Mailing_MailingReportTest extends CiviReportTestCase {

  /**
   * Data provider for mailing report forms.
   *
   * @return array
   */
  public static function mailingReportFormDataProvider(): array {
    return [
      'Summary' => ['CRM_Report_Form_Mailing_Summary'],
      'Detail' => ['CRM_Report_Form_Mailing_Detail'],
      'Bounce' => ['CRM_Report_Form_Mailing_Bounce'],
      'Clicks' => ['CRM_Report_Form_Mailing_Clicks'],
      'Opened' => ['CRM_Report_Form_Mailing_Opened'],
    ];
  }

  /**
   * Test instantiation and SQL query generation for mailing report forms.
   *
   * @dataProvider mailingReportFormDataProvider
   */
  public function testMailingReportFormQuery(string $formClass): void {
    $reportObj = $this->getReportObject($formClass, []);
    $sql = $reportObj->buildQuery(TRUE);
    $this->assertNotEmpty($sql);
    $rows = $reportObj->getResultSet();
    $this->assertIsArray($rows);
  }

}
