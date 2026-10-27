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
class CRM_Mailing_BAO_BouncePattern extends CRM_Mailing_DAO_BouncePattern {

  /**
   * @var array|null
   * @deprecated
   *   No longer used: the patterns are cached in \Civi::$statics.
   */
  public static $_patterns = NULL;

  /**
   * Build the static pattern array.
   */
  public static function buildPatterns() {
    Civi::$statics[__CLASS__]['patterns'] = [];
    $bp = new CRM_Mailing_BAO_BouncePattern();
    $bp->find();

    while ($bp->fetch()) {
      Civi::$statics[__CLASS__]['patterns'][$bp->bounce_type_id][] = $bp->pattern;
    }

    foreach (Civi::$statics[__CLASS__]['patterns'] as $type => $patterns) {
      if (count($patterns) == 1) {
        Civi::$statics[__CLASS__]['patterns'][$type] = '{(' . $patterns[0] . ')}im';
      }
      else {
        Civi::$statics[__CLASS__]['patterns'][$type] = '{(' . implode(')|(', $patterns) . ')}im';
      }
    }
  }

  /**
   * Try to match the string to a bounce type.
   *
   * @param string $message
   *   The message to be matched.
   *
   * @return array
   *   Tuple (bounce_type, bounce_reason)
   */
  public static function match($message) {
    // clean up $message and replace all white space by a single space, CRM-4767
    $message = preg_replace('/\s+/', ' ', $message);

    if (empty(Civi::$statics[__CLASS__]['patterns'])) {
      self::buildPatterns();
    }

    foreach (Civi::$statics[__CLASS__]['patterns'] as $type => $re) {
      if (preg_match($re, $message, $matches)) {
        $bounce = [
          'bounce_type_id' => $type,
          'bounce_reason' => $message,
        ];
        return $bounce;
      }
    }

    $bounce = [
      'bounce_type_id' => NULL,
      'bounce_reason' => $message,
    ];

    return $bounce;
  }

}
