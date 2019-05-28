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
    $idx = new CRM_Core_InnoDBIndexer(FALSE, array());
    $idx->fixSchemaDifferences();

    parent::tearDown();
  }

  public function testHasDeclaredIndex() {
    $idx = new CRM_Core_InnoDBIndexer(TRUE, array(
      'civicrm_contact' => array(
        array('first_name', 'last_name'),
        array('foo'),
      ),
      'civicrm_email' => array(
        array('whiz'),
      ),
    ));

    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', array('first_name', 'last_name')));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', array('last_name', 'first_name')));
    // not sure if this is right behavior
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', array('first_name')));
    // not sure if this is right behavior
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', array('last_name')));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', array('foo')));
    $this->assertFalse($idx->hasDeclaredIndex('civicrm_contact', array('whiz')));

    $this->assertFalse($idx->hasDeclaredIndex('civicrm_email', array('first_name', 'last_name')));
    $this->assertFalse($idx->hasDeclaredIndex('civicrm_email', array('foo')));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_email', array('whiz')));
  }

  /**
   * When disabled, there is no FTS index, so queries that rely on FTS index fail.
   */
  public function testDisabled() {
    $idx = new CRM_Core_InnoDBIndexer(FALSE, array(
      'civicrm_contact' => array(
        array('first_name', 'last_name'),
      ),
    ));
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

    $idx = new CRM_Core_InnoDBIndexer(TRUE, array(
      'civicrm_contact' => array(
        array('first_name', 'last_name'),
      ),
    ));
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
