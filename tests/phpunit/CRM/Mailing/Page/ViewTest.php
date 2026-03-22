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

use Civi\Api4\Email;

/**
 * Test class for CRM_Mailing_Page_View.
 * @group headless
 */
class CRM_Mailing_Page_ViewTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_group', 'civicrm_group_contact', 'civicrm_mailing', 'civicrm_mailing_group', 'civicrm_mailing_recipients', 'civicrm_mailing_event_queue']);
    parent::tearDown();
  }

  /**
   * Test that numeric ID access is denied for unprivileged users
   * when hash_mailing_url is enabled.
   */
  public function testHashNumericIdDeniedWithoutPermission(): void {
    Civi::settings()->set('hash_mailing_url', 1);
    $this->setupMailing();
    CRM_Core_DAO::executeQuery("UPDATE civicrm_mailing SET hash = %1 WHERE id = %2", [
      1 => ['109002430016e903', 'String'],
      2 => [$this->ids['Mailing']['default'], 'Positive'],
    ]);
    $this->setPermissions(['view public CiviMail content']);
    $_REQUEST['id'] = (string) $this->ids['Mailing']['default'];
    $page = new CRM_Mailing_Page_View('View Mailing');
    $this->expectException(CRM_Core_Exception::class);
    $page->run();
  }

  /**
   * Test that numeric ID access is allowed for users with access CiviMail
   * when hash_mailing_url is enabled.
   */
  public function testHashNumericIdAllowedWithCiviMailPermission(): void {
    Civi::settings()->set('hash_mailing_url', 1);
    $this->setupMailing();
    CRM_Core_DAO::executeQuery("UPDATE civicrm_mailing SET hash = %1 WHERE id = %2", [
      1 => ['109002430016e903', 'String'],
      2 => [$this->ids['Mailing']['default'], 'Positive'],
    ]);
    $this->setPermissions(['access CiviCRM', 'access CiviMail']);
    $_REQUEST['id'] = (string) $this->ids['Mailing']['default'];
    $page = new CRM_Mailing_Page_View('View Mailing');
    try {
      $page->run();
      $this->fail('Page Run should cause a PrematureExitException to occur');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Page rendered successfully — expected for admin users.
    }
  }

  public function testHashNumericMailingView(): void {
    Civi::settings()->set('hash_mailing_url', 1);
    $this->setupMailing();
    CRM_Core_DAO::executeQuery("UPDATE civicrm_mailing SET hash = %1 WHERE id = %2", [
      1 => ['109002430016e903', 'String'],
      2 => [$this->ids['Mailing']['default'], 'Positive'],
    ]);
    $_REQUEST['id'] = '109002430016e903';
    $page = new CRM_Mailing_Page_View('View Mailing');
    try {
      $page->run();
      $this->fail('Page Run should cause a Premeture Exit Exemption to occur');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
    }

  }

  /**
   * Set up a 'sent' mailing with 2 attached groups which the contact is part of.
   *
   * One group is a smarty group of all individuals.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function setupMailing(): void {

    $savedSearch = $this->createTestEntity('SavedSearch', [
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id'],
        'where' => [['contact_type', '=', 'Individual']],
      ],
    ]);

    $params = [
      'contact_id' => $this->individualCreate(),
      'group_id' => $this->groupCreate(['name' => 'Test group 2', 'title' => 'group title 2']),
    ];
    $this->createTestEntity('GroupContact', $params);

    $email = Email::get()
      ->addWhere('id', '=', $params['contact_id'])
      ->execute()->first();
    $this->createTestEntity('MailingEventQueue', [
      'mailing_id' => $this->createMailing(),
      'hash' => 'abc',
      'contact_id' => $params['contact_id'],
      'email_id' => $email['id'],
    ]);
    $this->createTestEntity('MailingGroup', [
      'mailing_id' => $this->ids['Mailing']['default'],
      'entity_table' => 'civicrm_group',
      'entity_id' => $params['group_id'],
      'group_type' => 'Base',
    ]);
    $this->createTestEntity('MailingGroup', [
      'mailing_id' => $this->ids['Mailing']['default'],
      'entity_table' => 'civicrm_group',
      'entity_id' => $this->groupCreate(['saved_search_id' => $savedSearch['id']], 'smart_group'),
      'group_type' => 'Include',
    ]);
  }

}
