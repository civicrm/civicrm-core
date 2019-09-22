<?php

namespace api\v4\Action;

use Civi\Api4\Contact;

/**
 * @group headless
 */
class ContactChecksumTest extends \api\v4\UnitTestCase {

  public function testGetChecksum() {
    $contact = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Check')
      ->addValue('last_name', 'Sum')
      ->addChain('cs', Contact::getChecksum()->setContactId('$id')->setTtl(500), 0)
      ->execute()
      ->first();

    $result = Contact::validateChecksum()
      ->setContactId($contact['id'])
      ->setChecksum($contact['cs']['checksum'])
      ->execute()
      ->first();

    $this->assertTrue($result['valid']);
  }

  public function testValidateChecksum() {
    $cid = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Checker')
      ->addValue('last_name', 'Sum')
      ->execute()
      ->first()['id'];

    $goodCs = \CRM_Contact_BAO_Contact_Utils::generateChecksum($cid, NULL, 500);
    $badCs = \CRM_Contact_BAO_Contact_Utils::generateChecksum($cid, strtotime('now - 1 week'), 1);

    $result1 = Contact::validateChecksum()
      ->setContactId($cid)
      ->setChecksum($goodCs)
      ->execute()
      ->first();
    $this->assertTrue($result1['valid']);

    $result2 = Contact::validateChecksum()
      ->setContactId($cid)
      ->setChecksum($badCs)
      ->execute()
      ->first();
    $this->assertFalse($result2['valid']);
  }

}
