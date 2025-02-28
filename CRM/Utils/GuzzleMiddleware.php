<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Additional helpers/utilities for use as Guzzle middleware.
 */
class CRM_Utils_GuzzleMiddleware {

  /**
   * The authx middleware sends authenticated requests via JWT.
   *
   * To add an authentication token to a specific request, the `$options`
   * must specify `authx_user` or `authx_contact_id`. Examples:
   *
   * $http = new GuzzleHttp\Client(['authx_user' => 'admin']);
   * $http->post('civicrm/admin', ['authx_user' => 'admin']);
   *
   * Supported options:
   *   - authx_ttl (int): Seconds of validity for JWT's
   *   - authx_host (string): Only send tokens for the given host.
   *   - authx_contact_id (int): The CiviCRM contact to authenticate with
   *   - authx_user (string): The CMS user to authenticate with
   *   - authx_flow (string): How to format the auth token. One of: 'param', 'xheader', 'header'.
   *
   * @return \Closure
   */
  public static function authx($defaults = []) {
    $defaults = array_merge([
      'authx_ttl' => 60,
      'authx_host' => parse_url(CIVICRM_UF_BASEURL, PHP_URL_HOST),
      'authx_contact_id' => NULL,
      'authx_user' => NULL,
      'authx_flow' => 'param',
    ], $defaults);
    return function(callable $handler) use ($defaults) {
      return function (RequestInterface $request, array $options) use ($handler, $defaults) {
        if ($request->getUri()->getHost() !== $defaults['authx_host']) {
          return $handler($request, $options);
        }

        $options = array_merge($defaults, $options);
        if (!empty($options['authx_contact_id'])) {
          $cid = $options['authx_contact_id'];
        }
        elseif (!empty($options['authx_user'])) {
          $r = civicrm_api3("Contact", "get", ["id" => "@user:" . $options['authx_user']]);
          foreach ($r['values'] as $id => $value) {
            $cid = $id;
            break;
          }
          if (empty($cid)) {
            throw new \RuntimeException("Failed to identify user ({$options['authx_user']})");
          }
        }
        else {
          $cid = NULL;
        }

        if ($cid) {
          if (!CRM_Extension_System::singleton()->getMapper()->isActiveModule('authx')) {
            throw new \RuntimeException("Authx is not enabled. Authenticated requests will not work.");
          }
          $tok = \Civi::service('crypto.jwt')->encode([
            'exp' => time() + $options['authx_ttl'],
            'sub' => 'cid:' . $cid,
            'scope' => 'authx',
          ]);

          switch ($options['authx_flow']) {
            case 'header':
              $request = $request->withHeader('Authorization', "Bearer $tok");
              break;

            case 'xheader':
              $request = $request->withHeader('X-Civi-Auth', "Bearer $tok");
              break;

            case 'param':
              if ($request->getMethod() === 'POST') {
                if (!empty($request->getHeader('Content-Type')) && !preg_grep(';application/x-www-form-urlencoded;', $request->getHeader('Content-Type'))) {
                  throw new \RuntimeException("Cannot append authentication credentials to HTTP POST. Unrecognized content type.");
                }
                $query = (string) $request->getBody();
                $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
                $request = new GuzzleHttp\Psr7\Request(
                  $request->getMethod(),
                  $request->getUri(),
                  $request->getHeaders(),
                  http_build_query(['_authx' => "Bearer $tok"]) . ($query ? '&' : '') . $query
                );
              }
              else {
                $query = $request->getUri()->getQuery();
                $request = $request->withUri($request->getUri()->withQuery(
                  http_build_query(['_authx' => "Bearer $tok"]) . ($query ? '&' : '') . $query
                ));
              }
              break;

            default:
              throw new \RuntimeException("Unrecognized authx flow: {$options['authx_flow']}");
          }
        }
        return $handler($request, $options);
      };
    };
  }

