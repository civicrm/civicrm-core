<?php

namespace Civi\Core;

use Civi\Core\Event\GenericHookEvent;

/**
 * Generate a URL.
 *
 * As input, this class takes a *logical URI*, which may include a range of configurable sub-parts (path, query, fragment, etc).
 *
 * As output, it provides a *concrete URL* that can be used by a web-browser to make requests.
 *
 * The typical way to construct a URL object is through `Civi::url()`, which features more
 * documentation and examples.
 *
 * This class-model has several properties. Most properties follow one of two patterns:
 *
 *   - URL components (such as `path`, `query`, `fragment`, `fragmentQuery`).
 *     These have getter/setter/adder methods. They are stored as raw URL substrings.
 *   - Smart flags (such as `preferFormat`, `ssl`, `cacheCode`).
 *     These have getter/setter methods. They are stored as simple types (booleans or strings).
 *     They also have aliases via `__construct(...$flags)` and `useFlags($flags)`
 *
 * URI components (`path`, `query`, etc) can be understood as raw-strings or data-arrays. Compare:
 *
 *  - "Path":           "civicrm/foo+bar/whiz+bang" vs ['civicrm', 'foo bar', 'whiz bang']
 *  - "Query:           "a=100&b=Hello+world" vs ["a" => 100, "b" => "Hello world"]
 *  - "Fragment":       "#/mailing/new" vs ["/mailing", "/new"]
 *  - "Fragment Query": "angularDebug=1" vs ["angularDebug" => 1]
 *
 * The raw-string is supported from all angles (storage+getters+setters+adders).
 * Additionally, the setters+adders accept arrays.
 *
 * This cl
 *
 * @see \Civi::url()
 */
final class Url implements \JsonSerializable {

  /**
   * @var string
   *   Ex: 'frontend', 'backend'
   */
  private $scheme;

  /**
   * NOTE: In most schemes, this field will be ignored.
   * It is only handled for HTTP/HTTPS. I haven't looked through
   * the edge-cases of mixing "host"ed vs non-"host"ed URLs. If
   * you think through those edges and want to expose
   * more helpers, cool.
   *
   * @var string
   */
  private $host;

  /**
   * NOTE: In most schemes, this field will be ignored.
   * It is only handled for HTTP/HTTPS. I haven't looked through
   * the edge-cases of mixing "host"ed vs non-"host"ed URLs. If
   * you think through those edges and want to expose
   * more helpers, cool.
   *
   * @var string
   */
  private $port;

  /**
   * @var string
   *   Ex: 'civicrm/dashboard'
   */
  private $path;

  /**
   * @var string
   *   Ex: abc=123&xyz=456
   */
  private $query;

  /**
   * @var string|null
   */
  private $fragment;

  /**
   * @var string|null
   */
  private $fragmentQuery;

  /**
   * Whether to auto-append the cache-busting resource code.
   *
   * @var bool|null
   *    NULL definition TBD (either "off" or "automatic"?)
   */
  private $cacheCode;

  /**
   * Preferred format.
   *
   * Note that this is not strictly guaranteed. It may sometimes return absolute URLs even if you
   * prefer relative URLs (e.g. if there's no easy/correct way to form a relative URL).
   *
   * @var string|null
   *   'relative' or 'absolute'
   *   NULL means "decide automatically"
   */
  private $preferFormat;

  /**
   * Whether to HTML-encode the output.
   *
   * Note: Why does this exist? It's insane, IMHO. There's nothing intrinsically HTML-y about URLs.
   * However, practically speaking, this class aims to replace `CRM_Utils_System::url()` which
   * performed HTML encoding by default. Retaining some easy variant of this flag should make the
   * off-ramp a bit smoother.
   *
   * @var bool
   */
  private $htmlEscape = FALSE;

  /**
   * @var bool|null
   *    NULL means "decide automatically"
   */
  private $ssl = NULL;

  /**
   * List of values to mix-in to the final/rendered URL.
   *
   * @var string[]|null
   */
  private $vars;

  /**
   * Define a dynamic lookup for variables.
   *
   * @var callable|null
   */
  private $varsCallback;

