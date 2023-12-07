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

/**
 * Redirects a user to the full url from a mailing url.
 *
 * General Usage: civicrm/mailing/url?qid={event_queue_id}&u={url_id}
 *
 * Additional arguments may be handled by extractPassthroughParameters().
 */
class CRM_Mailing_Page_Url extends CRM_Core_Page {

  /**
   * Redirect the user to the specified url.
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $queue_id = CRM_Utils_Request::retrieveValue('qid', 'Integer');
    $url_id = CRM_Utils_Request::retrieveValue('u', 'Integer', NULL, TRUE);
    $url = trim(CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::track($queue_id, $url_id));
    $query_string = $this->extractPassthroughParameters();

    if (strlen($query_string) > 0) {
      // Parse the url to preserve the fragment.
      $pieces = parse_url($url);

      if (isset($pieces['fragment'])) {
        $url = str_replace('#' . $pieces['fragment'], '', $url);
      }

      // Handle additional query string params.
      if ($query_string) {
        if (stristr($url, '?')) {
          $url .= '&' . $query_string;
        }
        else {
          $url .= '?' . $query_string;
        }
      }

      // slap the fragment onto the end per URL spec
      if (isset($pieces['fragment'])) {
        $url .= '#' . $pieces['fragment'];
      }
    }
    CRM_Utils_System::redirect($url, [
      'for' => 'civicrm/mailing/url',
      'queue_id' => $queue_id,
      'url_id' => $url_id,
      'noindex' => TRUE,
    ]);
  }

  /**
   * Determine if this request has any valid pass-through parameters.
   *
   * Under CRM-7103 (v3.3), all unrecognized query-parameters (besides qid/u) are passed
   * through as part of the redirect. This mechanism is relevant to certain
   * customizations (eg using `hook_alterMailParams` to append extra URL args)
   * but does not matter for normal URLs.
   *
   * The functionality seems vaguely problematic (IMHO) - especially now that
   * 'extern/url.php' is moving into the CMS/Civi router ('civicrm/mailing/url').
   * But it's the current protocol.
   *
   * A better design might be to support `hook_alterRedirect` in the CiviMail
   * click-through tracking. Then you don't have to take any untrusted inputs
   * and you can fix URL mistakes in realtime.
   *
   * @return string
   * @link https://issues.civicrm.org/jira/browse/CRM-7103
   */
  protected function extractPassthroughParameters():string {
    $config = CRM_Core_Config::singleton();

    $query_param = $_GET;
    unset($query_param['qid']);
    unset($query_param['u']);
    unset($query_param[$config->userFrameworkURLVar]);

    // @see dev/core#1865 for some additional query strings we need to remove as well.
    if ($config->userFramework === 'WordPress') {
      // Ugh
      unset($query_param['page']);
      unset($query_param['noheader']);
      unset($query_param['civiwp']);
    }
    elseif ($config->userFramework === 'Joomla') {
      unset($query_param['option']);
    }

    $query_string = http_build_query($query_param);
    return $query_string;
  }

}
