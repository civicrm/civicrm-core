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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_HTMLTidy {

  /**
   * Validate that a document is well-formed.
   *
   * @param string $html
   *   HTML document
   * @return string[]
   *   List of validation messages.
   */
  public static function validate(string $html): array {
    $tidy = new \tidy();
    $tidy->parseString($html, [
      'drop-empty-elements' => FALSE,
      'markup' => FALSE,
      'new-blocklevel-tags' => 'crm-angular-js',
      'new-empty-tags' => '',
      'new-inline-tags' => '',
      'new-pre-tags' => '',
    ], 'utf8');
    $errs = $tidy->errorBuffer ? explode("\n", $tidy->errorBuffer) : [];
    return preg_grep(static::getIgnoredErrors(), $errs, PREG_GREP_INVERT);
  }

  protected static function getIgnoredErrors() {
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__])) {
      $pats = [
        // We're loosey goosey about where to place <script>.
        '\<script\> isn\'t allowed in \<table\>',
        // This should probably be fixed - but not sure how.
        'nested emphasis \<label\>',
      ];
      \Civi::$statics[__CLASS__][__FUNCTION__] = ';(' . implode('|', $pats) . ');';
    }
    return \Civi::$statics[__CLASS__][__FUNCTION__];
  }

}
