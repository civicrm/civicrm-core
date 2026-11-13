<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\GroupContact;
use Civi\Api4\SubscriptionHistory;
use Civi\Test\TransactionalInterface;

/**
 * Test case for Afform.prefill and Afform.submit with GroupSubscription records.
 *
 * @group headless
 */
class AfformGroupSubscriptionUsageTest extends AfformUsageTestCase implements TransactionalInterface {

  /**
   * Tests subscribing and unsubscribing to groups
   */
  public function testGroupSubscription(): void {
    $groupName = __FUNCTION__;
    $lastName = uniqid(__FUNCTION__);
    $this->createTestRecord('Group', [
      'title' => 'Test Group',
      'name' => $groupName,
    ]);

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual', source: 'Test Sub'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" url-autofill="1"/>
  <af-entity data="{contact_id: 'Individual1'}" actions="{create: true, update: true}" security="FBAC" type="GroupSubscription" name="GroupSubscription1" label="Group Subscription 1" group-subscription="no-confirm" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <afblock-name-individual></afblock-name-individual>
  </fieldset>
  <fieldset af-fieldset="GroupSubscription1" class="af-container">
    <af-field name="$groupName" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Submit form with group checkbox checked
    $submission = [
      'Individual1' => [
        ['fields' => ['first_name' => 'Firsty', 'last_name' => $lastName]],
      ],
      'GroupSubscription1' => [
        ['fields' => [$groupName => TRUE]],
      ],
    ];

    $submitted = Afform::submit()
      ->setName($this->formName)
      ->setValues($submission)
      ->execute()->single();
    $cid = $submitted['Individual1'][0]['id'];

    // Submit again, this time leave the field NULL: contact should not be unsubscribed
    $submission['GroupSubscription1'][0]['fields'][$groupName] = NULL;
    $submitted2 = Afform::submit()
      ->setName($this->formName)
      ->setArgs(['Individual1' => $cid])
      ->setValues($submission)
      ->execute()->single();
    $this->assertSame($cid, $submitted2['Individual1'][0]['id']);

    // Verify subscription history
    $history = SubscriptionHistory::get(FALSE)
      ->addWhere('contact_id.last_name', '=', $lastName)
      ->execute()->single();
    $this->assertEquals('Form', $history['method']);
    $this->assertEquals('Added', $history['status']);
    $this->assertEquals('127.0.0.1', $history['tracking']);

    // Prefill - afform will show group checkbox checked
    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->setArgs(['Individual1' => $cid])
      ->execute()
      ->indexBy('name');

    $this->assertSame(TRUE, $prefill['GroupSubscription1']['values'][0]['fields'][$groupName]);

    // Submit again, this time unsubscribe
    $submission['GroupSubscription1'][0]['fields'][$groupName] = FALSE;
    $submitted2 = Afform::submit()
      ->setName($this->formName)
      ->setArgs(['Individual1' => $cid])
      ->setValues($submission)
      ->execute()->single();
    $this->assertSame($cid, $submitted2['Individual1'][0]['id']);

    // Verify subscription history
    $history = SubscriptionHistory::get(FALSE)
      ->addWhere('contact_id.last_name', '=', $lastName)
      ->addWhere('status', '=', 'Removed')
      ->execute()->single();
    $this->assertEquals('127.0.0.1', $history['tracking']);
    $this->assertEquals('Form', $history['method']);
  }

  /**
   * A contact found by a dedupe rule can be subscribed but not unsubscribed
   */
  public function testDedupeMatchIsNotUnsubscribed(): void {
    $groupName = __FUNCTION__;
    $otherGroupName = __FUNCTION__ . 'Other';
    $groupId = $this->createTestRecord('Group', ['title' => 'Test Group', 'name' => $groupName])['id'];
    $otherGroupId = $this->createTestRecord('Group', ['title' => 'Other Test Group', 'name' => $otherGroupName])['id'];
    $email = uniqid(__FUNCTION__) . '@example.org';
    $storedContactId = $this->createTestRecord('Individual', [
      'first_name' => 'Stored',
      'last_name' => 'Contact',
      'email_primary.email' => $email,
    ])['id'];
    $this->createTestRecord('GroupContact', ['contact_id' => $storedContactId, 'group_id' => $groupId, 'status' => 'Added']);

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" actions="{create: true, update: false}" security="FBAC" contact-dedupe="Individual.Unsupervised" />
  <af-entity data="{contact_id: 'Individual1'}" actions="{create: true, update: true}" security="FBAC" type="GroupSubscription" name="GroupSubscription1" group-subscription="no-confirm" />
  <fieldset af-fieldset="Individual1">
    <af-field name="first_name" />
    <af-field name="last_name" />
    <div af-join="Email" data="{is_primary: true}"><af-field name="email" /></div>
  </fieldset>
  <fieldset af-fieldset="GroupSubscription1">
    <af-field name="$groupName" />
    <af-field name="$otherGroupName" />
  </fieldset>
</af-form>
EOHTML;
    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];

    Afform::submit()
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [
          ['fields' => ['first_name' => 'Stored', 'last_name' => 'Contact'], 'joins' => ['Email' => [['email' => $email]]]],
        ],
        'GroupSubscription1' => [
          ['fields' => [$groupName => FALSE, $otherGroupName => TRUE]],
        ],
      ])
      ->execute();

    $statuses = GroupContact::get(FALSE)
      ->addWhere('contact_id', '=', $storedContactId)
      ->addSelect('group_id', 'status')
      ->execute()->column('status', 'group_id');
    $this->assertSame([$groupId => 'Added', $otherGroupId => 'Added'], $statuses);
  }

}
