<?php

namespace Civi\Contribute;

use Civi\Angular\ChangeSet;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;
use Civi\Contribute\Utils\PriceFieldUtils;
use Civi\Core\Event\GenericHookEvent;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test the price field specs and the Afform price option defn injector.
 *
 * @group headless
 */
class PriceFieldUtilsTest extends TestCase implements HeadlessInterface {

  private const SET_NAME = 'pfu_set';

  private const PARTIAL_PATH = '~/afform/pfuTestForm.aff.html';

  /**
   * @var int[]
   *   Price field ids, keyed by price field name.
   */
  private array $fieldIds = [];

  /**
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    PriceSet::create(FALSE)
      ->addValue('name', self::SET_NAME)
      ->addValue('title', 'Price Field Utils Set')
      ->addValue('extends:name', ['CiviContribute'])
      ->execute();
    $this->resetStaticCaches();
  }

  public function tearDown(): void {
    PriceFieldValue::delete(FALSE)
      ->addWhere('price_field_id.price_set_id.name', '=', self::SET_NAME)
      ->execute();
    PriceField::delete(FALSE)
      ->addWhere('price_set_id.name', '=', self::SET_NAME)
      ->execute();
    PriceSet::delete(FALSE)
      ->addWhere('name', '=', self::SET_NAME)
      ->execute();
    $this->resetStaticCaches();
  }

  /**
   * Each field gets its own options, even when the values were created
   * interleaved across fields.
   */
  public function testOptionsAreGroupedByField(): void {
    $this->addField('size', 'Size', 'Radio');
    $this->addField('colour', 'Colour', 'Select');
    $this->addField('extra', 'Extra', 'CheckBox');

    $small = $this->addValue('size', 'small', 'Small', 5);
    $red = $this->addValue('colour', 'red', 'Red', 5);
    $extraOne = $this->addValue('extra', 'extra_one', 'Extra one', 7);
    $large = $this->addValue('size', 'large', 'Large', 10);
    $blue = $this->addValue('colour', 'blue', 'Blue', 5);
    $medium = $this->addValue('size', 'medium', 'Medium', 7);
    $green = $this->addValue('colour', 'green', 'Green', 5);

    $specs = PriceFieldUtils::getPriceFieldSpecs()['Contribution'];

    $this->assertEqualsCanonicalizing(
      [$small, $large, $medium],
      array_keys($specs[self::SET_NAME . '.size']['options'])
    );
    $this->assertEqualsCanonicalizing(
      [$red, $blue, $green],
      array_keys($specs[self::SET_NAME . '.colour']['options'])
    );
    $this->assertSame([$extraOne], array_keys($specs[self::SET_NAME . '.extra']['options']));
    $this->assertSame('Large - ' . \Civi::format()->money(10), $specs[self::SET_NAME . '.size']['options'][$large]);
    $this->assertSame('Green - ' . \Civi::format()->money(5), $specs[self::SET_NAME . '.colour']['options'][$green]);
  }

  /**
   * Inactive values are left out of the options, and a field with no active
   * value has no options rather than inheriting another field's.
   */
  public function testInactiveValuesAreNotOptions(): void {
    $this->addField('size', 'Size', 'Radio');
    $this->addField('colour', 'Colour', 'Select');
    $small = $this->addValue('size', 'small', 'Small', 5);
    $this->addValue('size', 'retired', 'Retired', 5, ['is_active' => FALSE]);
    $this->addValue('colour', 'red', 'Red', 5, ['is_active' => FALSE]);

    $specs = PriceFieldUtils::getPriceFieldSpecs()['Contribution'];

    $this->assertSame([$small], array_keys($specs[self::SET_NAME . '.size']['options']));
    $this->assertArrayNotHasKey('options', $specs[self::SET_NAME . '.colour']);
  }

