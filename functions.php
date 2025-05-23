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

/**
 * @file
 *
 * CiviCRM is generally organized around classes, but there are a handful of global functions.
 * Declare them here.
 */

/**
 * Short-named function for string translation, defined in global scope so it's available everywhere.
 *
 * @param string $text
 *   String for translating.
 *   Ex: 'Hello, %1!'
 * @param array $params
 *   An array of additional parameters, as per `crm_translate()`.
 *   Ex: [1 => 'Dave']
 * @return string
 *   The translated string
 *   Ex: '¡Buenos días Dave!`
 * @see \CRM_Core_I18n::crm_translate()
 */
function ts($text, $params = []) {
  static $bootstrapReady = FALSE;
  static $lastLocale = NULL;
  static $i18n = NULL;
  static $function = NULL;

  if ($text == '') {
    return '';
  }

  // When the settings become available, lookup customTranslateFunction.
  if (!$bootstrapReady) {
    $bootstrapReady = (bool) \Civi\Core\Container::isContainerBooted();
    if ($bootstrapReady) {
      // just got ready: determine whether there is a working custom translation function
      $config = CRM_Core_Config::singleton();
      if (!empty($config->customTranslateFunction) && function_exists($config->customTranslateFunction)) {
        $function = $config->customTranslateFunction;
      }
    }
  }

  $civicrmLocale = CRM_Core_I18n::getLocale();
  if (!$i18n or $lastLocale != $civicrmLocale) {
    $i18n = CRM_Core_I18n::singleton();
    $lastLocale = $civicrmLocale;
  }

  if ($function) {
    return $function($text, $params);
  }
  else {
    return $i18n->crm_translate($text, $params);
  }
}

/**
 * Alternate name for `ts()`
 *
 * This is functionally equivalent to `ts()`. However, regular `ts()` is subject to extra linting
 * rules. Using `_ts()` can bypass the linting rules for the rare cases where you really want
 * special/dynamic values.
 *
 * @param array ...$args
 * @return string
 * @see ts()
 * @see \CRM_Core_I18n::crm_translate()
 * @internal
 */
function _ts(...$args) {
  $f = 'ts';
  return $f(...$args);
}
