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
      case 'Backdrop':
        // FIXME: In buildkit.git:app/config/backdrop-*, it downloads the *.po files, but it lacks drush support for activating them.
        $results[] = ['*SKIP*', NULL, NULL, NULL, NULL];
        break;

      case 'Drupal':
        $results[] = ['de_DE', 'de_CH', 't', 'Yes', 'Ja'];
        // That's weird... If you install Drupal's "de", and if you do `setUFLocale('de_DE')`, if
        // you lookup `getUFLocale()`... then it reports back as 'de_CH'. Feels arbitrary.
        // OTOH, D7 doesn't appear to distinguish national dialects, so `de_DE` and `de_CH` are the same thing...
        break;

      case 'Drupal8':
        // FIXME: In buildkit.git:app/config/drupal8-*, it downloads the *.po files, but it lacks drush support for activating them.
        $results[] = ['*SKIP*', NULL, NULL, NULL, NULL];
        break;

      case 'Joomla':
        // FIXME: In CRM_Utils_System_Joomla, the setUFLocale and getUFLocale are not fully implemented.
        // FIXME: In buildkit.git:app/config/joomla-*, it does not enable any languages.
        $results[] = ['*SKIP*', NULL, NULL, NULL, NULL];
        break;

      case 'WordPress':
        // FIXME: In CRM_Utils_System_WordPress, the setUFLocale and getUFLocale are not fully implemented.
        // FIXME: In buildkit.git:app/config/wp-*, it does not enable any languages.
        $results[] = ['*SKIP*', NULL, NULL, NULL, NULL];
        // $results[] = ['de_DE', 'de_DE', '__', 'Yes', 'Ja'];
        break;

      default:
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
    if ($civiLocale === '*SKIP*') {
      $this->markTestIncomplete('Current environment does not support testing of UF locale.');
    }

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
