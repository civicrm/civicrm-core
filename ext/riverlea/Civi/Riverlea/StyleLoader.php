<?php

namespace Civi\Riverlea;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use CRM_riverlea_ExtensionUtil as E;

/**
 * This class generates a `river.css` file for Riverlea streams containing
 * dynamically generated css content
 *
 * At the moment this is used to serve the right vars for a given dark mode setting
 *
 * @service riverlea.style_loader
 */
class StyleLoader extends AutoService implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  public const DYNAMIC_FILE = 'river.css';

  public const CORE_FILES = [
    '_variables.css',
    '_fonts.css',
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

  public function alterBundles(GenericHookEvent $e): void {
    if (!$this->isActive()) {
      return;
    }

    /**
     * @var \CRM_Core_Resources_Bundle
     */
    $bundle = $e->bundle;

    if ($bundle->name === 'coreResources') {
      if (\CRM_Core_Permission::check('administer CiviCRM')) {
        $bundle->addScriptFile('riverlea', 'js/previewer.js');
      }
    }

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
      if (\CRM_Utils_Request::retrieve('safe_css', 'Boolean')) {
        // safe mode - dont load dynamic styles
        return;
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
    $stream = $this->getStream();

    // we add the stream modified date to asset params as a cache buster
    $streamModified = $stream['modified_date'] ?? NULL;

    $isFrontend = \CRM_Utils_System::isFrontendPage();
    $darkMode = $isFrontend ? \Civi::settings()->get('riverlea_dark_mode_frontend') : \Civi::settings()->get('riverlea_dark_mode_backend');

    return [
      'stream' => $stream['name'],
      'modified' => $streamModified,
      'is_frontend' => $isFrontend,
      'dark_mode' => $darkMode,
    ];
  }

  protected function getStream(): array {
    $streamMeta = self::getAvailableStreamMeta();

    // admins can preview other streams using a url param
    if (\CRM_Core_Permission::check('administer CiviCRM')) {
      $streamOverride = \CRM_Utils_Request::retrieve('stream_override', 'String');
      // check override is a valid key before using
      if (isset($streamMeta[$streamOverride])) {
        return $streamMeta[$streamOverride];
      }
    }

    $key = \Civi::service('themes')->getActiveThemeKey();
    return $streamMeta[$key];
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

  /**
   * Validate the font size setting: it should be a floating
   * point number (CSS font size in rem)
   */
  public static function validateFontSize($value):bool {
    $fontSize = \CRM_Utils_Type::validate($value, 'Float', FALSE);
    if ($fontSize < 0.5 || $fontSize > 2) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get font size setting and add a variable for it
   * to the CSS properties of every stream
   */
  public static function onChangeFontsize($oldValue, $newValue, $metadata) {
    if ($oldValue != $newValue) {
      $fontSize = floatval($newValue);
      // Get current CSS properties for every stream
      $riverleaStreams = \Civi\Api4\RiverleaStream::get(TRUE)
        ->addSelect('vars')
        ->execute();
      // Add new font size to each stream as a CSS property
      foreach ($riverleaStreams as $riverleaStream) {
        $riverleaStream['vars']['--crm-font-size'] = $fontSize . "rem";
        // Write the new value to the CSS vars of each stream
        $results = \Civi\Api4\RiverleaStream::update(TRUE)
          ->addValue('vars', $riverleaStream['vars'])
          ->addWhere('id', '=', $riverleaStream['id'])
          ->execute();
      }
    }
  }

}