  /**
   * @param string|null $logicalUri
   * @param string|null $flags
   * @see \Civi::url()
   */
  public function __construct(?string $logicalUri = NULL, ?string $flags = NULL) {
    if ($logicalUri !== NULL) {
      $this->useUri($logicalUri);
    }
    if ($flags !== NULL) {
      $this->useFlags($flags);
    }
  }

  /**
   * Parse a logical URI.
   *
   * @param string $logicalUri
   * @return void
   */
  protected function useUri(string $logicalUri): void {
    if ($logicalUri[0] === '/') {
      // Scheme-relative path implies a preferences to inherit current scheme.
      $logicalUri = 'current:' . $logicalUri;
    }
    elseif ($logicalUri[0] === '[') {
      $logicalUri = 'asset://' . $logicalUri;
    }
    // else: Should we fill in scheme when there is NO indicator (eg $logicalUri===`civicrm/event/info')?
    // It could be a little annoying to write `frontend://` everywhere. It's not hard to add this.
    // But it's ambiguous whether `current://` or `default://` is the better interpretation.
    // I'd sooner vote for something explicit but short -- eg aliases (f<=>frontend; d<=>default)
    //   - `Civi::url('f://civicrm/event/info')`
    //   - `Civi::url('civicrm/event/info', 'f')`.

    $parsed = parse_url($logicalUri);
    if ($parsed === FALSE && preg_match(';^(\w+)://$;', $logicalUri, $m)) {
      $parsed = ['scheme' => $m[1], 'path' => ''];
    }
    $this->setScheme($parsed['scheme'] ?? '');
    if (in_array($this->scheme, ['http', 'https'])) {
      $this->host = $parsed['host'];
      $this->port = $parsed['port'] ?? '';
      $this->path = $parsed['path'] ?? '';
    }
    else {
      $this->path = $parsed['host'] ?? '';
      if (isset($parsed['path'])) {
        $this->path .= $parsed['path'];
      }
    }
    $this->query = $parsed['query'] ?? '';
    $fragmentParts = isset($parsed['fragment']) ? explode('?', $parsed['fragment'], 2) : [];
    $this->fragment = $fragmentParts[0] ?? '';
    $this->fragmentQuery = $fragmentParts[1] ?? '';
  }

  /**
   * Take another URL. Add its components into the components of this URL.
   *
   * @param \Civi\Core\Url $other
   * @param string[] $parts
   *   Ex: ['path', 'query', 'fragment', 'fragmentQuery', 'flags']
   *
   * @return $this
   */
  public function merge(Url $other, array $parts) {
    foreach ($parts as $part) {
      switch ($part) {
        case 'path':
          $this->addPath($other->getPath());
          break;

        case 'query':
          $this->addQuery($other->getQuery());
          break;

        case 'fragment':
          $this->addFragment($other->getFragment());
          break;

        case 'fragmentQuery':
          $this->addFragmentQuery($other->getFragmentQuery());
          break;

        case 'flags':
          if ($other->ssl !== NULL) {
            $this->setSsl($other->getSsl());
          }
          if ($other->cacheCode !== NULL) {
            $this->setCacheCode($other->getCacheCode());
          }
          if ($other->preferFormat !== NULL) {
            $this->setPreferFormat($other->getPreferFormat());
          }
          break;

        default:
          throw new \CRM_Core_Exception("Unrecognized URL merge flag: $part");
      }
    }
    return $this;
  }

  /**
   * @return string
   *   Ex: 'frontend' or 'backend'
   */
  public function getScheme() {
    return $this->scheme;
  }

  /**
   * @param string|null $scheme
   *   Ex: 'frontend' or 'backend'
   */
  public function setScheme(?string $scheme): Url {
    $this->scheme = $scheme;
    if ($scheme === 'https') {
      $this->setSsl(TRUE);
    }
    return $this;
  }

  /**
   * @return string|null
   *   Ex: 'civicrm/event/info'
   *   Ex: 'civicrm/hello+world%3F'
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * @param string|string[]|null $path
   *   Ex: 'civicrm/event/info'
   *   Ex: 'civicrm/hello+world%3F'
   *   Ex: ['civicrm', 'hello world?']
   */
  public function setPath($path): Url {
    $this->path = static::encodePath($path);
    return $this;
  }

