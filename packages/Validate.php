<?php
/**
 * Validation class
 *
 * Copyright (c) 1997-2006 Pierre-Alain Joye,Tomas V.V.Cox, Amir Saied  
 *
 * This source file is subject to the New BSD license, That is bundled  
 * with this package in the file LICENSE, and is available through      
 * the world-wide-web at                                                
 * http://www.opensource.org/licenses/bsd-license.php                   
 * If you did not receive a copy of the new BSDlicense and are unable   
 * to obtain it through the world-wide-web, please send a note to       
 * pajoye@php.net so we can mail you a copy immediately.                
 *
 * Author: Tomas V.V.Cox  <cox@idecnet.com>                             
 *         Pierre-Alain Joye <pajoye@php.net>                           
 *         Amir Mohammad Saied <amir@php.net>                           
 *
 *
 * Package to validate various datas. It includes :
 *   - numbers (min/max, decimal or not)
 *   - email (syntax, domain check)
 *   - string (predifined type alpha upper and/or lowercase, numeric,...)
 *   - date (min, max, rfc822 compliant)
 *   - uri (RFC2396)
 *   - possibility valid multiple data with a single method call (::multiple)
 *
 * @category   Validate
 * @package    Validate
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Pierre-Alain Joye <pajoye@php.net>
 * @author     Amir Mohammad Saied <amir@php.net>
 * @copyright  1997-2006 Pierre-Alain Joye,Tomas V.V.Cox,Amir Mohammad Saied
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    CVS: $Id: Validate.php,v 1.134 2009/01/28 12:27:33 davidc Exp $
 * @link       http://pear.php.net/package/Validate
 */

/**
 * Methods for common data validations
 */
define('VALIDATE_NUM',          '0-9');
define('VALIDATE_SPACE',        '\s');
define('VALIDATE_ALPHA_LOWER',  'a-z');
define('VALIDATE_ALPHA_UPPER',  'A-Z');
define('VALIDATE_ALPHA',        VALIDATE_ALPHA_LOWER . VALIDATE_ALPHA_UPPER);
define('VALIDATE_EALPHA_LOWER', VALIDATE_ALPHA_LOWER . '·ÈÌÛ˙˝‡ËÏÚ˘‰ÎÔˆ¸ˇ‚ÍÓÙ˚„Òı®ÂÊÁΩ¯˛ﬂ');
define('VALIDATE_EALPHA_UPPER', VALIDATE_ALPHA_UPPER . '¡…Õ”⁄›¿»Ã“ŸƒÀœ÷‹æ¬ Œ‘€√—’¶≈∆«º–ÿﬁ');
define('VALIDATE_EALPHA',       VALIDATE_EALPHA_LOWER . VALIDATE_EALPHA_UPPER);
define('VALIDATE_PUNCTUATION',  VALIDATE_SPACE . '\.,;\:&"\'\?\!\(\)');
define('VALIDATE_NAME',         VALIDATE_EALPHA . VALIDATE_SPACE . "'" . "-");
define('VALIDATE_STREET',       VALIDATE_NUM . VALIDATE_NAME . "/\\∫™\.");

define('VALIDATE_ITLD_EMAILS',  1);
define('VALIDATE_GTLD_EMAILS',  2);
define('VALIDATE_CCTLD_EMAILS', 4);
define('VALIDATE_ALL_EMAILS',   8);

/**
 * Validation class
 *
 * Package to validate various datas. It includes :
 *   - numbers (min/max, decimal or not)
 *   - email (syntax, domain check)
 *   - string (predifined type alpha upper and/or lowercase, numeric,...)
 *   - date (min, max)
 *   - uri (RFC2396)
 *   - possibility valid multiple data with a single method call (::multiple)
 *
 * @category  Validate
 * @package   Validate
 * @author    Tomas V.V.Cox <cox@idecnet.com>
 * @author    Pierre-Alain Joye <pajoye@php.net>
 * @author    Amir Mohammad Saied <amir@php.net>
 * @copyright 1997-2006 Pierre-Alain Joye,Tomas V.V.Cox,Amir Mohammad Saied
 * @license   http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Validate
 */
class Validate
{
    /**
     * International Top-Level Domain
     *
     * This is an array of the known international
     * top-level domain names.
     *
     * @access protected
     * @var    array     $_iTld (International top-level domains)
     */
    var $_itld = array(
        'arpa',
        'root',
    );

