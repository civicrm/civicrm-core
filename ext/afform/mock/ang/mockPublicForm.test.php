<?php

/**
 * @group e2e
 * @group ang
 */
class MockPublicFormTest extends \Civi\AfformMock\FormTestCase {

  const FILE = __FILE__;

  public function testGetPage() {
    $r = $this->createGuzzle()->get('civicrm/mock-public-form');
    $this->assertContentType('text/html', $r);
    $this->assertStatusCode(200, $r);
    $body = (string) $r->getBody();
    $this->assertContains('mockPublicForm', $body);
  }

  public function testPublicCreateAllowed() {
    $initialMaxId = CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_contact');

    $r = md5(random_bytes(16));

    $me = [0 => ['fields' => []]];
    $me[0]['fields']['first_name'] = 'Firsty' . $r;
    $me[0]['fields']['last_name'] = 'Lasty' . $r;

    $this->submit(['args' => [], 'values' => ['me' => $me]]);

    // Contact was created...
    $contact = Civi\Api4\Contact::get(FALSE)->addWhere('first_name', '=', 'Firsty' . $r)->execute()->single();
    $this->assertEquals('Firsty' . $r, $contact['first_name']);
    $this->assertEquals('Lasty' . $r, $contact['last_name']);
    $this->assertTrue($contact['id'] > $initialMaxId);
  }

  public function testPublicEditDisallowed() {
    $contact = Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'first_name' => 'FirstBegin',
        'last_name' => 'LastBegin',
        'contact_type' => 'Individual',
      ])
      ->execute()
      ->first();

    $r = md5(random_bytes(16));

    $me = [0 => ['fields' => []]];
    $me[0]['fields']['id'] = $contact['id'];
    $me[0]['fields']['first_name'] = 'Firsty' . $r;
    $me[0]['fields']['last_name'] = 'Lasty' . $r;

    $this->submitError(['args' => [], 'values' => ['me' => $me]]);
    $this->assertContentType('application/json')->assertStatusCode(403);

    // Contact hasn't changed
    $get = Civi\Api4\Contact::get(FALSE)->addWhere('id', '=', $contact['id'])->execute()->single();
    $this->assertEquals('FirstBegin', $get['first_name']);
    $this->assertEquals('LastBegin', $get['last_name']);

    // No other contacts were created or edited with the requested value.
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact WHERE first_name=%1', [1 => ["Firsty{$r}", 'String']]));
  }

}