  /**
   * An is_enter_qty field takes its unit amount from its own value, and
   * several such fields in one set do not mix.
   */
  public function testEnterQtyFieldsTakeTheirOwnAmount(): void {
    $this->addField('tickets', 'Tickets', 'Text', ['is_enter_qty' => TRUE]);
    $this->addField('meals', 'Meals', 'Text', ['is_enter_qty' => TRUE]);
    $this->addValue('tickets', 'ticket', 'Ticket', 25);
    $this->addValue('meals', 'meal', 'Meal', 12.5);

    $specs = PriceFieldUtils::getPriceFieldSpecs()['Contribution'];

    $this->assertEquals(25, $specs[self::SET_NAME . '.tickets']['amount']);
    $this->assertEquals(12.5, $specs[self::SET_NAME . '.meals']['amount']);
    $this->assertArrayNotHasKey('options', $specs[self::SET_NAME . '.tickets']);
  }

  /**
   * Amounts are appended to labels only where the field displays them, and
   * each distinct amount gets its own formatted text.
   */
  public function testAmountsAreAddedToLabelsWhereDisplayed(): void {
    $this->addField('shown', 'Shown', 'Radio');
    $this->addField('also_shown', 'Also shown', 'Radio');
    $this->addField('hidden', 'Hidden', 'Radio', ['is_display_amounts' => FALSE]);

    $ten = $this->addValue('shown', 'ten', 'Ten', 10);
    $tenAndAHalf = $this->addValue('shown', 'ten_half', 'Ten and a half', 10.5);
    $thousand = $this->addValue('also_shown', 'thousand', 'Thousand', 1000);
    $repeatedTen = $this->addValue('also_shown', 'ten_again', 'Ten again', 10);
    $hidden = $this->addValue('hidden', 'hidden_ten', 'Hidden ten', 10);

    $specs = PriceFieldUtils::getPriceFieldSpecs()['Contribution'];

    $shown = $specs[self::SET_NAME . '.shown']['options'];
    $alsoShown = $specs[self::SET_NAME . '.also_shown']['options'];
    $money = fn($amount) => \Civi::format()->money($amount);

    $this->assertSame('Ten - ' . $money(10), $shown[$ten]);
    $this->assertSame('Ten and a half - ' . $money(10.5), $shown[$tenAndAHalf]);
    $this->assertSame('Thousand - ' . $money(1000), $alsoShown[$thousand]);
    $this->assertSame('Ten again - ' . $money(10), $alsoShown[$repeatedTen]);
    $this->assertNotSame($money(10), $money(10.5));
    $this->assertSame('Hidden ten', $specs[self::SET_NAME . '.hidden']['options'][$hidden]);
  }

  /**
   * The injector must not look up the price field specs just because Angular
   * loaded. It only needs them when it alters an afform's markup.
   */
  public function testInjectorLooksUpSpecsOnlyWhenAlteringAfformMarkup(): void {
    $this->addField('size', 'Size', 'Radio');
    $this->addValue('size', 'small', 'Small', 5);
    $this->addValue('size', 'staff', 'Staff only', 5, ['visibility_id:name' => 'admin']);
    $this->resetStaticCaches();

    $changeSet = $this->registerInjector();
    $this->assertNotNull($changeSet, 'A site with admin price options needs the changeset');
    $this->assertFalse(isset(\Civi::$statics[PriceFieldUtils::class]), 'Specs were built when the hook ran');

    ChangeSet::applyResourceFilters([$changeSet], 'partials', [
      '~/crmApp/someOtherPartial.html' => '<af-field name="' . self::SET_NAME . '.size"></af-field>',
    ]);
    $this->assertFalse(isset(\Civi::$statics[PriceFieldUtils::class]), 'Specs were built for a partial that is not an afform');

    ChangeSet::applyResourceFilters([$changeSet], 'partials', [
      self::PARTIAL_PATH => $this->buildForm('size', []),
    ]);
    $this->assertTrue(isset(\Civi::$statics[PriceFieldUtils::class]), 'Specs were not built for an afform');
  }

