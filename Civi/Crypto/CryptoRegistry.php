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

namespace Civi\Crypto;

use Civi\Core\Service\AutoService;
use Civi\Crypto\Exception\CryptoException;

/**
 * The CryptoRegistry tracks a list of available keys and cipher suites:
 *
 * - A registered cipher suite is an instance of CipherSuiteInterface that
 *   provides a list of encryption options ("aes-cbc", "aes-ctr", etc) and
 *   an implementation for them.
 * - A registered key is an array that indicates a set of cryptographic options:
 *     - key: string, binary representation of the key
 *     - suite: string, e.g. "aes-cbc" or "aes-cbc-hs"
 *     - id: string, unique (non-sensitive) ID. Usually a fingerprint.
 *     - tags: string[], list of symbolic names/use-cases that may call upon this key
 *     - weight: int, when choosing a key for encryption, two similar keys will be
 *       be differentiated by weight. (Low values chosen before high values.)
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CryptoRegistry extends AutoService {

  const LAST_WEIGHT = 32768;

  const DEFAULT_SUITE = 'aes-cbc';

  const DEFAULT_KDF = 'hkdf-sha256';

  /**
   * List of available keys.
   *
   * @var array[]
   */
  protected $keys = [];

  /**
   * List of key-derivation functions. Used when loading keys.
   *
   * @var array
   */
  protected $kdfs = [];

  protected $cipherSuites = [];

  /**
   * Initialize a default instance of the registry.
   *
   * @return \Civi\Crypto\CryptoRegistry
   * @throws \CRM_Core_Exception
   * @throws \Civi\Crypto\Exception\CryptoException
   * @service crypto.registry
   */
  public static function createDefaultRegistry(): CryptoRegistry {
    $registry = new static();
    $registry->addCipherSuite(new \Civi\Crypto\PhpseclibCipherSuite());

    $registry->addPlainText(['tags' => ['CRED']]);
    if (defined('CIVICRM_CRED_KEYS') && CIVICRM_CRED_KEYS !== '') {
      foreach (explode(' ', CIVICRM_CRED_KEYS) as $n => $keyExpr) {
        $key = ['tags' => ['CRED'], 'weight' => $n];
        if ($keyExpr === 'plain') {
          $registry->addPlainText($key);
        }
        else {
          $registry->addSymmetricKey($registry->parseKey($keyExpr) + $key);
        }
      }
    }

    if (defined('CIVICRM_SIGN_KEYS') && CIVICRM_SIGN_KEYS !== '') {
      foreach (explode(' ', CIVICRM_SIGN_KEYS) as $n => $keyExpr) {
        $key = ['tags' => ['SIGN'], 'weight' => $n];
        $registry->addSymmetricKey($registry->parseKey($keyExpr) + $key);
      }
    }
    else {
      // If you are upgrading an old site that does not have a signing key, then there is a status-check advising you to fix it.
      // But apparently the current site hasn't fixed it yet. The UI+AssetBuilder need to work long enough for sysadmin to discover/resolve.
      // This fallback is sufficient for short-term usage in limited scenarios (AssetBuilder=>OK; AuthX=>No).
      // In a properly configured system, the WEAK_SIGN key is strictly unavailable - s.t. a normal site never uses WEAK_SIGN.
      $registry->addSymmetricKey([
        'tags' => ['WEAK_SIGN'],
        'suite' => 'jwt-hs256',
        'key' => hash_hkdf('sha256',
          json_encode([
            // DSN's and site-keys should usually be sufficient, but it's not strongly guaranteed,
            // so we'll toss in more spaghetti. (At a minimum, this should mitigate bots/crawlers.)
            \CRM_Utils_Constant::value('CIVICRM_DSN'),
            \CRM_Utils_Constant::value('CIVICRM_UF_DSN'),
            \CRM_Utils_Constant::value('CIVICRM_SITE_KEY') ?: $GLOBALS['civicrm_root'],
            \CRM_Utils_Constant::value('CIVICRM_UF_BASEURL'),
            \CRM_Utils_Constant::value('CIVICRM_DB_CACHE_PASSWORD'),
            \CRM_Utils_System::getSiteID(),
            \CRM_Utils_System::version(),
            \CRM_Core_Config::singleton()->userSystem->getVersion(),
            $_SERVER['HTTP_HOST'] ?? '',
          ])
        ),
      ]);
    }

    //if (isset($_COOKIE['CIVICRM_FORM_KEY'])) {
    //  $crypto->addSymmetricKey([
    //    'key' => base64_decode($_COOKIE['CIVICRM_FORM_KEY']),
    //    'suite' => 'aes-cbc',
    //    'tag' => ['FORM'],
    //  ]);
    //  // else: somewhere in CRM_Core_Form, we may need to initialize CIVICRM_FORM_KEY
    //}

    // Allow plugins to add/replace any keys and ciphers.
    \CRM_Utils_Hook::crypto($registry);
    return $registry;
  }

  public function __construct() {
    $this->cipherSuites['plain'] = TRUE;
    $this->keys['plain'] = [
      'key' => '',
      'suite' => 'plain',
      'tags' => [],
      'id' => 'plain',
      'weight' => self::LAST_WEIGHT,
    ];

    // Base64 - Useful for precise control. Relatively quick decode. Please bring your own entropy.
    $this->kdfs['b64'] = 'base64_decode';

    // HKDF - Forgiving about diverse inputs. Relatively quick decode. Please bring your own entropy.
    $this->kdfs['hkdf-sha256'] = function($v) {
      // NOTE: 256-bit output by default. Useful for pairing with AES-256.
      return hash_hkdf('sha256', $v);
    };

    // Possible future options: Read from PEM file. Run PBKDF2 on a passphrase.
  }

  /**
   * @param string|array $options
   *   Additional options:
   *     - key: string, a representation of the key as binary
   *     - suite: string, ex: 'aes-cbc'
   *     - tags: string[]
   *     - weight: int, default 0
   *     - id: string, a unique identifier for this key. (default: fingerprint the key+suite)
   *
   * @return array
   *   The full key record. (Same format as $options)
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function addSymmetricKey($options) {
    $defaults = [
      'suite' => self::DEFAULT_SUITE,
      'weight' => 0,
    ];
    $options = array_merge($defaults, $options);

    if (!isset($options['key'])) {
      throw new CryptoException("Missing crypto key");
    }

    if (!isset($options['id'])) {
      $options['id'] = \CRM_Utils_String::base64UrlEncode(sha1($options['suite'] . chr(0) . $options['key'], TRUE));
    }
    // Manual key IDs should be validated.
    elseif (!$this->isValidKeyId($options['id'])) {
      throw new CryptoException("Malformed key ID");
    }

    $this->keys[$options['id']] = $options;
    return $options;
  }

  /**
   * Determine if a key ID is well-formed.
   *
   * @param string $id
   * @return bool
   */
  public function isValidKeyId($id) {
    if (strpos($id, "\n") !== FALSE) {
      return FALSE;
    }
    return (bool) preg_match(';^[a-zA-Z0-9_\-\.:,=+/\;\\\\]+$;s', $id);
  }

  /**
   * Enable plain-text encoding.
   *
   * @param array $options
   *   Array with options:
   *   - tags: string[]
   * @return array
   */
  public function addPlainText($options) {
    static $n = 0;
    $defaults = [
      'suite' => 'plain',
      'weight' => self::LAST_WEIGHT,
    ];
    $options = array_merge($defaults, $options);
    $options['id'] = 'plain' . ($n++);
    $this->keys[$options['id']] = $options;
    return $options;
  }

  /**
   * @param CipherSuiteInterface $cipherSuite
   *   The encryption/decryption callback/handler
   * @param string[]|null $names
   *   Symbolic names. Ex: 'aes-cbc'
   *   If NULL, probe $cipherSuite->getNames()
   */
  public function addCipherSuite(CipherSuiteInterface $cipherSuite, $names = NULL) {
    $names = $names ?: $cipherSuite->getSuites();
    foreach ($names as $name) {
      $this->cipherSuites[$name] = $cipherSuite;
    }
  }

  public function getKeys() {
    return $this->keys;
  }

  /**
   * Locate a key in the list of available keys.
   *
   * @param string|string[] $keyIds
   *   List of IDs or tags. The first match in the list is returned.
   *   If multiple keys match the same tag, then the one with lowest 'weight' is returned.
   * @return array
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function findKey($keyIds) {
    $keyIds = (array) $keyIds;
    foreach ($keyIds as $keyIdOrTag) {
      if (isset($this->keys[$keyIdOrTag])) {
        return $this->keys[$keyIdOrTag];
      }

      $matchKeyId = NULL;
      $matchWeight = self::LAST_WEIGHT;
      foreach ($this->keys as $key) {
        if (in_array($keyIdOrTag, $key['tags']) && $key['weight'] <= $matchWeight) {
          $matchKeyId = $key['id'];
          $matchWeight = $key['weight'];
        }
      }
      if ($matchKeyId !== NULL) {
        return $this->keys[$matchKeyId];
      }
    }

    throw new CryptoException("Failed to find key by ID or tag (" . implode(' ', $keyIds) . ")");
  }

  /**
   * Find all the keys that apply to a tag.
   *
   * @param string|string[] $keyTag
   *
   * @return array
   *   List of keys, indexed by id, ordered by weight.
   */
  public function findKeysByTag($keyTag) {
    $keyTag = (array) $keyTag;
    $keys = array_filter($this->keys, function ($key) use ($keyTag) {
      return !empty(array_intersect($keyTag, $key['tags'] ?? []));
    });
    uasort($keys, function($a, $b) {
      return ($a['weight'] ?? 0) - ($b['weight'] ?? 0);
    });
    return $keys;
  }

  /**
   * @param string $name
   * @return \Civi\Crypto\CipherSuiteInterface
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function findSuite($name) {
    if (isset($this->cipherSuites[$name])) {
      return $this->cipherSuites[$name];
    }
    else {
      throw new CryptoException('Unknown cipher suite ' . $name);
    }
  }

  /**
   * @param string $keyExpr
   *   String in the form "<suite>:<key-encoding>:<key-value>".
   *
   *   'aes-cbc:b64:cGxlYXNlIHVzZSAzMiBieXRlcyBmb3IgYWVzLTI1NiE='
   *   'aes-cbc:hkdf-sha256:ABCD1234ABCD1234ABCD1234ABCD1234'
   *   '::ABCD1234ABCD1234ABCD1234ABCD1234'
   *
   * @return array
   *   Properties:
   *    - key: string, binary representation
   *    - suite: string, ex: 'aes-cbc'
   * @throws CryptoException
   */
  public function parseKey($keyExpr) {
    [$suite, $keyFunc, $keyVal] = explode(':', $keyExpr);
    if ($suite === '') {
      $suite = self::DEFAULT_SUITE;
    }
    if ($keyFunc === '') {
      $keyFunc = self::DEFAULT_KDF;
    }
    if (isset($this->kdfs[$keyFunc])) {
      return [
        'suite' => $suite,
        'key' => call_user_func($this->kdfs[$keyFunc], $keyVal),
      ];
    }
    else {
      throw new CryptoException("Crypto key has unrecognized type");
    }
  }

}
