<?php
namespace Civi\Setup;

use Civi\Smarty;

class SmartyUtil {

  /**
   * Create a Smarty instance.
   *
   * @return \Civi\Smarty
   * @throws \SmartyException
   */
  public static function createSmarty($srcPath) {
    $smarty = new Smarty();
    $smarty->setTemplateDir(implode(DIRECTORY_SEPARATOR, [$srcPath, 'xml', 'templates']));
    $smarty->addPluginsDir([
      implode(DIRECTORY_SEPARATOR, [$srcPath, 'CRM', 'Core', 'Smarty', 'plugins']),
    ]);
    $smarty->setCompileDir(\Civi\Setup\FileUtil::createTempDir('templates_c'));
    $smarty->clearAllCache();
    require_once implode(DIRECTORY_SEPARATOR, [$srcPath, 'CRM', 'Core', 'CodeGen', 'Util', 'MessageTemplates.php']);
    $smarty->registerPlugin('modifier', 'json_encode', 'json_encode');
    $smarty->registerPlugin('modifier', 'count', 'count');
    $smarty->registerPlugin('modifier', 'implode', 'implode');
    \CRM_Core_CodeGen_Util_MessageTemplates::assignSmartyVariables($smarty);
    return $smarty;
  }

}
