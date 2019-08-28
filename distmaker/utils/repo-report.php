<?php

/**
 * Make a small report about the git content in a given folder+branch.
 * @param string $path
 * @param string $branch
 * @return array
 *   - branch: string
 *   - commit: string
 */
function repo($path, $branch) {
  $escPath = escapeshellarg($path);
  $escBranch = escapeshellarg($branch);
  $commit = file_exists($path) ? trim(`cd $escPath ; git show $escBranch | head -n1 | cut -f2 -d\ `) : NULL;
  if (!empty($commit)) {
    return array(
      'branch' => $branch,
      'commit' => $commit,
    );
  }
  else {
    return array();
  }
}

$DM_SOURCEDIR = getenv('DM_SOURCEDIR');
$DM_VERSION = getenv('DM_VERSION');
$data = array(
  'version' => $DM_VERSION,
  'timestamp' => array(
    'pretty' => date('r'),
    'epoch' => time(),
  ),
  'tar' => array(),
  'git' => array(
    'civicrm-backdrop@1.x' => repo("$DM_SOURCEDIR/backdrop", getenv('DM_REF_BACKDROP')),
    'civicrm-core' => repo("$DM_SOURCEDIR", getenv('DM_REF_CORE')),
    'civicrm-drupal@6.x' => repo("$DM_SOURCEDIR/drupal", getenv('DM_REF_DRUPAL6')),
    'civicrm-drupal@7.x' => repo("$DM_SOURCEDIR/drupal", getenv('DM_REF_DRUPAL')),
    'civicrm-drupal-8' => repo("$DM_SOURCEDIR/drupal-8", getenv('DM_REF_DRUPAL8')),
    'civicrm-joomla' => repo("$DM_SOURCEDIR/joomla", getenv('DM_REF_JOOMLA')),
    'civicrm-packages' => repo("$DM_SOURCEDIR/packages", getenv('DM_REF_PACKAGES')),
    'civicrm-wordpress' => repo("$DM_SOURCEDIR/WordPress", getenv('DM_REF_WORDPRESS')),
  ),
);

if (getenv('BPACK')) {
  $data['tar']['Backdrop'] = "civicrm-$DM_VERSION-backdrop-unstable.tar.gz";
}
if (getenv('J5PACK')) {
  $data['tar']['Joomla'] = "civicrm-$DM_VERSION-joomla.zip";
}
if (getenv('D56PACK')) {
  $data['tar']['Drupal6'] = "civicrm-$DM_VERSION-drupal6.tar.gz";
}
if (getenv('D5PACK')) {
  $data['tar']['Drupal'] = "civicrm-$DM_VERSION-drupal.tar.gz";
}
if (getenv('WPPACK')) {
  $data['tar']['WordPress'] = "civicrm-$DM_VERSION-wordpress.zip";
}
if (getenv('L10NPACK')) {
  $data['tar']['L10n'] = "civicrm-$DM_VERSION-l10n.tar.gz";
}

ksort($data);
ksort($data['tar']);
ksort($data['git']);
$data['rev'] = $DM_VERSION . '-' . md5(json_encode($data));
echo json_encode($data);