  /**
   * Add this as a Guzzle handler/middleware if you wish to simplify
   * the construction of Civi-related URLs. It enables URL schemes for:
   *
   * - route://ROUTE_NAME (aka) route:ROUTE_NAME
   * - backend://ROUTE_NAME (aka) backend:ROUTE_NAME
   * - frontend://ROUTE_NAME (aka) frontend:ROUTE_NAME
   * - var://PATH_EXPRESSION (aka) var:PATH_EXPRESSION
   * - ext://EXTENSION/FILE (aka) ext:EXTENSION/FILE
   * - assetBuilder://ASSET_NAME?PARAMS (aka) assetBuilder:ASSET_NAME?PARAMS
   *
   * Compare:
   *
   * $http->get(CRM_Utils_System::url('civicrm/dashboard', NULL, TRUE, NULL, FALSE, ??))
   * $http->get('route://civicrm/dashboard')
   * $http->get('frontend://civicrm/dashboard')
   * $http->get('backend://civicrm/dashboard')
   *
   * $http->get(Civi::paths()->getUrl('[civicrm.files]/foo.txt'))
   * $http->get('var:[civicrm.files]/foo.txt')
   *
   * $http->get(Civi::resources()->getUrl('my.other.ext', 'foo.js'))
   * $http->get('ext:my.other.ext/foo.js')
   *
   * $http->get(Civi::service('asset_builder')->getUrl('my-asset.css', ['a'=>1, 'b'=>2]))
   * $http->get('assetBuilder:my-asset.css?a=1&b=2')
   *
   * Note: To further simplify URL expressions, Guzzle allows you to set a 'base_uri'
   * option (which is applied as a prefix to any relative URLs). Consider using
   * `base_uri=auto:`. This allows you to implicitly use the most common types
   * (routes+variables):
   *
   * $http->get('civicrm/dashboard')
   * $http->get('[civicrm.files]/foo.txt')
   *
   * @return \Closure
   */
  public static function url() {
    return function(callable $handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        $newUri = self::filterUri($request->getUri());
        if ($newUri !== NULL) {
          $request = $request->withUri(\CRM_Utils_Url::parseUrl($newUri));
        }

        return $handler($request, $options);
      };
    };
  }

  /**
   * @param \Psr\Http\Message\UriInterface $oldUri
   *
   * @return string|null
   *   The string formation of the new URL, or NULL for unchanged URLs.
   */
  protected static function filterUri(\Psr\Http\Message\UriInterface $oldUri) {
    // Copy the old ?query-params and #fragment-params on top of $newBase.
    $copyParams = function ($newBase) use ($oldUri) {
      if ($oldUri->getQuery()) {
        $newBase .= str_contains($newBase, '?') ? '&' : '?';
        $newBase .= $oldUri->getQuery();
      }
      if ($oldUri->getFragment()) {
        $newBase .= '#' . $oldUri->getFragment();
      }
      return $newBase;
    };

    $hostPath = urldecode($oldUri->getHost() . $oldUri->getPath());
    $scheme = $oldUri->getScheme();
    if ($scheme === 'auto') {
      // Ex: 'auto:civicrm/my-page' ==> Router
      // Ex: 'auto:[civicrm.root]/js/foo.js' ==> Resource file
      $scheme = ($hostPath[0] === '[') ? 'var' : 'route';
    }

    if ($scheme === 'route') {
      $menu = CRM_Core_Menu::get($hostPath);
      $scheme = ($menu && !empty($menu['is_public'])) ? 'frontend' : 'backend';
    }

    switch ($scheme) {
      case 'assetBuilder':
        // Ex: 'assetBuilder:dynamic.css' or 'assetBuilder://dynamic.css?foo=bar'
        // Note: It's more useful to pass params to the asset-builder than to the final HTTP request.
        $assetParams = [];
        parse_str('' . $oldUri->getQuery(), $assetParams);
        return \Civi::service('asset_builder')->getUrl($hostPath, $assetParams);

      case 'ext':
        // Ex: 'ext:other.ext.name/file.js' or 'ext://other.ext.name/file.js'
        [$ext, $file] = explode('/', $hostPath, 2);
        return $copyParams(\Civi::resources()->getUrl($ext, $file));

      case 'var':
        // Ex: 'var:[civicrm.files]/foo.txt' or  'var://[civicrm.files]/foo.txt'
        return $copyParams(\Civi::paths()->getUrl($hostPath, 'absolute'));

      case 'backend':
        // Ex: 'backend:civicrm/my-page' or 'backend://civicrm/my-page'
        return $copyParams(\CRM_Utils_System::url($hostPath, NULL, TRUE, NULL, FALSE));

      case 'frontend':
        // Ex: 'frontend:civicrm/my-page' or 'frontend://civicrm/my-page'
        return $copyParams(\CRM_Utils_System::url($hostPath, NULL, TRUE, NULL, FALSE, TRUE));

      default:
        return NULL;
    }
  }

  /**
   * This logs the list of outgoing requests in curl format.
   */
  public static function curlLog(\Psr\Log\LoggerInterface $logger) {

    $curlFmt = new class() extends \GuzzleHttp\MessageFormatter {

      public function format(RequestInterface $request, ?ResponseInterface $response = NULL, ?\Throwable $error = NULL): string {
        $cmd = '$ curl';
        if ($request->getMethod() !== 'GET') {
          $cmd .= ' -X ' . escapeshellarg($request->getMethod());
        }
        foreach ($request->getHeaders() as $header => $lines) {
          foreach ($lines as $line) {
            $cmd .= ' -H ' . escapeshellarg("$header: $line");
          }
        }
        $body = (string) $request->getBody();
        if ($body !== '') {
          $cmd .= ' -d ' . escapeshellarg($body);
        }
        $cmd .= ' ' . escapeshellarg((string) $request->getUri());
        return $cmd;
      }

    };

    return \GuzzleHttp\Middleware::log($logger, $curlFmt);
  }

}
