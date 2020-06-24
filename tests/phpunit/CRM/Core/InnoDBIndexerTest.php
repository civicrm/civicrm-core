<?php

/**
 * Class CRM_Core_InnoDBIndexerTest
 * @group headless
 */
class CRM_Core_InnoDBIndexerTest extends CiviUnitTestCase {

  public function tearDown() {
    // May or may not cleanup well if there's a bug in the indexer.
    // This is better than nothing -- and better than duplicating the
    // cleanup logic.
    $idx = new CRM_Core_InnoDBIndexer(FALSE, []);
    $idx->fixSchemaDifferences();

    parent::tearDown();
  }

  public function testHasDeclaredIndex() {
    $idx = new CRM_Core_InnoDBIndexer(TRUE, [
      'civicrm_contact' => [
        ['first_name', 'last_name'],
        ['foo'],
      ],
      'civicrm_email' => [
        ['whiz'],
      ],
    ]);

    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['first_name', 'last_name']));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['last_name', 'first_name']));
    // not sure if this is right behavior
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['first_name']));
    // not sure if this is right behavior
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['last_name']));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['foo']));
    $this->assertFalse($idx->hasDeclaredIndex('civicrm_contact', ['whiz']));

    $this->assertFalse($idx->hasDeclaredIndex('civicrm_email', ['first_name', 'last_name']));
    $this->assertFalse($idx->hasDeclaredIndex('civicrm_email', ['foo']));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_email', ['whiz']));
  }

  /**
   * When disabled, there is no FTS index, so queries that rely on FTS index fail.
   */
  public function testDisabled() {
    $idx = new CRM_Core_InnoDBIndexer(FALSE, [
      'civicrm_contact' => [
        ['first_name', 'last_name'],
      ],
    ]);
    $idx->fixSchemaDifferences();

    try {
      CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_contact WHERE MATCH(first_name,last_name) AGAINST ("joe")');
      $this->fail("Missed expected exception");
    }
    catch (Exception $e) {
      $this->assertTrue(TRUE, 'Received expected exception');
    }
  }

  /**
   * When enabled, the FTS index is created, so queries that rely on FTS work.
   */
  public function testEnabled() {
    if (!$this->supportsFts()) {
      $this->markTestSkipped("Local installation of InnoDB does not support FTS.");
      return;
    }

    $idx = new CRM_Core_InnoDBIndexer(TRUE, [
      'civicrm_contact' => [
        ['first_name', 'last_name'],
      ],
    ]);
    $idx->fixSchemaDifferences();

    CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_contact WHERE MATCH(first_name,last_name) AGAINST ("joe")');
  }

  /**
   * @return mixed
   */
  public function supportsFts() {
    return version_compare(CRM_Core_DAO::singleValueQuery('SELECT VERSION()'), '5.6.0', '>=');
  }

}