  /**
   * Add new sections to the path.
   *
   * When adding new parts to the path, there is an implicit delimiter ('/') between parts.
   *
   * @param string|string[] $path
   *   Ex: 'civicrm/event/info'
   *   Ex: 'civicrm/hello+world%3F'
   *   Ex: ['civicrm', 'hello world?']
   * @return $this
   */
  public function addPath($path): Url {
    static::appendString($this->path, '/', static::encodePath($path));
    return $this;
  }

  /**
   * @return string|null
   *   Ex: 'name=John+Doughnut&id=9'
   */
  public function getQuery(): ?string {
    return $this->query;
  }

  /**
   * @param string|string[]|null $query
   *   Ex: 'name=John+Doughnut&id=9'
   *   Ex: ['name' => 'John Doughnut', 'id' => 9]
   * @return $this
   */
  public function setQuery($query): Url {
    if (is_array($query)) {
      $query = \CRM_Utils_System::makeQueryString($query);
    }
    $this->query = $query;
    return $this;
  }

  /**
   * @param string|string[] $query
   *   Ex: 'name=John+Doughnut&id=9'
   *   Ex: ['name' => 'John Doughnut', 'id' => 9]
   * @return $this
   */
  public function addQuery($query): Url {
    if (is_array($query)) {
      $query = \CRM_Utils_System::makeQueryString($query);
    }
    static::appendString($this->query, '&', $query);
    return $this;
  }

  /**
   * Get the primary fragment.
   *
   * NOTE: This is the primary fragment identifier (as in `#id` or `#/client/side/route`).
   * and does not include fregment queries. (as in '#?angularDebug=1').
   *
   * @return string|null
   *   Ex: '/mailing/new'
   *   Ex: '/foo+bar%3F/newish%3F'
   * @see Url::getFragmentQuery()
   * @see Url::composeFragment()
   */
  public function getFragment(): ?string {
    return $this->fragment;
  }

  /**
   * Replace the fragment.
   *
   * NOTE: This is the primary fragment identifier (as in `#id` or `#/client/side/route`).
   * and does not include fregment queries. (as in '#?angularDebug=1').
   *
   * @param string|string[]|null $fragment
   *   Ex: '/mailing/new'
   *   Ex: '/foo+bar/newish%3F'
   *   Ex: ['', 'foo bar', 'newish?']
   * @return $this
   * @see Url::setFragmentQuery()
   * @see url::composeFragment()
   */
  public function setFragment($fragment): Url {
    $this->fragment = static::encodePath($fragment);
    return $this;
  }

  /**
   * Add to fragment.
   *
   * @param string|string[] $fragment
   *   Ex: 'mailing/new'
   *   Ex: 'foo+bar/newish%3F'
   *   Ex: ['foo bar', 'newish?']
   * @return $this
   */
  public function addFragment($fragment): Url {
    static::appendString($this->fragment, '/', static::encodePath($fragment));
    return $this;
  }

  /**
   * @return string|null
   *   Ex: 'name=John+Doughnut&id=9'
   */
  public function getFragmentQuery(): ?string {
    return $this->fragmentQuery;
  }

  /**
   * @param string|string[]|null $fragmentQuery
   *   Ex: 'name=John+Doughnut&id=9'
   *   Ex: ['name' => 'John Doughnut', 'id' => 9]
   * @return $this
   */
  public function setFragmentQuery($fragmentQuery) {
    if (is_array($fragmentQuery)) {
      $fragmentQuery = \CRM_Utils_System::makeQueryString($fragmentQuery);
    }
    $this->fragmentQuery = $fragmentQuery;
    return $this;
  }

  /**
   * @param string|array $fragmentQuery
   *   Ex: 'name=John+Doughnut&id=9'
   *   Ex: ['name' => 'John Doughnut', 'id' => 9]
   * @return $this
   */
  public function addFragmentQuery($fragmentQuery): Url {
    if (is_array($fragmentQuery)) {
      $fragmentQuery = \CRM_Utils_System::makeQueryString($fragmentQuery);
    }
    static::appendString($this->fragmentQuery, '&', $fragmentQuery);
    return $this;
  }

