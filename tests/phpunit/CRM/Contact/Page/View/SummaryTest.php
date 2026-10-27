<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Test\HeadlessInterface;

/**
 * Test class for CRM_Contact_Page_View_Summary
 *
 * @group headless
 */
class CRM_Contact_Page_View_SummaryTest extends CiviUnitTestCase implements HeadlessInterface {

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return \Civi\Test::headless()->apply();
  }

  public function tearDown(): void {
    unset($_GET['cid'], $_REQUEST['cid']);
    \Civi::settings()->revert('contact_summary_tab_counts');
    parent::tearDown();
  }

  public function testGetTabsWithTemplateReplacement(): void {
    $contact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Tab',
      'last_name' => 'Tester',
    ]);

    $_GET['cid'] = $_REQUEST['cid'] = $contact['id'];

    $hookCiviTabset = function (\Civi\Core\Event\GenericHookEvent $e) {
      $existingTabs = array_column($e->tabs, 'id');
      $existingRel = array_search('rel', $existingTabs);
      if ($existingRel !== FALSE) {
        unset($e->tabs[$existingRel]);
      }
      $e->tabs['rel'] = [
        'id' => 'rel',
        'title' => 'Replaced Relationships',
        'template' => 'afform/InlineAfform.tpl',
        'count' => FALSE,
      ];

      $existingAct = array_search('activity', $existingTabs);
      if ($existingAct !== FALSE) {
        unset($e->tabs[$existingAct]);
      }
      $e->tabs['activity'] = [
        'id' => 'activity',
        'title' => 'Replaced Activities',
        'hideCount' => TRUE,
      ];
    };

    \Civi::dispatcher()->addListener('hook_civicrm_tabset', $hookCiviTabset);

    $page = new \CRM_Contact_Page_View_Summary();
    $page->set('contactType', 'Individual');
    $page->getContactID();

    $tabs = $page->getTabs();

    \Civi::dispatcher()->removeListener('hook_civicrm_tabset', $hookCiviTabset);

    $this->assertArrayHasKey('rel', $tabs);
    $this->assertFalse($tabs['rel']['count']);

    $this->assertArrayHasKey('activity', $tabs);
    $this->assertNull($tabs['activity']['count']);

    $this->assertArrayHasKey('log', $tabs);
    $this->assertIsNumeric($tabs['log']['count']);
  }

  public function testContactSummaryTabCountsDisabled(): void {
    $contact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Tab',
      'last_name' => 'Tester',
    ]);

    $_GET['cid'] = $_REQUEST['cid'] = $contact['id'];

    \Civi::settings()->set('contact_summary_tab_counts', FALSE);

    $page = new \CRM_Contact_Page_View_Summary();
    $page->set('contactType', 'Individual');
    $page->getContactID();

    $tabs = $page->getTabs();

    $this->assertNotEmpty($tabs);
    foreach ($tabs as $tab) {
      $this->assertTrue($tab['hideCount']);
      $this->assertFalse(is_numeric($tab['count']));
    }

    $this->assertFalse(\CRM_Contact_BAO_Contact::getCountComponent('activity', $contact['id']));
    $this->assertIsInt(\CRM_Contact_BAO_Contact::getCountComponent('activity', $contact['id'], FALSE));
  }

  public function testCustomFieldTabCount(): void {
    $contact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Custom',
      'last_name' => 'Tester',
    ]);

    $customGroup = $this->createTestEntity('CustomGroup', [
      'title' => 'Multi Record Group',
      'name' => 'MultiRecordGroup',
      'extends' => 'Individual',
      'style' => 'Tab',
      'is_multiple' => TRUE,
    ]);

    $customField = $this->createTestEntity('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Favorite Food',
      'name' => 'favorite_food',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);

    // Initial count is 0
    $count = \CRM_Contact_BAO_Contact::getCountComponent('custom_' . $customGroup['id'], $contact['id']);
    $this->assertSame(0, $count);

    // Add two custom records
    $this->createTestEntity('Custom_' . $customGroup['name'], [
      'entity_id' => $contact['id'],
      $customField['name'] => 'Pizza',
    ], 'rec1');

    $this->createTestEntity('Custom_' . $customGroup['name'], [
      'entity_id' => $contact['id'],
      $customField['name'] => 'Tacos',
    ], 'rec2');

    // Count by custom group tab identifier (custom_<groupId>)
    $countByTabId = \CRM_Contact_BAO_Contact::getCountComponent('custom_' . $customGroup['id'], $contact['id']);
    $this->assertSame(2, $countByTabId);

    // Verify Summary getTabs() picks up the count
    $_GET['cid'] = $_REQUEST['cid'] = $contact['id'];
    $page = new \CRM_Contact_Page_View_Summary();
    $page->set('contactType', 'Individual');
    $page->getContactID();
    $tabs = $page->getTabs();

    $tabKey = 'custom_' . $customGroup['id'];
    $this->assertArrayHasKey($tabKey, $tabs);
    $this->assertSame(2, $tabs[$tabKey]['count']);
    $this->assertFalse($tabs[$tabKey]['hideCount']);

    // Invalid type throws exception
    $this->expectException(\CRM_Core_Exception::class);
    \CRM_Contact_BAO_Contact::getCountComponent('nonexistent_component_type', $contact['id']);
  }

}
