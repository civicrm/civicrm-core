<?php

$sourceCheckoutDir = $GLOBALS['_SERVER']['DM_SOURCEDIR'] ?? $argv[1];
$targetDir = $GLOBALS['_SERVER']['DM_TMPDIR'] . '/com_civicrm' ?? $argv[2];
$version = $GLOBALS['_SERVER']['DM_VERSION'] ?? $argv[3];
$pkgType = $GLOBALS['_SERVER']['DM_PKGTYPE'] ?? $argv[4];

ini_set('include_path',
  "{$sourceCheckoutDir}:{$sourceCheckoutDir}/packages:" . ini_get('include_path')
);

define('CIVICRM_UF', 'Joomla');
$GLOBALS['civicrm_root'] = $sourceCheckoutDir;
require_once $sourceCheckoutDir . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

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
  $permissions = CRM_Core_Permission::getCorePermissions();

  $crmFolderDir = $sourceCheckoutDir . DIRECTORY_SEPARATOR . 'CRM';

  // @todo call getCoreAndComponentPermissions instead and let that
  // do the work of these next 15-20 lines.
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