  /**
   * @return bool|null
   */
  public function getCacheCode(): ?bool {
    return $this->cacheCode;
  }

  /**
   * Specify whether to append a cache-busting code.
   *
   * @param bool|null $cacheCode
   *   TRUE: Do append
   *   FALSE: Do not append
   * @return $this;
   */
  public function setCacheCode(?bool $cacheCode) {
    $this->cacheCode = $cacheCode;
    return $this;
  }

  /**
   * @return string|null
   *   'relative' or 'absolute'
   */
  public function getPreferFormat(): ?string {
    return $this->preferFormat;
  }

  /**
   * Specify whether to prefer absolute or relative formatting.
   *
   * @param string|null $preferFormat
   *   One of:
   *   - 'relative': Prefer relative format, if available
   *   - 'absolute': Prefer absolute format
   *   - NULL: Decide format based on current environment/request. (Ordinary web UI requests prefer 'relative'.)
   */
  public function setPreferFormat(?string $preferFormat): Url {
    $this->preferFormat = $preferFormat;
    return $this;
  }

  /**
   * @return bool
   */
  public function getHtmlEscape(): bool {
    return $this->htmlEscape;
  }

  /**
   * Specify whether to enable HTML escaping of the final output.
   *
   * @param bool $htmlEscape
   * @return $this
   */
  public function setHtmlEscape(bool $htmlEscape): Url {
    $this->htmlEscape = $htmlEscape;
    return $this;
  }

  /**
   * @return bool|null
   */
  public function getSsl(): ?bool {
    return $this->ssl;
  }

  /**
   * Specify whether the hyperlink should use SSL.
   *
   * @param bool|null $ssl
   *   TRUE: Force SSL on. (Convert "http:" to "https:")
   *   FALSE: Force SSL off. (Convert "https:" to "http:")
   *   NULL: Inherit current SSL-ness
   */
  public function setSsl(?bool $ssl): Url {
    $this->ssl = $ssl;
    return $this;
  }

  /**
   * @return string[]|null
   */
  public function getVars(): ?array {
    return $this->vars;
  }

  /**
   * Specify a list of variables. After composing all parts of the URL, variables will be replaced
   * with their URL-encoded values.
   *
   * Example:
   *   Civi::url('frontend://civicrm/greeter?cid=[contact]&msg=[message]')
   *     ->setVars(['contact' => 123, 'message' => 'Hello to you & you & you!');
   *
   * @param string[]|null $vars
   * @return $this
   */
  public function setVars(?array $vars): Url {
    $this->vars = $vars;
    return $this;
  }

  /**
   * Add more variables. After composing all parts of the URL, variables will be replaced
   * with their URL-encoded values.
   *
   * Example:
   *   Civi::url('frontend://civicrm/greeter?cid=[contact]&msg=[message]')
   *     ->addVars(['contact' => 123, 'message' => 'Hello to you & you & you!');
   *
   * @param string[] $vars
   * @return $this
   */
  public function addVars(array $vars): Url {
    $this->vars = $vars + ($this->vars ?: []);
    return $this;
  }

  /**
   * @return callable|null
   */
  public function getVarsCallback(): ?callable {
    return $this->varsCallback;
  }

  /**
   * Configure dynamic lookup for variables.
   *
   * @param callable|null $varsCallback
   *   Function(string $varName): ?string
   *   Determine the string-value of the variable. (May be ''.)
   *   If the variable is unavailable, return NULL.
   * @return $this
   */
  public function setVarsCallback(?callable $varsCallback) {
    $this->varsCallback = $varsCallback;
    return $this;
  }

