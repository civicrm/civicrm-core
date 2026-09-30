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

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\Mailing;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class MailingTest extends Api4TestBase implements TransactionalInterface {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviMail');
  }

  /**
   * A save carrying an older modified_date than the stored one is refused, as APIv3 does (CRM-20892).
   */
  public function testSaveFromStaleCopyIsRefused(): void {
    $mailing = Mailing::create(FALSE)
      ->setValues(['name' => 'Stale test', 'subject' => 'First', 'body_html' => '<p>First</p>'])
      ->execute()->single();
    $loaded = Mailing::get(FALSE)->addSelect('modified_date')->addWhere('id', '=', $mailing['id'])
      ->execute()->single()['modified_date'];
    // Another tab or user saves later.
    \CRM_Core_DAO::executeQuery('UPDATE civicrm_mailing SET subject = %1, modified_date = %2 WHERE id = %3', [
      1 => ['Second', 'String'],
      2 => [date('Y-m-d H:i:s', strtotime($loaded) + 60), 'String'],
      3 => [$mailing['id'], 'Integer'],
    ]);

    foreach (['save', 'update'] as $action) {
      try {
        $call = $action === 'save'
          ? Mailing::save(FALSE)->addRecord(['id' => $mailing['id'], 'modified_date' => $loaded, 'subject' => 'Stale'])
          : Mailing::update(FALSE)->addWhere('id', '=', $mailing['id'])
            ->setValues(['modified_date' => $loaded, 'subject' => 'Stale']);
        $call->execute();
        $this->fail("Mailing.$action from a stale copy was not refused");
      }
      catch (\CRM_Core_Exception $e) {
        $this->assertStringContainsString('Content maybe out of date', $e->getMessage());
      }
    }
    $this->assertSame('Second', $this->storedSubject($mailing['id']));

    $current = Mailing::get(FALSE)->addSelect('modified_date')->addWhere('id', '=', $mailing['id'])
      ->execute()->single()['modified_date'];
    Mailing::save(FALSE)->addRecord(['id' => $mailing['id'], 'modified_date' => $current, 'subject' => 'Current'])
      ->execute();
    $this->assertSame('Current', $this->storedSubject($mailing['id']));

    // A write that does not carry modified_date is not checked.
    Mailing::update(FALSE)->addWhere('id', '=', $mailing['id'])->addValue('subject', 'Unpinned')->execute();
    $this->assertSame('Unpinned', $this->storedSubject($mailing['id']));
  }

  /**
   * A new mailing's response carries the modified_date left by its hash write, so the editor's next save lands.
   */
  public function testCreateReturnsModifiedDateAfterHash(): void {
    // Move this connection's clock between the insert and the hash write, as a second boundary would.
    $shiftClock = function ($e): void {
      if ($e->entity === 'Mailing' && $e->action === 'create') {
        \CRM_Core_DAO::executeQuery('SET @@session.timestamp = UNIX_TIMESTAMP() + 60');
      }
    };
    \Civi::dispatcher()->addListener('hook_civicrm_post', $shiftClock);
    try {
      $mailing = Mailing::save(FALSE)
        ->addRecord(['name' => 'Hash test', 'subject' => 'First', 'body_html' => '<p>First</p>'])
        ->execute()->single();
    }
    finally {
      \Civi::dispatcher()->removeListener('hook_civicrm_post', $shiftClock);
      \CRM_Core_DAO::executeQuery('SET @@session.timestamp = DEFAULT');
    }
    $stored = \CRM_Core_DAO::singleValueQuery('SELECT modified_date FROM civicrm_mailing WHERE id = %1',
      [1 => [$mailing['id'], 'Integer']]);
    $this->assertSame(strtotime($stored), strtotime($mailing['modified_date']));

    Mailing::save(FALSE)
      ->addRecord(['id' => $mailing['id'], 'modified_date' => $mailing['modified_date'], 'subject' => 'Second'])
      ->execute();
    $this->assertSame('Second', $this->storedSubject($mailing['id']));
  }

  private function storedSubject(int $id): string {
    return (string) \CRM_Core_DAO::singleValueQuery('SELECT subject FROM civicrm_mailing WHERE id = %1',
      [1 => [$id, 'Integer']]);
  }

}