    /**
     * Generic top-level domain
     *
     * This is an array of the official
     * generic top-level domains.
     *
     * @access protected
     * @var    array     $_gTld (Generic top-level domains)
     */
    var $_gtld = array(
        'aero',
        'biz',
        'cat',
        'com',
        'coop',
        'edu',
        'gov',
        'info',
        'int',
        'jobs',
        'mil',
        'mobi',
        'museum',
        'name',
        'net',
        'org',
        'pro',
        'travel',
        'asia',
        'post',
        'tel',
        'geo',
    );

    /**
     * Country code top-level domains
     *
     * This is an array of the official country
     * codes top-level domains
     *
     * @access protected
     * @var    array     $_ccTld (Country Code Top-Level Domain)
     */
    var $_cctld = array(
        'ac',
        'ad','ae','af','ag',
        'ai','al','am','an',
        'ao','aq','ar','as',
        'at','au','aw','ax',
        'az','ba','bb','bd',
        'be','bf','bg','bh',
        'bi','bj','bm','bn',
        'bo','br','bs','bt',
        'bu','bv','bw','by',
        'bz','ca','cc','cd',
        'cf','cg','ch','ci',
        'ck','cl','cm','cn',
        'co','cr','cs','cu',
        'cv','cx','cy','cz',
        'de','dj','dk','dm',
        'do','dz','ec','ee',
        'eg','eh','er','es',
        'et','eu','fi','fj',
        'fk','fm','fo','fr',
        'ga','gb','gd','ge',
        'gf','gg','gh','gi',
        'gl','gm','gn','gp',
        'gq','gr','gs','gt',
        'gu','gw','gy','hk',
        'hm','hn','hr','ht',
        'hu','id','ie','il',
        'im','in','io','iq',
        'ir','is','it','je',
        'jm','jo','jp','ke',
        'kg','kh','ki','km',
        'kn','kp','kr','kw',
        'ky','kz','la','lb',
        'lc','li','lk','lr',
        'ls','lt','lu','lv',
        'ly','ma','mc','md',
        'me','mg','mh','mk',
        'ml','mm','mn','mo',
        'mp','mq','mr','ms',
        'mt','mu','mv','mw',
        'mx','my','mz','na',
        'nc','ne','nf','ng',
        'ni','nl','no','np',
        'nr','nu','nz','om',
        'pa','pe','pf','pg',
        'ph','pk','pl','pm',
        'pn','pr','ps','pt',
        'pw','py','qa','re',
        'ro','rs','ru','rw',
        'sa','sb','sc','sd',
        'se','sg','sh','si',
        'sj','sk','sl','sm',
        'sn','so','sr','st',
        'su','sv','sy','sz',
        'tc','td','tf','tg',
        'th','tj','tk','tl',
        'tm','tn','to','tp',
        'tr','tt','tv','tw',
        'tz','ua','ug','uk',
        'us','uy','uz','va',
        'vc','ve','vg','vi',
        'vn','vu','wf','ws',
        'ye','yt','yu','za',
        'zm','zw',
    );

    /**
     * Validate a tag URI (RFC4151)
     *
     * @param string $uri tag URI to validate
     *
     * @return boolean true if valid tag URI, false if not
     *
     * @access private
     */
    function __uriRFC4151($uri)
    {
        $datevalid = false;
        if (preg_match(
            '/^tag:(?<name>.*),(?<date>\d{4}-?\d{0,2}-?\d{0,2}):(?<specific>.*)(.*:)*$/', $uri, $matches)) {
            $date  = $matches['date'];
            $date6 = strtotime($date);
            if ((strlen($date) == 4) && $date <= date('Y')) {
                $datevalid = true;
            } elseif ((strlen($date) == 7) && ($date6 < strtotime("now"))) {
                $datevalid = true;
            } elseif ((strlen($date) == 10) && ($date6 < strtotime("now"))) {
                $datevalid = true;
            }
            if (self::email($matches['name'])) {
                $namevalid = true;
            } else {
                $namevalid = self::email('info@' . $matches['name']);
            }
            return $datevalid && $namevalid;
        } else {
            return false;
        }
    }

