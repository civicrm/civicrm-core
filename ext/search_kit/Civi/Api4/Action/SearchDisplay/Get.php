<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\Generic\Result;
use Civi\Search\Translator;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  protected function getObjects(Result $result) {
    parent::getObjects($result);

    $locale = $this->getLanguage();
    $tsLocale = \CRM_Core_I18n::getLocale();
    $requestedLocale = $locale ?? $tsLocale;

    // we need to translate anything else
    if ($requestedLocale == 'en_US') {
      return;
    }

    // search displays have been asked in a different locale than the current one
    if (!empty($locale) && $locale != $tsLocale) {
      \CRM_Core_I18n::singleton()->setLocale($locale);
      $localeToRestore = $tsLocale;
    }

    // loop on each search display for translation
    foreach ($result as &$sd) {
      $domain = NULL;
      $managed = \Civi\Api4\Managed::get(FALSE)
        ->addSelect('module:name')
        ->addWhere('entity_type', '=', 'SearchDisplay')
        ->addWhere('entity_id', '=', $sd['id'])
        ->execute()
        ->first();
      if ($managed) {
        $domain = $managed['module:name'];
      }

      Translator::walkTexts($sd, fn(string $text) => self::translate($text, $domain));
    }
    if (isset($localeToRestore)) {
      \CRM_Core_I18n::singleton()->setLocale($localeToRestore);
    }
  }

  /**
   * Translate with core's strings first, then those of the extension that manages the display.
   */
  private static function translate(string $text, ?string $domain): string {
    $translated = _ts(trim($text));
    if ($translated == $text && !empty($domain)) {
      $translated = _ts(trim($text), ['domain' => $domain]);
    }
    return $translated;
  }

}
