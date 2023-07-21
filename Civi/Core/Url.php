<?php

namespace Civi\Core;

/**
 * Generate a URL.
 *
 * As input, this class takes a *logical URI*, which may include a range of configurable sub-parts (path, query, fragment, etc).
 *
 * As output, it provides a *concrete URL* that can be used by a web-browser to make requests.
 */
final class Url {

  /**
   * @var string
   *   Ex: 'frontend', 'backend'
   */
  private $scheme;

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
   * @param string $logicalUri
   * @param string|null $flags
   * @see \Civi::url()
   */
  public function __construct(string $logicalUri, ?string $flags = NULL) {
    if ($logicalUri[0] === '/') {
      $logicalUri = 'current:' . $logicalUri;
    }

    $parsed = parse_url($logicalUri);
    $this->scheme = $parsed['scheme'] ?? NULL;
    $this->path = $parsed['host'] ?? NULL;
    if (isset($parsed['path'])) {
      $this->path .= $parsed['path'];
    }
    $this->query = $parsed['query'] ?? NULL;
    $this->fragment = $parsed['fragment'] ?? NULL;

    if ($flags !== NULL) {
      $this->useFlags($flags);
    }
  }

  /**
   * @return string
   */
  public function getScheme() {
    return $this->scheme;
  }

  /**
   * @param string $scheme
   */
  public function setScheme(string $scheme): Url {
    $this->scheme = $scheme;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * @param string $path
   */
  public function setPath(string $path): Url {
    $this->path = $path;
    return $this;
  }

  /**
   * @param string|string[] $pathParts
   * @return $this
   */
  public function addPath($pathParts): Url {
    $suffix = implode('/', (array) $pathParts);
    if ($this->path === NULL) {
      $this->path = $suffix;
    }
    else {
      $this->path = rtrim($this->path, '/') . '/' . $suffix;
    }
    return $this;
  }

  /**
   * @return string|null
   */
  public function getQuery(): ?string {
    return $this->query;
  }

  /**
   * @param string|array|null $query
   */
  public function setQuery($query): Url {
    $this->query = \CRM_Utils_System::makeQueryString($query);
    return $this;
  }

  /**
   * @param string|array $query
   * @return $this
   */
  public function addQuery($query): Url {
    if ($this->query === NULL) {
      $this->query = \CRM_Utils_System::makeQueryString($query);
    }
    else {
      $this->query .= '&' . \CRM_Utils_System::makeQueryString($query);
    }
    return $this;
  }

  /**
   * @return string|null
   */
  public function getFragment(): ?string {
    return $this->fragment;
  }

  /**
   * @param string|null $fragment
   */
  public function setFragment(?string $fragment): Url {
    $this->fragment = \CRM_Utils_System::makeQueryString($fragment);
    return $this;
  }

  /**
   * @param string|array $fragment
   * @return $this
   */
  public function addFragment($fragment): Url {
    if ($this->fragment === NULL) {
      $this->fragment = \CRM_Utils_System::makeQueryString($fragment);
    }
    else {
      $this->fragment .= '&' . \CRM_Utils_System::makeQueryString($fragment);
    }
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
   * @param string|null $preferFormat
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
   * @param bool $htmlEscape
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
   * @param bool|null $ssl
   */
  public function setSsl(?bool $ssl): Url {
    $this->ssl = $ssl;
    return $this;
  }

  /**
   * @param string $flags
   *   A series of flag-letters. Any of the following:
   *   - [a]bsolute
   *   - [r]elative
   *   - [h]tml
   *   - [s]sl
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

        // (p)lain text encoding
        case 'p':
          $this->htmlEscape = FALSE;
          break;

        // (s)sl
        case 's';
          $this->ssl = TRUE;
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
    $scheme = $this->getScheme();

    if ($scheme === NULL || $scheme === 'current') {
      $scheme = static::detectScheme();
    }

    if ($scheme === 'default') {
      // TODO Use metadata to pick $scheme = 'frontend' or 'backend' or 'service';
      throw new \RuntimeException("FIXME: Implement lookup for default ");
    }

    switch ($scheme) {
      case 'frontend':
      case 'service':
        $result = $userSystem->url($this->getPath(), $this->getQuery(), $preferFormat === 'absolute', $this->getFragment(), TRUE, FALSE, FALSE);
        break;

      case 'backend':
        $result = $userSystem->url($this->getPath(), $this->getQuery(), $preferFormat === 'absolute', $this->getFragment(), FALSE, TRUE, FALSE);
        break;

      case 'assetBuilder':
        $assetName = $this->getPath();
        $assetParams = [];
        parse_str('' . $this->getQuery(), $assetParams);
        $result = \Civi::service('asset_builder')->getUrl($assetName, $assetParams);
        break;

      case 'ext':
        $parts = explode('/', $this->getPath(), 2);
        $result = \Civi::resources()->getUrl($parts[0], $parts[1] ?? NULL, FALSE);
        if ($this->query) {
          $result .= '?' . $this->query;
        }
        if ($this->fragment) {
          $result .= '#' . $this->fragment;
        }
        break;

      default:
        throw new \RuntimeException("Unknown URL scheme: {$this->getScheme()}");
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

}
