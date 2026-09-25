<?php

/**
 * Allow an extension to autoload "Custom Elements" (aka "Web Components") from *.js, *.mjs, *.css.
 *
 * Elements are loaded from eponymous files. For example:
 *
 *   <hello-world>
 *      ==> "$EXTENSION/element/hello-world.js"
 *      ==> "$EXTENSION/element/hello-world.css"
 *
 * All [M]JS files are treated as ECMAScript Modules, so they may use `import` statements.
 * (In some deployments, `import`s may work with relative-paths. This is not currently guaranteed.
 * For stronger compatibility, imports SHOULD rely on the import-map prefix for `$EXTENSION/`.)
 *
 * If you need to register elements with different file-layouts, use hook_civicrm_elements(array &$elements).
 *
 * If you're doing funny business (like generating JS files) and need to refresh the index, call:
 *
 *   Civi::service('elements@1')->flush();
 *
 * @mixinName elements
 * @mixinVersion 1.0.0
 * @since 6.11
 *
 * Note: Requires hook_esmImportMap (v5.63+).
 * Note: Requires core mixin-loader (v5.45+; built-in version-handling).
 */
namespace Civi\Mixin\ElementsV1;

use Civi;
use CRM_Utils_String;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class Elements {

  const ASSET = 'elements.js';

  const SUBDIR = 'element';

  protected $registered = FALSE;

  public static function instance(): Elements {
    if (!isset(Civi::$statics[static::CLASS])) {
      Civi::$statics[static::CLASS] = new Elements();
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

  /**
   * Register as a service in the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   * @return void
   */
  public function container(ContainerBuilder $container): void {
    $container->setDefinition('elements@1', new Definition(static::CLASS))
      ->setFactory([static::CLASS, 'instance'])
      ->setPublic(TRUE);
  }

  /**
   * Render the `elements.js`, which provides an autoloader and registry.
   *
   * @internal
   * @see \CRM_Utils_Hook::buildAsset()
   */
  public function buildAsset($asset, $params, &$mimeType, &$content) {
    if ($asset !== static::ASSET) {
      return;
    }

    $mimeType = 'text/javascript';
    $registry = $this->getAll();
    $content = strtr($this->getTemplate(), [
      'ELEMENTS_REGISTRY' => json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    ]);
  }

  /**
   * Add the `elements.js` autoloader to all regular page-views.
   *
   * @internal
   * @see \CRM_Utils_Hook::alterBundle()
   */
  public function alterBundle(\CRM_Core_Resources_Bundle $bundle) {
    if ($bundle->name === 'coreResources') {
      $url = Civi::service('asset_builder')->getUrl(static::ASSET, ['id' => $this->getId()]);
      $bundle->addModuleUrl($url);
      // There might be some optimization opportunity to fetch the autoloader
      // in parallel with the main HTML doc. addModuleUrl() doesn't currently support
      // that (tho I suppose addMarkup() would). In any case, if you pursue that, then
      // also look closely at the init steps in `elements.js`.
    }
  }

  /**
   * Get a list of all known elements.
   *
   * @api
   * @return array
   */
  public function getAll(): array {
    $elements = [];
    $event = Civi\Core\Event\GenericHookEvent::create(['elements' => &$elements]);
    Civi::dispatcher()->dispatch('hook_civicrm_elements', $event);
    return $elements;
  }

  /**
   * @return string
   */
  public function getId(): string {
    $id = Civi::cache('long')->get('elements-id');
    if ($id === NULL) {
      $id = CRM_Utils_String::createRandom(8, CRM_Utils_String::ALPHANUMERIC);
      Civi::cache('long')->set('elements-id', $id, 7 * 24 * 60 * 60);
    }
    return $id;
  }

  /**
   * @api
   */
  public function flush(): void {
    Civi::cache('long')->delete('elements-id');
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
 * As a mixin, we receive a notification for each extension that enables `elements@1`.
 *
 * @param \CRM_Extension_MixInfo $mixInfo
 * @param \CRM_Extension_BootCache $bootCache
 */
return function ($mixInfo, $bootCache) {

  Elements::instance()->register();

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
   * Statically register each *.js, *.mjs, *.css file from $EXTENSION/element/.
   */
  Civi::dispatcher()->addListener('&hook_civicrm_elements', function(array &$elements) use ($mixInfo) {
    if ($mixInfo->isActive()) {
      $fileTypes = [
        '[a-z]*-*.js' => 'js',
        '[a-z]*-*.mjs' => 'js',
        '[a-z]*-*.css' => 'css',
      ];
      foreach ($fileTypes as $filePattern => $fileType) {
        $files = (array) glob($mixInfo->getPath(Elements::SUBDIR . '/' . $filePattern));
        foreach ($files as $file) {
          $elementName = preg_replace('/\.(js|mjs|css)$/', '', basename($file));
          if (!isset($elements[$elementName][$fileType])) {
            $elements[$elementName][$fileType] = [];
          }
          $elements[$elementName][$fileType][] = $mixInfo->longName . '/' . Elements::SUBDIR . '/' . basename($file) . '?ts=' . filemtime($file);
        }
      }
    }
  }, 1000);

};

###############################################################################
## Below, we stop executing PHP. The rest of the file contains the template
## for "elements.js".
__HALT_COMPILER();
const loading = new Set();
const registry = window.CRM.elements = ELEMENTS_REGISTRY;

async function maybeLoad(tagName) {
  if (!registry[tagName] || customElements.get(tagName) || loading.has(tagName)) {
    return;
  }

  loading.add(tagName);
  const { js = [], css = [] } = registry[tagName];

  try {
    css.forEach(logicalUri => {
      const href = import.meta.resolve(logicalUri);
      if (!document.querySelector(`link[href="${href}"]`)) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
      }
    });

    await Promise.all(js.map(src => import(src)));

  } catch (err) {
    console.error(`Failed to load assets for ${tagName}`, err);
    // Remove from loading set so a retry can be attempted later if needed
    loading.delete(tagName);
  }
}

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
