<?php

namespace Civi\Standalone;

use Civi\Api4;
use Civi\Test\EndToEndInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group e2e
 */
class SessionHandlerTest extends TestCase implements EndToEndInterface {

  public function setUp(): void {
    parent::setUp();

    if (CIVICRM_UF !== 'Standalone') {
      $this->markTestSkipped('Test only applies on Standalone');
    }
  }

  public function tearDown(): void {
    Api4\Session::delete(FALSE)
      ->addWhere('id', '>', 0)
      ->execute();

    parent::tearDown();
  }

  public function testHandler(): void {
    $session_handler = new SessionHandler();

    // Open a session
    $session_handler->open('/path', 'SESSCIVISO');

    // Create a unique session ID
    $session_id = $session_handler->create_sid();
    $this->assertEquals(64, strlen($session_id));

    // Try to read data from the session
    $data = $session_handler->read($session_id);
    // Should return an empty string because the session doesn't exist yet
    $this->assertEquals('', $data);

    // Write to the session
    $session_handler->write($session_id, 'CiviCRM|a:1:{s:4:"ufID";i:1}');

    // Close the session
    $session_handler->close();

    // Check the stored session
    $session = self::getSession($session_id);
    $this->assertIsArray($session);
    $this->assertEquals('CiviCRM|a:1:{s:4:"ufID";i:1}', $session['data']);

    $original_timestamp = $session['last_accessed'];

    sleep(1);

    // Re-open the session
    $session_handler->open('/path', 'SESSCIVISO');

    // Validate the session
    $this->assertTrue($session_handler->validateId($session_id));

    // Read from the session
    $data = $session_handler->read($session_id);
    $this->assertEquals('CiviCRM|a:1:{s:4:"ufID";i:1}', $data, 'Session should have stored data');

    // Update the session timestamp
    $session_handler->updateTimestamp($session_id, 'CiviCRM|a:1:{s:4:"ufID";i:1}');

    // Close the session
    $session_handler->close();

    // Check the stored session
    $session = self::getSession($session_id);
    $this->assertIsArray($session);
    $this->assertEquals('CiviCRM|a:1:{s:4:"ufID";i:1}', $session['data']);

    $updated_timestamp = $session['last_accessed'];

    $this->assertGreaterThan(
      strtotime($original_timestamp),
      strtotime($updated_timestamp),
      'Session timestamp should have been updated'
    );

    sleep(1);

    // Re-open the session
    $session_handler->open('/path', 'SESSCIVISO');

    // Validate the session
    $this->assertTrue($session_handler->validateId($session_id));

    // Read from the session
    $data = $session_handler->read($session_id);
    $this->assertEquals('CiviCRM|a:1:{s:4:"ufID";i:1}', $data, 'Session data should not have changed');

    // Destroy the session
    $session_handler->destroy($session_id);

    // Close the session
    $session_handler->close();

    // Check the stored session
    $session = self::getSession($session_id);
    $this->assertNull($session, 'Session should have been deleted');
  }

  public function testGarbageCollection() {
    $session_handler = new SessionHandler();
    $old_sid = $session_handler->create_sid();

    // Create an old (expired) session
    Api4\Session::create(FALSE)
      ->addValue('session_id', $old_sid)
      ->addValue('data', 'CiviCRM|a:0:{}')
      ->addValue('last_accessed', date('Y-m-d H:i:s', strtotime('-1 week')))
      ->execute();

    // Open a new session to trigger garbage collection
    $session_handler->open('/path', 'SESSCIVISO');
    $new_sid = $session_handler->create_sid();
    $session_handler->read($new_sid);

    // Run garbage collection with a $max_lifetime of 24 hours
    $session_handler->gc(60 * 60 * 24);

    // Finish the current session lifecycle
    $session_handler->write($new_sid, 'CiviCRM|a:0:{}');
    $session_handler->close();

    // The old session should now be gone
    $old_session = self::getSession($old_sid);
    $this->assertNull($old_session, 'Expired session should have been deleted');

    // The new session should still be here
    $new_session = self::getSession($new_sid, 'New session should not have been deleted');
    $this->assertIsArray($new_session);
  }

  public function testTransaction() {
    $session_handler = new SessionHandler();

    // Start a transaction
    $tx = new \CRM_Core_Transaction();

    // Create a new session
    $session_handler->open('/path', 'SESSCIVISO');
    $session_id = $session_handler->create_sid();
    $session_handler->read($session_id);
    $session_handler->write($session_id, 'CiviCRM|a:0:{}');
    $session_handler->close();

    // Re-open the session
    $session_handler->open('/path', 'SESSCIVISO');
    $session_handler->validateId($session_id);
    $session_handler->read($session_id);
    $session_handler->write($session_id, 'CiviCRM|a:1:{s:4:"ufID";i:1}');

    // Rollback the transaction
    $tx->rollback();

    // Continue with session management
    $session_handler->close();

    // Make sure the session has not been affected by the transaction rollback
    $session = self::getSession($session_id);
    $this->assertIsArray($session);
    $this->assertEquals('CiviCRM|a:1:{s:4:"ufID";i:1}', $session['data']);
  }

  private static function getSession($session_id) {
    return Api4\Session::get(FALSE)
      ->addSelect('*')
      ->addWhere('session_id', '=', $session_id)
      ->execute()
      ->first();
  }

}
