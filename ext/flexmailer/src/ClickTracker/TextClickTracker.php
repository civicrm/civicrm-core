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
namespace Civi\FlexMailer\ClickTracker;

class TextClickTracker implements ClickTrackerInterface {

  public function filterContent($msg, $mailing_id, $queue_id) {
    return self::replaceTextUrls($msg,
      function ($url) use ($mailing_id, $queue_id) {
        return \CRM_Mailing_BAO_TrackableURL::getTrackerURL($url, $mailing_id,
          $queue_id);
      }
    );
  }

  /**
   * Find any URLs and replace them.
   *
   * @param string $text
   * @param callable $replace
   *   Function(string $oldUrl) => string $newUrl.
   * @return mixed
   *   String, text.
   */
  public static function replaceTextUrls($text, $replace) {
    $callback = function ($matches) use ($replace) {
      // ex: $matches[0] == 'http://foo.com'
      return $replace($matches[0]);
    };
    // Find any HTTP(S) URLs in the text.
    // return preg_replace_callback('/\b(?:(?:https?):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i', $callback, $tex
    return preg_replace_callback('/\b(?:(?:https?):\/\/)[-A-Z0-9+&@#\/%=~_|$?!:,.{}\[\];]*[A-Z0-9+&@#\/%=~_|${}\[\];]/i',
      $callback, $text);
  }

}
