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

      self::ts($sd, 'label', $domain);
      foreach ($sd['settings']['columns'] as &$column) {
        self::ts($column, 'label', $domain);
        if ($column['links']) {
          foreach ($column['links'] as $idx => &$link) {
            self::ts($link, 'text', $domain);
          }
        }
      }
      if ($sd['settings']['toolbar']) {
        foreach ($sd['settings']['toolbar'] as $idx => &$link) {
          self::ts($link, 'text', $domain);
        }
      }
    }
    if (isset($localeToRestore)) {
      \CRM_Core_I18n::singleton()->setLocale($localeToRestore);
    }
  }

  private static function ts(&$item, $keyName, $domain = NULL) {
    if (!empty($item[$keyName])) {
      $origValue = $item[$keyName];

      // first try default core translation (mo file or custom translation)
      $item[$keyName] = _ts(trim($item[$keyName]));

      // no translation and a domain, try the domain mo
      if ($item[$keyName] == $origValue && !empty($domain)) {
        $item[$keyName] = _ts(trim($item[$keyName]), ['domain' => $domain]);
      }
    }
  }

}
