<?php
namespace Civi\Afform;

// Hopefully temporry workaround for loading core test classes
require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use Civi\Api4\Afform;

/**
 * @group headless
 */
class AfformContactSummaryTest extends \api\v4\Api4TestBase {

  private $formNames = [
    'contact_summary_test1',
    'contact_summary_test2',
    'contact_summary_test3',
    'contact_summary_test4',
    'contact_summary_test5',
  ];

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->install('org.civicrm.search_kit')->apply();
  }

  public function tearDown(): void {
    Afform::revert(FALSE)->addWhere('name', 'IN', $this->formNames)->execute();
    parent::tearDown();
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
      ->addValue('contact_summary', 'tab')
      ->addValue('summary_contact_type', ['Organization'])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[1])
      ->addValue('title', 'Test C')
      ->addValue('contact_summary', 'tab')
      ->addValue('summary_contact_type', ['FooBar'])
      ->addValue('icon', 'smiley-face')
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[2])
      ->addValue('title', 'Test A')
      ->addValue('contact_summary', 'tab')
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[3])
      ->addValue('title', 'Test D')
      ->addValue('contact_summary', 'tab')
      ->addValue('summary_contact_type', ['Individual'])
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

    $this->assertArrayHasKey($this->formNames[1], $tabs);
    $this->assertArrayHasKey($this->formNames[2], $tabs);
    $this->assertArrayNotHasKey($this->formNames[0], $tabs);
    $this->assertArrayHasKey($this->formNames[3], $tabs);
    $this->assertArrayNotHasKey($this->formNames[4], $tabs);
    $this->assertEquals('Test C', $tabs[$this->formNames[1]]['title']);
    $this->assertEquals(['Individual'], $tabs[$this->formNames[1]]['contact_type']);
    $this->assertEquals(['Individual'], $tabs[$this->formNames[3]]['contact_type']);
    $this->assertEquals('Test A', $tabs[$this->formNames[2]]['title']);
    $this->assertEquals('crm-i smiley-face', $tabs[$this->formNames[1]]['icon']);
    // Fallback icon
    $this->assertEquals('crm-i fa-list-alt', $tabs[$this->formNames[2]]['icon']);
    // Forms should be sorted by title alphabetically
    $this->assertGreaterThan($tabs[$this->formNames[2]]['weight'], $tabs[$this->formNames[1]]['weight']);
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
      ->addValue('contact_summary', 'block')
      ->addValue('summary_contact_type', ['Individual', 'Household'])
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[1])
      ->addValue('title', 'Test C')
      ->addValue('type', 'form')
      ->addValue('contact_summary', 'block')
      ->addValue('summary_contact_type', ['Farm'])
      ->addValue('icon', 'smiley-face')
      ->execute();
    Afform::create()
      ->addValue('name', $this->formNames[2])
      ->addValue('type', 'form')
      ->addValue('title', 'Test A')
      ->addValue('contact_summary', 'block')
      ->execute();

    // Call pageRun hook and then assert afforms have been added to the appropriate region
    $dummy = new \CRM_Contact_Page_View_Summary();
    $dummy->set('cid', $cid);
    \CRM_Utils_Hook::pageRun($dummy);

    // TODO: Be more flexible
    // The presence of any other afform blocks in the system might alter the left-right assumptions here
    $blockA = \CRM_Core_Region::instance('contact-basic-info-left')->get('afform:' . $this->formNames[2]);
    $this->assertStringContainsString("<contact-summary-test3 options=\"{contact_id: $cid}\"></contact-summary-test3>", $blockA['markup']);

    $blockB = \CRM_Core_Region::instance('contact-basic-info-right')->get('afform:' . $this->formNames[1]);
    $this->assertStringContainsString("<contact-summary-test2 options=\"{contact_id: $cid}\"></contact-summary-test2>", $blockB['markup']);

    // Block for wrong contact type should not appear
    $this->assertNull(\CRM_Core_Region::instance('contact-basic-info-left')->get('afform:' . $this->formNames[0]));
    $this->assertNull(\CRM_Core_Region::instance('contact-basic-info-right')->get('afform:' . $this->formNames[0]));

    // Ensure blocks show up in ContactLayoutEditor
    $blocks = [];
    afform_civicrm_contactSummaryBlocks($blocks);

    $this->assertEquals(['Individual', 'Household'], $blocks['afform_search']['blocks'][$this->formNames[0]]['contact_type']);
    // Sub-type should have been converted to parent type
    $this->assertEquals(['Organization'], $blocks['afform_form']['blocks'][$this->formNames[1]]['contact_type']);
    $this->assertNull($blocks['afform_form']['blocks'][$this->formNames[2]]['contact_type']);
    // Forms should be sorted by title
    $order = array_flip(array_keys($blocks['afform_form']['blocks']));
    $this->assertGreaterThan($order[$this->formNames[2]], $order[$this->formNames[1]]);
  }

}
