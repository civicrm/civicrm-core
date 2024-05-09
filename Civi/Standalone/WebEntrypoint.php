<?php

namespace Civi\Standalone;

/**
 * Logic for loading web pages (would be in index.php, but here we can update it)
 */
class WebEntrypoint {

  /**
   * If we think Civi is installed, run the page
   *
   * Otherwise show the Web installer
   *
   * @see self::checkCiviInstalled
   */
  public static function index(): void {
    if (self::checkCiviInstalled()) {
      ErrorHandler::setHandler();
      self::invoke();
    }
    else {
      self::installer();
    }
  }

  /**
   * if CIVICRM_INSTALLED flag is set in settings, we think the database
   * *should* already be installed and we'll never show the installer
   *
   * if its not set, we check for presence of an existing database
   *
   * in general setting the flag is probably a good idea to never show the installer again
   * e.g. you dont want to show the installer if your database goes down
   * (especially as the standalone installer is permissionless - no cms user accounts! - and
   * may even know your database credentials if these are provided as env variables)
   */
  public static function checkCiviInstalled(): bool {
    if (defined('CIVICRM_INSTALLED')) {
      return !!CIVICRM_INSTALLED;
    }
    if (defined('CIVICRM_DSN')) {
      // can we autodetect a good value here?
    }
    return FALSE;
  }

  /**
   * Standalone specific wrapper for CRM_Core_Invoke
   * - ensures config container is booted
   * - handles the route args
   */
  public static function invoke(): void {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // initialise config and boot container
    \CRM_Core_Config::singleton();

    // Add CSS, JS, etc. that is required for this page.
    \CRM_Core_Resources::singleton()->addCoreResources();
    $parts = explode('?', $requestUri);
    $args = explode('/', $parts[0] ?? '');
    // Remove empty path segments, a//b becomes equivalent to a/b
    $args = array_values(array_filter($args));
    if (!$args) {
      // This is a request for the site's homepage. See if we have one.
      $item = \CRM_Core_Invoke::getItem('/');
      if (!$item) {
        // We have no public homepage, so send them to login.
        // This doesn't allow for /civicrm itself to be public,
        // but that's got to be a pretty edge case, right?!
        \CRM_Utils_System::redirect('/civicrm/login');
      }
    }
    // This IS required for compatibility. e.g. the extensions (at least) quickform uses it for the form's action attribute.
    $_GET['q'] = implode('/', $args);

    // Render the page
    print \CRM_Core_Invoke::invoke($args);
  }

  public static function installer(): void {
    \Civi\Setup::assertProtocolCompatibility(1.0);

    // the core folder is up two directories from this file
    // TODO: use AppSettings to get the configured core path
    $corePath = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, __DIR__), 0, -2));

    \Civi\Setup::init([
      // This is just enough information to get going.
      'cms'     => 'Standalone',
      'srcPath' => $corePath,
    ]);

    $coreUrl = \Civi\Setup::instance()->getModel()->mandatorySettings['userFrameworkResourceURL'];

    $ctrl = \Civi\Setup::instance()->createController()->getCtrl();
    $ctrl->setUrls([
      // The URL of this setup controller. May be used for POST-backs
      'ctrl'             => '/civicrm',
      // The base URL for loading resource files (images/javascripts) for this project. Includes trailing slash.
      'res'              => $coreUrl . '/setup/res/',
      'jquery.js'        => $coreUrl . '/bower_components/jquery/dist/jquery.min.js',
      'font-awesome.css' => $coreUrl . '/bower_components/font-awesome/css/font-awesome.min.css',
    ]);
    \Civi\Setup\BasicRunner::run($ctrl);
    exit();
  }

}
