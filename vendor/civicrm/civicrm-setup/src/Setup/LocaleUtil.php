<?php
namespace Civi\Setup;

class LocaleUtil {

  /**
   * Figure out which of $langs is the closest to $lang.
   *
   * @param string $preferredLang
   *   The user's preferred language.
   *   Ex: `en`, `fr`, or `fr_CA`.
   * @param array $availLangs
   *   List of available languages.
   *   Ex: ['en_US' => 'English (US)', 'fr_CA' => 'French (Canadian)'].
   * @return string
   *   Ex: 'en_US'.
   */
  public static function pickClosest($preferredLang, $availLangs, $default = 'en_US') {
    // Perhaps we have this exact language?
    if (isset($availLangs[$preferredLang])) {
      return $preferredLang;
    }

    list ($first) = explode('_', $preferredLang);

    // Do we have a hard-coded preference? Use this for real oddballs.
    $overrides = array(
      'en' => 'en_US',
    );
    if (isset($overrides[$preferredLang]) && isset($availLangs[$overrides[$preferredLang]])) {
      return $overrides[$preferredLang];
    }

    // Perhaps we have the canonical variant (e.g. `fr` => `fr_FR`)?
    $canon = $first . '_' . strtoupper($first);
    if (isset($availLangs[$canon])) {
      return $canon;
    }

    // Is there anything else that looks remotely close? (e.g. `cy` => `cy_GB`)
    ksort($availLangs);
    foreach ($availLangs as $availLang => $availLabel) {
      if (strpos($availLang, $first) === 0) {
        return $availLang;
      }
    }

    // Nothing worked.
    return $default;
  }

}
