<?php

namespace Civi\AfformMock;

use Civi\Test\LocaleTestTrait;
use CRM_AfformMock_ExtensionUtil as E;
use Civi\Afform\StringVisitor;
use Civi\Api4\Afform;

/**
 * @group headless
 * @group ang
 */
class MockTranslateTest extends \PHPUnit\Framework\TestCase implements \Civi\Test\HeadlessInterface {

  const EXAMPLE_LOCALE = 'fr_FR';

  const TARGET_NAME = '*';
  // const TARGET_NAME = 'tTranslatedBr1';

  use LocaleTestTrait;

  /**
   * @var object|null
   *  Autoclean object. When dereferenced, it will cleanup the l10n settings.
   */
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

    \CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 0');
    \CRM_Core_DAO::executeQuery('TRUNCATE TABLE civicrm_translation');
    \CRM_Core_DAO::executeQuery('TRUNCATE TABLE civicrm_translation_source');
    \CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 1');
  }

  public static function tearDownAfterClass(): void {
    // After the last test, we can undo our special multilingual setup.
    static::$cleanL10n = NULL;
    parent::tearDownAfterClass();
  }

  public function getExamples(): array {
    $exs = [];
    // $files = \Civi::resources()->getPath('org.civicrm.afform-mock', 'ang/translate/*.aff.html');
    $pattern = getenv('MOCK_TRANSLATE_PREFIX') ?: static::TARGET_NAME;
    $files = dirname(__DIR__, 4) . '/ang/translate/' . $pattern . '.aff.html';
    foreach (glob($files) as $file) {
      $name = str_replace('.aff.html', '', basename($file));
      $exs[$name] = [$name];
    }
    return $exs;
  }

  /**
   * @param string $name
   *   Name of a sample Afform from `ang/translate/`.
   * @dataProvider getExamples
   */
  public function testTranslate(string $name): void {
    $inputHtml = file_get_contents(E::path('ang/translate/' . $name . '.aff.html'));
    $expectStrings = require E::path('ang/translate/' . $name . '.strings.php');
    $applyTranslations = require E::path('ang/translate/' . $name . '.translate.php');
    $expectHtml = file_get_contents(E::path('ang/translate/' . $name . '.rendered.html'));

    $savedHtml = Afform::get(FALSE)
      ->addWhere('name', '=', $name)
      ->addSelect('layout')
      ->setLayoutFormat('html')
      ->execute()
      ->single()['layout'];
    $this->assertEquals(trim($inputHtml), trim($savedHtml), 'Saved HTML should be an exact match to the input');

    // Does the string-scanner find our localizable strings?
    $actualStrings = StringVisitor::extractStrings([], $inputHtml);
    sort($actualStrings);
    sort($expectStrings);
    $this->assertEquals($expectStrings, $actualStrings, 'Scanner should find expected strings');

    // Let's apply some translations and see if they work
    $this->setTranslations(static::EXAMPLE_LOCALE, $applyTranslations);
    try {
      \CRM_Core_I18n::singleton()->setLocale(static::EXAMPLE_LOCALE);
      $rendered = \CRM_Utils_Array::single(
        \Civi::service('angular')->getPartials($name)
      );
    }
    finally {
      \CRM_Core_I18n::singleton()->setLocale('en_US');
    }
    $this->assertEquals(trim($expectHtml), trim($rendered), 'Rendered HTML should have translations applied');
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
      $sourceKey = \CRM_Core_BAO_TranslationSource::createGuid($original);
      $createSource = \Civi\Api4\TranslationSource::create(FALSE)
        ->setValues([
          'source' => $original,
          'source_key' => $sourceKey,
          'entity' => 'afform',
          'context_key' => \CRM_Core_BAO_TranslationSource::createGuid(':::afform'),
        ])
        ->execute();
      $sourceId = $createSource->single()['id'];
    }

    $record = [
      'status_id' => 1,
      'language' => $language,
      'string' => $translated,
      'source_key' => $sourceKey,
    ];
    \Civi\Api4\Translation::save(FALSE)
      ->addRecord($record)
      ->setMatch(['source_key', 'entity_table', 'entity_field', 'entity_id', 'status_id', 'language'])
      ->execute();
  }

}
