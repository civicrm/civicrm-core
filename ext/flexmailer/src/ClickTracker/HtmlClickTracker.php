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

use Civi\Core\Service\AutoService;

/**
 * @service civi_flexmailer_html_click_tracker
 */
class HtmlClickTracker extends AutoService implements ClickTrackerInterface {

  public function filterContent($msg, $mailing_id, $queue_id) {
    return self::replaceHrefUrls($msg,
      function ($url) use ($mailing_id, $queue_id) {
        $data = \CRM_Mailing_BAO_MailingTrackableURL::getTrackerURL(
          html_entity_decode($url), $mailing_id, $queue_id);
        $data = htmlentities($data, ENT_NOQUOTES);
        return $data;
      }
    );
  }

  /**
   * Find any HREF-style URLs and replace them.
   *
   * @param string $html
   * @param callable $replace
   *   Function(string $oldHtmlUrl) => string $newHtmlUrl.
   * @return mixed
   *   String, HTML.
   */
  public static function replaceHrefUrls($html, $replace) {
    $useNoFollow = TRUE;
    $callback = function ($matches) use ($replace, $useNoFollow) {
      $replacement = $replace($matches[2]);

      // See: https://github.com/civicrm/civicrm-core/pull/12561
      // If we track click-throughs on a link, then don't encourage search-engines to traverse them.
      // At a policy level, I'm not sure I completely agree, but this keeps things consistent.
      // You can tell if we're tracking a link because $replace() yields a diff URL.
      $noFollow = '';
      if ($useNoFollow && $replacement !== $matches[2]) {
        $noFollow = " rel='nofollow'";
      }

      return $matches[1] . $replacement . $matches[3] . $noFollow;
    };

    // Find anything like href="..." or href='...' inside a tag.
    $tmp = preg_replace_callback(
      ';(\<a[^>]*href *= *")([^">]+)(");iu', $callback, $html);
    return preg_replace_callback(
      ';(\<a[^>]*href *= *\')([^\'>]+)(\');iu', $callback, $tmp);
  }

  //  /**
  //   * Find URL expressions; replace them with tracked URLs.
  //   *
  //   * @param string $msg
  //   * @param int $mailing_id
  //   * @param int|string $queue_id
  //   * @param bool $html
  //   * @return string
  //   *   Updated $msg
  //   */
  //  public static function scanAndReplace_old($msg, $mailing_id, $queue_id, $html = FALSE) {
  //
  //    $protos = '(https?|ftp)';
  //    $letters = '\w';
  //    $gunk = '/#~:.?+=&%@!\-';
  //    $punc = '.:?\-';
  //    $any = "{$letters}{$gunk}{$punc}";
  //    if ($html) {
  //      $pattern = "{\\b(href=([\"'])?($protos:[$any]+?(?=[$punc]*[^$any]|$))([\"'])?)}im";
  //    }
  //    else {
  //      $pattern = "{\\b($protos:[$any]+?(?=[$punc]*[^$any]|$))}eim";
  //    }
  //
  //    $trackURL = \CRM_Mailing_BAO_TrackableURL::getTrackerURL('\\1', $mailing_id, $queue_id);
  //    $replacement = $html ? ("href=\"{$trackURL}\"") : ("\"{$trackURL}\"");
  //
  //    $msg = preg_replace($pattern, $replacement, $msg);
  //    if ($html) {
  //      $msg = htmlentities($msg, ENT_NOQUOTES);
  //    }
  //    return $msg;
  //  }

}
