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

  /**
   * Names of every form created during a test, reverted on tearDown.
   *
   * @var string[]
   */
  private $forms = [];

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform'])
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();
    \CRM_Core_Config::singleton()->userPermissionTemp->grant('administer CiviCRM');
    // Default form: the EntityTag join has no explicit actions, so both update
    // and delete default to TRUE and saveJoins() takes the REPLACE path.
    $this->formName = $this->createTagForm('');
  }

  public function tearDown(): void {
    foreach ($this->forms as $name) {
      Afform::revert(FALSE)->addWhere('name', '=', $name)->execute();
    }
    parent::tearDown();
  }

  /**
   * Create a Contact-tags form and return its name. $joinActions sets the
   * EntityTag join container's `actions` attribute (e.g.
   * '{update: true, delete: false}'); an empty string omits it, leaving the
   * default of update+delete both allowed.
   */
  private function createTagForm(string $joinActions): string {
    $name = 'mockTagForm' . rand(0, 1000000);
    $joinAttr = $joinActions === '' ? '' : " actions=\"$joinActions\"";
    Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues([
        'name' => $name,
        'title' => 'Contact tags test',
        'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
        'layout' => <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" url-autofill="1" security="RBAC" />
  <fieldset af-fieldset="Individual1">
    <af-field name="first_name" />
    <div af-join="EntityTag"$joinAttr>
      <af-field name="tag_id" defn="{input_attrs: {multiple: true}, label: 'Tags'}" />
    </div>
  </fieldset>
</af-form>
EOHTML,
      ])
      ->execute();
    $this->forms[] = $name;
    return $name;
  }

  private function createContactTag(string $prefix): int {
    return Tag::create(FALSE)
      ->addValue('name', uniqid($prefix))
      ->addValue('used_for', 'civicrm_contact')
      ->execute()->single()['id'];
  }

  private function submitTags(string $firstName, $tagIdValue): int {
    return $this->submitJoinRecords($firstName, [['tag_id' => $tagIdValue]]);
  }

  /**
   * Submit the form (create mode) with arbitrary EntityTag join records and
   * return the created contact's id. saveJoins() processes the whole
   * join-records array, so passing several records exercises the multi-record
   * write without needing an af-repeat layout.
   */
  private function submitJoinRecords(string $firstName, array $joinRecords, ?string $formName = NULL): int {
    $result = Afform::submit()
      ->setName($formName ?? $this->formName)
      ->setValues([
        'Individual1' => [
          [
            'fields' => ['first_name' => $firstName, 'last_name' => 'Tagged'],
            'joins' => ['EntityTag' => $joinRecords],
          ],
        ],
      ])
      ->execute();
    return $result[0]['Individual1'][0]['id'];
  }

  /**
   * Re-submit the form in update mode for an existing contact. The contact id
   * comes from the server-trusted arg (url-autofill on the af-entity), not from
   * a client-submitted field, so the submit must edit that contact rather than
   * create a new one; this asserts no new Individual is created. As core's own
   * update-mode submit tests do, the saved tags are read back from the database
   * (savedTagIds) rather than from the submit result, which does not echo the
   * arg-supplied id back in update mode.
   */
  private function updateTags(int $contactId, array $joinRecords, ?string $formName = NULL): void {
    $countBefore = $this->individualCount();
    Afform::submit()
      ->setName($formName ?? $this->formName)
      ->setArgs(['Individual1' => $contactId])
      ->setValues([
        'Individual1' => [
          [
            'fields' => ['last_name' => 'Tagged'],
            'joins' => ['EntityTag' => $joinRecords],
          ],
        ],
      ])
      ->execute();
    $this->assertSame($countBefore, $this->individualCount());
  }

  /**
   * The number of Individual contacts, used to prove an update-mode submit
   * edits the contact named by the arg instead of creating a new one.
   */
  private function individualCount(): int {
    return Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('contact_type', '=', 'Individual')
      ->execute()
      ->count();
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

  /**
   * Duplicate ids in the submitted array collapse to one row per tag.
   */
  public function testMultiSelectJoinFieldDeduplicates(): void {
    $tagA = $this->createContactTag('tagDupA_');
    $tagB = $this->createContactTag('tagDupB_');
    $expected = [$tagA, $tagB];
    sort($expected);

    $cid = $this->submitTags(uniqid(__FUNCTION__), [$tagA, $tagA, $tagB]);

    $this->assertEquals($expected, $this->savedTagIds($cid));
  }

  /**
   * Several join records in one submit are expanded and written as a set.
   * saveJoins() reads the whole join-records array, so this exercises the
   * multi-record write without an af-repeat layout. Two records that overlap
   * on a tag must each be saved once; pre-fix the duplicate issued two inserts
   * of the same (entity_id, entity_table, tag_id) and hit the unique index.
   */
  public function testOverlappingMultiValueJoinRecordsSaveEachTagOnce(): void {
    $tagA = $this->createContactTag('tagOvlA_');
    $tagB = $this->createContactTag('tagOvlB_');
    $tagC = $this->createContactTag('tagOvlC_');
    $expected = [$tagA, $tagB, $tagC];
    sort($expected);

    $cid = $this->submitJoinRecords(uniqid('ovl_'), [
      ['tag_id' => [$tagA, $tagB]],
      ['tag_id' => [$tagB, $tagC]],
    ]);

    $this->assertEquals($expected, $this->savedTagIds($cid));
  }

  /**
   * An expanded multi-value join record followed by a scalar one (the shape a
   * prefilled row produces) must keep every value: pre-fix the scalar record
   * overwrote a slot the expanded record had already taken and its tag was
   * never saved.
   */
  public function testExpandedThenScalarJoinRecordsKeepEveryTag(): void {
    $tagA = $this->createContactTag('tagMixA_');
    $tagB = $this->createContactTag('tagMixB_');
    $tagC = $this->createContactTag('tagMixC_');
    $expected = [$tagA, $tagB, $tagC];
    sort($expected);

    $cid = $this->submitJoinRecords(uniqid('mix_'), [
      ['tag_id' => [$tagA, $tagB]],
      ['tag_id' => $tagC],
    ]);

    $this->assertEquals($expected, $this->savedTagIds($cid));
  }

  /**
   * Re-saving an existing contact in update mode must not hit the entity_tag
   * unique key, and the submitted selection drives the final set (re-save
   * keeps, shrink removes, grow adds).
   *
   * This is the case the create tests cannot reach: they each create a fresh
   * contact, so no row exists and `match` never fires. Here the contact already
   * has rows, so the expanded records (which carry no id) would collide with
   * the unique (entity_id, entity_table, tag_id) index unless `match`
   * id-matches the existing rows first. Each updateTags() step also asserts no
   * new contact is created, i.e. the submit edited the existing contact rather
   * than creating a new one.
   */
  public function testUpdatingTagsReSavesWithoutDuplicatingAndFollowsSelection(): void {
    $tagA = $this->createContactTag('tagUpdA_');
    $tagB = $this->createContactTag('tagUpdB_');
    $tagC = $this->createContactTag('tagUpdC_');

    $cid = $this->submitTags(uniqid('tagUpd_'), [$tagA, $tagB]);
    $ab = [$tagA, $tagB];
    sort($ab);
    $this->assertEquals($ab, $this->savedTagIds($cid));

    // Re-save the same two tags: must update the same contact, not duplicate.
    $this->updateTags($cid, [['tag_id' => [$tagA, $tagB]]]);
    $this->assertEquals($ab, $this->savedTagIds($cid));

    // Shrink: a de-selected tag is removed.
    $this->updateTags($cid, [['tag_id' => [$tagA]]]);
    $this->assertEquals([$tagA], $this->savedTagIds($cid));

    // Grow: newly selected tags are added.
    $abc = [$tagA, $tagB, $tagC];
    sort($abc);
    $this->updateTags($cid, [['tag_id' => [$tagA, $tagB, $tagC]]]);
    $this->assertEquals($abc, $this->savedTagIds($cid));
  }

  /**
   * With Delete unchecked on the join (update allowed, delete not), saveJoins
   * takes the `save` branch rather than `replace`. A re-submitted tag the
   * contact already has must id-match the existing row via `match` instead of
   * inserting a duplicate and hitting the unique index.
   */
  public function testReSaveOnDeleteDisabledJoinDoesNotDuplicate(): void {
    $form = $this->createTagForm('{update: true, delete: false}');
    $tagA = $this->createContactTag('tagSaveA_');
    $tagB = $this->createContactTag('tagSaveB_');
    $ab = [$tagA, $tagB];
    sort($ab);

    $cid = $this->submitJoinRecords(uniqid('sv_'), [['tag_id' => [$tagA, $tagB]]], $form);
    $this->assertEquals($ab, $this->savedTagIds($cid));

    // Re-submit the same tags in update mode: must not raise a duplicate-key error.
    $this->updateTags($cid, [['tag_id' => [$tagA, $tagB]]], $form);
    $this->assertEquals($ab, $this->savedTagIds($cid));
  }

  /**
   * With both Update and Delete unchecked the join is create-only; saveJoins
   * takes the `save` branch and strips any client-submitted id. A fresh contact
   * still attaches every selected tag.
   */
  public function testCreateOnlyJoinSavesEveryTag(): void {
    $form = $this->createTagForm('{update: false, delete: false}');
    $tagA = $this->createContactTag('tagCoA_');
    $tagB = $this->createContactTag('tagCoB_');
    $expected = [$tagA, $tagB];
    sort($expected);

    $cid = $this->submitJoinRecords(uniqid('co_'), [['tag_id' => [$tagA, $tagB]]], $form);

    $this->assertEquals($expected, $this->savedTagIds($cid));
  }

}
