<?php
require_once '../Civi/Bootstrap.php';
Civi\Bootstrap::singleton()->boot();

$queue_id = CRM_Utils_Array::value('q', $_GET);
if (!$queue_id) {
  echo "Missing input parameters\n";
  exit();
}

CRM_Mailing_Event_BAO_Opened::open($queue_id);

$filename = "../i/tracker.gif";

header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Content-Description: File Transfer');
header('Content-type: image/gif');
header('Content-Length: ' . filesize($filename));

header('Content-Disposition: inline; filename=tracker.gif');

readfile($filename);

exit();
