<?php

/**
 * Auto-detect list of certificate-authorities for use by HTTPS clients.
 *
 * This is designed to provide sane defaults for typical one-way
 * authentication.
 */
class CA_Config_Curl
{
    static private $_singleton;

    /**
     * Provide a singleton instance to simplify integration. If you prefer
     * to manage the lifecycle of the config object, then consider using
     * "probe()" or "new" instead.
     *
     * @return CA_Config_Curl
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
     * @return CA_Config_Curl
     */
    static public function probe($policy = array())
    {
        if (isset($policy['enable_ssl']) && $policy['enable_ssl'] === FALSE) {
            return new CA_Config_Curl(FALSE, FALSE, NULL);
        }
        $version = curl_version();
        if (!in_array('https', $version['protocols'])) {
            return new CA_Config_Curl(FALSE, FALSE, NULL);
        }
        if (isset($policy['verify_peer']) && $policy['verify_peer'] === FALSE) {
            return new CA_Config_Curl(TRUE, FALSE, NULL);
        }
        if (isset($policy['cafile'])) {
            if (file_exists($policy['cafile']) && is_readable($policy['cafile'])) {
                return new CA_Config_Curl(TRUE, TRUE, $policy['cafile']);
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
        // can't directly detect if system has CA pre-configured; use heuristic based on OS
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // PHP probably doesn't have a default cafile
            if (empty($policy['fallback_cafile']) || !file_exists($policy['fallback_cafile'])) {
                throw new Exception("Certificate Authority file is required on Windows. Please contact the system administrator.");
            } elseif (time() > filemtime($policy['fallback_cafile']) + $policy['fallback_ttl']) {
                throw new Exception("Certificate Authority file is too old. Please contact the system administrator. See also: " . $policy['fallback_cafile']);
            } else {
                return new CA_Config_Curl(TRUE, TRUE, $policy['fallback_cafile']);
            }
        } else {
            // Most PHP builds include a built-in reference to a CA list
            return new CA_Config_Curl(TRUE, TRUE, NULL);
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
     * Format the CA config in a manner appropriate to curl_setopt_array()
     *
     * @return array
     */
    public function toCurlOptions()
    {
        $options = array();
        $options[CURLOPT_SSL_VERIFYPEER] = $this->verifyPeer;
        $options[CURLOPT_SSL_VERIFYHOST] = $this->verifyPeer ? 2 : 0;
        if ($this->caFile) {
            $options[CURLOPT_CAINFO] = $this->caFile;
        } // else: system default
        return $options;
    }
}
