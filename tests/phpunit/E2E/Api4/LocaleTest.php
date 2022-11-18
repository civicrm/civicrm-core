<?php

namespace E2E\Api4;

use Civi\API\Event\RespondEvent;

/**
 * Class LocaleTest
 *
 * @package E2E\Api4
 * @group e2e
 */
class LocaleTest extends \CiviEndToEndTestCase {

  public function getLanguageExamples(): array {
    $results = [];
    switch (CIVICRM_UF) {
      case 'Drupal':
      case 'Drupal8':
      case 'Backdrop':
        $results[] = ['de_DE', 'de_CH', 't', 'Yes', 'Ja'];
        // That's weird -- you install Drupal's "de", and you do `setUFLocale('de_DE')`, and
        // the result is... to report back as 'de_DE'. Weird. But the actual string is OK...
        break;

      case 'WordPress':
        $results[] = ['de_DE', 'de_DE', '__', 'Yes', 'Ja'];
        break;

      case 'Joomla':
      default:
        $this->fail('Test not implemented for ' . CIVICRM_UF);
    }
    return $results;
  }

  /**
   * APIv4 allows you to request that operations be processed in a specific language.
   *
   * @param string $civiLocale
   *   The locale to specify in Civi. (eg Civi\Api4\Foo::bar()->setLanguage($civiLocale))
   *   Ex: 'de_DE'
   * @param string $expectUfLocale
   *   The corresponding locale in the UF.
   *   Ex: 'de'
   * @param string|array $translator
   *   The user-framework's translation method.
   *   Ex: 't' (Drupal) or '__' (WordPress)
   * @param string $inputString
   *   The string to translate.
   *   Ex: 'Yes', 'Login', 'Continue'
   * @param string $expectString
   *   The string that should be returned.
   *   Ex: 'Ja', 'Anmeldung', 'Fortsetzen'
   *
   * @dataProvider getLanguageExamples
   */
  public function testSetLanguage($civiLocale, $expectUfLocale, $translator, $inputString, $expectString) {
    $actualStrings = [];
    $actualLocales = [];
    \Civi::dispatcher()->addListener('civi.api.respond', function (RespondEvent $e) use (&$actualStrings, &$actualLocales, $translator, $inputString, $civiLocale) {
      $isTranslatedRequest = ($e->getApiRequest()->getLanguage() === $civiLocale);
      if ($isTranslatedRequest) {
        $actualLocales[] = \CRM_Utils_System::getUFLocale();
        $actualStrings[] = call_user_func($translator, $inputString);
      }
    }, -100);
    $contacts = civicrm_api4('Contact', 'get', [
      'limit' => 25,
      'language' => $civiLocale,
    ]);
    $this->assertTrue(count($contacts) > 0, 'The API call should return successfully.');
    $this->assertEquals(1, count($actualLocales), 'We should observed one UF locale.');
    $this->assertEquals(1, count($actualStrings), 'We should observed one UF translation.');
    $this->assertEquals($expectUfLocale . ':' . $expectString, $actualLocales[0] . ':' . $actualStrings[0],
      "Expected UF to report locale as '$expectUfLocale:$expectString'.");
  }

}