  /**
   * Apply a series of flags using short-hand notation.
   *
   * @param string $flags
   *   List of flag-letters, such as (a)bsolute or (r)elative
   *   For a full list, see Civi::url().
   * @see Civi::url()
   * @return $this
   */
  public function useFlags(string $flags): Url {
    $len = strlen($flags);
    for ($i = 0; $i < $len; $i++) {
      switch ($flags[$i]) {
        // (a)bsolute url
        case 'a':
          $this->preferFormat = 'absolute';
          break;

        // (r)elative url
        case 'r':
          $this->preferFormat = 'relative';
          break;

        // (h)tml encoding
        case 'h':
          $this->htmlEscape = TRUE;
          break;

        // (t)ext encoding (canonical URL form)
        case 't':
          $this->htmlEscape = FALSE;
          break;

        // (s)sl
        case 's';
          $this->ssl = TRUE;
          break;

        // (c)ache code for resources
        case 'c':
          $this->cacheCode = TRUE;
          break;
      }
    }
    return $this;
  }

  /**
   * Render the final URL as a string.
   *
   * @return string
   */
  public function __toString(): string {
    $userSystem = \CRM_Core_Config::singleton()->userSystem;
    $preferFormat = $this->getPreferFormat() ?: static::detectFormat();
    $renderedPieces = $this->toRenderedPieces();
    $scheme = $renderedPieces['scheme'];

    if ($scheme === NULL || $scheme === 'current') {
      $scheme = static::detectScheme();
    }

    if ($scheme === 'default') {
      $scheme = \CRM_Core_Menu::isPublicRoute($renderedPieces['path']) ? 'frontend' : 'backend';
    }

    // Goal: After this switch(), we should have the $scheme, $path, and $query combined.
    switch ($scheme) {
      case 'assetBuilder':
        $assetName = $renderedPieces['path'];
        $assetParams = [];
        parse_str('' . $renderedPieces['query'], $assetParams);
        $result = \Civi::service('asset_builder')->getUrl($assetName, $assetParams);
        break;

      case 'asset':
        if (preg_match(';^\[([\w\.]+)\](.*)$;', $renderedPieces['path'], $m)) {
          [, $var, $rest] = $m;
          $varValue = rtrim(\Civi::paths()->getVariable($var, 'url'), '/');
          $result = $varValue . $rest . $this->composeQuery($renderedPieces['query']);
        }
        else {
          throw new \RuntimeException("Malformed asset path: " . $renderedPieces['path']);
        }
        break;

      case 'ext':
        $parts = explode('/', $renderedPieces['path'], 2);
        $result = \Civi::resources()->getUrl($parts[0], $parts[1] ?? NULL, FALSE) . $this->composeQuery($renderedPieces['query']);
        break;

      case 'http':
      case 'https':
        $port = is_numeric($this->port) ? ":{$this->port}" : "";
        $path = $this->getPath();
        $result = $this->getScheme() . '://' . $this->host . $port . $path . $this->composeQuery($renderedPieces['query']);
        break;

      // Handle 'frontend', 'backend', 'service', and any extras.
      default:
        $result = $userSystem->getRouteUrl($scheme, $renderedPieces['path'], $renderedPieces['query']);
        if ($result === NULL) {
          $event = GenericHookEvent::create(['url' => $this, 'result' => &$result]);
          \Civi::dispatcher()->dispatch('civi.url.render.' . $scheme, $event);
          if ($result instanceof Url) {
            return $result->__toString();
          }
        }
        if ($result === NULL) {
          throw new \RuntimeException("Unknown URL scheme: $scheme");
        }
        break;
    }

    if ($this->cacheCode) {
      $result = \Civi::resources()->addCacheCode($result);
    }

    $result .= $this->composeFragment($renderedPieces['fragment'], $renderedPieces['fragmentQuery']);

    if ($preferFormat === 'relative') {
      $result = \CRM_Utils_Url::toRelative($result);
    }
    elseif ($preferFormat === 'absolute') {
      $result = \CRM_Utils_Url::toAbsolute($result);
    }

    // TODO decide if the current default is good enough for future
    $ssl = $this->getSsl() ?: \CRM_Utils_System::isSSL();
    if ($ssl && str_starts_with($result, 'http:')) {
      $result = 'https:' . substr($result, 5);
    }
    elseif (!$ssl && str_starts_with($result, 'https:')) {
      $result = 'http:' . substr($result, 6);
    }

    return $this->htmlEscape ? htmlentities($result) : $result;
  }

