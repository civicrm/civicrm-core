<?php

/**
 * Allow an extension to autoload WebComponents (*.js, *.mjs).
 *
 * Components are loaded from eponymous files. For example:
 *
 *   <hello-world>    -->    "$EXTENSION/js/component/hello-world.js"
 *
 * All files are treated as ECMAScript Modules, so they may use `import` statements.
 *
 * If you need to register components with different file-names, use hook_civicrm_componentJsPaths(&$paths).
 *
 * If you're doing funny business (like generating JS files) and need to refresh the index, call:
 *
 *   Civi::service('component-js@1')->flush();
 *
 * @mixinName component-js
 * @mixinVersion 1.0.0
 * @since 6.11
 *
 * Note: Requires hook_esmImportMap (v5.63+).
 * Note: Requires core mixin-loader (v5.45+; built-in version-handling).
 */
namespace Civi\Mixin\ComponentJsV1;

use Civi;
use CRM_Utils_String;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ComponentJs {

  const ASSET = 'component.js';

  const SUBDIR = 'js/component';

  protected $registered = FALSE;

  public static function instance(): ComponentJs {
    if (!isset(Civi::$statics[static::CLASS])) {
      Civi::$statics[static::CLASS] = new ComponentJs();
    }
    return Civi::$statics[static::CLASS];
  }

  public function register(): void {
    if ($this->registered) {
      return;
    }
    $this->registered = TRUE;

    Civi::dispatcher()->addListener('&hook_civicrm_container', [$this, 'container']);
    Civi::dispatcher()->addListener('&hook_civicrm_buildAsset', [$this, 'buildAsset']);
    Civi::dispatcher()->addListener('&hook_civicrm_alterBundle', [$this, 'alterBundle']);
  }

  public function container(ContainerBuilder $container): void {
    $container->setDefinition('component-js@1', new Definition(static::CLASS))
      ->setFactory([static::CLASS, 'instance'])
      ->setPublic(TRUE);
  }

  /**
   * @internal
   * @see \CRM_Utils_Hook::buildAsset()
   */
  public function buildAsset($asset, $params, &$mimeType, &$content) {
    if ($asset !== static::ASSET) {
      return;
    }

    $mimeType = 'text/javascript';
    $staticPaths = $this->getStaticPaths();
    $content = strtr($this->getTemplate(), [
      'COMPONENT_JS_STATIC_PATHS' => json_encode($staticPaths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    ]);
  }

  /**
   * @internal
   * @see \CRM_Utils_Hook::alterBundle()
   */
  public function alterBundle(\CRM_Core_Resources_Bundle $bundle) {
    if ($bundle->name === 'coreResources') {
      $url = Civi::service('asset_builder')->getUrl(static::ASSET, ['id' => $this->getId()]);
      $bundle->addModuleUrl($url);
    }
  }

  /**
   * @internal
   * @return array
   */
  public function getStaticPaths(): array {
    $componentPaths = [];
    $event = Civi\Core\Event\GenericHookEvent::create(['componentPaths' => &$componentPaths]);
    Civi::dispatcher()->dispatch('hook_civicrm_componentJsPaths', $event);
    return $componentPaths;
  }

  /**
   * @return string
   */
  public function getId(): string {
    $id = Civi::cache('long')->get('component-js-id');
    if ($id === NULL) {
      $id = CRM_Utils_String::createRandom(8, CRM_Utils_String::ALPHANUMERIC);
      Civi::cache('long')->set('component-js-id', $id, 7 * 24 * 60 * 60);
    }
    return $id;
  }

  public function flush(): void {
    Civi::cache('long')->delete('component-js-id');
  }

  /**
   * @return string
   */
  private function getTemplate(): string {
    $fp = fopen(__FILE__, 'r');
    fseek($fp, __COMPILER_HALT_OFFSET__);
    $template = stream_get_contents($fp);
    fclose($fp);
    return $template;
  }

}

/**
 * As a mixin, we receive a notification for each extension that enables `component-js@1`.
 *
 * @param \CRM_Extension_MixInfo $mixInfo
 * @param \CRM_Extension_BootCache $bootCache
 */
return function ($mixInfo, $bootCache) {

  ComponentJs::instance()->register();

  /**
   * Register this extension with the ESM import-map ('my-extension/' => '/var/www/sites/default/civicrm/ext/my-extension').
   *
   * @see \CRM_Utils_Hook::esmImportMap()
   */
  Civi::dispatcher()->addListener('&hook_civicrm_esmImportMap', function(\Civi\Esm\ImportMap $importMap) use ($mixInfo) {
    if ($mixInfo->isActive()) {
      $importMap->addPrefix($mixInfo->longName . '/', $mixInfo->longName);
    }
  }, 500);

  /**
   * Statically register each *.js or *.mjs file from $EXTENSION/js/component/.
   */
  Civi::dispatcher()->addListener('&hook_civicrm_componentJsPaths', function(array &$components) use ($mixInfo) {
    if ($mixInfo->isActive()) {
      $files = array_merge(
        // Only JS files that look like valid tag-names. Allows you to (eg) create `_bundle.js` which is handled separately.
        (array) glob($mixInfo->getPath(ComponentJs::SUBDIR . '/[a-z]*-*.js')),
        (array) glob($mixInfo->getPath(ComponentJs::SUBDIR . '/[a-z]*-*.mjs')),
      );
      foreach ($files as $file) {
        $tag = preg_replace('/\.(js|mjs)$/', '', basename($file));
        $components[$tag] = $mixInfo->longName . '/' . ComponentJs::SUBDIR . '/' . basename($file) . '?ts=' . filemtime($file);
      }
    }
  }, 1000);

};

###############################################################################
## Below, we stop executing PHP. The rest of the file contains the template
## for "component.js".
__HALT_COMPILER();
const $ = window.CRM.$;
const loading = new Set();
const registry = window.CRM.componentJs = COMPONENT_JS_STATIC_PATHS;

function maybeLoad(tagName) {
  if (!registry[tagName] || customElements.get(tagName) || loading.has(tagName)) {
    return;
  }

  loading.add(tagName);
  import(registry[tagName]).catch(err => {
    console.error(`Failed to load ${tagName}`, err);
    loading.delete(tagName);
  });
}

$(()=>{

  // Scan initial DOM
  document.querySelectorAll('*').forEach(el => {
    maybeLoad(el.tagName.toLowerCase());
  });


  // Watch for new nodes
  const observer = new MutationObserver(mutations => {
    for (const m of mutations) {
      for (const node of m.addedNodes) {
        if (node.nodeType === 1) {
          maybeLoad(node.tagName.toLowerCase());
          node.querySelectorAll?.('*').forEach(el =>
            maybeLoad(el.tagName.toLowerCase())
          );
        }
      }
    }
  });

  observer.observe(document.documentElement, {
    childList: true,
    subtree: true,
  });
});
