<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Test;
use Civi\Test\Api4TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class AfformPlacementTest extends TestCase implements HeadlessInterface {

  use Api4TestTrait;

  private $formNames = [
    'afFormTest0',
    'afFormTabTest1',
    'afSearchTabTest2',
    'afSearchtest3',
    'afFormTabTest4',
  ];

  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()->installMe(__DIR__)->install('org.civicrm.search_kit')->apply();
  }

  public function tearDown(): void {
    Afform::revert(FALSE)->addWhere('name', 'IN', $this->formNames)->execute();
    $this->deleteTestRecords();
  }

  public function testAfformContactSummaryTab(): void {
    $this->saveTestRecords('ContactType', [
      'records' => [
        ['name' => 'FooBar', 'label' => 'FooBar', 'parent_id:name' => 'Individual'],
      ],
      'match' => ['name'],
    ]);

    Afform::create()
      ->addValue('name', $this->formNames[0])
      ->addValue('title', 'Test B')
      ->addValue('placement', ['contact_summary_tab'])
      ->addValue('placement_filters', ['contact_type' => ['Organization']])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[1])
      ->addValue('title', 'Test C')
      ->addValue('placement', ['contact_summary_tab'])
      ->addValue('placement_filters', ['contact_type' => ['FooBar']])
      ->addValue('icon', 'smiley-face')
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[2])
      ->addValue('title', 'Test A')
      ->addValue('placement', ['contact_summary_tab'])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[3])
      ->addValue('title', 'Test D')
      ->addValue('placement', ['contact_summary_tab'])
      ->addValue('placement_filters', ['contact_type' => ['Individual']])
      ->addValue('placement_weight', 99)
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[4])
      ->addValue('title', 'Test E')
      ->execute();

    $tabs = [];
    $context = [
      'contact_id' => 0,
      'contact_type' => 'Individual',
      'contact_sub_type' => ['FooBar'],
      'caller' => 'UnitTests',
    ];
    \CRM_Utils_Hook::tabset('civicrm/contact/view', $tabs, $context);

    $tabs = array_column($tabs, NULL, 'id');

    $this->assertArrayHasKey('test1', $tabs);
    $this->assertArrayHasKey('test2', $tabs);
    $this->assertArrayNotHasKey('test0', $tabs);
    $this->assertArrayHasKey('test3', $tabs);
    $this->assertArrayNotHasKey('test4', $tabs);
    $this->assertEquals('Test C', $tabs['test1']['title']);
    $this->assertEquals(['Individual'], $tabs['test1']['contact_type']);
    $this->assertEquals(['Individual'], $tabs['test3']['contact_type']);
    $this->assertEquals('Test A', $tabs['test2']['title']);
    $this->assertEquals('crm-i smiley-face', $tabs['test1']['icon']);
    // Fallback icon
    $this->assertEquals('crm-i fa-list-alt', $tabs['test2']['icon']);
    // Forms should be sorted by title alphabetically
    $this->assertGreaterThan($tabs['test2']['weight'], $tabs['test1']['weight']);
    // Should respect explicit weight
    $this->assertEquals(99, $tabs['test3']['weight']);
  }

  public function testAfformContactSummaryBlock(): void {
    $this->saveTestRecords('ContactType', [
      'records' => [
        ['name' => 'Farm', 'label' => 'Farm', 'parent_id:name' => 'Organization'],
      ],
      'match' => ['name'],
    ]);

    $cid = $this->createTestRecord('Contact', [
      'contact_type' => 'Organization',
      'contact_sub_type' => ['Farm'],
    ])['id'];

    Afform::create()
      ->addValue('name', $this->formNames[0])
      ->addValue('title', 'Test B')
      ->addValue('type', 'search')
      ->addValue('placement', ['contact_summary_block'])
      ->addValue('placement_filters', ['contact_type' => ['Individual', 'Household']])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[1])
      ->addValue('title', 'Test C')
      ->addValue('type', 'form')
      ->addValue('placement', ['contact_summary_block'])
      ->addValue('placement_filters', ['contact_type' => ['Farm']])
      ->addValue('icon', 'smiley-face')
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[2])
      ->addValue('type', 'form')
      ->addValue('title', 'Test A')
      ->addValue('placement', ['contact_summary_block'])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[3])
      ->addValue('type', 'form')
      ->addValue('title', 'A Weight Test')
      ->addValue('placement', ['contact_summary_block'])
      ->addValue('placement_weight', 99)
      ->execute();

    // Call pageRun hook and then assert afforms have been added to the appropriate region
    $dummy = new \CRM_Contact_Page_View_Summary();
    $dummy->set('cid', $cid);
    \CRM_Utils_Hook::pageRun($dummy);

    // Find an afform on the contact summary (either the left or right side)
    $getFromRegion = function ($formName) {
      return \CRM_Core_Region::instance('contact-basic-info-left')->get('afform:' . $formName)
        ?: \CRM_Core_Region::instance('contact-basic-info-right')->get('afform:' . $formName);
    };

    $blockA = $getFromRegion($this->formNames[2]);
    $this->assertStringContainsString("<af-search-tab-test2 options=", $blockA['markup']);
    $this->assertStringContainsString("\"contact_id\":$cid", $blockA['markup']);

    $blockB = $getFromRegion($this->formNames[1]);
    $this->assertStringContainsString("<af-form-tab-test1 options=", $blockB['markup']);
    $this->assertStringContainsString("\"contact_id\":$cid", $blockB['markup']);

    // Block for wrong contact type should not appear
    $this->assertNull($getFromRegion($this->formNames[0]));

    // Ensure blocks show up in ContactLayoutEditor
    $blocks = [];
    $null = NULL;
    \CRM_Utils_Hook::singleton()->invoke(['blocks'], $blocks,
      $null, $null, $null, $null, $null, 'civicrm_contactSummaryBlocks'
    );

    $this->assertEquals(['Individual', 'Household'], $blocks['afform_search']['blocks'][$this->formNames[0]]['contact_type']);
    // Sub-type should have been converted to parent type
    $this->assertEquals(['Organization'], $blocks['afform_form']['blocks'][$this->formNames[1]]['contact_type']);
    $this->assertNull($blocks['afform_form']['blocks'][$this->formNames[2]]['contact_type']);
    // Forms should be sorted by title
    $order = array_flip(array_keys($blocks['afform_form']['blocks']));
    $this->assertGreaterThan($order[$this->formNames[2]], $order[$this->formNames[1]]);
    // Unless explicit weight is given
    $this->assertGreaterThan($order[$this->formNames[3]], $order[$this->formNames[2]]);
  }

  public function testAfformCaseSummaryBlock(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->saveTestRecords('ContactType', [
      'records' => [
        ['name' => 'Ghost', 'label' => 'Ghost', 'parent_id:name' => 'Individual'],
      ],
      'match' => ['name'],
    ]);

    $cid = $this->createTestRecord('Individual', [
      'contact_sub_type' => ['Ghost'],
    ])['id'];

    $cases = $this->saveTestRecords('Case', [
      'records' => [
        ['contact_id' => $cid, 'case_type_id:name' => 'housing_support'],
      ],
      'defaults' => [
        'creator_id' => $this->createTestRecord('Individual')['id'],
      ],
    ]);

    Afform::create()
      ->addValue('name', $this->formNames[0])
      ->addValue('title', 'Test A')
      ->addValue('type', 'search')
      ->addValue('placement', ['case_summary_block'])
      ->addValue('placement_weight', 4)
      ->addValue('placement_filters', [
        'contact_type' => ['Organization', 'Household'],
        'case_type' => ['housing_support'],
      ])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[1])
      ->addValue('title', 'Test B')
      ->addValue('type', 'form')
      ->addValue('placement', ['case_summary_block'])
      ->addValue('placement_weight', 3)
      ->addValue('placement_filters', [
        'contact_type' => ['Ghost'],
        'case_type' => ['housing_support'],
      ])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[2])
      ->addValue('type', 'form')
      ->addValue('title', 'Test C')
      ->addValue('placement', ['case_summary_block'])
      ->addValue('placement_weight', 2)
      ->addValue('placement_filters', [
        'case_type' => ['adult_day_care_referral'],
      ])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[3])
      ->addValue('type', 'form')
      ->addValue('title', 'Test D')
      ->addValue('placement', ['case_summary_block'])
      ->addValue('placement_weight', 1)
      ->execute();

    // Call buildForm hook and then assert afforms have been added to the appropriate region
    $caseView = new \CRM_Case_Form_CaseView();
    $caseView->controller = new \CRM_Core_Controller_Simple('CRM_Case_Form_CaseView', 'Case');
    $caseView->set('id', $cases[0]['id']);
    $caseView->set('cid', $cid);
    \CRM_Utils_Hook::buildForm('CRM_Case_Form_CaseView', $caseView);

    // Find an afform on the case summary
    $getFromRegion = function ($formName) {
      return \CRM_Core_Region::instance('case-view-custom-data-view')->get('afform:' . $formName);
    };

    $blockA = $getFromRegion($this->formNames[0]);
    $this->assertEmpty($blockA);

    $blockB = $getFromRegion($this->formNames[1]);
    $this->assertStringContainsString("\"contact_id\":$cid", $blockB['markup']);
    $this->assertStringContainsString("\"case_id\":{$cases[0]['id']}", $blockB['markup']);
    $this->assertEquals(3, $blockB['weight']);

    $blockC = $getFromRegion($this->formNames[2]);
    $this->assertEmpty($blockC);

    $blockD = $getFromRegion($this->formNames[3]);
    $this->assertStringContainsString("\"contact_id\":$cid", $blockD['markup']);
    $this->assertStringContainsString("\"case_id\":{$cases[0]['id']}", $blockD['markup']);
    $this->assertEquals(1, $blockD['weight']);
  }

}
