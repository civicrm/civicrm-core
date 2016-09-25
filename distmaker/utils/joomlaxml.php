<?php
define('CIVICRM_MYSQL_STRICT', 0);
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
 * @param int $perm
 *
 * @internal param \mode $peram for that directory
 */
function createDir($dir, $perm = 0755) {
  if (!is_dir($dir)) {
    echo "Outdir: $dir\n";
    mkdir($dir, $perm, TRUE);
  }
}

/**
 * @param $version
 */
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
  fwrite($fd, $xml);
  fclose($fd);

  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton(FALSE);

  require_once 'CRM/Core/Permission.php';
  require_once 'CRM/Utils/String.php';
  require_once 'CRM/Core/I18n.php';
  $permissions = CRM_Core_Permission::getCorePermissions(TRUE);

  $crmFolderDir = $sourceCheckoutDir . DIRECTORY_SEPARATOR . 'CRM';

  require_once 'CRM/Core/Component.php';
  $components = CRM_Core_Component::getComponentsFromFile($crmFolderDir);
  foreach ($components as $comp) {
    $perm = $comp->getPermissions(FALSE, TRUE);
    if ($perm) {
      $info = $comp->getInfo();
      foreach ($perm as $p => $attr) {
        $title = $info['translatedName'] . ': ' . array_shift($attr);
        array_unshift($attr, $title);
        $permissions[$p] = $attr;
      }
    }
  }

  $perms_array = array();
  foreach ($permissions as $perm => $attr) {
    // give an empty string as default description
    $attr[] = '';

    //order matters here, but we deal with that later
    $perms_array[CRM_Utils_String::munge(strtolower($perm))] = array(
      'title' => array_shift($attr),
      'description' => array_shift($attr),
    );
  }
  $smarty->assign('permissions', $perms_array);

  $output = $targetDir . '/admin/access.xml';
  $xml    = $smarty->fetch('access.tpl');
  $fd     = fopen($output, "w");
  fwrite($fd, $xml);
  fclose($fd);
}
