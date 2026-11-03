<?php

use Civi\Api4\Individual;

/**
 * Getter behavior shared by hook_civicrm_pre and
 * hook_civicrm_post/postCommit: getValue(), hasValue() and getValues().
 *
 * @group headless
 */
class CRM_Core_BAO_HookGetterTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * @var string
   */
  private $hookType;

  protected function tearDown(): void {
    // Won't get cleaned up otherwise (even if we did use a transaction, because it is DDL).
    if (!empty($this->ids['CustomGroup'])) {
      \Civi\Api4\CustomGroup::delete(FALSE)
        ->addWhere('id', 'IN', $this->ids['CustomGroup'])
        ->execute();
    }
    parent::tearDown();
  }

  public function hookTypeProvider(): array {
    return [
      'pre' => ['pre'],
      'post' => ['post'],
    ];
  }

  /**
   * @dataProvider hookTypeProvider
   */
  public function testCustomValuesWithHookGetters(string $hookType): void {
    $this->hookType = $hookType;
    $customGroupId = $this->createCustomGroup([
      'name' => 'testGroupWithHookGetter',
      'extends' => 'Individual',
    ]);
    $field1Id = $this->createTextCustomField([
      'custom_group_id' => $customGroupId,
      'default_value' => NULL,
      'name' => 'field1',
    ])['id'];
    $field2Id = $this->createIntCustomField([
      'custom_group_id' => $customGroupId,
      'name' => 'field2',
      'default_value' => NULL,
    ])['id'];
    $field3Id = $this->createBooleanCustomField([
      'custom_group_id' => $customGroupId,
      'name' => 'field3',
    ])['id'];
    $field4Id = $this->createStringCheckboxCustomField([
      'custom_group_id' => $customGroupId,
      'name' => 'field4',
    ])['id'];

    Civi::dispatcher()->addListener("hook_civicrm_{$hookType}::Individual", [$this, 'assertGetterValues']);

    // Will invoke assertGetterValues() via Api4.
    Individual::create(FALSE)
      ->addValue('first_name', 'Ms. Right')
      ->addValue('testGroupWithHookGetter.field1', 'correct value')
      ->addValue('testGroupWithHookGetter.field2', 123)
      ->addValue('testGroupWithHookGetter.field3', TRUE)
      ->addValue('testGroupWithHookGetter.field4:label', ['Lilac', 'Purple'])
      ->execute()->single();

    // Same assertions, but via the legacy custom_<id> API3 format.
    civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Ms. Right',
      "custom_$field1Id" => 'correct value',
      "custom_$field2Id" => 123,
      "custom_$field3Id" => 1,
      "custom_$field4Id" => CRM_Utils_Array::implodePadded(['L', 'P']),
    ]);

    // Same again via the weird custom_<id>_-1 format from CRM_Contact_Form_Edit
    $params = [
      'contact_type' => 'Individual',
      'first_name' => 'Ms. Right',
      "custom_{$field1Id}_-1" => 'correct value',
      "custom_{$field2Id}_-1" => 123,
      "custom_{$field3Id}_-1" => 1,
      "custom_{$field4Id}_-1" => ['L', 'P'],
    ];
    if ($hookType === 'pre') {
      CRM_Utils_Hook::pre('create', 'Individual', NULL, $params);
    }
    else {
      $object = new CRM_Contact_DAO_Contact();
      CRM_Utils_Hook::post('create', 'Individual', NULL, $object, $params);
    }
  }

  /**
   * @param \Civi\Core\Event\PreEvent|\Civi\Core\Event\PostEvent $event
   */
  public function assertGetterValues($event): void {
    $expectedValues = [
      'contact_type' => 'Individual',
      'first_name' => 'Ms. Right',
      'testGroupWithHookGetter.field1' => 'correct value',
      'testGroupWithHookGetter.field2' => 123,
      'testGroupWithHookGetter.field3' => TRUE,
      'testGroupWithHookGetter.field4' => ['L', 'P'],
    ];

    // getValues() should return all custom fields in longName format
    // regardless of which format they were set in.
    $getValues = $event->getValues();
    if ($this->hookType === 'pre') {
      // PreEvent::$params only ever holds what was actually passed in, so
      // this can be asserted as a matching array after removing irrelevantParams.
      $irrelevantParams = ['check_permissions', 'modified_date', 'version', 'skip_greeting_processing', 'testGroupWithHookGetter.field4:label'];
      CRM_Utils_Array::remove($getValues, $irrelevantParams);
    }
    else {
      // PostEvent::$params is the full saved record, not just what
      // was passed in, so only remove the fields we aren't checking.
      $getValues = array_intersect_key($getValues, $expectedValues);
    }
    // Multi-select order isn't guaranteed, so sort before comparing.
    sort($getValues['testGroupWithHookGetter.field4']);
    sort($expectedValues['testGroupWithHookGetter.field4']);
    $this->assertEquals($expectedValues, $getValues);

    $this->assertTrue($event->hasValue('first_name'));
    $this->assertTrue($event->hasValue('testGroupWithHookGetter.field1'));
    $this->assertFalse($event->hasValue('last_name'));
    $this->assertFalse($event->hasValue('testGroupWithHookGetter.nosuchfield'));

    // hasValue() should distinguish a param explicitly set to NULL from one
    // that's simply not present.
    $this->assertFalse($event->hasValue('nick_name'));
    $event->params['nick_name'] = NULL;
    $this->assertTrue($event->hasValue('nick_name'));
    $this->assertNull($event->getValue('nick_name'));
    unset($event->params['nick_name']);

    $this->assertSame('Ms. Right', $event->getValue('first_name'));
    $this->assertSame('correct value', $event->getValue('testGroupWithHookGetter.field1'));
    $this->assertSame(123, $event->getValue('testGroupWithHookGetter.field2'));
    $this->assertEquals(TRUE, $event->getValue('testGroupWithHookGetter.field3'));
    $this->assertEqualsCanonicalizing(['L', 'P'], $event->getValue('testGroupWithHookGetter.field4'));
  }

}
