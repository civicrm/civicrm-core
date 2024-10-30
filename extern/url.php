<?php
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
require_once 'CRM/Core/Error.php';
require_once 'CRM/Utils/Array.php';

CRM_Core_Config::singleton();

// To keep backward compatibility for URLs generated
// by CiviCRM < 1.7, we check for the q variable as well.
$queue_id = $_GET['qid'] ?? $_GET['q'] ?? NULL;

$url_id = $_GET['u'] ?? NULL;

if (!$url_id) {
  echo "Missing input parameters\n";
  exit();
}

require_once 'CRM/Mailing/Event/BAO/TrackableURLOpen.php';
$url = CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::track($queue_id, $url_id);

// CRM-7103
// Looking for additional query variables and append them when redirecting.
$query_param = $_GET;
unset($query_param['q'], $query_param['qid'], $query_param['u']);
$query_string = http_build_query($query_param);

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

// CRM-18320 - Fix encoded ampersands (see CRM_Utils_System::redirect)
$url = str_replace('&amp;', '&', $url);

// CRM-17953 - The CMS is not bootstrapped so cannot use CRM_Utils_System::redirect
header('X-Robots-Tag: noindex');
header('Location: ' . $url);
CRM_Utils_System::civiExit();
