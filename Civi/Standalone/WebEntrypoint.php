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
   * Determine whether to display the web-based installer.
   *
   * If `civicrm.settings.php` (CIVICRM_SETTINGS_PATH) has been loaded, then the database
   * *should* already be installed, and we won't re-run the installer.
   *
   * It is probably a good idea to disallow re-installation here.
   * e.g. you dont want to show the installer if your database goes down
   * (especially as the standalone installer is permissionless - no cms user accounts! - and
   * may even know your database credentials if these are provided as env variables)
   */
  private static function checkCiviInstalled(): bool {
    return defined('CIVICRM_SETTINGS_PATH');
  }

  /**
   * Standalone specific wrapper for CRM_Core_Invoke
   * - ensures config container is booted
   * - handles the route args
   */
  public static function invoke(): void {
    // parse the request uri (should we use URL for this?)
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $parts = explode('?', $requestUri);
    $args = explode('/', $parts[0] ?? '');
    // Remove empty path segments, a//b becomes equivalent to a/b
    $args = array_values(array_filter($args));

    // if request is for any path that doesn't start civicrm,
    // throw 404 before we waste any effort doing anything else
    // (may well be spam)
    if ($args && ($args[0] !== 'civicrm')) {
      http_response_code(404);
      print 'Path not found';
      exit();
    }

    // initialise config and boot container
    \CRM_Core_Config::singleton();

    // Add CSS, JS, etc. that is required for this page.
    \CRM_Core_Resources::singleton()->addCoreResources();
    if (!$args) {
      // This is a request for the site's homepage. See if we have one.

      $homepage = \CRM_Core_Invoke::getItem('civicrm/home') ? '/civicrm/home' : NULL;

      if ($homepage) {
        \CRM_Utils_System::redirect($homepage);
      }
      else {
        // We have no public homepage, so send them to login.
        // This doesn't allow for /civicrm itself to be public,
        // but that's got to be a pretty edge case, right?!
        \CRM_Utils_System::redirect('/civicrm/login');
      }
    }
    // This is required for compatibility. e.g. the extensions (at least) quickform uses it for the form's action attribute.
    $_GET['q'] = implode('/', $args);

    // Render the page
    print \CRM_Core_Invoke::invoke($args);
  }

  public static function installer(): void {
    \Civi\Setup::assertProtocolCompatibility('1.0');

    // the core folder is up two directories from this file
    // TODO: use AppSettings to get the configured core path
    $corePath = dirname(__DIR__, 2);

    \Civi\Setup::init([
      // This is just enough information to get going.
      'cms'     => 'Standalone',
      'srcPath' => $corePath,
    ]);

    $coreUrl = \Civi\Setup::instance()->getModel()->paths['civicrm.root']['url'];

    $ctrl = \Civi\Setup::instance()->createController()->getCtrl();
    $ctrl->setUrls([
      // The URL of this setup controller. May be used for POST-backs
      'ctrl'             => '/civicrm',
      // The base URL for loading resource files (images/javascripts) for this project. Includes trailing slash.
      'res'              => $coreUrl . '/setup/res/',
      'jquery.js'        => $coreUrl . '/bower_components/jquery/dist/jquery.min.js',
      'font-awesome.css' => $coreUrl . '/bower_components/font-awesome/css/all.min.css',
    ]);
    \Civi\Setup\BasicRunner::run($ctrl);
    exit();
  }

}
