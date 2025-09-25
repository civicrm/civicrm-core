<?php

namespace Civi\Riverlea;

use CRM_riverlea_ExtensionUtil as E;

/**
 * This class generates a `river.css` file for Riverlea streams containing
 * dynamically generated css content
 *
 * At the moment this is used to serve the right vars for a given dark mode setting
 *
 * @service riverlea.style_loader
 */
class StyleLoader implements \Symfony\Component\EventDispatcher\EventSubscriberInterface, \Civi\Core\Service\AutoServiceInterface {

  use \Civi\Core\Service\AutoServiceTrait;

  public const DYNAMIC_FILE = 'river.css';

  public const CORE_FILES = [
    '_variables.css',
    '_base.css',
    '_cms.css',
    'components/_accordion.css',
    'components/_alerts.css',
    'components/_buttons.css',
    'components/_form.css',
    'components/_icons.css',
    'components/_nav.css',
    'components/_tabs.css',
    'components/_dropdowns.css',
    'components/_tables.css',
    'components/_dialogs.css',
    'components/_page.css',
    'components/_components.css',
    'components/_front.css',
    '_fixes.css',
  ];

  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_themes' => ['onGetThemes', 0],
      'hook_civicrm_alterBundle' => ['alterBundles', 0],
      'hook_civicrm_buildAsset' => ['buildDynamicCss', 0],
    ];
  }

  /**
   * Is a Riverlea stream selected as the current theme?
   */
  public function isActive(): bool {
    $themeKey = \Civi::service('themes')->getActiveThemeKey();
    $themeSearchOrder = \Civi::service('themes')->get($themeKey)['search_order'] ?? [];
    return in_array('_riverlea_core_', $themeSearchOrder);
  }

  public function onGetThemes($e): void {
    // always add (hidden) Riverlea base theme
    $e->themes['_riverlea_core_'] = [
      'ext' => 'riverlea',
      'title' => 'Riverlea: base theme',
      'prefix' => 'core/',
      'search_order' => ['_riverlea_core_', '_fallback_'],
    ];

    try {
      $streams = $this->getAvailableStreamMeta();
    }
    catch (\CRM_Core_Exception $e) {
      // dont crash the whole hook if Riverlea is broken
      \CRM_Core_Session::setStatus('Error occured making Riverlea streams available to the theme engine: ' . $e->getMessage());
      return;
    }

    foreach ($streams as $name => $stream) {
      $themeMeta = [
        'title' => $stream['label'],
        'search_order' => [],
      ];

      $extension = $stream['extension'];

      // we only add the stream itself to the search order if
      // it has an extension (which indicates it may have its own
      // file overrides)
      if ($extension) {
        $themeMeta['search_order'][] = $name;

        // used to resolve files from this stream
        $themeMeta['ext'] = $extension;
        $themeMeta['prefix'] = $stream['file_prefix'] ?? '';
      }

      $themeMeta['search_order'][] = '_riverlea_core_';
      $themeMeta['search_order'][] = '_fallback_';

      $e->themes[$name] = $themeMeta;
    }
  }

  public function alterBundles($e): void {
    if (!$this->isActive()) {
      return;
    }

    $bundle = $e->bundle;

    if ($bundle->name === 'bootstrap3') {
      $bundle->clear();
      $bundle->addStyleFile('riverlea', 'core/css/_bootstrap.css');
      $bundle->addScriptFile('greenwich', 'extern/bootstrap3/assets/javascripts/bootstrap.min.js', [
        'translate' => FALSE,
      ]);
      $bundle->addScriptFile('greenwich', 'js/noConflict.js', [
        'translate' => FALSE,
      ]);
    }

    if ($bundle->name === 'coreStyles') {
      // queue all core files in order
      // between crm-i at -101
      // and civicrm.css at -99
      $j = count(self::CORE_FILES);
      foreach (self::CORE_FILES as $i => $file) {
        $bundle->addStyleFile('riverlea', "core/css/{$file}", ['weight' => -100 + ($i / $j)]);
      }
      // get the URL for dynamic css asset (aka "the river")
      $riverUrl = \Civi::service('asset_builder')->getUrl(
        self::DYNAMIC_FILE,
        $this->getCssParams()
      );
      // queue dynamic css late
      $bundle->addStyleUrl($riverUrl, ['weight' => 100]);
    }
  }

  protected function getAvailableStreamMeta(): array {
    $streams = \Civi::$statics['riverlea_streams'] ?? NULL;

    if (is_null($streams)) {
      $streams = (array) \Civi\Api4\RiverleaStream::get(FALSE)
        ->addSelect('name', 'label', 'extension', 'file_prefix', 'parent_id', 'id', 'modified_date')
        ->execute()
        ->indexBy('name');

      \Civi::$statics['riverlea_streams'] = $streams;
    }

    return $streams;
  }

  public function getCssParams(): array {
    $stream = \Civi::service('themes')->getActiveThemeKey();

    // we add the stream modified date to asset params as a cache buster
    $streamMeta = $this->getAvailableStreamMeta()[$stream] ?? [];
    $streamModified = $streamMeta['modified_date'] ?? NULL;

    return [
      'stream' => $stream,
      'modified' => $streamModified,
      'is_frontend' => \CRM_Utils_System::isFrontendPage(),
    ];
  }

  /**
   * Generate asset content (when accessed via AssetBuilder).
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   *
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public function buildDynamicCss($e) {
    if ($e->asset !== static::DYNAMIC_FILE) {
      return;
    }
    $e->mimeType = 'text/css';

    $render = \Civi\Api4\RiverleaStream::render(FALSE)
      ->addWhere('name', '=', $e->params['stream'])
      ->setIsFrontend($e->params['is_frontend'])
      ->execute()
      ->first();

    $e->content = $render['content'] ?? '';
  }

}