  /**
   * @return array{scheme: ?string, path: ?string, query: ?string}
   */
  private function toRenderedPieces(): array {
    return [
      'scheme' => $this->replaceVars('scheme', $this->getScheme()),
      'path' => $this->replaceVars('path', $this->getPath()),
      'query' => $this->replaceVars('query', $this->getQuery()),
      'fragment' => $this->replaceVars('fragment', $this->fragment),
      'fragmentQuery' => $this->replaceVars('fragmentQuery', $this->fragmentQuery),
    ];
  }

  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    return $this->__toString();
  }

  private function replaceVars(string $context, ?string $expr): ?string {
    if ($expr === NULL || $this->vars === NULL) {
      return $expr;
    }
    $result = preg_replace_callback('/\[(\w+)\]/', function($m) {
      $var = $m[1];
      if (isset($this->vars[$var])) {
        return urlencode($this->vars[$var]);
      }
      if ($this->varsCallback !== NULL) {
        $value = call_user_func($this->varsCallback, $var);
        if ($value !== NULL) {
          return urlencode($value);
        }
      }
      return "[$var]";
    }, $expr);
    return $result;
  }

  /**
   * @return string
   *   '' or '?foo=bar'
   */
  private function composeQuery(?string $query): string {
    if ($query !== NULL && $query !== '') {
      return '?' . $query;
    }
    else {
      return '';
    }
  }

  /**
   * @return string
   *   '' or '#foobar'
   */
  private function composeFragment(?string $baseFragment, ?string $fragmentQuery): string {
    $fullFragment = $baseFragment ?: '';
    if ($fragmentQuery !== NULL && $fragmentQuery !== '') {
      $fullFragment .= '?' . $fragmentQuery;
    }
    return ($fullFragment === '') ? '' : "#$fullFragment";
  }

  private static function detectFormat(): string {
    // Some environments may override default - e.g. cv-cli prefers absolute URLs
    // WISHLIST: If handling `Job.*`, then 'absolute'
    // WISHLIST: If active route is a web-service/web-hook/IPN, then 'absolute'
    foreach ($GLOBALS['civicrm_url_defaults'] ?? [] as $default) {
      if (isset($default['format'])) {
        return $default['format'];
      }
    }

    // Web UI: Most CiviCRM routes (`CRM_Core_Invoke::invoke()`) and CMS blocks
    return 'relative';
  }

  private static function detectScheme(): string {
    // Some environments may override default - e.g. cv-cli prefers 'default://'.
    // WISHLIST: If handling `Job.*`, then `default://'
    // WISHLIST: If active route is a web-service/web-hook/IPN, then 'default://'
    foreach ($GLOBALS['civicrm_url_defaults'] ?? [] as $default) {
      if (isset($default['scheme'])) {
        return $default['scheme'];
      }
    }

    // Web UI: Most CiviCRM routes (`CRM_Core_Invoke::invoke()`) and CMS blocks
    return \CRM_Core_Config::singleton()->userSystem->isFrontEndPage() ? 'frontend' : 'backend';
  }

  /**
   * @param string|string[]|null $path
   *   Ex: 'greet/hello+world/en'
   *   Ex: ['greet', 'hello world', 'en']
   * @return string|null
   *   Ex: 'greet/hello+world/en'
   */
  private static function encodePath($path): ?string {
    if (is_array($path)) {
      $encodedArray = array_map('urlencode', $path);
      return implode('/', $encodedArray);
    }
    else {
      return $path;
    }
  }

  private static function appendString(?string &$var, string $separator, ?string $value): void {
    if ($value === NULL || $value === '') {
      return;
    }

    if ($var === NULL || $var === '') {
      $var = $value;
      return;
    }

    // Dedupe separators
    if (str_ends_with($var, $separator)) {
      $var = rtrim($var, $separator);
    }
    if ($value[0] === $separator) {
      $value = ltrim($value, $separator);
    }

    $var = $var . $separator . $value;
  }

}
