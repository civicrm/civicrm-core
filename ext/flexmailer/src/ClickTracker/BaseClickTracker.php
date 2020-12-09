<?php
/**
 *
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC. All rights reserved.                        |
 * |                                                                    |
 * | This work is published under the GNU AGPLv3 license with some      |
 * | permitted exceptions and without any warranty. For full license    |
 * | and copyright information, see https://civicrm.org/licensing       |
 * +--------------------------------------------------------------------+
 *
 */


namespace Civi\FlexMailer\ClickTracker;

class BaseClickTracker {

  public static $getTrackerURL = ['CRM_Mailing_BAO_TrackableURL', 'getTrackerURL'];

  /**
   * Create a trackable URL for a URL with tokens.
   *
   * @param string $url
   * @param int $mailing_id
   * @param int|string $queue_id
   *
   * @return string
   */
  public static function getTrackerURLForUrlWithTokens($url, $mailing_id, $queue_id) {

    // Parse the URL.
    // (not using parse_url because it's messy to reassemble)
    if (!preg_match('/^([^?#]+)([?][^#]*)?(#.*)?$/', $url, $parsed)) {
      // Failed to parse it, give up and don't track it.
      return $url;
    }

    // If we have a token in the URL + path section, we can't tokenise.
    if (strpos($parsed[1], '{') !== FALSE) {
      return $url;
    }

    $trackable_url = $parsed[1];

    // Process the query parameters, if there are any.
    $tokenised_params = [];
    $static_params = [];
    if (!empty($parsed[2])) {
      $query_key_value_pairs = explode('&', substr($parsed[2], 1));

      // Separate the tokenised from the static parts.
      foreach ($query_key_value_pairs as $_) {
        if (strpos($_, '{') === FALSE) {
          $static_params[] = $_;
        }
        else {
          $tokenised_params[] = $_;
        }
      }
      // Add the static params to the trackable part.
      if ($static_params) {
        $trackable_url .= '?' . implode('&', $static_params);
      }
    }

    // Get trackable URL.
    $getTrackerURL = static::$getTrackerURL;
    $data = $getTrackerURL($trackable_url, $mailing_id, $queue_id);

    // Append the tokenised bits and the fragment.
    if ($tokenised_params) {
      // We know the URL will already have the '?'
      $data .= '&' . implode('&', $tokenised_params);
    }
    if (!empty($parsed[3])) {
      $data .= $parsed[3];
    }
    return $data;
  }

}
