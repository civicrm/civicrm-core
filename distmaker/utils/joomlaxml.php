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

  $xml = renderCivicrmXml($version, date('F d Y'), $pkgType);
  writeFile($targetDir . '/civicrm.xml', $xml);

  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton(FALSE);

  require_once 'CRM/Core/Permission.php';
  require_once 'CRM/Utils/String.php';
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
  ksort($perms_array);

  $xml = renderAccessXml($perms_array);
  writeFile($targetDir . '/admin/access.xml', $xml);
}

function writeFile(string $file, string $content): void {
  if (!file_put_contents($file, $content)) {
    throw new \Exception("Failed to create $file");
  }
}

function renderCivicrmXml($CiviCRMVersion, $creationDate, $pkgType): string {
  $buf[] = '<?xml version="1.0" encoding="utf-8"?>';
  $buf[] = '<extension method="upgrade" type="component" version="2.5">';
  $buf[] = '  <name>CiviCRM</name>';
  $buf[] = sprintf('  <creationDate>%s</creationDate>', xmlspecialchars($creationDate));
  $buf[] = '  <copyright>(C) CiviCRM LLC</copyright>';
  $buf[] = '  <author>CiviCRM LLC</author>';
  $buf[] = '  <authorEmail>info@civicrm.org</authorEmail>';
  $buf[] = '  <authorUrl>civicrm.org</authorUrl>';
  $buf[] = sprintf('  <version>%s</version>', xmlspecialchars($CiviCRMVersion));
  $buf[] = '  <description>CiviCRM</description>';
  $buf[] = '  <files folder="site">';
  $buf[] = '    <filename>civicrm.php</filename>';
  $buf[] = '    <filename>civicrm.html.php</filename>';
  $buf[] = '    <folder>views</folder>';
  $buf[] = '    <folder>elements</folder>';
  $buf[] = '  </files>';
  $buf[] = '  <install>';
  $buf[] = '    <queries>';
  $buf[] = '    </queries>';
  $buf[] = '  </install>';
  $buf[] = '  <uninstall>';
  $buf[] = '      <queries>';
  $buf[] = '      </queries>';
  $buf[] = '  </uninstall>';
  $buf[] = '  <scriptfile>script.civicrm.php</scriptfile>';
  $buf[] = '  <administration>';
  $buf[] = '    <menu task="civicrm/dashboard&amp;reset=1">COM_CIVICRM_MENU</menu>';
  $buf[] = '    <files folder="admin">';
  $buf[] = '      <filename>admin.civicrm.php</filename>';
  $buf[] = '      <filename>civicrm.php</filename>';
  $buf[] = '      <filename>configure.php</filename>';
  $buf[] = '      <filename>access.xml</filename>';
  $buf[] = '      <filename>config.xml</filename>';
  if ($pkgType === 'alt') {
    $buf[] = '      <folder>civicrm</folder>';
  }
  else {
    $buf[] = '      <filename>civicrm.zip</filename>';
  }
  $buf[] = '      <folder>helpers</folder>';
  $buf[] = '    </files>';
  $buf[] = '    <languages folder="admin">';
  $buf[] = '      <language tag="en-GB">language/en-GB/en-GB.com_civicrm.ini</language>';
  $buf[] = '      <language tag="en-GB">language/en-GB/en-GB.com_civicrm.sys.ini</language>';
  $buf[] = '    </languages>';
  $buf[] = '  </administration>';
  $buf[] = '  <plugins>';
  $buf[] = '      <plugin folder="admin/plugins" plugin="civicrm" name="CiviCRM User Management" group="user" />';
  $buf[] = '      <plugin folder="admin/plugins" plugin="civicrmsys" name="CiviCRM System Listener" group="system" />';
  $buf[] = '      <plugin folder="admin/plugins" plugin="civicrmicon" name="CiviCRM QuickIcon" group="quickicon" />';
  $buf[] = '  </plugins>';
  $buf[] = '</extension>';

  return implode("\n", $buf);
}

function renderAccessXml(array $permissions): string {
  $buf[] = '<?xml version="1.0" encoding="utf-8"?>';
  $buf[] = '<access component="com_civicrm">';
  $buf[] = '  <section name="component">';
  $buf[] = '            <action name="core.admin" title="Configure Joomla! ACL" description="Manage CiviCRM Joomla! ACL." />';
  $buf[] = '            <action name="core.manage" title="See CiviCRM is installed" description="CiviCRM will be shown in list of installed components." />';
  foreach ($permissions as $name => $perm) {
    // $buf[] = '            <action name="civicrm.{$name}" title="{$perm.title}" description="{$perm.description}" />';
    $buf[] = sprintf('            <action name="%s" title="%s" description="%s" />',
      xmlspecialchars("civicrm.{$name}", '"'),
      xmlspecialchars($perm['title'], '"'),
      xmlspecialchars($perm['description'], '"')
    );
  }
  $buf[] = '  </section>';
  $buf[] = '</access>';
  return implode("\n", $buf);
}

function xmlspecialchars(string $value, ?string $context = NULL): string {
  $map = [
    '<' => '&lt;',
    '>' => '&gt;',
    '&' => '&amp;',
    '"' => '&quot;',
    '\'' => '&apos;',
  ];
  if ($context === '"') {
    unset($map["'"]);
  }
  elseif ($context === "'") {
    unset($map['"']);
  }
  return strtr($value, $map);
}
