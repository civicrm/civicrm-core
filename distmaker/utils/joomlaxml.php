<?php
if (isset($GLOBALS['_SERVER']['DM_SOURCEDIR'])) {
  $sourceCheckoutDir = $GLOBALS['_SERVER']['DM_SOURCEDIR'];
}
else {
  $sourceCheckoutDir = $argv[1];
}
$sourceCheckoutDirLength = strlen($sourceCheckoutDir);

if (isset($GLOBALS['_SERVER']['DM_TMPDIR'])) {
  $targetDir = $GLOBALS['_SERVER']['DM_TMPDIR'] . '/com_civicrm';
}
else {
  $targetDir = $argv[2];
}
$targetDirLength = strlen($targetDir);

if (isset($GLOBALS['_SERVER']['DM_VERSION'])) {
  $version = $GLOBALS['_SERVER']['DM_VERSION'];
}
else {
  $version = $argv[3];
}

if (isset($GLOBALS['_SERVER']['DM_PKGTYPE'])) {
  $pkgType = $GLOBALS['_SERVER']['DM_PKGTYPE'];
}
else {
  $pkgType = $argv[4];
}

ini_set('include_path',
  "{$sourceCheckoutDir}:{$sourceCheckoutDir}/packages:" . ini_get('include_path')
);
require_once "$sourceCheckoutDir/civicrm.config.php";
require_once 'Smarty/Smarty.class.php';

generateJoomlaConfig($version);

/**
 * This function creates destination directory
 *
 * @param $dir directory name to be created
 * @param $peram mode for that directory
 *
 */
function createDir($dir, $perm = 0755) {
  if (!is_dir($dir)) {
    echo "Outdir: $dir\n";
    mkdir($dir, $perm, TRUE);
  }
}

function generateJoomlaConfig($version) {
  global $targetDir, $sourceCheckoutDir, $pkgType;

  $smarty = new Smarty();
  $smarty->template_dir = $sourceCheckoutDir . '/xml/templates';
  $smarty->compile_dir = '/tmp/templates_c_u' . posix_geteuid();
  createDir($smarty->compile_dir);

  $smarty->assign('CiviCRMVersion', $version);
  $smarty->assign('creationDate', date('F d Y'));
  $smarty->assign('pkgType', $pkgType);

  $xml = $smarty->fetch('joomla.tpl');

  $output = $targetDir . '/civicrm.xml';
  $fd = fopen($output, "w");
  fputs($fd, $xml);
  fclose($fd);

  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton(FALSE);

  require_once 'CRM/Core/Permission.php';
  require_once 'CRM/Utils/String.php';
  $permissions = CRM_Core_Permission::getCorePermissions();

  $crmFolderDir = $sourceCheckoutDir . DIRECTORY_SEPARATOR . 'CRM';

  require_once 'CRM/Core/Component.php';
  $components = CRM_Core_Component::getComponentsFromFile($crmFolderDir);
  foreach ($components as $comp) {
    $perm = $comp->getPermissions();
    if ($perm) {
      $info = $comp->getInfo();
      foreach ($perm as $p) {
        $permissions[$p] = $info['translatedName'] . ': ' . $p;
      }
    }
  }

  $perms_array = array();
  foreach ($permissions as $perm => $title) {
    //order matters here, but we deal with that later
    $perms_array[CRM_Utils_String::munge(strtolower($perm))] = $title;
  }
  $smarty->assign('permissions', $perms_array);

  $output = $targetDir . '/admin/access.xml';
  $xml    = $smarty->fetch('access.tpl');
  $fd     = fopen($output, "w");
  fputs($fd, $xml);
  fclose($fd);
}

