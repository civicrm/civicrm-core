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

use Civi\Api4\ReportInstance;
use Civi\Api4\Survey;

/**
 * @group headless
 */
class CRM_Campaign_BAO_SurveyTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_survey', 'civicrm_report_instance']);
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviCampaign');
    parent::tearDown();
  }

  /**
   * Deleting a survey also deletes its report instance.
   */
  public function testDeleteSurveyDeletesReport(): void {
    $survey = $this->createTestEntity('Survey', ['title' => 'Survey with report']);
    $report = $this->createTestEntity('ReportInstance', [
      'name' => 'survey_' . $survey['id'],
      'title' => 'Survey report',
      'report_id' => 'survey/detail',
    ]);

    Survey::delete(FALSE)->addWhere('id', '=', $survey['id'])->execute();

    $this->assertCount(0, Survey::get(FALSE)->addWhere('id', '=', $survey['id'])->execute());
    $this->assertCount(0, ReportInstance::get(FALSE)->addWhere('id', '=', $report['id'])->execute());
  }

}
