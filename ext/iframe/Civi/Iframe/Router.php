<?php
namespace Civi\Iframe;

use CRM_Iframe_ExtensionUtil as E;
use Civi\Core\Service\AutoService;

/**
 * @service iframe.router
 */
class Router extends AutoService {

  public function invoke(array $params) {
    if (!$this->isAllowedRoute($params['route'])) {
      throw new \CRM_Core_Exception("Route not available for embedding.");
    }

    $config = \CRM_Core_Config::singleton();
    $_GET[$config->userFrameworkURLVar] = $params['route'];

    $handler = $this->getHandler();
    $handler($params);
  }

  public function isAllowedRoute(string $route): bool {
    if (!preg_match(';^civicrm/;', $route)) {
      return FALSE;
    }

    $flags = \Civi::settings()->get('iframe_allow');
    if (in_array('ajax', $flags) && preg_match(';^civicrm/ajax/;', $route)) {
      return TRUE;
    }
    if (in_array('public', $flags) && \CRM_Core_Menu::isPublicRoute($route)) {
      return TRUE;
    }

    $list = explode("\n", \Civi::settings()->get('iframe_allow_other'));
    \Civi::dispatcher()->dispatch('civi.iframe.allowRoutes', \Civi\Core\Event\GenericHookEvent::create([
      'routes' => &$list,
    ]));
    $matches = \CRM_Utils_String::filterByWildcards($list, [$route]);
    if (!empty($matches)) {
      return TRUE;
    }

    return FALSE;
  }

  protected function getHandler(): callable {
    $setting = \Civi::settings()->get('iframe_layout');
    if ($setting === 'auto') {
      $setting = 'basic';
    }
    return [$this, 'invoke' . ucfirst($setting)];
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

  /**
   * Execute and display the requested route. Apply basic formatting.
   *
   * This means that CMS navigation and theming are disabled. However,
   * HTML <HEAD> and Civi theming are enabled.
   *
   * @param array $params
   *   Open-ended parameters provided by the entry-script (`iframe.php`).
   * @throws \CRM_Core_Exception
   */
  protected function invokeBasic(array $params): void {
    \CRM_Core_Resources::singleton()->addCoreResources('html-header');

    ob_start();
    $pageContent = \CRM_Core_Invoke::invoke(explode('/', $params['route']));
    $printedContent = ob_get_clean();
    if (empty($pageContent) && !empty($printedContent)) {
      $pageContent = $printedContent;
    }

    $htmlHeader = \CRM_Core_Region::instance('html-header')->render('');
    $locale = \CRM_Core_I18n::getLocale();

    echo \CRM_Core_Smarty::singleton()->fetchWith('iframe-basic-page.tpl', [
      'lang' => substr($locale, 0, 2),
      'dir' => \CRM_Core_I18n::isLanguageRTL($locale) ? 'rtl' : 'ltr',
      'head' => $htmlHeader,
      'body' => $pageContent,
    ]);
  }

  protected function invokeCms(array $params):void {
    switch (CIVICRM_UF) {
      case 'Drupal':
        \CRM_Core_Resources::singleton()->addCoreResources();
        // FIXME: ^^^ should be handled by civicrm_html_head(), but the arg(0) guard misfires.

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