  /**
   * Only options that are admin-only get the visibility rule.
   */
  public function testInjectorTagsOnlyAdminOptions(): void {
    $this->addField('size', 'Size', 'Radio');
    $small = $this->addValue('size', 'small', 'Small', 5);
    $staff = $this->addValue('size', 'staff', 'Staff only', 5, ['visibility_id:name' => 'admin']);
    $this->resetStaticCaches();

    $changeSet = $this->registerInjector();
    $result = ChangeSet::applyResourceFilters([$changeSet], 'partials', [
      self::PARTIAL_PATH => $this->buildForm('size', [
        ['id' => $small, 'label' => 'Small'],
        ['id' => $staff, 'label' => 'Staff only'],
      ]),
    ]);

    $options = $this->getOptionsFromMarkup($result[self::PARTIAL_PATH]);
    $this->assertCount(2, $options);
    $this->assertArrayNotHasKey('if', $options[0]);
    $this->assertSame(
      [['Contribution1[0][fields][' . PriceFieldUtils::RESTRICTED_OPTIONS_FLAG . ']', 'IS NOT EMPTY']],
      $options[1]['if']
    );
  }

  /**
   * With no admin options there is nothing to tag, so the injector registers
   * nothing and builds nothing.
   */
  public function testInjectorDoesNothingWithoutAdminOptions(): void {
    $this->addField('size', 'Size', 'Radio');
    $this->addValue('size', 'small', 'Small', 5);
    $this->resetStaticCaches();

    $this->assertNull($this->registerInjector());
    $this->assertFalse(isset(\Civi::$statics[PriceFieldUtils::class]));
  }

  private function resetStaticCaches(): void {
    unset(\Civi::$statics[PriceFieldUtils::class]);
    unset(\Civi::$statics[PriceFieldUtils::class . '::restrictedPriceFieldValueIds']);
  }

  private function addField(string $name, string $label, string $htmlType, array $values = []): void {
    $this->fieldIds[$name] = PriceField::create(FALSE)
      ->setValues($values + [
        'name' => $name,
        'label' => $label,
        'html_type' => $htmlType,
        'price_set_id:name' => self::SET_NAME,
      ])
      ->execute()
      ->single()['id'];
  }

  private function addValue(string $fieldName, string $name, string $label, float $amount, array $values = []): int {
    return (int) PriceFieldValue::create(FALSE)
      ->setValues($values + [
        'name' => $name,
        'label' => $label,
        'amount' => $amount,
        'price_field_id' => $this->fieldIds[$fieldName],
        'financial_type_id:name' => 'Donation',
      ])
      ->execute()
      ->single()['id'];
  }

  /**
   * Run the injector's alterAngular listener and return the changeset it
   * registered, if any.
   */
  private function registerInjector(): ?ChangeSet {
    $angular = new class() {

      public ?ChangeSet $changeSet = NULL;

      public function add(ChangeSet $changeSet): self {
        $this->changeSet = $changeSet;
        return $this;
      }

    };
    \Civi::service('civi.contribute.price_option_defn_injector')
      ->preprocess(GenericHookEvent::create(['angular' => $angular]));
    return $angular->changeSet;
  }

  /**
   * Afform markup with one price field on a Contribution fieldset.
   */
  private function buildForm(string $fieldName, array $options): string {
    $defn = $options ? ' defn="' . htmlspecialchars(\CRM_Utils_JS::encode(['options' => $options]), ENT_COMPAT) . '"' : '';
    return '<div af-fieldset="Contribution1"><af-field name="' . self::SET_NAME . '.' . $fieldName . '"' . $defn . '></af-field></div>';
  }

  private function getOptionsFromMarkup(string $html): array {
    $doc = \phpQuery::newDocument($html, 'text/html');
    $defn = \CRM_Utils_JS::getRawProps(pq('af-field', $doc)->attr('defn'));
    return \CRM_Utils_JS::decode($defn['options']);
  }

}
