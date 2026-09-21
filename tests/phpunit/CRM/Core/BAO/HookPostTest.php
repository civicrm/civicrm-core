<?php

use Civi\Core\Event\PostEvent;

/**
 * Post-hook-only behavior: getValue()/hasValue()/getValues() are shared
 * with Pre and covered by CRM_Core_BAO_HookGetterTest.
 *
 * @group headless
 */
class CRM_Core_BAO_HookPostTest extends CiviUnitTestCase {

  /**
   * Unlike PreEvent, PostEvent::$params can be NULL, since some core
   * post() callers omit it (e.g. deletes).
   */
  public function testGettersTolerateNullParams(): void {
    $object = new CRM_Contact_DAO_Contact();
    $event = new PostEvent('delete', 'Individual', 1, $object, NULL);

    $this->assertFalse($event->hasValue('first_name'));
    $this->assertNull($event->getValue('first_name'));
    $this->assertEquals([], $event->getValues());
  }

}