    /**
     * Validate a number
     *
     * @param string $number  Number to validate
     * @param array  $options array where:
     *                          'decimal'  is the decimal char or false when decimal
     *                                     not allowed.
     *                                     i.e. ',.' to allow both ',' and '.'
     *                          'dec_prec' Number of allowed decimals
     *                          'min'      minimum value
     *                          'max'      maximum value
     *
     * @return boolean true if valid number, false if not
     *
     * @access public
     */
    function number($number, $options = array())
    {
        $decimal = $dec_prec = $min = $max = null;
        if (is_array($options)) {
            extract($options);
        }

        $dec_prec  = $dec_prec ? "{1,$dec_prec}" : '+';
        $dec_regex = $decimal  ? "[$decimal][0-9]$dec_prec" : '';

        if (!preg_match("|^[-+]?\s*[0-9]+($dec_regex)?\$|", $number)) {
            return false;
        }

        if ($decimal != '.') {
            $number = strtr($number, $decimal, '.');
        }

        $number = (float)str_replace(' ', '', $number);
        if ($min !== null && $min > $number) {
            return false;
        }

        if ($max !== null && $max < $number) {
            return false;
        }
        return true;
    }

    /**
     * Converting a string to UTF-7 (RFC 2152)
     *
     * @param string $string string to be converted
     *
     * @return  string  converted string
     *
     * @access  private
     */
    function __stringToUtf7($string)
    {
        $return = '';
        $utf7   = array(
                        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
                        'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
                        'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g',
                        'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r',
                        's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2',
                        '3', '4', '5', '6', '7', '8', '9', '+', ','
                    );


        $state = 0;

        if (!empty($string)) {
            $i = 0;
            while ($i <= strlen($string)) {
                $char = substr($string, $i, 1);
                if ($state == 0) {
                    if ((ord($char) >= 0x7F) || (ord($char) <= 0x1F)) {
                        if ($char) {
                            $return .= '&';
                        }
                        $state = 1;
                    } elseif ($char == '&') {
                        $return .= '&-';
                    } else {
                        $return .= $char;
                    }
                } elseif (($i == strlen($string) ||
                            !((ord($char) >= 0x7F)) || (ord($char) <= 0x1F))) {
                    if ($state != 1) {
                        if (ord($char) > 64) {
                            $return .= '';
                        } else {
                            $return .= $utf7[ord($char)];
                        }
                    }
                    $return .= '-';
                    $state   = 0;
                } else {
                    switch($state) {
                    case 1:
                        $return .= $utf7[ord($char) >> 2];
                        $residue = (ord($char) & 0x03) << 4;
                        $state   = 2;
                        break;
                    case 2:
                        $return .= $utf7[$residue | (ord($char) >> 4)];
                        $residue = (ord($char) & 0x0F) << 2;
                        $state   = 3;
                        break;
                    case 3:
                        $return .= $utf7[$residue | (ord($char) >> 6)];
                        $return .= $utf7[ord($char) & 0x3F];
                        $state   = 1;
                        break;
                    }
                }
                $i++;
            }
            return $return;
        }
        return '';
    }

    /**
     * Validate an email according to full RFC822 (inclusive human readable part)
     *
     * @param string $email   email to validate,
     *                        will return the address for optional dns validation
     * @param array  $options email() options
     *
     * @return boolean true if valid email, false if not
     *
     * @access private
     */
    function __emailRFC822(&$email, &$options)
    {
        static $address   = null;
        static $uncomment = null;
        if (!$address) {
            // atom        =  1*<any CHAR except specials, SPACE and CTLs>
            $atom = '[^][()<>@,;:\\".\s\000-\037\177-\377]+\s*';
            // qtext       =  <any CHAR excepting <">,     ; => may be folded
            //         "\" & CR, and including linear-white-space>
            $qtext = '[^"\\\\\r]';
            // quoted-pair =  "\" CHAR                     ; may quote any char
            $quoted_pair = '\\\\.';
            // quoted-string = <"> *(qtext/quoted-pair) <">; Regular qtext or
            //                                             ;   quoted chars.
            $quoted_string = '"(?:' . $qtext . '|' . $quoted_pair . ')*"\s*';
            // word        =  atom / quoted-string
            $word = '(?:' . $atom . '|' . $quoted_string . ')';
            // local-part  =  word *("." word)             ; uninterpreted
            //                                             ; case-preserved
            $local_part = $word . '(?:\.\s*' . $word . ')*';
            // dtext       =  <any CHAR excluding "[",     ; => may be folded
            //         "]", "\" & CR, & including linear-white-space>
            $dtext = '[^][\\\\\r]';
            // domain-literal =  "[" *(dtext / quoted-pair) "]"
            $domain_literal = '\[(?:' . $dtext . '|' . $quoted_pair . ')*\]\s*';
            // sub-domain  =  domain-ref / domain-literal
            // domain-ref  =  atom                         ; symbolic reference
            $sub_domain = '(?:' . $atom . '|' . $domain_literal . ')';
            // domain      =  sub-domain *("." sub-domain)
            $domain = $sub_domain . '(?:\.\s*' . $sub_domain . ')*';
            // addr-spec   =  local-part "@" domain        ; global address
            $addr_spec = $local_part . '@\s*' . $domain;
            // route       =  1#("@" domain) ":"           ; path-relative
            $route = '@' . $domain . '(?:,@\s*' . $domain . ')*:\s*';
            // route-addr  =  "<" [route] addr-spec ">"
            $route_addr = '<\s*(?:' . $route . ')?' . $addr_spec . '>\s*';
            // phrase      =  1*word                       ; Sequence of words
            $phrase = $word  . '+';
            // mailbox     =  addr-spec                    ; simple address
            //             /  phrase route-addr            ; name & addr-spec
            $mailbox = '(?:' . $addr_spec . '|' . $phrase . $route_addr . ')';
            // group       =  phrase ":" [#mailbox] ";"
            $group = $phrase . ':\s*(?:' . $mailbox . '(?:,\s*' . $mailbox . ')*)?;\s*';
            //     address     =  mailbox                      ; one addressee
            //                 /  group                        ; named list
            $address = '/^\s*(?:' . $mailbox . '|' . $group . ')$/';

            $uncomment =
            '/((?:(?:\\\\"|[^("])*(?:' . $quoted_string .
                                             ')?)*)((?<!\\\\)\((?:(?2)|.)*?(?<!\\\\)\))/';
        }
        // strip comments
        $email = preg_replace($uncomment, '$1 ', $email);
        return preg_match($address, $email);
    }

