<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Contact;

/**
 * Test case for Afform.prefill and Afform.submit.
 *
 * @group headless
 */
class AfformCustomFieldUsageTest extends AfformUsageTestCase {

  public function tearDown(): void {
    parent::tearDown();
  }

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    self::$layouts['customMulti'] = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
  <fieldset af-fieldset="Individual1">
    <legend class="af-text">Individual 1</legend>
    <afblock-name-individual></afblock-name-individual>
    <div af-join="Custom_MyThings" af-repeat="Add" max="2">
      <afblock-custom-my-things></afblock-custom-my-things>
    </div>
  </fieldset>
  <button class="af-button btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;
  }

  /**
   * Checks that by creating a multi-record field group,
   * Afform has automatically generated a block to go with it,
   * which can be submitted multiple times
   */
  public function testMultiRecordCustomBlock(): void {
    $this->createTestRecord('CustomGroup', [
      'name' => 'MyThings',
      'title' => 'My Things',
      'style' => 'Tab with table',
      'extends' => 'Contact',
      'is_multiple' => TRUE,
      'max_multiple' => 2,
    ]);
    $this->saveTestRecords('CustomField', [
      'defaults' => [
        'custom_group_id.name' => 'MyThings',
      ],
      'records' => [
        [
          'name' => 'my_text',
          'label' => 'My Text',
          'data_type' => 'String',
          'html_type' => 'Text',
        ],
        [
          'name' => 'my_friend',
          'label' => 'My Friend',
          'data_type' => 'ContactReference',
          'html_type' => 'Autocomplete-Select',
        ],
      ],
    ]);

    // Creating a custom group should automatically create an afform block
    $block = Afform::get()
      ->addWhere('name', '=', 'afblockCustom_MyThings')
      ->addSelect('layout', 'directive_name')
      ->setLayoutFormat('shallow')
      ->setFormatWhitespace(TRUE)
      ->execute()->single();
    $this->assertEquals('afblock-custom-my-things', $block['directive_name']);
    $this->assertEquals('my_text', $block['layout'][0]['name']);
    $this->assertEquals('my_friend', $block['layout'][1]['name']);

    $cid1 = $this->createTestRecord('Individual')['id'];
    $cid2 = $this->createTestRecord('Individual')['id'];

    $this->useValues([
      'layout' => self::$layouts['customMulti'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $firstName = uniqid(__FUNCTION__);
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => $firstName,
            'last_name' => 'tester',
          ],
          'joins' => [
            'Custom_MyThings' => [
              ['my_text' => 'One', 'my_friend' => $cid1],
              ['my_text' => 'Two', 'my_friend' => $cid2],
              ['my_text' => 'Not allowed', 'my_friend' => $cid2],
            ],
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();
    $contact = Contact::get(FALSE)
      ->addWhere('first_name', '=', $firstName)
      ->addJoin('Custom_MyThings AS Custom_MyThings', 'LEFT', ['id', '=', 'Custom_MyThings.entity_id'])
      ->addSelect('Custom_MyThings.my_text', 'Custom_MyThings.my_friend')
      ->addOrderBy('Custom_MyThings.id')
      ->execute();
    $this->assertEquals('One', $contact[0]['Custom_MyThings.my_text']);
    $this->assertEquals($cid1, $contact[0]['Custom_MyThings.my_friend']);
    $this->assertEquals('Two', $contact[1]['Custom_MyThings.my_text']);
    $this->assertEquals($cid2, $contact[1]['Custom_MyThings.my_friend']);
    $this->assertTrue(empty($contact[2]));
  }

}
