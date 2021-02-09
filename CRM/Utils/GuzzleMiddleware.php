<?php

use Psr\Http\Message\RequestInterface;

/**
 * Additional helpers/utilities for use as Guzzle middleware.
 */
class CRM_Utils_GuzzleMiddleware {

  /**
   * Add this as a Guzzle handler/middleware if you wish to simplify
   * the construction of Civi-related URLs. It enables URL schemes for:
   *
   * - route://ROUTE_NAME (aka) route:ROUTE_NAME
   * - var://PATH_EXPRESSION (aka) var:PATH_EXPRESSION
   * - ext://EXTENSION/FILE (aka) ext:EXTENSION/FILE
   * - assetBuilder://ASSET_NAME?PARAMS (aka) assetBuilder:ASSET_NAME?PARAMS
   *
   * Compare:
   *
   * $http->get(CRM_Utils_System::url('civicrm/dashboard', NULL, TRUE, NULL, FALSE))
   * $http->get('route:civicrm/dashboard')
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
        $newBase .= strpos($newBase, '?') !== FALSE ? '&' : '?';
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

      case 'route':
        // Ex: 'route:civicrm/my-page' or 'route://civicrm/my-page'
        return $copyParams(\CRM_Utils_System::url($hostPath, NULL, TRUE, NULL, FALSE));

      default:
        return NULL;
    }
  }

}
