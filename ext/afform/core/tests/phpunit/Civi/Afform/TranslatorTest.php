<?php

namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Test\HeadlessInterface;
use Civi\Test\LocaleTestTrait;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class TranslatorTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  use LocaleTestTrait;

  const EXAMPLE_FORM = 'afformTranslatorTest';

  const EXAMPLE_LOCALE = 'fr_FR';

  protected static $cleanL10n = NULL;

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  public function setUp(): void {
    parent::setUp();

    // (1) Translations are only activated on multilingual. We might want to relax that (and also
    //     make this test faster). But for now, we'll go with it...
    // (2) We only want to initialize multilingual once, but useMultilingual() is non-static... so force it...
    static::$cleanL10n ??= $this->useMultilingual(['en_US' => static::EXAMPLE_LOCALE]);

    \CRM_Core_DAO::executeQuery('TRUNCATE TABLE civicrm_translation');
    \CRM_Core_DAO::executeQuery('TRUNCATE TABLE civicrm_translation_source');
  }

  public static function tearDownAfterClass(): void {
    // After the last test, we can undo our special multilingual setup.
    static::$cleanL10n = NULL;
    Afform::revert(FALSE)->addWhere('name', '=', self::EXAMPLE_FORM)->execute();
    parent::tearDownAfterClass();
  }

  public function getExamples() {
    $exs = [];
    $exs['empty-div'] = [
      '<div></div>',
      [],
      [],
      '<div></div>',
    ];
    $exs['static-text'] = [
      '<div><span>Static Text</span></div>',
      [],
      ['Static Text' => 'This should not be replaced because it was just static text.'],
      '<div><span>Static Text</span></div>',
    ];
    $exs['original-text-1'] = [
      // Here, we have localizable text... but there is no replacement string... so we just keep the original.
      "<af-form ctrl=\"afform\"><p class=\"af-text\">Localizable Text</p></af-form>",
      ['Localizable Text'],
      [],
      "<af-form ctrl=\"afform\" ng-form=\"afformTranslatorTest\"><p class=\"af-text\">Localizable Text</p></af-form>",
    ];
    $exs['original-text-3'] = [
      // Here, we have 3 localizable texts... but there are no replacement strings... so we just keep the originals.
      "<af-form ctrl=\"afform\"><p class=\"af-text\">Localizable<br> Text</p>\n<div class=\"af-markup\">Boo<br>Boo</div>\n<button>Go time!</button></af-form>",
      ['Localizable<br/> Text', 'Boo<br/>Boo', 'Go time!'],
      [],
      "<af-form ctrl=\"afform\" ng-form=\"afformTranslatorTest\"><p class=\"af-text\">Localizable<br> Text</p>\n<div class=\"af-markup\">Boo<br>Boo</div>\n<button>Go time!</button></af-form>",
    ];
    $exs['original-rich-text'] = [
      // Here, we have localizable text... but there is no replacement string... so we just keep the original.
      "<af-form ctrl=\"afform\"><p class=\"af-text\"><em>Localizable</em> Text</p></af-form>",
      ['<em>Localizable</em> Text'],
      [],
      "<af-form ctrl=\"afform\" ng-form=\"afformTranslatorTest\"><p class=\"af-text\"><em>Localizable</em> Text</p></af-form>",
    ];
    $exs['basic-translation'] = [
      // Here, we have localizable text... and we do translate it...
      "<af-form ctrl=\"afform\"><p class=\"af-text\"><em>Localizable</em> Text</p></af-form>",
      ['<em>Localizable</em> Text'],
      ['<em>Localizable</em> Text' => 'Texte <em>localisable</em>'],
      "<af-form ctrl=\"afform\" ng-form=\"afformTranslatorTest\"><p class=\"af-text\">Texte <em>localisable</em></p></af-form>",
    ];
    $exs['mixed-static-translated'] = [
      // Here, we have a mix of static text and localizable text...
      "<af-form ctrl=\"afform\"><span>Static Start</span>\n<p class=\"af-text\">Localizable Text</p>\n<span>Static End</span></af-form>",
      ['Localizable Text'],
      ['Localizable Text' => 'Texte localisable', 'Static Start' => 'Fake news!', 'Static End' => 'Fake news!'],
      "<af-form ctrl=\"afform\" ng-form=\"afformTranslatorTest\"><span>Static Start</span>\n<p class=\"af-text\">Texte localisable</p>\n<span>Static End</span></af-form>",
    ];

    return $exs;
    // return [$exs['static-text']];
    // return [$exs['original-rich-text']];
    // return [$exs['basic-translation']];
    // return [$exs['mixed-static-translated']];
  }

  /**
   * @param string $inputHtml
   *   Original Afform HTML
   *   Ex: '<p class="af-text">Hello world</a>'
   * @param array $expectStrings
   *   List of strings that should be requested
   *   Ex: 'Hello world'
   * @param array $applyTranslations
   *   List of translations to define
   * @param string $expectHtml
   *   Final Afform HTML
   *   Ex: '<p class="af-text">Bon jour, tout le monde</a>'
   * @return void
   * @dataProvider getExamples
   */
  public function testTranslate(string $inputHtml, array $expectStrings, array $applyTranslations, string $expectHtml): void {
    // First, save the HTML and make sure it saved properly.
    Afform::save(FALSE)
      ->setLayoutFormat('html')
      ->addRecord([
        'name' => static::EXAMPLE_FORM,
        'permission' => 'access CiviCRM',
        'title' => 'Translator Test',
        'layout' => $inputHtml,
      ])
      ->execute();

    $savedHtml = Afform::get(FALSE)
      ->addWhere('name', '=', static::EXAMPLE_FORM)
      ->addSelect('layout')
      ->setLayoutFormat('html')
      ->execute()
      ->single()['layout'];
    $this->assertEquals($inputHtml, $savedHtml, 'Saved HTML should be an exact match to the input');

    // Does the string-scanner find our localizable strings?
    $actualStrings = (new StringScanner())->scan([], $inputHtml)->getStrings();
    $this->assertEquals($expectStrings, $actualStrings, 'Scanner should find expected strings');

    // Let's apply some translations and see if they work
    $this->setTranslations(static::EXAMPLE_LOCALE, $applyTranslations);
    try {
      \CRM_Core_I18n::singleton()->setLocale(static::EXAMPLE_LOCALE);
      $rendered = \CRM_Utils_Array::single(
        \Civi::service('angular')->getPartials(static::EXAMPLE_FORM)
      );
    }
    finally {
      \CRM_Core_I18n::singleton()->setLocale('en_US');
    }
    $this->assertEquals($expectHtml, $rendered, 'Rendered HTML should have translations applied');
  }

  /**
   * @param string $language
   *   Ex: 'fr_FR'
   * @param array $applyTranslations
   *   Ex: ['Hello' => 'Bonjour', 'Later dawg' => 'Plus tard mon gars']
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function setTranslations(string $language, array $applyTranslations): void {
    foreach ($applyTranslations as $original => $translation) {
      $this->setTranslation($original, $language, $translation);
    }
  }

  /**
   * Store a translation
   *
   * TODO: This helper feels like it would be useful elsewhere...
   *
   * @param string $original
   *   Ex: 'Hello world'
   * @param string $language
   *   Ex: 'fr_FR'
   * @param string $translated
   *   Ex: 'Bonjour, tout le monde'
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function setTranslation(string $original, string $language, string $translated): void {
    $getSource = \Civi\Api4\TranslationSource::get(FALSE)
      ->addWhere('source', '=', $original)
      ->execute();
    if ($getSource->count()) {
      $sourceId = $getSource->single()['id'];
    }
    else {
      $createSource = \Civi\Api4\TranslationSource::create(FALSE)
        ->setValues(['source' => $original])
        ->execute();
      $sourceId = $createSource->single()['id'];
    }

    $record = [
      'status_id' => 1,
      'language' => $language,
      'entity_table' => 'civicrm_translation_source',
      'entity_field' => 'source',
      'entity_id' => $sourceId,
      'string' => $translated,
    ];
    \Civi\Api4\Translation::save(FALSE)
      ->addRecord($record)
      ->setMatch(['entity_table', 'entity_field', 'entity_id', 'status_id', 'language'])
      ->execute();
  }

}
