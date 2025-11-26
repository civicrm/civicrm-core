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

/**
 * Metadata for an extension (e.g. the extension's "info.xml" file)
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Info {

  /**
   * Extension info file name.
   */
  const FILENAME = 'info.xml';

  /**
   * @var string|null
   */
  public $key = NULL;

  /**
   * @var string|null
   */
  public $type = NULL;

  /**
   * @var string|null
   */
  public $name = NULL;

  /**
   * @var string|null
   */
  public $label = NULL;

  /**
   * @var string|null
   */
  public $file = NULL;

  /**
   * @var array
   *   Each item is a specification like:
   *   array('type'=>'psr4', 'namespace'=>'Foo\Bar', 'path'=>'/foo/bar').
   */
  public $classloader = [];

  /**
   * @var array
   *   Each item is they key-name of an extension required by this extension.
   */
  public $requires = [];

  /**
   * (Optional) The parent of a submodule.
   *
   * If the parent is installed, then the submodule becomes eligible for auto-installation.
   * If the parent is uninstalled, then the submodule must be uninstalled.
   *
   * @var string|null
   */
  public $parent = NULL;

  /**
   * @var array
   *   List of expected mixins.
   *   Ex: ['civix@2.0.0']
   */
  public $mixins = [];

  /**
   * @var array
   *   List of strings (tag-names).
   */
  public $tags = [];

  /**
   * @var array
   *   List of authors.
   *   Ex: [0 => ['name' => 'Alice', 'email' => 'a@b', 'homepage' => 'https://example.com', 'role' => 'Person']]
   */
  public $authors = [];

  /**
   * @var array|null
   *   The current maintainer at time of publication.
   *   This is deprecated in favor of $authors.
   * @deprecated
   */
  public $maintainer = NULL;

  /**
   * @var string|null
   *  The name of a class which handles the install/upgrade lifecycle.
   * @see \CRM_Extension_Upgrader_Interface
   */
  public $upgrader = NULL;

  /**
   * @var array|null
   */
  public $civix;

  /**
   * @var string|null
   */
  public $comments;

  /**
   * @var array
   *   Ex: ['ver' => '5.50']
   */
  public $compatibility;

  /**
   * @var array
   *   Ex: ['ver' => '8.4']
   */
  public $php_compatibility;

  /**
   * @var array
   *   Ex: ['ver' => '5']
   */
  public $smarty_compatibility;

  /**
   * @var string|null
   */
  public $description;

  /**
   * @var string|null
   *   Ex: 'stable', 'alpha', 'beta'
   */
  public $develStage;

  /**
   * @var string|null
   *   Ex: 'ready', 'not_ready'
   */
  public $ready;

  /**
   * @var int|null
   *   Ex: 1234
   */
  public $usage;

  /**
   * Full URL of the zipball for this extension/version.
   *
   * This property is (usually) only provided on the feed of new/available extensions.
   *
   * @var string|null
   */
  public $downloadUrl;

  /**
   * @var string|null
   *   Ex: 'GPL-3.0'
   */
  public $license;

  /**
   * @var string|null
   *   Ex: '2025-01-02'
   */
  public $releaseDate;

  /**
   * @var array|null
   *   Ex: ['Documentation' => 'https://example.org/my-extension/docs']
   */
  public $urls;

  /**
   * @var string|null
   *   Ex: '1.2.3'
   */
  public $version;

  /**
   * @var array
   */
  public $typeInfo;

  /**
   * @var string
   */
  public $url;

  /**
   * @var string
   */
  public $category;

  /**
   * Load extension info an XML file.
   *
   * @param string $file
   *
   * @throws CRM_Extension_Exception_ParseException
   * @return CRM_Extension_Info
   */
  public static function loadFromFile($file) {
    [$xml, $error] = CRM_Utils_XML::parseFile($file);
    if ($xml === FALSE) {
      throw new CRM_Extension_Exception_ParseException("Failed to parse info XML: $error");
    }

    $instance = new CRM_Extension_Info();
    $instance->parse($xml);
    return $instance;
  }

  /**
   * Load extension info a string.
   *
   * @param string $string
   *   XML content.
   *
   * @throws CRM_Extension_Exception_ParseException
   * @return CRM_Extension_Info
   */
  public static function loadFromString($string) {
    [$xml, $error] = CRM_Utils_XML::parseString($string);
    if ($xml === FALSE) {
      throw new CRM_Extension_Exception_ParseException("Failed to parse info XML: $string");
    }

    $instance = new CRM_Extension_Info();
    $instance->parse($xml);
    return $instance;
  }

  /**
   * Build a reverse-dependency map.
   *
   * @param array $infos
   *   The universe of available extensions.
   *   Ex: $infos['org.civicrm.foobar'] = new CRM_Extension_Info().
   * @return array
   *   If "org.civicrm.api" is required by "org.civicrm.foo", then return
   *   array('org.civicrm.api' => array(CRM_Extension_Info[org.civicrm.foo])).
   *   Array(string $key => array $requiredBys).
   */
  public static function buildReverseMap($infos) {
    $revMap = [];
    foreach ($infos as $info) {
      foreach ($info->requires as $key) {
        $revMap[$key][] = $info;
      }
    }
    return $revMap;
  }

  /**
   * @param string|null $key
   * @param string|null $type
   * @param string|null $name
   * @param string|null $label
   * @param string|null $file
   */
  public function __construct($key = NULL, $type = NULL, $name = NULL, $label = NULL, $file = NULL) {
    $this->key = $key;
    $this->type = $type;
    $this->name = $name;
    $this->label = $label;
    $this->file = $file;
  }

  /**
   * Copy attributes from an XML document to $this
   *
   * @param SimpleXMLElement $info
   * @param bool $useVariables
   *  Whether to interpolate variables like [civicrm.version]
   */
  public function parse($info, bool $useVariables = TRUE) {
    // Note that these variables must evaluated at fairly low-level of bootstrap.
    // So it's good to be conservative about how much dynamism you put in.
    $vars = [
      '[civicrm.version]' => CRM_Utils_System::version(),
      '[civicrm.majorVersion]' => CRM_Utils_System::majorVersion(),
      '[civicrm.releaseDate]' => CRM_Utils_System::versionXml()['releaseDate'],
      '[self.key]' => (string) $info->attributes()->key,
    ];
    $eval = $useVariables ? (fn($v) => $this->interpolate($v, $vars)) : (fn($v) => $v);

    $this->key = (string) $info->attributes()->key;
    $this->type = (string) $info->attributes()->type;
    $this->file = (string) $info->file;
    $this->label = (string) $info->name;
    $this->upgrader = (string) $info->upgrader;

    // Convert first level variables to CRM_Core_Extension properties
    // and deeper into arrays. An exception for URLS section, since
    // we want them in special format.
    foreach ($info as $attr => $val) {
      if (!property_exists($this, $attr)) {
        continue;
      }
      if (!count($val->children())) {
        $this->$attr = $eval(is_array($this->$attr) ? [] : trim((string) $val));
      }
      elseif ($attr === 'urls') {
        $this->urls = [];
        foreach ($val->url as $url) {
          $urlAttr = (string) $url->attributes()->desc;
          $this->urls[$urlAttr] = (string) $url;
        }
        ksort($this->urls);
      }
      elseif ($attr === 'classloader') {
        $this->classloader = [];
        foreach ($val->psr4 as $psr4) {
          $this->classloader[] = [
            'type' => 'psr4',
            'prefix' => (string) $psr4->attributes()->prefix,
            'path' => (string) $psr4->attributes()->path,
          ];
        }
        foreach ($val->psr0 as $psr0) {
          $this->classloader[] = [
            'type' => 'psr0',
            'prefix' => (string) $psr0->attributes()->prefix,
            'path' => (string) $psr0->attributes()->path,
          ];
        }
      }
      elseif ($attr === 'tags') {
        $this->tags = [];
        foreach ($val->tag as $tag) {
          $this->tags[] = (string) $tag;
        }
      }
      elseif ($attr === 'mixins') {
        $this->mixins = [];
        foreach ($val->mixin as $mixin) {
          $this->mixins[] = (string) $mixin;
        }
      }
      elseif ($attr === 'requires') {
        $this->requires = $this->filterRequirements($val);
      }
      elseif ($attr === 'maintainer') {
        $this->maintainer = CRM_Utils_XML::xmlObjToArray($val);
        $this->authors[] = [
          'name' => (string) $val->author,
          'email' => (string) $val->email,
          'role' => 'Maintainer',
        ];
      }
      elseif ($attr === 'authors') {
        foreach ($val->author as $author) {
          $this->authors[] = $thisAuthor = CRM_Utils_XML::xmlObjToArray($author);
          if ('maintainer' === strtolower($thisAuthor['role'] ?? '')) {
            $this->maintainer = ['author' => $thisAuthor['name'], 'email' => $thisAuthor['email'] ?? NULL];
          }
        }
      }
      else {
        $this->$attr = $eval(CRM_Utils_XML::xmlObjToArray($val));
      }
    }

    if (in_array('mgmt:enable-when-satisfied', $this->tags)) {
      if ($this->parent && !in_array($this->parent, $this->requires)) {
        $this->requires[] = $this->parent;
      }
      else {
        \Civi::log()->warning("Extension ($info->key) is tagged \"mgmt:enable-when-satisfied\", but no parent is declared.");
      }
    }
  }

  private function interpolate($value, $vars) {
    if (is_string($value)) {
      return strtr($value, $vars);
    }
    elseif (is_array($value)) {
      return array_map(fn($item) => $this->interpolate($item, $vars), $value);
    }
    else {
      return $value;
    }
  }

  public function isInstallable(): bool {
    $manager = CRM_Extension_System::singleton()->getManager();
    foreach ($this->requires as $require) {
      if (!$manager->isEnabled($require)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Filter out invalid requirements, e.g. extensions that have been moved to core.
   *
   * @param SimpleXMLElement $requirements
   * @return array
   */
  public function filterRequirements($requirements) {
    $filtered = [];
    $compatInfo = CRM_Extension_System::getCompatibilityInfo();
    foreach ($requirements->ext ?? [] as $ext) {
      $ext = (string) $ext;
      if (empty($compatInfo[$ext]['obsolete'])) {
        $filtered[] = $ext;
      }
    }
    return $filtered;
  }

}