    /**
     * Full TLD Validation function
     *
     * This function is used to make a much more proficient validation
     * against all types of official domain names.
     *
     * @param string $email   The email address to check.
     * @param array  $options The options for validation
     *
     * @access protected
     *
     * @return bool True if validating succeeds
     */
    function _fullTLDValidation($email, $options)
    {
        $validate = array();
        if(!empty($options["VALIDATE_ITLD_EMAILS"])) array_push($validate, 'itld');
        if(!empty($options["VALIDATE_GTLD_EMAILS"])) array_push($validate, 'gtld');
        if(!empty($options["VALIDATE_CCTLD_EMAILS"])) array_push($validate, 'cctld');

        $self = new Validate;

        $toValidate = array();

        foreach ($validate as $valid) {
            $tmpVar = '_' . (string)$valid;

            $toValidate[$valid] = $self->{$tmpVar};
        }

        $e = $self->executeFullEmailValidation($email, $toValidate);

        return $e;
    }
    
    /**
     * Execute the validation
     *
     * This function will execute the full email vs tld
     * validation using an array of tlds passed to it.
     *
     * @param string $email       The email to validate.
     * @param array  $arrayOfTLDs The array of the TLDs to validate
     *
     * @access public
     *
     * @return true or false (Depending on if it validates or if it does not)
     */
    function executeFullEmailValidation($email, $arrayOfTLDs)
    {
        $emailEnding = explode('.', $email);
        $emailEnding = $emailEnding[count($emailEnding)-1];
        foreach ($arrayOfTLDs as $validator => $keys) {
            if (in_array($emailEnding, $keys)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate an email
     *
     * @param string $email  email to validate
     * @param mixed  boolean (BC) $check_domain Check or not if the domain exists
     *              array $options associative array of options
     *              'check_domain' boolean Check or not if the domain exists
     *              'use_rfc822' boolean Apply the full RFC822 grammar
     *
     * Ex.
     *  $options = array(
     *      'check_domain' => 'true',
     *      'fullTLDValidation' => 'true',
     *      'use_rfc822' => 'true',
     *      'VALIDATE_GTLD_EMAILS' => 'true',
     *      'VALIDATE_CCTLD_EMAILS' => 'true',
     *      'VALIDATE_ITLD_EMAILS' => 'true',           
     *      );
     *
     * @return boolean true if valid email, false if not
     *
     * @access public
     */
    function email($email, $options = null)
    {
        $check_domain = false;
        $use_rfc822   = false;
        if (is_bool($options)) {
            $check_domain = $options;
        } elseif (is_array($options)) {
            extract($options);
        }

        /**
         * Check for IDN usage so we can encode the domain as Punycode
         * before continuing.
         */
        $hasIDNA = false;

        if (@include_once('Net/IDNA.php')) {
            $hasIDNA = true;
        }

        if ($hasIDNA === true) {
            if (strpos($email, '@') !== false) {
                list($name, $domain) = explode('@', $email, 2);

                // Check if the domain contains characters > 127 which means 
                // it's an idn domain name.
                $chars = count_chars($domain, 1);
                if (!empty($chars) && max(array_keys($chars)) > 127) {
                    $idna   = Net_IDNA::singleton();
                    $domain = $idna->encode($domain);
                }

                $email = "$name@$domain";
            }
        }
        
        /**
         * @todo Fix bug here.. even if it passes this, it won't be passing
         *       The regular expression below
         */
        if (isset($fullTLDValidation)) {
            //$valid = Validate::_fullTLDValidation($email, $fullTLDValidation);
            $valid = Validate::_fullTLDValidation($email, $options);

            if (!$valid) {
                return false;
            }
        }

        // the base regexp for address
        $regex = '&^(?:                                               # recipient:
         ("\s*(?:[^"\f\n\r\t\v\b\s]+\s*)+")|                          #1 quoted name
         ([-\w!\#\$%\&\'*+~/^`|{}]+(?:\.[-\w!\#\$%\&\'*+~/^`|{}]+)*)) #2 OR dot-atom
         @(((\[)?                     #3 domain, 4 as IPv4, 5 optionally bracketed
         (?:(?:(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:[0-1]?[0-9]?[0-9]))\.){3}
               (?:(?:25[0-5])|(?:2[0-4][0-9])|(?:[0-1]?[0-9]?[0-9]))))(?(5)\])|
         ((?:[a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)*[a-z0-9](?:[-a-z0-9]*[a-z0-9])?)  #6 domain as hostname
         \.((?:([^- ])[-a-z]*[-a-z]))) #7 TLD 
         $&xi';

        //checks if exists the domain (MX or A)
        if ($use_rfc822? Validate::__emailRFC822($email, $options) :
                preg_match($regex, $email)) {
            if ($check_domain && function_exists('checkdnsrr')) {
                list ($account, $domain) = explode('@', $email);
                if (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A')) {
                    return true;
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Validate a string using the given format 'format'
     *
     * @param string $string  String to validate
     * @param array  $options Options array where:
     *                          'format' is the format of the string
     *                              Ex:VALIDATE_NUM . VALIDATE_ALPHA (see constants)
     *                          'min_length' minimum length
     *                          'max_length' maximum length
     *
     * @return boolean true if valid string, false if not
     *
     * @access public
     */
    function string($string, $options)
    {
        $format     = null;
        $min_length = 0;
        $max_length = 0;

        if (is_array($options)) {
            extract($options);
        }

        if ($format && !preg_match("|^[$format]*\$|s", $string)) {
            return false;
        }

        if ($min_length && strlen($string) < $min_length) {
            return false;
        }

        if ($max_length && strlen($string) > $max_length) {
            return false;
        }

        return true;
    }

    /**
     * Validate an URI (RFC2396)
     * This function will validate 'foobarstring' by default, to get it to validate
     * only http, https, ftp and such you have to pass it in the allowed_schemes
     * option, like this:
     * <code>
     * $options = array('allowed_schemes' => array('http', 'https', 'ftp'))
     * var_dump(Validate::uri('http://www.example.org', $options));
     * </code>
     *
     * NOTE 1: The rfc2396 normally allows middle '-' in the top domain
     *         e.g. http://example.co-m should be valid
     *         However, as '-' is not used in any known TLD, it is invalid
     * NOTE 2: As double shlashes // are allowed in the path part, only full URIs
     *         including an authority can be valid, no relative URIs
     *         the // are mandatory (optionally preceeded by the 'sheme:' )
     * NOTE 3: the full complience to rfc2396 is not achieved by default
     *         the characters ';/?:@$,' will not be accepted in the query part
     *         if not urlencoded, refer to the option "strict'"
     *
     * @param string $url     URI to validate
     * @param array  $options Options used by the validation method.
     *                          key => type
     *                          'domain_check' => boolean
     *                              Whether to check the DNS entry or not
     *                          'allowed_schemes' => array, list of protocols
     *                              List of allowed schemes ('http',
     *                              'ssh+svn', 'mms')
     *                          'strict' => string the refused chars
     *                              in query and fragment parts
     *                              default: ';/?:@$,'
     *                              empty: accept all rfc2396 foreseen chars
     *
     * @return boolean true if valid uri, false if not
     *
     * @access public
     */
    function uri($url, $options = null)
    {
        $strict = ';/?:@$,';
        $domain_check = false;
        $allowed_schemes = null;
        if (is_array($options)) {
            extract($options);
        }
        if (is_array($allowed_schemes) &&
            in_array("tag", $allowed_schemes)
        ) {
            if (strpos($url, "tag:") === 0) {
                return self::__uriRFC4151($url);
            }
        }

        if (preg_match(
             '&^(?:([a-z][-+.a-z0-9]*):)?                             # 1. scheme
              (?://                                                   # authority start
              (?:((?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'();:\&=+$,])*)@)?    # 2. authority-userinfo
              (?:((?:[a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)*[a-z](?:[a-z0-9]+)?\.?)  # 3. authority-hostname OR
              |([0-9]{1,3}(?:\.[0-9]{1,3}){3}))                       # 4. authority-ipv4
              (?::([0-9]*))?)                                        # 5. authority-port
              ((?:/(?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'():@\&=+$,;])*)*/?)? # 6. path
              (?:\?([^#]*))?                                          # 7. query
              (?:\#((?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'();/?:@\&=+$,])*))? # 8. fragment
              $&xi', $url, $matches)) {
            $scheme = isset($matches[1]) ? $matches[1] : '';
            $authority = isset($matches[3]) ? $matches[3] : '' ;
            if (is_array($allowed_schemes) &&
                !in_array($scheme, $allowed_schemes)
            ) {
                return false;
            }
            if (!empty($matches[4])) {
                $parts = explode('.', $matches[4]);
                foreach ($parts as $part) {
                    if ($part > 255) {
                        return false;
                    }
                }
            } elseif ($domain_check && function_exists('checkdnsrr')) {
                if (!checkdnsrr($authority, 'A')) {
                    return false;
                }
            }
            if ($strict) {
                $strict = '#[' . preg_quote($strict, '#') . ']#';
                if ((!empty($matches[7]) && preg_match($strict, $matches[7]))
                 || (!empty($matches[8]) && preg_match($strict, $matches[8]))) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Validate date and times. Note that this method need the Date_Calc class
     *
     * @param string $date    Date to validate
     * @param array  $options array options where :
     *                          'format' The format of the date (%d-%m-%Y)
     *                                   or rfc822_compliant
     *                          'min'    The date has to be greater
     *                                   than this array($day, $month, $year)
     *                                   or PEAR::Date object
     *                          'max'    The date has to be smaller than
     *                                   this array($day, $month, $year)
     *                                   or PEAR::Date object
     *
     * @return boolean true if valid date/time, false if not
     *
     * @access public
     */
    function date($date, $options)
    {
        $max    = false;
        $min    = false;
        $format = '';

        if (is_array($options)) {
            extract($options);
        }

        if (strtolower($format) == 'rfc822_compliant') {
            $preg = '&^(?:(Mon|Tue|Wed|Thu|Fri|Sat|Sun),) \s+
                    (?:(\d{2})?) \s+
                    (?:(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)?) \s+
                    (?:(\d{2}(\d{2})?)?) \s+
                    (?:(\d{2}?)):(?:(\d{2}?))(:(?:(\d{2}?)))? \s+
                    (?:[+-]\d{4}|UT|GMT|EST|EDT|CST|CDT|MST|MDT|PST|PDT|[A-IK-Za-ik-z])$&xi';

            if (!preg_match($preg, $date, $matches)) {
                return false;
            }

            $year    = (int)$matches[4];
            $months  = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                             'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            $month   = array_keys($months, $matches[3]);
            $month   = (int)$month[0]+1;
            $day     = (int)$matches[2];
            $weekday = $matches[1];
            $hour    = (int)$matches[6];
            $minute  = (int)$matches[7];
            isset($matches[9]) ? $second = (int)$matches[9] : $second = 0;

            if ((strlen($year) != 4)        ||
                ($day    > 31   || $day < 1)||
                ($hour   > 23)  ||
                ($minute > 59)  ||
                ($second > 59)) {
                    return false;
            }
        } else {
            $date_len = strlen($format);
            for ($i = 0; $i < $date_len; $i++) {
                $c = $format{$i};
                if ($c == '%') {
                    $next = $format{$i + 1};
                    switch ($next) {
                    case 'j':
                    case 'd':
                        if ($next == 'j') {
                            $day = (int)Validate::_substr($date, 1, 2);
                        } else {
                            $day = (int)Validate::_substr($date, 0, 2);
                        }
                        if ($day < 1 || $day > 31) {
                            return false;
                        }
                        break;
                    case 'm':
                    case 'n':
                        if ($next == 'm') {
                            $month = (int)Validate::_substr($date, 0, 2);
                        } else {
                            $month = (int)Validate::_substr($date, 1, 2);
                        }
                        if ($month < 1 || $month > 12) {
                            return false;
                        }
                        break;
                    case 'Y':
                    case 'y':
                        if ($next == 'Y') {
                            $year = Validate::_substr($date, 4);
                            $year = (int)$year?$year:'';
                        } else {
                            $year = (int)(substr(date('Y'), 0, 2) .
                                              Validate::_substr($date, 2));
                        }
                        if (strlen($year) != 4 || $year < 0 || $year > 9999) {
                            return false;
                        }
                        break;
                    case 'g':
                    case 'h':
                        if ($next == 'g') {
                            $hour = Validate::_substr($date, 1, 2);
                        } else {
                            $hour = Validate::_substr($date, 2);
                        }
                        if (!preg_match('/^\d+$/', $hour) || $hour < 0 || $hour > 12) {
                            return false;
                        }
                        break;
                    case 'G':
                    case 'H':
                        if ($next == 'G') {
                            $hour = Validate::_substr($date, 1, 2);
                        } else {
                            $hour = Validate::_substr($date, 2);
                        }
                        if (!preg_match('/^\d+$/', $hour) || $hour < 0 || $hour > 24) {
                            return false;
                        }
                        break;
                    case 's':
                    case 'i':
                        $t = Validate::_substr($date, 2);
                        if (!preg_match('/^\d+$/', $t) || $t < 0 || $t > 59) {
                            return false;
                        }
                        break;
                    default:
                        trigger_error("Not supported char `$next' after % in offset " . ($i+2), E_USER_WARNING);
                    }
                    $i++;
                } else {
                    //literal
                    if (Validate::_substr($date, 1) != $c) {
                        return false;
                    }
                }
            }
        }
        // there is remaing data, we don't want it
        if (strlen($date) && (strtolower($format) != 'rfc822_compliant')) {
            return false;
        }

        if (isset($day) && isset($month) && isset($year)) {
            if (!checkdate($month, $day, $year)) {
                return false;
            }

            if (strtolower($format) == 'rfc822_compliant') {
                if ($weekday != date("D", mktime(0, 0, 0, $month, $day, $year))) {
                    return false;
                }
            }

            if ($min) {
                include_once 'Date/Calc.php';
                if (is_a($min, 'Date') &&
                    (Date_Calc::compareDates($day, $month, $year,
                        $min->getDay(), $min->getMonth(), $min->getYear()) < 0)
                ) {
                    return false;
                } elseif (is_array($min) &&
                        (Date_Calc::compareDates($day, $month, $year,
                            $min[0], $min[1], $min[2]) < 0)
                ) {
                    return false;
                }
            }

            if ($max) {
                include_once 'Date/Calc.php';
                if (is_a($max, 'Date') &&
                    (Date_Calc::compareDates($day, $month, $year,
                        $max->getDay(), $max->getMonth(), $max->getYear()) > 0)
                ) {
                    return false;
                } elseif (is_array($max) &&
                        (Date_Calc::compareDates($day, $month, $year,
                            $max[0], $max[1], $max[2]) > 0)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Substr
     *
     * @param string &$date Date
     * @param string $num   Length
     * @param string $opt   Unknown   
     *
     * @access private
     * @return string
     */
    function _substr(&$date, $num, $opt = false)
    {
        if ($opt && strlen($date) >= $opt && preg_match('/^[0-9]{'.$opt.'}/', $date, $m)) {
            $ret = $m[0];
        } else {
            $ret = substr($date, 0, $num);
        }
        $date = substr($date, strlen($ret));
        return $ret;
    }

    function _modf($val, $div)
    {
        if (function_exists('bcmod')) {
            return bcmod($val, $div);
        } elseif (function_exists('fmod')) {
            return fmod($val, $div);
        }
        $r = $val / $div;
        $i = intval($r);
        return intval($val - $i * $div + .1);
    }

    /**
     * Calculates sum of product of number digits with weights
     *
     * @param string $number  number string
     * @param array  $weights reference to array of weights
     *
     * @access protected
     *
     * @return int returns product of number digits with weights
     */
    function _multWeights($number, &$weights)
    {
        if (!is_array($weights)) {
            return -1;
        }
        $sum = 0;

        $count = min(count($weights), strlen($number));
        if ($count == 0) { // empty string or weights array
            return -1;
        }
        for ($i = 0; $i < $count; ++$i) {
            $sum += intval(substr($number, $i, 1)) * $weights[$i];
        }

        return $sum;
    }

    /**
     * Calculates control digit for a given number
     *
     * @param string $number     number string
     * @param array  $weights    reference to array of weights
     * @param int    $modulo     (optionsl) number
     * @param int    $subtract   (optional) number
     * @param bool   $allow_high (optional) true if function can return number higher than 10
     *
     * @access protected
     *
     * @return  int -1 calculated control number is returned
     */
    function _getControlNumber($number, &$weights, $modulo = 10, $subtract = 0, $allow_high = false)
    {
        // calc sum
        $sum = Validate::_multWeights($number, $weights);
        if ($sum == -1) {
            return -1;
        }
        $mod = Validate::_modf($sum, $modulo);  // calculate control digit

        if ($subtract > $mod && $mod > 0) {
            $mod = $subtract - $mod;
        }
        if ($allow_high === false) {
            $mod %= 10;           // change 10 to zero
        }
        return $mod;
    }

    /**
     * Validates a number
     *
     * @param string $number   number to validate
     * @param array  $weights  reference to array of weights
     * @param int    $modulo   (optional) number
     * @param int    $subtract (optional) number
     *
     * @access protected
     *
     * @return  bool true if valid, false if not
     */
    function _checkControlNumber($number, &$weights, $modulo = 10, $subtract = 0)
    {
        if (strlen($number) < count($weights)) {
            return false;
        }
        $target_digit  = substr($number, count($weights), 1);
        $control_digit = Validate::_getControlNumber($number, $weights, $modulo, $subtract, $modulo > 10);

        if ($control_digit == -1) {
            return false;
        }
        if ($target_digit === 'X' && $control_digit == 10) {
            return true;
        }
        if ($control_digit != $target_digit) {
            return false;
        }
        return true;
    }

    /**
     * Bulk data validation for data introduced in the form of an
     * assoc array in the form $var_name => $value.
     * Can be used on any of Validate subpackages
     *
     * @param array   $data     Ex: array('name' => 'toto', 'email' => 'toto@thing.info');
     * @param array   $val_type Contains the validation type and all parameters used in.
     *                          'val_type' is not optional
     *                          others validations properties must have the same name as the function
     *                          parameters.
     *                          Ex: array('toto'=>array('type'=>'string','format'='toto@thing.info','min_length'=>5));
     * @param boolean $remove   if set, the elements not listed in data will be removed
     *
     * @return array   value name => true|false    the value name comes from the data key
     *
     * @access public
     */
    function multiple(&$data, &$val_type, $remove = false)
    {
        $keys  = array_keys($data);
        $valid = array();

        foreach ($keys as $var_name) {
            if (!isset($val_type[$var_name])) {
                if ($remove) {
                    unset($data[$var_name]);
                }
                continue;
            }
            $opt       = $val_type[$var_name];
            $methods   = get_class_methods('Validate');
            $val2check = $data[$var_name];
            // core validation method
            if (in_array(strtolower($opt['type']), $methods)) {
                //$opt[$opt['type']] = $data[$var_name];
                $method = $opt['type'];
                unset($opt['type']);

                if (sizeof($opt) == 1 && is_array(reset($opt))) {
                    $opt = array_pop($opt);
                }
                $valid[$var_name] = call_user_func(array('Validate', $method), $val2check, $opt);

                /**
                 * external validation method in the form:
                 * "<class name><underscore><method name>"
                 * Ex: us_ssn will include class Validate/US.php and call method ssn()
                 */
            } elseif (strpos($opt['type'], '_') !== false) {
                $validateType = explode('_', $opt['type']);
                $method       = array_pop($validateType);
                $class        = implode('_', $validateType);
                $classPath    = str_replace('_', DIRECTORY_SEPARATOR, $class);
                $class        = 'Validate_' . $class;
                if (!@include_once "Validate/$classPath.php") {
                    trigger_error("$class isn't installed or you may have some permissoin issues", E_USER_ERROR);
                }

                $ce = substr(phpversion(), 0, 1) > 4 ?
                    class_exists($class, false) : class_exists($class);
                if (!$ce ||
                    !in_array($method, get_class_methods($class))
                ) {
                    trigger_error("Invalid validation type $class::$method",
                        E_USER_WARNING);
                    continue;
                }
                unset($opt['type']);
                if (sizeof($opt) == 1) {
                    $opt = array_pop($opt);
                }
                $valid[$var_name] = call_user_func(array($class, $method),
                    $data[$var_name], $opt);
            } else {
                trigger_error("Invalid validation type {$opt['type']}",
                    E_USER_WARNING);
            }
        }
        return $valid;
    }
}

