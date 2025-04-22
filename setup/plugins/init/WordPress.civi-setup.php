<?php
/**
 * @file
 *
 * Determine default settings for WordPress.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkAuthorized', function (\Civi\Setup\Event\CheckAuthorizedEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'WordPress') {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkAuthorized'));
    $e->setAuthorized(current_user_can('activate_plugins'));
  });


\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'WordPress' || !function_exists('current_user_can')) {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    // Note: We know WP is bootstrapped, but we don't know if the `civicrm` plugin is active,
    // so we have to make an educated guess.
    $civicrmPluginFile = _civicrm_wordpress_plugin_file();

    // Compute settingsPath.
    $uploadDir = wp_upload_dir();
    $preferredSettingsPath = $uploadDir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm.settings.php';
    $oldSettingsPath = plugin_dir_path($civicrmPluginFile) . 'civicrm.settings.php';
    if (file_exists($preferredSettingsPath)) {
      $model->settingsPath = $preferredSettingsPath;
    }
    elseif (file_exists($oldSettingsPath)) {
      $model->settingsPath = $oldSettingsPath;
    }
    else {
      $model->settingsPath = $preferredSettingsPath;
    }

    $model->paths['civicrm.private']['path'] = implode(DIRECTORY_SEPARATOR, [$uploadDir['basedir'], 'civicrm']);
    $model->templateCompilePath = implode(DIRECTORY_SEPARATOR, [$uploadDir['basedir'], 'civicrm', 'templates_c']);

    // Compute DSN.
    list(/*$host*/, /*$port*/, $socket) = Civi\Setup\DbUtil::decodeHostPort(DB_HOST);
    $model->db = $model->cmsDb = array(
      'server' => $socket ? sprintf('unix(%s)', $socket) : DB_HOST,
      'username' => DB_USER,
      'password' => DB_PASSWORD,
      'database' => DB_NAME,
    );

    // Compute URLs
    $model->cmsBaseUrl = site_url();
    $model->paths['wp.frontend.base']['url'] = home_url() . '/';
    $model->paths['wp.backend.base']['url'] = admin_url();
    $model->mandatorySettings['userFrameworkResourceURL'] = plugin_dir_url($civicrmPluginFile) . 'civicrm';

    // Compute default locale.
    $langs = $model->getField('lang', 'options');
    $wpLang = get_locale();
    $model->lang = isset($langs[$wpLang]) ? $wpLang : 'en_US';
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'WordPress') {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Activate CiviCRM plugin', basename(__FILE__)));

    if (!function_exists('activate_plugin')) {
      require_once ABSPATH . 'wp-admin/includes/admin.php';
    }

    $plugin = _civicrm_wordpress_plugin_file();
    if (!is_plugin_active($plugin)) {
      activate_plugin($plugin);
    }
  }, \Civi\Setup::PRIORITY_MAIN - 100);

function _civicrm_wordpress_plugin_file(): string {
  return implode(DIRECTORY_SEPARATOR, [WP_PLUGIN_DIR, 'civicrm', 'civicrm.php']);
}
