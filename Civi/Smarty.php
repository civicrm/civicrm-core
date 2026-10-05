<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi;

/**
 * Fascade for accessing the Smarty object.
 *
 * @property bool $auto_literal
 * @property string|null $cache_id
 * @property int $cache_lifetime
 * @property bool $cache_locking
 * @property bool $cache_modified_check
 * @property int $caching
 * @property int $compile_check
 * @property string|null $compile_id
 * @property bool $compile_locking
 * @property bool $config_booleanize
 * @property bool $config_overwrite
 * @property bool $config_read_hidden
 * @property string[] $config_vars
 * @property string $debug_tpl
 * @property bool|int $debugging
 * @property string $debugging_ctrl
 * @property callable|null $default_config_handler_func
 * @property string $default_config_type
 * @property array $default_modifiers
 * @property string $default_resource_type
 * @property callable|null $default_template_handler_func
 * @property int|null $error_reporting
 * @property bool $error_unassigned
 * @property bool $escape_html
 * @property bool $force_cache
 * @property bool $force_compile
 * @property string $left_delimiter
 * @property array $literals
 * @property float $locking_timeout
 * @property bool $merge_compiled_includes
 * @property \Smarty\Data|\Smarty\Smarty|null $parent
 * @property array $registered_classes
 * @property array $registered_objects
 * @property array $registered_plugins
 * @property array $registered_resources
 * @property string $right_delimiter
 * @property string|null $security_class
 * @property \Smarty\Security|null $security_policy
 * @property string $smarty_debug_id
 * @property int $start_time
 * @property array $tplFunctions
 * @property \Smarty\Variable[] $tpl_vars
 * @property bool $use_sub_dirs
 *
 * @method \Smarty\Smarty addConfigDir(string|array $config_dir, $key = NULL)
 * @method \Smarty\Smarty addDefaultModifiers(array|string $modifiers)
 * @method void addExtension(\Smarty\Extension\ExtensionInterface $extension)
 * @method \Smarty\Smarty addLiterals(array|string $literals = NULL)
 * @method \Smarty\Smarty addTemplateDir(string|array $template_dir, string $key = NULL, bool $isConfig = false)
 * @method \Smarty\Data append(array|string $tpl_var, $value = NULL, bool $merge = false, bool $nocache = false)
 * @method \Smarty\Data assign(array|string $tpl_var, $value = NULL, bool $nocache = false, int $scope = NULL)
 * @method mixed assignConfigVars(array $new_config_vars, array $sections = [])
 * @method \Smarty\Data assignGlobal(string $varName, $value = NULL, bool $nocache = false)
 * @method \Smarty\Data clearAllAssign()
 * @method int clearAllCache(int $exp_time = NULL)
 * @method \Smarty\Data clearAssign(string|array $tpl_var)
 * @method int clearCache(string $template_name, string $cache_id = NULL, string $compile_id = NULL, int $exp_time = NULL)
 * @method int clearCompiledTemplate(string $resource_name = NULL, string $compile_id = NULL, int $exp_time = NULL)
 * @method \Smarty\Data clearConfig(string|null $name = NULL)
 * @method int compileAllConfig(string $extension = '.conf', bool $force_compile = false, int $time_limit = 0, int $max_errors = NULL)
 * @method int compileAllTemplates(string $extension = '.tpl', bool $force_compile = false, int $time_limit = 0, int $max_errors = NULL)
 * @method mixed configLoad(string $config_file, $sections = NULL)
 * @method \Smarty\Data createData(?\Smarty\Data $parent = NULL, $name = NULL)
 * @method \Smarty\Template createTemplate(string $template_name, $cache_id = NULL, $compile_id = NULL, $parent = NULL)
 * @method \Smarty\Smarty disableSecurity()
 * @method void display(string $template = NULL, $cache_id = NULL, $compile_id = NULL)
 * @method \Smarty\Smarty enableSecurity(string|\Smarty\Security $security_class = NULL)
 * @method string fetch(string $template = NULL, $cache_id = NULL, $compile_id = NULL)
 * @method bool getAutoLiteral()
 * @method ?\Smarty\BlockHandler\BlockHandlerInterface getBlockHandler(string $blockTagName)
 * @method string getCacheDir()
 * @method \Smarty\Cacheresource\Base getCacheResource()
 * @method string getCachingType()
 * @method int getCompileCheck()
 * @method string getCompileDir()
 * @method array getConfigDir($index = NULL)
 * @method mixed getConfigVariable(string $varName)
 * @method mixed getConfigVars(string $varname = NULL)
 * @method \Smarty\Debug getDebug()
 * @method string getDebugTemplate()
 * @method array getDefaultModifiers()
 * @method ?callable getDefaultPluginHandlerFunc()
 * @method int getDefaultScope()
 * @method array getExtensions()
 * @method ?\Smarty\FunctionHandler\FunctionHandlerInterface getFunctionHandler(string $functionName)
 * @method string getLeftDelimiter()
 * @method array getLiterals()
 * @method mixed getModifierCallback(string $modifierName)
 * @method ?\Smarty\Compile\Modifier\ModifierCompilerInterface getModifierCompiler(string $modifier)
 * @method \Smarty\Data|\Smarty\Smarty|null getParent()
 * @method array getPluginsDir()
 * @method object getRegisteredObject(string $object_name)
 * @method ?array getRegisteredPlugin(string $type, string $name)
 * @method string getRightDelimiter()
 * @method object|null getRuntime(string $type)
 * @method \Smarty\Smarty getSmarty()
 * @method array|string getTemplateDir($index = NULL, bool $isConfig = false)
 * @method mixed getTemplateVars(string $varName = NULL, bool $searchParents = true)
 * @method mixed|null getValue($varName, bool $searchParents = true)
 * @method \Smarty\Variable getVariable(string $varName, bool $searchParents = true, bool $errorEnable = true)
 * @method bool hasConfigVariable($varName)
 * @method bool hasRuntime(string $type)
 * @method bool hasVariable($varName)
 * @method bool isCached(null|string|\Smarty\Template $template = NULL, $cache_id = NULL, $compile_id = NULL)
 * @method bool isMutingUndefinedOrNullWarnings()
 * @method void muteUndefinedOrNullWarnings()
 * @method \Smarty\Smarty prependTemplateDir(string $new_template_dir, bool $is_config = false)
 * @method \Smarty\Smarty registerCacheResource(string $name, \Smarty\Cacheresource\Base $resource_handler)
 * @method \Smarty\Smarty registerClass(string $class_name, string $class_impl)
 * @method \Smarty\Smarty registerDefaultConfigHandler(callable $callback)
 * @method \Smarty\Smarty registerDefaultPluginHandler(callable $callback)
 * @method \Smarty\Smarty registerDefaultTemplateHandler(callable $callback)
 * @method \Smarty\Smarty registerFilter(string $type, callable $callback, string|null $name = NULL)
 * @method \Smarty\Smarty registerObject(string $object_name, object $object, array $allowed_methods_properties = [], bool $format = true, array $block_methods = [])
 * @method \Smarty\Smarty registerPlugin(string $type, string $name, callable $callback, bool $cacheable = true)
 * @method \Smarty\Smarty registerResource(string $name, \Smarty\Resource\BasePlugin $resource_handler)
 * @method mixed setAutoLiteral(bool $auto_literal = true)
 * @method \Smarty\Smarty setCacheDir(string $cache_dir)
 * @method mixed setCacheId(string $cache_id)
 * @method mixed setCacheLifetime(int $cache_lifetime)
 * @method void setCacheModifiedCheck(bool $cache_modified_check)
 * @method void setCacheResource(\Smarty\Cacheresource\Base $cacheResource)
 * @method mixed setCaching(int $caching)
 * @method void setCachingType($type)
 * @method mixed setCompileCheck(int $compile_check)
 * @method \Smarty\Smarty setCompileDir(string $compile_dir)
 * @method mixed setCompileId(string $compile_id)
 * @method mixed setCompileLocking(bool $compile_locking)
 * @method mixed setConfigBooleanize(bool $config_booleanize)
 * @method \Smarty\Smarty setConfigDir($config_dir)
 * @method mixed setConfigOverwrite(bool $config_overwrite)
 * @method mixed setConfigReadHidden(bool $config_read_hidden)
 * @method \Smarty\Smarty setDebugTemplate(string $tpl_name)
 * @method mixed setDebugging(bool $debugging)
 * @method \Smarty\Smarty setDefaultModifiers(array|string $modifiers)
 * @method mixed setDefaultResourceType(string $default_resource_type)
 * @method mixed setErrorReporting(int $error_reporting)
 * @method mixed setEscapeHtml(bool $escape_html)
 * @method void setExtensions(array $extensions)
 * @method mixed setForceCompile(bool $force_compile)
 * @method mixed setLeftDelimiter(string $left_delimiter)
 * @method \Smarty\Smarty setLiterals(array|string $literals = NULL)
 * @method mixed setMergeCompiledIncludes(bool $merge_compiled_includes)
 * @method void setParent(\Smarty\Data|\Smarty\Smarty|null $parent)
 * @method \Smarty\Smarty setPluginsDir(string|array $plugins_dir)
 * @method mixed setRightDelimiter($right_delimiter)
 * @method \Smarty\Smarty setTemplateDir(string|array $template_dir, bool $isConfig = false)
 * @method mixed setUseSubDirs(bool $use_sub_dirs)
 * @method void setVariable($varName, \Smarty\Variable $variableObject)
 * @method bool templateExists(string $resource_name)
 * @method mixed testInstall(&$errors = NULL)
 * @method \Smarty\Smarty unloadFilter(string $type, string $name)
 * @method \Smarty\Smarty unregisterCacheResource($name)
 * @method \Smarty\Smarty unregisterFilter(string $type, callable|string $name)
 * @method \Smarty\Smarty unregisterObject(string $object_name)
 * @method \Smarty\Smarty unregisterPlugin(string $type, string $name)
 * @method \Smarty\Smarty unregisterResource(string $type)
 */
