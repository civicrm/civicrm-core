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

use Civi\Api4\CaseType;
use Civi\Api4\OptionValue;
use Civi\Api4\RelationshipType;

/**
 * Class CiviReportTestCase
 */
class CiviCaseTestCase extends CiviUnitTestCase {

  /**
   * @var string
   * Symbolic-name
   */
  protected $caseType;

  protected $caseTypeId;

  public function setUp(): void {
    parent::setUp();
    $this->startTrackingEntities();

    // CRM-9404 - set-up is a bit cumbersome but had to put something in place to set up activity types & case types
    $optionValues = [
      'Medical evaluation' => 'Medical evaluation',
      'Mental health evaluation' => "Mental health evaluation",
      'Secure temporary housing' => 'Secure temporary housing',
      'Long-term housing plan' => 'Long-term housing plan',
      'ADC referral' => 'ADC referral',
      'Income and benefits stabilization' => 'Income and benefits stabilization',
    ];
    foreach ($optionValues as $name => $label) {
      CRM_Core_BAO_OptionValue::ensureOptionValueExists([
        'option_group_id' => 'activity_type',
        'name' => $name,
        'label' => $label,
        'component_id' => 'CiviCase',
      ]);
    }

    // We used to be inconsistent about "HousingSupport" vs "housing_support".
    // Now, the rule is simply: use the "name" from "civicrm_case_type.name".
    $this->caseType = 'housing_support';
    $this->caseTypeId = 1;

    $this->individualCreate(['first_name' => 'site', 'last_name' => 'admin'], 'site_admin');
    $this->loadAllFixtures();

    // enable the default custom templates for the case type xml files
    $this->customDirectories(['template_path' => TRUE]);

    /** @var \CRM_Utils_Hook_UnitTests $hooks  */
    $hooks = \CRM_Utils_Hook::singleton();
    $hooks->setHook('civicrm_caseTypes', [$this, 'hook_caseTypes']);
    \CRM_Case_XMLRepository::singleton(TRUE);
    \CRM_Case_XMLProcessor::flushStaticCaches();

    // create a logged in USER since the code references it for source_contact_id
    $this->createLoggedInUser();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->customDirectories(['template_path' => FALSE]);
    OptionValue::delete(FALSE)
      ->addWhere('name', '=', 'First act')
      ->execute();
    $this->quickCleanup([
      'civicrm_activity',
      'civicrm_contact',
      'civicrm_case',
      'civicrm_case_contact',
      'civicrm_case_activity',
      'civicrm_activity_contact',
      'civicrm_managed',
      'civicrm_relationship',
      'civicrm_uf_match',
      'civicrm_group_contact',
      'civicrm_file',
      'civicrm_entity_file',
    ], TRUE);
    if (!empty($this->ids['CaseType'])) {
      CaseType::delete(FALSE)->addWhere('id', 'IN', $this->ids['CaseType'])->execute();
    }
    CRM_Case_XMLRepository::singleton()->flush();
    $this->assertEntityCleanup();
    parent::tearDown();
  }

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public static function tearDownAfterClass(): void {
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviCase');
    RelationshipType::delete(FALSE)
      ->addWhere('name_b_a', 'IN', [
        'Homeless Services Coordinator',
        'Health Services Coordinator',
        'Senior Services Coordinator',
        'Benefits Specialist',
      ])
      ->execute();
    parent::tearDownAfterClass();
  }

  /**
   * Subclasses may override this if they want to be explicit about the case-type definition.
   *
   * @param $caseTypes
   * @see CRM_Utils_Hook::caseTypes
   */
  public function hook_caseTypes(&$caseTypes) {
  }

  /**
   * @return string[]
   */
  public function getTrackedEntities(): array {
    return ['civicrm_case_type'];
  }

}
