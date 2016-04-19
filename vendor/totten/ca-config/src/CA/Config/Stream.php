<?php

/**
 * Auto-detect list of certificate-authorities for use by HTTPS clients.
 *
 * This is designed to provide sane defaults for typical one-way
 * authentication.
 *
 * @code
 * $caConfig = CA_Config_Stream::singleton();
 * if ($caConfig->isEnableSSL()) {
 *   $context = stream_context_create(array(
 *     'ssl' => $caConfig->toStreamOptions(),
 *   ));
 *   $data = file_get_contents('https://example.com/', 0, $context);
 * } else {
 *   printf("This system does not support SSL.");
 * }
 * @endcode
 */
class CA_Config_Stream
{
    static private $_singleton;

    /**
     * Provide a singleton instance to simplify integration. If you prefer
     * to manage the lifecycle of the config object, then consider using
     * "probe()" or "new" instead.
     *
     * @return CA_Config_Stream
     */
    static public function singleton()
    {
        if (! self::$_singleton) {
            global $CA_CONFIG;
            self::$_singleton = self::probe($CA_CONFIG ? $CA_CONFIG : array());
        }
        return self::$_singleton;
    }

    /**
     * Factory fuction which produces a configuration based on a policy and based
     * on local system resources.
     *
     * @param $policy array:
     *  - enable_ssl: bool; default: TRUE
     *  - verify_peer: bool; default: TRUE
     *  - cafile: string, path to aggregated PEM; overrides any system defaults
     *  - fallback_cafile: string, path to aggregated PEM; used on systems which lack default; set FALSE to disable
     *  - fallback_ttl: int, seconds, the max age of the fallback cafile before it's regarded as stale; default: 5 years
     * @return CA_Config_Stream
     */
    static public function probe($policy = array())
    {
        if (isset($policy['enable_ssl']) && $policy['enable_ssl'] === FALSE) {
            return new CA_Config_Stream(FALSE, FALSE, NULL);
        }
        $sw = stream_get_wrappers();
        if (!extension_loaded('openssl') || !in_array('https', $sw)) {
            return new CA_Config_Stream(FALSE, FALSE, NULL);
        }
        if (isset($policy['verify_peer']) && $policy['verify_peer'] === FALSE) {
            return new CA_Config_Stream(TRUE, FALSE, NULL);
        }
        if (isset($policy['cafile'])) {
            if (file_exists($policy['cafile']) && is_readable($policy['cafile'])) {
                return new CA_Config_Stream(TRUE, TRUE, $policy['cafile']);
            } else {
                throw new Exception("Certificate Authority file is missing. Please contact the system administrator. See also: " . $policy['cafile']);
            }
        }

        if (!isset($policy['fallback_ttl'])) {
            $policy['fallback_ttl'] = 5 * 364 * 24 * 60 * 60;
        }
        if (!isset($policy['fallback_cafile'])) {
            $policy['fallback_cafile'] = dirname(__FILE__) . '/cacert.pem';
        }

        if (empty($policy['fallback_cafile']) || !file_exists($policy['fallback_cafile'])) {
            throw new Exception("Certificate Authority file is required for SSL. Please contact the system administrator.");
        } elseif (time() > filemtime($policy['fallback_cafile']) + $policy['fallback_ttl']) {
            throw new Exception("Certificate Authority file is too old. Please contact the system administrator. See also: " . $policy['fallback_cafile']);
        } else {
            return new CA_Config_Stream(TRUE, TRUE, $policy['fallback_cafile']);
        }
    }

    public function __construct($enableSSL, $verifyPeer, $caFile)
    {
        $this->enableSSL = $enableSSL;
        $this->verifyPeer = $verifyPeer;
        $this->caFile = $caFile;
    }

    /**
     * Whether SSL is supported at all
     *
     * @return bool
     */
    public function isEnableSSL()
    {
        return $this->enableSSL;
    }

    /**
     * Whether server certifiates should be verified
     *
     * @return bool
     */
    public function isVerifyPeer()
    {
        return $this->verifyPeer;
    }

    /**
     * Path to a CA file (if available/applicable)
     *
     * @return string
     */
    public function getCaFile()
    {
        return $this->caFile;
    }

    /**
     * Format the CA config in a manner appropriate to file_get_contents('https://')
     *
     * @return array
     */
    public function toStreamOptions()
    {
        $options = array();
        $options['verify_peer'] = $this->verifyPeer;
        if ($this->caFile) {
            $options['cafile'] = $this->caFile;
        } // else: system default
        return $options;
    }
}
