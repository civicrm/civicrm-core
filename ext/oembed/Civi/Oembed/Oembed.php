<?php
namespace Civi\Oembed;

use Civi\Core\Service\AutoService;

/**
 * @service oembed
 */
class Oembed extends AutoService {

  private int $minPixels = 100;

  private int $maxPixels = 8000;

  private int $defaultWidth = 600;

  private int $defaultHeight = 400;

  public function create(string $path, array $query = [], array $options = []): array {
    $options = $this->normalizeOptions($options);
    $query = $this->findPropagatedParams($query);

    $route = \CRM_Core_Menu::get($path);
    $result = [
      'type' => 'rich',
      'version' => '1.0',
      'title' => $route['title'],
      // 'author_name' => 'Bees',
      // 'author_url' => 'http://www.flickr.com/photos/bees/',
      // 'cache_age' => 'xxx',
      'provider_name' => \CRM_Core_BAO_Domain::getDomain()->name,
      'provider_url' => \Civi::paths()->getUrl('[cms.root]/.', 'absolute'),
      'html' => sprintf('<iframe src="%s" width="%s" height="%d"></iframe>',
        \Civi::url("iframe://$path")->addQuery($query),
        $options['maxwidth'],
        $options['maxheight']
      ),
      'width' => $options['maxwidth'],
      'height' => $options['maxheight'],
    ];

    // Allow other components to alter values like `title` or `provider_name`.
    \Civi::dispatcher()->dispatch('hook_civicrm_oembed', \Civi\Core\Event\GenericHookEvent::create([
      'oembed' => &$result,
      'request' => [
        'route' => $route,
        'path' => $path,
        'query' => $query,
      ],
    ]));

    return $result;
  }

  /**
   * @param string $path
   * @param array $query
   * @param array $options
   *   Ex: ['maxwidth' => '102']
   * @return string
   */
  public function createLinkTags(string $path, array $query = [], array $options = []): string {
    $oembed = static::create($path, $query);
    $query = $this->findPropagatedParams($query);
    $url = \Civi::url('frontend://civicrm/oembed', 'a')->addQuery([
      'url' => (string) \Civi::url('frontend://' . $path, 'a')->addQuery($query),
    ]);
    $url->addQuery(\CRM_Utils_Array::subset($options, ['maxwidth', 'maxheight']));

    $buf = '';
    $buf .= sprintf("<link rel='alternate' type='application/json+oembed' href='%s' title='%s' />\n",
      (clone $url)->addQuery('format=json'),
      htmlentities($oembed['title'])
    );
    $buf .= sprintf("<link rel='alternate' type='text/xml+oembed' href='%s' title='%s' />\n",
      (clone $url)->addQuery('format=xml'),
      htmlentities($oembed['title'])
    );
    return $buf;
  }

  protected function normalizeOptions(array $options): array {
    $result = $options;
    $result['maxwidth'] = $this->applyPixelConstraint($result['maxwidth'] ?? $this->defaultWidth);
    $result['maxheight'] = $this->applyPixelConstraint($result['maxheight'] ?? $this->defaultHeight);
    return $result;
  }

  private function applyPixelConstraint($value): int {
    $value = \CRM_Utils_Type::validate($value, 'Integer');
    $value = max($value, $this->minPixels);
    $value = min($value, $this->maxPixels);
    return $value;
  }

  /**
   * Identify any query parameters that should be preserved/propagated to the equivalent oEmbed request.
   *
   * Note that support for this may vary by oEmbed client. When using <LINK>-based discovery,
   * some clients (eg WordPress) may interject with their preferred value of `url=XYZ`.
   */
  public function findPropagatedParams(array $query): array {
    $urlVar = \CRM_Core_Config::singleton()->userFrameworkURLVar;
    $result = [];
    foreach ($query as $key => $value) {
      if ($key[0] !== '_' && !in_array($key, [$urlVar])) {
        $result[$key] = $value;
      }
    }
    return $result;
  }

  /**
   * Do we permit embedding of this route?
   *
   * @param string $path
   * @return bool
   */
  public function isAllowedRoute(string $path): bool {
    return \Civi::service('iframe.router')->isAllowedRoute($path) && !preg_match(';^civicrm/(ajax|asset);', $path);
  }

  public function getDefaultWidth(): int {
    return $this->defaultWidth;
  }

  public function getDefaultHeight(): int {
    return $this->defaultHeight;
  }

}
