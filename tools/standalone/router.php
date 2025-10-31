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

if (PHP_SAPI !== 'cli-server') {
  http_response_code(403);
  die("Forbidden");
}

/**
 * The StandaloneRouter allows you to run CiviCRM's "Standalone" UF with the PHP built-in server.
 * It is intended for local development.
 *
 * Ex: php -S localhost:8000 -t srv/web/ tools/standalone/router.php
 */
class StandaloneRouter {

  private const ALLOW_VIRTUAL_FILES = ';\.(jpg|png|css|js|html|txt|json|yml|xml|md|woff2)$;';

  private $routes = [];

  public function __construct() {
    // Note: Routing rules are processed sequentially, until one handles the request.

    // The above would be prettier in php74's `fn()` notation.

    // Redirect common entry points
    $this->addRoute(';^/$;', function($m) {
      return $this->sendRedirect('/civicrm/');
    });

    $this->addRoute(';^/civicrm$;', function($m) {
      return $this->sendRedirect('/civicrm/');
    });

    // If it looks like a Civi route, then call CRM_Core_Invoke.
    $this->addRoute(';^/(civicrm/.*)$;', function($m) {
      return $this->invoke($m[1]);
    });

    // If there's a concrete file in HTTP root (`web/`), then serve that.
    $this->addRoute(';/(.*);', function($m) {
      $file = $this->findFile($_SERVER['DOCUMENT_ROOT'], $m[1]);
      return ($file === NULL) ? FALSE : $this->sendDirect();
    });

    // Virtually mount civicrm-{core,packages}. This allows us to serve their static assets directly (even on systems that lack symlinks).

    $this->addRoute(';^/core/packages/(.*);', function($m) {
      return $this->sendFileFromFolder($this->findPackages(), $m[1]);
    });
    $this->addRoute(';^/core/vendor/(.*);', function($m) {
      return $this->sendFileFromFolder($this->findVendor(), $m[1]);
    });
    $this->addRoute(';^/core/(.*);', function($m) {
      return $this->sendFileFromFolder($this->findCore(), $m[1]);
    });

    // $this->addRoute(';^/core/packages/(.*);', fn($m) => $this->sendFileFromFolder($this->findPackages(), $m[1]));
    // $this->addRoute(';^/core/vendor/(.*);', fn($m) => $this->sendFileFromFolder($this->findVendor(), $m[1]));
    // $this->addRoute(';^/core/(.*);', fn($m) => $this->sendFileFromFolder($this->findCore(), $m[1]));

    // $this->addRoute(';^/civicrm-packages/(.*);', fn($m) => $this->sendFileFromFolder($this->findPackages(), $m[1]));
    // $this->addRoute(';^/civicrm-core/(.*);', fn($m) => $this->sendFileFromFolder($this->findCore(), $m[1]));
    //
    // $this->addRoute(';^/assets/civicrm/core/(.*);', fn($m) => $this->sendFileFromFolder($this->findPackages(), $m[1]));
    // $this->addRoute(';^/assets/civicrm/packages/(.*);', fn($m) => $this->sendFileFromFolder($this->findCore(), $m[1]));

    // TODO: Consider allowing CRM_Core_Invoke to handle any route. May affect UF interop.
  }

  /**
   * Receive a request through the PHP built-in HTTP server. Decide how to process it.
   *
   * @link https://www.php.net/manual/en/features.commandline.webserver.php
   * @return bool
   *   TRUE if the request has been handled.
   *   FALSE if the request has not been handled.
   */
  public function main(): bool {
    $url = parse_url($_SERVER['REQUEST_URI']);
    foreach ($this->routes as $route) {
      if (preg_match($route['regex'], $url['path'], $matches)) {
        $handled = call_user_func($route['handler'], $matches);
        if ($handled === TRUE) {
          return TRUE;
        }
        if ($handled === '*SEND-DIRECT*') {
          return FALSE;
        }
      }
    }
    return $this->sendError(404, "Not found");
  }

  /**
   * Register another route.
   *
   * @param string $regex
   *   Regular expression to run against the request-path.
   *   Ex: ';^/foobar/(.*);'
   * @param callable $handler
   *   Function to call when routing the match. Receives the regex-matches as input.
   *   Ex: fn($m) => $this->sendError(500, 'This path is foobared: ' . $m[1]);
   *   The handler should return TRUE (if handled), FALSE (if skipped), or selected string constants.
   * @return void
   */
  public function addRoute(string $regex, callable $handler): void {
    $this->routes[] = [
      'regex' => $regex,
      'handler' => $handler,
    ];
  }

