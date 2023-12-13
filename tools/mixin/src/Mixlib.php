<?php

/**
 * The Mixlib class is a utility for downloading/listing available mixins.
 * It is used by some test-infra and by civix.
 */
class Mixlib {

  /**
   * Local path to the mixlib folder.
   *
   * @var string|null
   */
  private $mixlibDir;

  /**
   * Public URL of the mixlib folder.
   *
   * @var string|null
   */
  private $mixlibUrl;

  private $cache = [];

  /**
   * Mixlib constructor.
   *
   * @param string|NULL $mixlibDir
   *   Ex: Civi::paths()->getPath('[civicrm.root]/mixin')
   * @param string|NULL $mixlibUrl
   *   Ex: "https://raw.githubusercontent.com/civicrm/civicrm-core/5.45/mixin"
   *   Ex: "https://raw.githubusercontent.com/totten/civicrm-core/master-mix-dec/mixin"
   */
  public function __construct(?string $mixlibDir = NULL, ?string $mixlibUrl = NULL) {
    $this->mixlibDir = $mixlibDir ?: dirname(__DIR__, 3) . '/mixin';
    $this->mixlibUrl = $mixlibUrl;
  }

  public function getList(): array {
    if ($this->mixlibDir === NULL || !file_exists($this->mixlibDir)) {
      throw new \RuntimeException("Cannot get list of available mixins");
    }

    if (isset($this->cache['getList'])) {
      return $this->cache['getList'];
    }

    $dirs = (array) glob($this->mixlibDir . '/*@*');
    $mixinNames = [];
    foreach ($dirs as $dir) {
      if (is_dir($dir)) {
        $mixinNames[] = basename($dir);
      }
    }
    sort($mixinNames);
    $this->cache['getList'] = $mixinNames;
    return $mixinNames;
  }

  /**
   * @param string $mixin
   *
   * @return array
   *   Item with keys:
   *   - mixinName: string, eg 'mgd-php'
   *   - mixinVersion: string, eg '1.0.2'
   *   - mixinConstraint: string, eg 'mgd-php@1.0.2'
   *   - mixinFile: string, eg 'mgd-php@1.0.2.mixin.php'
   *   - src: string, unevaluated PHP source
   */
  public function get(string $mixin) {
    if (isset($this->cache["parsed:$mixin"])) {
      return $this->cache["parsed:$mixin"];
    }

    $phpCode = $this->getSourceCode($mixin);
    $mixinSpec = $this->parseString($phpCode);
    $mixinSpec['mixinName'] ??= preg_replace(';@.*$;', '', $mixin);

    $parts = explode('@', $mixin);
    $effectiveVersion = !empty($mixinSpec['mixinVersion']) ? $mixinSpec['mixinVersion'] : ($parts[1] ?? '');
    if ($effectiveVersion) {
      $mixinSpec = array_merge([
        'mixinConstraint' => $mixinSpec['mixinName'] . '@' . $effectiveVersion,
        'mixinFile' => $mixinSpec['mixinName'] . '@' . $effectiveVersion . '.mixin.php',
      ], $mixinSpec);
    }
    else {
      $mixinSpec = array_merge([
        'mixinFile' => $mixinSpec['mixinName'] . '.mixin.php',
      ], $mixinSpec);
    }
    $mixinSpec['src'] = $phpCode;
    $this->cache["parsed:$mixin"] = $mixinSpec;

    return $this->cache["parsed:$mixin"];
  }

  /**
   * Consolidate and retrieve the listed mixins.
   *
   * @param array $mixinConstraints
   *   Ex: ['foo@1.0', 'bar@1.2', 'bar@1.3']
   * @return array
   *   Ex: ['foo@1.0' => array, 'bar@1.3' => array]
   */
  public function consolidate(array $mixinConstraints): array {
    // Find and remove duplicate constraints. Pick tightest constraint.
    // array(string $mixinName => string $mixinVersion)
    $preferredVersions = [];
    foreach ($mixinConstraints as $mixinName) {
      [$name, $version] = explode('@', $mixinName);
      if (!isset($preferredVersions[$name])) {
        $preferredVersions[$name] = $version;
      }
      elseif (version_compare($version, $preferredVersions[$name], '>=')) {
        $preferredVersions[$name] = $version;
      }
    }

    // Resolve current versions matching constraint.
    $result = [];
    foreach ($preferredVersions as $mixinName => $mixinVersion) {
      $result[] = $mixinName . '@' . $mixinVersion;
    }
    sort($result);
    return $result;
  }

  /**
   * Consolidate and retrieve the listed mixins.
   *
   * @param array $mixinConstraints
   *   Ex: ['foo@1.0', 'bar@1.2', 'bar@1.3']
   * @return array
   *   Ex: ['foo@1.0' => array, 'bar@1.3' => array]
   */
  public function resolve(array $mixinConstraints): array {
    $mixinConstraints = $this->consolidate($mixinConstraints);

    $result = [];
    foreach ($mixinConstraints as $mixinConstraint) {
      [$expectName, $expectVersion] = explode('@', $mixinConstraint);
      $mixin = $this->get($mixinConstraint);
      $this->assertValid($mixin);
      if (!version_compare($mixin['mixinVersion'], $expectVersion, '>=') || $mixin['mixinName'] !== $expectName) {
        throw new \RuntimeException(sprintf("Received incompatible version (expected=\"%s@%s\", actual=\"%s@%s\")", $expectName, $expectVersion, $mixin['mixinName'], $mixin['mixinVersion']));
      }
      $result[$mixin['mixinConstraint']] = $mixin;
    }
    return $result;
  }