class Smarty {

  private \Smarty\Smarty $smarty;

  private $registeredPluginDirectories = [];

  public function __construct() {
    $this->smarty = new \Smarty\Smarty();
  }

  public function __call($name, $arguments) {
    return call_user_func_array([$this->smarty, $name], $arguments);
  }

  public function __get($name) {
    // Quick form accesses these in HTML_QuickForm_Renderer_ArraySmarty->_renderRequired()
    if ($name === 'left_delimiter') {
      return $this->smarty->getLeftDelimiter();
    }
    if ($name === 'right_delimiter') {
      return $this->smarty->getRightDelimiter();
    }
    return $this->smarty->$name;
  }

  public function __set($name, $value) {
    $this->smarty->$name = $value;
  }

  /**
   * @throws \Smarty\Exception
   */
  public function loadFilter($type, $name) {
    if ($type === 'pre') {
      $this->smarty->registerFilter($type, 'smarty_prefilter_' . $name);
    }
    else {
      $this->smarty->loadFilter($type, $name);
    }
  }

  /**
   * @param null|string|array $pluginsDirectories
   *
   * @return void
   * @throws \Smarty\Exception
   */
  public function addPluginsDir($pluginsDirectories): void {
    foreach ((array) $pluginsDirectories as $pluginsDirectory) {
      if (in_array($pluginsDirectory, $this->registeredPluginDirectories, TRUE)) {
        continue;
      }
      $files = scandir($pluginsDirectory);
      foreach ($files as $file) {
        if (str_starts_with($file, '.')) {
          continue;
        }
        $registeredPlugins = $this->smarty->registered_plugins;
        if (\CRM_Utils_File::isIncludable($pluginsDirectory . DIRECTORY_SEPARATOR . $file)) {
          require_once $pluginsDirectory . DIRECTORY_SEPARATOR . $file;
          $parts = explode('.', $file);
          if (!empty($registeredPlugins[$parts[0]][$parts[1]])) {
            continue;
          }
          $this->smarty->registerPlugin($parts[0], $parts[1], 'smarty_' . $parts[0] . '_' . $parts[1]);
        }
      }
      $this->registeredPluginDirectories[] = $pluginsDirectory;
    }
  }

  public function getVersion(): ?int {
    return 5;
  }

}
