<?php
namespace Civi\Api4\Action\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Contact;
use Civi\Api4\EntityTag;
use Civi\Api4\Tag;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests Afform submit saving of a multi-value join field.
 *
 * A multi-select bound to a single-value join column (e.g. EntityTag.tag_id)
 * submits one join record whose value is an array; Submit::saveJoins must attach
 * every selected value (one civicrm_entity_tag row per value), rather than
 * writing the array into a single record (which saves nothing / the wrong tag).
 *
 * @group headless
 */
class SubmitTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * @var string
   */
  private $formName;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform'])
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();
    \CRM_Core_Config::singleton()->userPermissionTemp->grant('administer CiviCRM');
    $this->formName = 'mockTagForm' . rand(0, 100000);
    Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues([
        'name' => $this->formName,
        'title' => 'Contact tags test',
        'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
        'layout' => <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
  <fieldset af-fieldset="Individual1">
    <af-field name="first_name" />
    <div af-join="EntityTag">
      <af-field name="tag_id" defn="{input_attrs: {multiple: true}, label: 'Tags'}" />
    </div>
  </fieldset>
</af-form>
EOHTML,
      ])
      ->execute();
  }

  public function tearDown(): void {
    Afform::revert(FALSE)->addWhere('name', '=', $this->formName)->execute();
    parent::tearDown();
  }

  private function createContactTag(string $prefix): int {
    return Tag::create(FALSE)
      ->addValue('name', uniqid($prefix))
      ->addValue('used_for', 'civicrm_contact')
      ->execute()->single()['id'];
  }

  private function submitTags(string $firstName, $tagIdValue): int {
    Afform::submit()
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [
          [
            'fields' => ['first_name' => $firstName, 'last_name' => 'Tagged'],
            'joins' => ['EntityTag' => [['tag_id' => $tagIdValue]]],
          ],
        ],
      ])
      ->execute();
    return Contact::get(FALSE)
      ->addWhere('first_name', '=', $firstName)
      ->execute()->single()['id'];
  }

  private function savedTagIds(int $contactId): array {
    $ids = (array) EntityTag::get(FALSE)
      ->addSelect('tag_id')
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->addWhere('entity_id', '=', $contactId)
      ->execute()->column('tag_id');
    sort($ids);
    return $ids;
  }

  /**
   * A multi-select (array of ids in one join record) attaches every value.
   */
  public function testMultiSelectJoinFieldSavesEveryValue(): void {
    $tagA = $this->createContactTag('tagA_');
    $tagB = $this->createContactTag('tagB_');
    $expected = [$tagA, $tagB];
    sort($expected);

    $cid = $this->submitTags(uniqid(__FUNCTION__), [$tagA, $tagB]);

    $this->assertEquals($expected, $this->savedTagIds($cid));
  }

  /**
   * A single scalar value still attaches exactly that tag (no regression).
   */
  public function testScalarJoinFieldStillSaves(): void {
    $tag = $this->createContactTag('tagScalar_');

    $cid = $this->submitTags(uniqid(__FUNCTION__), $tag);

    $this->assertEquals([$tag], $this->savedTagIds($cid));
  }

}
