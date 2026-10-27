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
  }

}