  /**
   * Invoke a civicrm route.
   *
   * @param string $path
   *   Ex: 'civicrm/admin/foobar'
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function invoke(string $path): bool {
    // Do we need this?
    $_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';
    $_SERVER['SCRIPT_NAME'] = DIRECTORY_SEPARATOR . 'index.php';
    $_SERVER['PHP_SELF'] = DIRECTORY_SEPARATOR . 'index.php';

    // echo "Invoke route: " . htmlentities($path) . "<br>";

    // require_once $this->findVendor() . '/autoload.php';
    // require_once 'CRM/Core/ClassLoader.php';
    // CRM_Core_ClassLoader::singleton()->register();
    $settingsPhp = $this->findSettingsPhp();
    if (!file_exists($settingsPhp)) {
      return $this->runSetup();
    }

    require_once $settingsPhp;

    // Required so that the userID is set before generating the menu
    \CRM_Core_Session::singleton()->initialize();
    // Add CSS, JS, etc. that is required for this page.
    \CRM_Core_Resources::singleton()->addCoreResources();

    $args = explode('/', $path);
    // Remove empty values
    $args = array_values(array_filter($args));
    // Set this for compatibility
    $_GET['q'] = implode('/', $args);
    // And finally render the page
    print CRM_Core_Invoke::invoke($args);

    return TRUE;
  }

  public function runSetup(): bool {
    $classLoader = implode(DIRECTORY_SEPARATOR, [$this->findCore(), 'CRM', 'Core', 'ClassLoader.php']);
    require_once $classLoader;
    CRM_Core_ClassLoader::singleton()->register();

    $coreUrl = '/core';

    \Civi\Setup::assertProtocolCompatibility(1.0);

    \Civi\Setup::init([
      // This is just enough information to get going.
      'cms'     => 'Standalone',
      'srcPath' => $this->findCore(),
    ]);
    $ctrl = \Civi\Setup::instance()->createController()->getCtrl();

    $ctrl->setUrls([
      // The URL of this setup controller. May be used for POST-backs
      'ctrl'             => '/civicrm', /* @todo this had url('civicrm') ? */
      // The base URL for loading resource files (images/javascripts) for this project. Includes trailing slash.
      'res'              => $coreUrl . '/setup/res/',
      'jquery.js'        => $coreUrl . '/bower_components/jquery/dist/jquery.min.js',
      'font-awesome.css' => $coreUrl . '/bower_components/font-awesome/css/all.min.css',
    ]);
    \Civi\Setup\BasicRunner::run($ctrl);
    exit();

  }

  public function sendRedirect($path) {
    header('Location: ' . $path);
    return TRUE;
  }

  public function sendFileFromFolder(string $basePath, string $relPath): bool {
    if (!preg_match(static::ALLOW_VIRTUAL_FILES, $relPath)) {
      return $this->sendError(403, "File type not allowed");
    }

    $absFile = $this->findFile($basePath, $relPath);
    if ($absFile === NULL) {
      return $this->sendError(404, "File not found");
    }

    require_once $this->findVendor() . '/autoload.php';

    $info = new SplFileInfo($absFile);
    $mimeRepository = new \MimeTyper\Repository\MimeDbRepository();
    header('Content-Type: ' . $mimeRepository->findType($info->getExtension()));
    header('Content-Length: ' . $info->getSize());
    readfile($absFile, FALSE);
    return TRUE;
  }

  public function sendDirect(): string {
    return '*SEND-DIRECT*';
  }

  public function sendError(int $code, string $message): bool {
    http_response_code($code);
    printf("<h1>HTTP %s: %s</h1>", $code, htmlentities($message));
    return TRUE;
  }

  public function findCore(): string {
    return dirname(__DIR__, 2);
  }

  public function findVendor(): string {
    return $this->findCore() . '/vendor';
  }

  public function findPackages(): string {
    $core = $this->findCore();
    if (file_exists($core . '/packages')) {
      return $core . '/packages';
    }
    if (file_exists(dirname($core) . '/civicrm-packages')) {
      return dirname($core) . '/civicrm-packages';
    }
    throw new \RuntimeException("Failed to find civicrm-packages");
  }

  public function findSettingsPhp(): string {
    return dirname($_SERVER['DOCUMENT_ROOT']) . '/data/civicrm.settings.php';
  }

  /**
   * @param string $basePath
   * @param string $relPath
   * @return string|null
   *   If file exists, then return the combined (absolute) path.
   *   If file does not exist, then return NULL.
   */
  private function findFile(string $basePath, string $relPath): ?string {
    $realBase = realpath($basePath);
    $realRel = realpath($basePath . '/' . $relPath);
    if ($realBase && $realRel && str_starts_with($realRel, $realBase)) {
      return $basePath . '/' . $relPath;
    }
    return NULL;
  }

}

return (new StandaloneRouter())->main();
