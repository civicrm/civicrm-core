<?php

function repo($path, $branch) {
  $escPath = escapeshellarg($path);
  $escBranch = escapeshellarg($branch);
  return array(
    'branch' => $branch,
    'commit' => `cd $escPath ; git show $escBranch | head -n1 | cut -f2 -d\ `,
  );
}

$DM_SOURCEDIR = getenv('DM_SOURCEDIR');
$data = array(
  'version' => getenv('DM_VERSION'),
  'timestamp' => array(
    'pretty' => date('r'),
    'epoch' => time(),
  ),
  'civicrm-core' => repo("$DM_SOURCEDIR", getenv('DM_REF_CORE')),
  'civicrm-packages' => repo("$DM_SOURCEDIR/packages", getenv('DM_REF_PACKAGES')),
);
ksort($data);
$data['sig'] = md5(json_encode($data));
echo json_encode($data);
