<?php
namespace Civi\Setup;

class SmartyUtil {

  /**
   * Create a Smarty instance.
   *
   * @return \Smarty
   * @throws \SmartyException
   */
  public static function createSmarty($srcPath) {
    require_once 'CRM/Core/I18n.php';

    $packagePath = PackageUtil::getPath($srcPath);
    require_once $packagePath . '/smarty4/vendor/autoload.php';

    $smarty = new \Smarty();
    $smarty->setTemplateDir(implode(DIRECTORY_SEPARATOR, [$srcPath, 'xml', 'templates']));
    $pluginsDirectory = $smarty->getPluginsDir();
    $pluginsDirectory[] = implode(DIRECTORY_SEPARATOR, [$srcPath, 'CRM', 'Core', 'Smarty', 'plugins']);
    // Doesn't seem to work very well.... since I still need require_once below
    $smarty->setPluginsDir($pluginsDirectory);
    $smarty->setCompileDir(\Civi\Setup\FileUtil::createTempDir('templates_c'));
    $smarty->clearAllCache();

    require_once 'CRM/Core/Smarty/plugins/modifier.crmEscapeSingleQuotes.php';
    $smarty->registerPlugin('modifier', 'crmEscapeSingleQuotes', 'smarty_modifier_crmEscapeSingleQuotes');

    // CRM-5308 / CRM-3507 - we need {localize} to work in the templates
    require_once implode(DIRECTORY_SEPARATOR, [$srcPath, 'CRM', 'Core', 'Smarty', 'plugins', 'block.localize.php']);
    $smarty->registerPlugin('block', 'localize', 'smarty_block_localize');
    require_once 'CRM/Core/Smarty/plugins/modifier.crmCountCharacters.php';
    $smarty->registerPlugin('modifier', 'crmCountCharacters', 'smarty_modifier_crmCountCharacters');
    require_once implode(DIRECTORY_SEPARATOR, [$srcPath, 'CRM', 'Core', 'CodeGen', 'Util', 'MessageTemplates.php']);
    $smarty->registerPlugin('modifier', 'json_encode', 'json_encode');
    $smarty->registerPlugin('modifier', 'count', 'count');
    $smarty->registerPlugin('modifier', 'implode', 'implode');
    \CRM_Core_CodeGen_Util_MessageTemplates::assignSmartyVariables($smarty);
    return $smarty;
  }

}
