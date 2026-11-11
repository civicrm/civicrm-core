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

  /**
   * User-facing texts of a display: TRUE marks a text, an array a nested setting, [0 => spec] a list of them.
   */
  private const TEXTS = [
    'label' => TRUE,
    'settings' => [
      'description' => TRUE,
      'noResultsText' => TRUE,
      'button' => TRUE,
      'tally' => ['label' => TRUE],
      'editableRow' => ['createLabel' => TRUE],
      'addButton' => ['text' => TRUE],
      'toolbar' => [['text' => TRUE, 'title' => TRUE]],
      'columns' => [
        [
          'label' => TRUE,
          'title' => TRUE,
          'text' => TRUE,
          'rewrite' => TRUE,
          'empty_value' => TRUE,
          'image' => ['alt' => TRUE],
          'tally' => ['label' => TRUE],
          'links' => [['text' => TRUE, 'title' => TRUE]],
        ],
      ],
    ],
  ];

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

      self::translateTexts($sd, self::TEXTS, $domain);
    }
    if (isset($localeToRestore)) {
      \CRM_Core_I18n::singleton()->setLocale($localeToRestore);
    }
  }

  /**
   * Translates the given keys of an array; a nested spec descends into a key, a list spec into each item.
   */
  private static function translateTexts(array &$item, array $texts, ?string $domain): void {
    foreach ($texts as $key => $spec) {
      // The rewrite of an html column is markup, not a text
      if ($key === 'rewrite' && ($item['type'] ?? NULL) === 'html') {
        continue;
      }
      if ($spec === TRUE) {
        self::ts($item, $key, $domain);
      }
      elseif (isset($item[$key]) && is_array($item[$key])) {
        if (isset($spec[0])) {
          foreach ($item[$key] as &$child) {
            if (is_array($child)) {
              self::translateTexts($child, $spec[0], $domain);
            }
          }
        }
        else {
          self::translateTexts($item[$key], $spec, $domain);
        }
      }
    }
  }

  private static function ts(&$item, $keyName, $domain = NULL) {
    if (!empty($item[$keyName]) && is_string($item[$keyName]) && !self::isTemplate($item[$keyName])) {
      $origValue = $item[$keyName];

      // first try default core translation (mo file or custom translation)
      $item[$keyName] = _ts(trim($item[$keyName]));

      // no translation and a domain, try the domain mo
      if ($item[$keyName] == $origValue && !empty($domain)) {
        $item[$keyName] = _ts(trim($item[$keyName]), ['domain' => $domain]);
      }
    }
  }

  /**
   * Markup or Smarty in a text marks it as a template, which a translation could break.
   */
  private static function isTemplate(string $text): bool {
    return (str_contains($text, '<') && str_contains($text, '>'))
      || (str_contains($text, '{') && str_contains($text, '}'));
  }

}