  /**
   * @param string $mixin
   *  Ex: 'foo@1.2.3', 'foo-bar@4.5.6', 'polyfill',
   * @return string
   */
  protected function getSourceCode(string $mixin): string {
    if ($mixin === 'polyfill') {
      $file = 'polyfill.php';
    }
    elseif (preg_match(';^([-\w]+)@(\d+)([\.\d]+)?;', $mixin, $m)) {
      // Get the last revision within the major series.
      $file = sprintf('%s@%s/mixin.php', $m[1], $m[2]);
    }
    else {
      throw new \RuntimeException("Failed to parse mixin name ($mixin)");
    }

    if ($this->mixlibDir && file_exists($this->mixlibDir . '/' . $file)) {
      return file_get_contents($this->mixlibDir . '/' . $file);
    }

    if ($this->mixlibUrl) {
      $url = $this->mixlibUrl . '/' . $file;
      $download = file_get_contents($url);
      if (!empty($download)) {
        $this->cache["src:$mixin"] = $download;
        return $download;
      }
    }

    throw new \RuntimeException("Failed to locate $file (mixlibDir={$this->mixlibDir}, mixlibUrl={$this->mixlibUrl})");
  }

  public function assertValid(array $mixin): array {
    if (empty($mixin['mixinVersion'])) {
      throw new \RuntimeException("Invalid {$mixin["file"]}. There is no @mixinVersion annotation.");
    }
    if (empty($mixin['mixinVersion'])) {
      throw new \RuntimeException("Invalid {$mixin["file"]}. There is no @mixinName annotation.");
    }
    return $mixin;
  }

  /**
   * @param string $phpCode
   * @return array
   */
  protected function parseString(string $phpCode): array {
    $commmentTokens = [T_DOC_COMMENT, T_COMMENT, T_FUNC_C, T_METHOD_C, T_TRAIT_C, T_CLASS_C];
    $mixinSpec = [];
    foreach (token_get_all($phpCode) as $token) {
      if (is_array($token) && in_array($token[0], $commmentTokens)) {
        $mixinSpec = $this->parseComment($token[1]);
        break;
      }
    }
    return $mixinSpec;
  }

  protected function parseComment(string $comment): array {
    $info = [];
    $param = NULL;
    foreach (preg_split("/((\r?\n)|(\r\n?))/", $comment) as $num => $line) {
      if (!$num || strpos($line, '*/') !== FALSE) {
        continue;
      }
      $line = ltrim(trim($line), '*');
      if (strlen($line) && $line[0] === ' ') {
        $line = substr($line, 1);
      }
      if (strpos(ltrim($line), '@') === 0) {
        $words = explode(' ', ltrim($line, ' @'));
        $key = array_shift($words);
        $param = NULL;
        if ($key == 'var') {
          $info['type'] = explode('|', $words[0]);
        }
        elseif ($key == 'return') {
          $info['return'] = explode('|', $words[0]);
        }
        elseif ($key == 'options' || $key == 'ui_join_filters') {
          $val = str_replace(', ', ',', implode(' ', $words));
          $info[$key] = explode(',', $val);
        }
        elseif ($key == 'throws' || $key == 'see') {
          $info[$key][] = implode(' ', $words);
        }
        elseif ($key == 'param' && $words) {
          $type = $words[0][0] !== '$' ? explode('|', array_shift($words)) : NULL;
          $param = rtrim(array_shift($words), '-:()/');
          $info['params'][$param] = [
            'type' => $type,
            'description' => $words ? ltrim(implode(' ', $words), '-: ') : '',
            'comment' => '',
          ];
        }
        else {
          // Unrecognized annotation, but we'll duly add it to the info array
          $val = implode(' ', $words);
          $info[$key] = strlen($val) ? $val : TRUE;
        }
      }
      elseif ($param) {
        $info['params'][$param]['comment'] .= $line . "\n";
      }
      elseif ($num == 1) {
        $info['description'] = ucfirst($line);
      }
      elseif (!$line) {
        if (isset($info['comment'])) {
          $info['comment'] .= "\n";
        }
        else {
          $info['comment'] = NULL;
        }
      }
      // For multi-line description.
      elseif (count($info) === 1 && isset($info['description']) && substr($info['description'], -1) !== '.') {
        $info['description'] .= ' ' . $line;
      }
      else {
        $info['comment'] = isset($info['comment']) ? "{$info['comment']}\n$line" : $line;
      }
    }
    if (isset($info['comment'])) {
      $info['comment'] = rtrim($info['comment']);
    }
    return $info;
  }

}
