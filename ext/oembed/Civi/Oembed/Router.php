<?php
namespace Civi\Oembed;

use CRM_Oembed_ExtensionUtil as E;
use Civi\Core\Service\AutoService;

/**
 * @service oembed.router
 */
class Router extends AutoService {

  public function invoke(array $params) {
    if (!$this->isAllowedRoute($params['route'])) {
      throw new \CRM_Core_Exception("Route not available for embedding.");
    }

    $config = \CRM_Core_Config::singleton();
    $_GET[$config->userFrameworkURLVar] = $params['route'];

    $handler = [$this, 'invoke' . ucfirst($this->getChrome())];
    $handler($params);
  }

  public function isAllowedRoute(string $route): bool {
    return preg_match(';^civicrm/;', $route);
  }

  public function getChrome(): string {
    // return 'raw';
    return 'full';
  }

  protected function invokeRaw(array $params): void {
    // Based on Civi-D7 `civicrm_invoke()`

    ob_start();
    $pageContent = \CRM_Core_Invoke::invoke(explode('/', $params['route']));
    $printedContent = ob_get_clean();

    if (empty($pageContent) && !empty($printedContent)) {
      $pageContent = $printedContent;
    }
    echo $pageContent;
  }

  protected function invokeFull(array $params):void {
    switch (CIVICRM_UF) {
      case 'Drupal':
        \menu_execute_active_handler();
        break;

      case 'Drupal8':
        /** @var \Drupal\Core\DrupalKernel $kernel */
        $kernel = $params['drupalKernel'];
        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $params['drupalRequest'];

        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
        break;

      default:
        throw new \CRM_Core_Exception("Unimplemented: invokeCms(" . CIVICRM_UF . ")");
    }
  }

}
