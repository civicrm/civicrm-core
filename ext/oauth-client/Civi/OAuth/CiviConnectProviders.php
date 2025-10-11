<?php

namespace Civi\OAuth;

use Civi\Core\Service\AutoService;
use CRM_OAuth_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OAuth providers that are tagged as `CiviConnect` should have some special behaviors, e.g.
 *
 * - Inspect the setting `oauth_civi_connect_urls`.
 * - In the provider definition, replace `{civi_connect_url}` with a real URL.
 * - If the settings have enabled "sandbox" or "local" options, then also use those.
 *
 * @service oauth_client.civi_connect_providers
 */
class CiviConnectProviders extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_oauthProviders' => ['hook_civicrm_oauthProviders', -2000],
      '&hook_civicrm_initiators::PaymentProcessor' => ['pickDefaultPaymentInitiators', -2000],
    ];
  }

  /**
   * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_oauthProviders/
   */
  public function hook_civicrm_oauthProviders(array &$providers) {
    // Any providers tagged as `CiviConnect` are actually templates.
    $templates = array_filter($providers, fn($p) => in_array('CiviConnect', $p['tags'] ?? []));
    foreach (array_keys($templates) as $templateName) {
      unset($providers[$templateName]);
    }

    // For each of these templates, we'll fill-in data and point to the available CiviConnect server(s).
    $hosts = $this->getHosts();
    foreach ($templates as $templateName => $template) {
      foreach ($hosts as $host) {
        if (empty($host['url'])) {
          continue;
        }

        $vars = [
          '{civi_connect_url}' => $host['url'],
        ];
        $providerName = call_user_func($host['name()'], $templateName);
        $providers[$providerName] = $this->filterTree($template,
          fn($s) => strtr($s, $vars)
        );
        $providers[$providerName]['name'] = $providerName;
        $providers[$providerName]['title'] = call_user_func($host['title()'], $providers[$providerName]['title']);
        $providers[$providerName]['tags'] = preg_replace('/^CiviConnect$/', $host['tag'], $providers[$providerName]['tags']);
      }
    }

    ksort($providers);
  }

  /**
   * @return array
   */
  protected function getHosts(): array {
    $urlText = \Civi::settings()->get('oauth_civi_connect_urls');
    $urls = [];
    foreach (preg_split(';[ \r\n\t]+;', trim($urlText)) as $urlLine) {
      [$name, $url] = explode('=', $urlLine, 2);
      $urls[$name] = rtrim($url, '/');
    }

    $hosts = [];
    $hosts['live'] = [
      'url' => $urls['live'] ?? NULL,
      'name()' => fn($name) => $name,
      'title()' => fn($title) => $title,
      'tag' => 'CiviConnect',
    ];
    $hosts['sandbox'] = [
      'url' => $urls['sandbox'] ?? NULL,
      'name()' => fn($name) => $name . '_sandbox',
      'title()' => fn($title) => E::ts('%1 (Sandbox)', [1 => $title]),
      'tag' => 'CiviConnectSandbox',
    ];
    $hosts['local'] = [
      'url' => $urls['local'] ?? NULL,
      'name()' => fn($name) => $name . '_local',
      'title()' => fn($title) => E::ts('%1 (Local)', [1 => $title]),
      'tag' => 'CiviConnectLocal',
    ];

    return $hosts;
  }

  /**
   * Recursively apply a filter to all string-values in an array-tree.
   *
   * @param array $array
   * @param callable $filter
   * @return array
   */
  protected function filterTree(array $array, callable $filter): array {
    foreach ($array as &$item) {
      if (is_string($item)) {
        $item = $filter($item);
      }
      if (is_array($item)) {
        $item = $this->filterTree($item, $filter);
      }
    }
    return $array;
  }

  /**
   * In "Edit Payment Processors", there are options for different initiators. If they have
   * any of our tags ("CiviConnect", "CiviConnectSandbox", "CiviConnectLocal"), then use them
   * to help pick our defaults.
   *
   * - Payment Processors have two variants -- live and testing.
   * - CiviConnect providers can have two variants -- live and sandbox (and possibly local).
   * - There's an affinity for live<=>live and testing<=>sandbox.
   * - Or, if you've got local override, then that's generally preferred.
   *
   * @param array $context
   * @param array $available
   * @param $default
   *
   * @see \CRM_Utils_Hook::initiators()
   */
  public function pickDefaultPaymentInitiators(array $context, array &$available, &$default): void {
    if (empty($available) || $default !== NULL) {
      return;
    }

    $options = [];

    // Dumb guess: Pick the first
    foreach ($available as $key => $initiator) {
      $options[50] = $key;
      break;
    }

    // But it's better if we match by type (e.g. live<=>live, testing<=>sandbox).
    foreach ($available as $key => $initiator) {
      $envTag = array_values(array_intersect(
        ['CiviConnect', 'CiviConnectSandbox', 'CiviConnectLocal'],
        $initiator['tags'] ?? []
      ))[0] ?? NULL;
      if ($envTag === 'CiviConnect' && !$context['is_test']) {
        $options[20] = $key;
      }
      elseif ($envTag === 'CiviConnectSandbox' && $context['is_test']) {
        $options[20] = $key;
      }
      elseif ($envTag === 'CiviConnectLocal') {
        $options[10] = $key;
      }
    }

    ksort($options);
    $default = array_shift($options);
  }

  // The sub-step of reconciling mgd's seems quirky. If we can't make it automatic, then just leave if dumb for now.
  // public static function onChangeUrls() {
  //   \Civi::cache('long')->delete(\Civi\Api4\OAuthProvider::PROVIDERS_CACHE_KEY);
  //   \CRM_Core_ManagedEntities::singleton()->reconcile([E::LONG_NAME]);
  // }

}
