<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Class to abstract token replacement.
 */
class CRM_Utils_Token {
  public static $_requiredTokens = NULL;

  public static $_tokens = [
    'action' => [
      'forward',
      'optOut',
      'optOutUrl',
      'reply',
      'unsubscribe',
      'unsubscribeUrl',
      'resubscribe',
      'resubscribeUrl',
      'subscribeUrl',
    ],
    'mailing' => [
      'id',
      'name',
      'group',
      'subject',
      'viewUrl',
      'editUrl',
      'scheduleUrl',
      'approvalStatus',
      'approvalNote',
      'approveUrl',
      'creator',
      'creatorEmail',
    ],
    'user' => [
      // we extract the stuff after the role / permission and return the
      // civicrm email addresses of all users with that role / permission
      // useful with rules integration
      'permission:',
      'role:',
    ],
    // populate this dynamically
    'contact' => NULL,
    // populate this dynamically
    'contribution' => NULL,
    'domain' => [
      'name',
      'phone',
      'address',
      'email',
      'id',
      'description',
    ],
    'subscribe' => ['group'],
    'unsubscribe' => ['group'],
    'resubscribe' => ['group'],
    'welcome' => ['group'],
  ];

  /**
   * @deprecated
   *   This is used by CiviMail but will be made redundant by FlexMailer.
   * @return array
   */
  public static function getRequiredTokens() {
    if (self::$_requiredTokens == NULL) {
      self::$_requiredTokens = [
        'domain.address' => ts("Domain address - displays your organization's postal address."),
        'action.optOutUrl or action.unsubscribeUrl' => [
          'action.optOut' => ts("'Opt out via email' - displays an email address for recipients to opt out of receiving emails from your organization."),
          'action.optOutUrl' => ts("'Opt out via web page' - creates a link for recipients to click if they want to opt out of receiving emails from your organization. Alternatively, you can include the 'Opt out via email' token."),
          'action.unsubscribe' => ts("'Unsubscribe via email' - displays an email address for recipients to unsubscribe from the specific mailing list used to send this message."),
          'action.unsubscribeUrl' => ts("'Unsubscribe via web page' - creates a link for recipients to unsubscribe from the specific mailing list used to send this message. Alternatively, you can include the 'Unsubscribe via email' token or one of the Opt-out tokens."),
        ],
      ];
    }
    return self::$_requiredTokens;
  }

  /**
   * Check a string (mailing body) for required tokens.
   *
   * @param string $str
   *   The message.
   *
   * @return bool|array
   *   true if all required tokens are found,
   *    else an array of the missing tokens
   */
  public static function requiredTokens(&$str) {
    // FlexMailer is a refactoring of CiviMail which provides new hooks/APIs/docs. If the sysadmin has opted to enable it, then use that instead of CiviMail.
    $requiredTokens = defined('CIVICRM_FLEXMAILER_HACK_REQUIRED_TOKENS') ? Civi\Core\Resolver::singleton()->call(CIVICRM_FLEXMAILER_HACK_REQUIRED_TOKENS, []) : CRM_Utils_Token::getRequiredTokens();

    $missing = [];
    foreach ($requiredTokens as $token => $value) {
      if (!is_array($value)) {
        if (!preg_match('/(^|[^\{])' . preg_quote('{' . $token . '}') . '/', $str)) {
          $missing[$token] = $value;
        }
      }
      else {
        $present = FALSE;
        $desc = NULL;
        foreach ($value as $t => $d) {
          $desc = $d;
          if (preg_match('/(^|[^\{])' . preg_quote('{' . $t . '}') . '/', $str)) {
            $present = TRUE;
          }
        }
        if (!$present) {
          $missing[$token] = $desc;
        }
      }
    }

    if (empty($missing)) {
      return TRUE;
    }
    return $missing;
  }

  /**
   * Wrapper for token matching.
   *
   * @param string $type
   *   The token type (domain,mailing,contact,action).
   * @param string $var
   *   The token variable.
   * @param string $str
   *   The string to search.
   *
   * @return bool
   *   Was there a match
   */
  public static function token_match($type, $var, &$str) {
    $token = preg_quote('{' . "$type.$var") . '(\|.+?)?' . preg_quote('}');
    return preg_match("/(^|[^\{])$token/", $str);
  }

  /**
   * Wrapper for token replacing.
   *
   * @param string $type
   *   The token type.
   * @param string $var
   *   The token variable.
   * @param string $value
   *   The value to substitute for the token.
   * @param string $str (reference) The string to replace in
   *
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function &token_replace($type, $var, $value, &$str, $escapeSmarty = FALSE) {
    $token = preg_quote('{' . "$type.$var") . '(\|([^\}]+?))?' . preg_quote('}');
    if (!$value) {
      $value = '$3';
    }
    if ($escapeSmarty) {
      $value = self::tokenEscapeSmarty($value);
    }
    $str = preg_replace("/([^\{])?$token/", "\${1}$value", $str);
    return $str;
  }

  /**
   * Get< the regex for token replacement
   *
   * @param string $token_type
   *   A string indicating the the type of token to be used in the expression.
   *
   * @return string
   *   regular expression sutiable for using in preg_replace
   */
  private static function tokenRegex($token_type) {
    return '/(?<!\{|\\\\)\{' . $token_type . '\.([\w]+(\-[\w\s]+)?)\}(?!\})/';
  }

  /**
   * Escape the string so a malicious user cannot inject smarty code into the template.
   *
   * @param string $string
   *   A string that needs to be escaped from smarty parsing.
   *
   * @return string
   *   the escaped string
   */
  public static function tokenEscapeSmarty($string) {
    // need to use negative look-behind, as both str_replace() and preg_replace() are sequential
    return preg_replace(['/{/', '/(?<!{ldelim)}/'], ['{ldelim}', '{rdelim}'], $string);
  }

  /**
   * Replace all the domain-level tokens in $str
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param object $domain
   *   The domain BAO.
   * @param bool $html
   *   Replace tokens with HTML or plain text.
   *
   * @param null $knownTokens
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function &replaceDomainTokens(
    $str,
    &$domain,
    $html = FALSE,
    $knownTokens = NULL,
    $escapeSmarty = FALSE
  ) {
    $key = 'domain';
    if (
      !$knownTokens || empty($knownTokens[$key])
    ) {
      return $str;
    }

    $str = preg_replace_callback(
      self::tokenRegex($key),
      function ($matches) use (&$domain, $html, $escapeSmarty) {
        return CRM_Utils_Token::getDomainTokenReplacement($matches[1], $domain, $html, $escapeSmarty);
      },
      $str
    );
    return $str;
  }

  /**
   * @param $token
   * @param $domain
   * @param bool $html
   * @param bool $escapeSmarty
   *
   * @return mixed|null|string
   */
  public static function getDomainTokenReplacement($token, &$domain, $html = FALSE, $escapeSmarty = FALSE) {
    // check if the token we were passed is valid
    // we have to do this because this function is
    // called only when we find a token in the string

    $loc = &$domain->getLocationValues();

    if (!in_array($token, self::$_tokens['domain'])) {
      $value = "{domain.$token}";
    }
    elseif ($token == 'address') {
      static $addressCache = [];

      $cache_key = $html ? 'address-html' : 'address-text';
      if (array_key_exists($cache_key, $addressCache)) {
        return $addressCache[$cache_key];
      }

      $value = NULL;
      // Construct the address token

      if (!empty($loc[$token])) {
        if ($html) {
          $value = $loc[$token][1]['display'];
          $value = str_replace("\n", '<br />', $value);
        }
        else {
          $value = $loc[$token][1]['display_text'];
        }
        $addressCache[$cache_key] = $value;
      }
    }
    elseif ($token == 'name' || $token == 'id' || $token == 'description') {
      $value = $domain->$token;
    }
    elseif ($token == 'phone' || $token == 'email') {
      // Construct the phone and email tokens

      $value = NULL;
      if (!empty($loc[$token])) {
        foreach ($loc[$token] as $index => $entity) {
          $value = $entity[$token];
          break;
        }
      }
    }

    if ($escapeSmarty) {
      $value = self::tokenEscapeSmarty($value);
    }

    return $value;
  }

  /**
   * Replace all the org-level tokens in $str
   *
   * @fixme: This function appears to be broken, as it depends on
   * nonexistant method: CRM_Core_BAO_CustomValue::getContactValues()
   * Marking as deprecated until this is fixed
   * @deprecated
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param object $org
   *   Associative array of org properties.
   * @param bool $html
   *   Replace tokens with HTML or plain text.
   *
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function &replaceOrgTokens($str, &$org, $html = FALSE, $escapeSmarty = FALSE) {
    self::$_tokens['org']
      = array_merge(
        array_keys(CRM_Contact_BAO_Contact::importableFields('Organization')),
        ['address', 'display_name', 'checksum', 'contact_id']
      );

    $cv = NULL;
    foreach (self::$_tokens['org'] as $token) {
      // print "Getting token value for $token<br/><br/>";
      if ($token == '') {
        continue;
      }

      // If the string doesn't contain this token, skip it.

      if (!self::token_match('org', $token, $str)) {
        continue;
      }

      // Construct value from $token and $contact

      $value = NULL;

      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($token)) {
        // only generate cv if we need it
        if ($cv === NULL) {
          $cv = CRM_Core_BAO_CustomValue::getContactValues($org['contact_id']);
        }
        foreach ($cv as $cvFieldID => $value) {
          if ($cvFieldID == $cfID) {
            $value = CRM_Core_BAO_CustomField::displayValue($value, $cfID);
            break;
          }
        }
      }
      elseif ($token == 'checksum') {
        $cs = CRM_Contact_BAO_Contact_Utils::generateChecksum($org['contact_id']);
        $value = "cs={$cs}";
      }
      elseif ($token == 'address') {
        // Build the location values array

        $loc = [];
        $loc['display_name'] = CRM_Utils_Array::retrieveValueRecursive($org, 'display_name');
        $loc['street_address'] = CRM_Utils_Array::retrieveValueRecursive($org, 'street_address');
        $loc['city'] = CRM_Utils_Array::retrieveValueRecursive($org, 'city');
        $loc['state_province'] = CRM_Utils_Array::retrieveValueRecursive($org, 'state_province');
        $loc['postal_code'] = CRM_Utils_Array::retrieveValueRecursive($org, 'postal_code');

        // Construct the address token

        $value = CRM_Utils_Address::format($loc);
        if ($html) {
          $value = str_replace("\n", '<br />', $value);
        }
      }
      else {
        $value = CRM_Utils_Array::retrieveValueRecursive($org, $token);
      }

      self::token_replace('org', $token, $value, $str, $escapeSmarty);
    }

    return $str;
  }

  /**
   * Replace all mailing tokens in $str
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param object $mailing
   *   The mailing BAO, or null for validation.
   * @param bool $html
   *   Replace tokens with HTML or plain text.
   *
   * @param null $knownTokens
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function &replaceMailingTokens(
    $str,
    &$mailing,
    $html = FALSE,
    $knownTokens = NULL,
    $escapeSmarty = FALSE
  ) {
    $key = 'mailing';
    if (!$knownTokens || !isset($knownTokens[$key])) {
      return $str;
    }

    $str = preg_replace_callback(
      self::tokenRegex($key),
      function ($matches) use (&$mailing, $escapeSmarty) {
        return CRM_Utils_Token::getMailingTokenReplacement($matches[1], $mailing, $escapeSmarty);
      },
      $str
    );
    return $str;
  }

  /**
   * @param $token
   * @param $mailing
   * @param bool $escapeSmarty
   *
   * @return string
   */
  public static function getMailingTokenReplacement($token, &$mailing, $escapeSmarty = FALSE) {
    $value = '';
    switch ($token) {
      // CRM-7663

      case 'id':
        $value = $mailing ? $mailing->id : 'undefined';
        break;

      case 'name':
        $value = $mailing ? $mailing->name : 'Mailing Name';
        break;

      case 'group':
        $groups = $mailing ? $mailing->getGroupNames() : ['Mailing Groups'];
        $value = implode(', ', $groups);
        break;

      case 'subject':
        $value = $mailing->subject;
        break;

      case 'viewUrl':
        $mailingKey = $mailing->id;
        if ($hash = CRM_Mailing_BAO_Mailing::getMailingHash($mailingKey)) {
          $mailingKey = $hash;
        }
        $value = CRM_Utils_System::url('civicrm/mailing/view',
          "reset=1&id={$mailingKey}",
          TRUE, NULL, FALSE, TRUE
        );
        break;

      case 'editUrl':
      case 'scheduleUrl':
        // Note: editUrl and scheduleUrl used to be different, but now there's
        // one screen which can adapt based on permissions (in workflow mode).
        $value = CRM_Utils_System::url('civicrm/mailing/send',
          "reset=1&mid={$mailing->id}&continue=true",
          TRUE, NULL, FALSE, TRUE
        );
        break;

      case 'html':
        $page = new CRM_Mailing_Page_View();
        $value = $page->run($mailing->id, NULL, FALSE, TRUE);
        break;

      case 'approvalStatus':
        $value = CRM_Core_PseudoConstant::getLabel('CRM_Mailing_DAO_Mailing', 'approval_status_id', $mailing->approval_status_id);
        break;

      case 'approvalNote':
        $value = $mailing->approval_note;
        break;

      case 'approveUrl':
        $value = CRM_Utils_System::url('civicrm/mailing/approve',
          "reset=1&mid={$mailing->id}",
          TRUE, NULL, FALSE, TRUE
        );
        break;

      case 'creator':
        $value = CRM_Contact_BAO_Contact::displayName($mailing->created_id);
        break;

      case 'creatorEmail':
        $value = CRM_Contact_BAO_Contact::getPrimaryEmail($mailing->created_id);
        break;

      default:
        $value = "{mailing.$token}";
        break;
    }

    if ($escapeSmarty) {
      $value = self::tokenEscapeSmarty($value);
    }
    return $value;
  }

  /**
   * Replace all action tokens in $str
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param array $addresses
   *   Assoc. array of VERP event addresses.
   * @param array $urls
   *   Assoc. array of action URLs.
   * @param bool $html
   *   Replace tokens with HTML or plain text.
   * @param array $knownTokens
   *   A list of tokens that are known to exist in the email body.
   *
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function &replaceActionTokens(
    $str,
    &$addresses,
    &$urls,
    $html = FALSE,
    $knownTokens = NULL,
    $escapeSmarty = FALSE
  ) {
    $key = 'action';
    // here we intersect with the list of pre-configured valid tokens
    // so that we remove anything we do not recognize
    // I hope to move this step out of here soon and
    // then we will just iterate on a list of tokens that are passed to us
    if (!$knownTokens || empty($knownTokens[$key])) {
      return $str;
    }

    $str = preg_replace_callback(
      self::tokenRegex($key),
      function ($matches) use (&$addresses, &$urls, $html, $escapeSmarty) {
        return CRM_Utils_Token::getActionTokenReplacement($matches[1], $addresses, $urls, $html, $escapeSmarty);
      },
      $str
    );
    return $str;
  }

  /**
   * @param $token
   * @param $addresses
   * @param $urls
   * @param bool $html
   * @param bool $escapeSmarty
   *
   * @return mixed|string
   */
  public static function getActionTokenReplacement(
    $token,
    &$addresses,
    &$urls,
    $html = FALSE,
    $escapeSmarty = FALSE
  ) {
    // If the token is an email action, use it.  Otherwise, find the
    // appropriate URL.

    if (!in_array($token, self::$_tokens['action'])) {
      $value = "{action.$token}";
    }
    else {
      $value = CRM_Utils_Array::value($token, $addresses);

      if ($value == NULL) {
        $value = CRM_Utils_Array::value($token, $urls);
      }

      if ($value && $html) {
        // fix for CRM-2318
        if ((substr($token, -3) != 'Url') && ($token != 'forward')) {
          $value = "mailto:$value";
        }
      }
      elseif ($value && !$html) {
        $value = str_replace('&amp;', '&', $value);
      }
    }

    if ($escapeSmarty) {
      $value = self::tokenEscapeSmarty($value);
    }
    return $value;
  }

  /**
   * Replace all the contact-level tokens in $str with information from
   * $contact.
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param array $contact
   *   Associative array of contact properties.
   * @param bool $html
   *   Replace tokens with HTML or plain text.
   * @param array $knownTokens
   *   A list of tokens that are known to exist in the email body.
   * @param bool $returnBlankToken
   *   Return unevaluated token if value is null.
   *
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function &replaceContactTokens(
    $str,
    &$contact,
    $html = FALSE,
    $knownTokens = NULL,
    $returnBlankToken = FALSE,
    $escapeSmarty = FALSE
  ) {
    // Refresh contact tokens in case they have changed. There is heavy caching
    // in exportable fields so there is no benefit in doing this conditionally.
    self::$_tokens['contact'] = array_merge(
      array_keys(CRM_Contact_BAO_Contact::exportableFields('All')),
      ['checksum', 'contact_id']
    );

    $key = 'contact';
    // here we intersect with the list of pre-configured valid tokens
    // so that we remove anything we do not recognize
    // I hope to move this step out of here soon and
    // then we will just iterate on a list of tokens that are passed to us
    if (!$knownTokens || empty($knownTokens[$key])) {
      return $str;
    }

    $str = preg_replace_callback(
      self::tokenRegex($key),
      function ($matches) use (&$contact, $html, $returnBlankToken, $escapeSmarty) {
        return CRM_Utils_Token::getContactTokenReplacement($matches[1], $contact, $html, $returnBlankToken, $escapeSmarty);
      },
      $str
    );

    $str = preg_replace('/\\\\|\{(\s*)?\}/', ' ', $str);
    return $str;
  }

  /**
   * @param $token
   * @param $contact
   * @param bool $html
   * @param bool $returnBlankToken
   * @param bool $escapeSmarty
   *
   * @return bool|mixed|null|string
   */
  public static function getContactTokenReplacement(
    $token,
    &$contact,
    $html = FALSE,
    $returnBlankToken = FALSE,
    $escapeSmarty = FALSE
  ) {
    if (self::$_tokens['contact'] == NULL) {
      /* This should come from UF */

      self::$_tokens['contact']
        = array_merge(
          array_keys(CRM_Contact_BAO_Contact::exportableFields('All')),
          ['checksum', 'contact_id']
        );
    }

    // Construct value from $token and $contact

    $value = NULL;
    $noReplace = FALSE;

    // Support legacy tokens
    $token = CRM_Utils_Array::value($token, self::legacyContactTokens(), $token);

    // check if the token we were passed is valid
    // we have to do this because this function is
    // called only when we find a token in the string

    if (!in_array($token, self::$_tokens['contact'])) {
      $noReplace = TRUE;
    }
    elseif ($token == 'checksum') {
      $hash = CRM_Utils_Array::value('hash', $contact);
      $contactID = CRM_Utils_Array::retrieveValueRecursive($contact, 'contact_id');
      $cs = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactID,
        NULL,
        NULL,
        $hash
      );
      $value = "cs={$cs}";
    }
    else {
      $value = CRM_Utils_Array::retrieveValueRecursive($contact, $token);

      // FIXME: for some pseudoconstants we get array ( 0 => id, 1 => label )
      if (is_array($value)) {
        $value = $value[1];
      }
      // Convert pseudoconstants using metadata
      elseif ($value && is_numeric($value)) {
        $allFields = CRM_Contact_BAO_Contact::exportableFields('All');
        if (!empty($allFields[$token]['pseudoconstant'])) {
          $value = CRM_Core_PseudoConstant::getLabel('CRM_Contact_BAO_Contact', $token, $value);
        }
      }
      elseif ($value && CRM_Utils_String::endsWith($token, '_date')) {
        $value = CRM_Utils_Date::customFormat($value);
      }
    }

    if (!$html) {
      $value = str_replace('&amp;', '&', $value);
    }

    // if null then return actual token
    if ($returnBlankToken && !$value) {
      $noReplace = TRUE;
    }

    if ($noReplace) {
      $value = "{contact.$token}";
    }

    if ($escapeSmarty
      && !($returnBlankToken && $noReplace)
    ) {
      // $returnBlankToken means the caller wants to do further attempts at
      // processing unreplaced tokens -- so don't escape them yet in this case.
      $value = self::tokenEscapeSmarty($value);
    }

    return $value;
  }

  /**
   * Replace all the hook tokens in $str with information from
   * $contact.
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param array $contact
   *   Associative array of contact properties (including hook token values).
   * @param $categories
   * @param bool $html
   *   Replace tokens with HTML or plain text.
   *
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function &replaceHookTokens(
    $str,
    &$contact,
    &$categories,
    $html = FALSE,
    $escapeSmarty = FALSE
  ) {
    foreach ($categories as $key) {
      $str = preg_replace_callback(
        self::tokenRegex($key),
        function ($matches) use (&$contact, $key, $html, $escapeSmarty) {
          return CRM_Utils_Token::getHookTokenReplacement($matches[1], $contact, $key, $html, $escapeSmarty);
        },
        $str
      );
    }
    return $str;
  }

  /**
   * Parse html through Smarty resolving any smarty functions.
   * @param string $tokenHtml
   * @param array $entity
   * @param string $entityType
   * @return string
   *   html parsed through smarty
   */
  public static function parseThroughSmarty($tokenHtml, $entity, $entityType = 'contact') {
    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      $smarty = CRM_Core_Smarty::singleton();
      // also add the tokens to the template
      $smarty->assign_by_ref($entityType, $entity);
      $tokenHtml = $smarty->fetch("string:$tokenHtml");
    }
    return $tokenHtml;
  }

  /**
   * @param $token
   * @param $contact
   * @param $category
   * @param bool $html
   * @param bool $escapeSmarty
   *
   * @return mixed|string
   */
  public static function getHookTokenReplacement(
    $token,
    &$contact,
    $category,
    $html = FALSE,
    $escapeSmarty = FALSE
  ) {
    $value = CRM_Utils_Array::value("{$category}.{$token}", $contact);

    if ($value && !$html) {
      $value = str_replace('&amp;', '&', $value);
    }

    if ($escapeSmarty) {
      $value = self::tokenEscapeSmarty($value);
    }

    return $value;
  }

  /**
   * unescapeTokens removes any characters that caused the replacement routines to skip token replacement
   * for example {{token}}  or \{token}  will result in {token} in the final email
   *
   * this routine will remove the extra backslashes and braces
   *
   * @param $str ref to the string that will be scanned and modified
   */
  public static function unescapeTokens(&$str) {
    $str = preg_replace('/\\\\|\{(\{\w+\.\w+\})\}/', '\\1', $str);
  }

  /**
   * Replace unsubscribe tokens.
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param object $domain
   *   The domain BAO.
   * @param array $groups
   *   The groups (if any) being unsubscribed.
   * @param bool $html
   *   Replace tokens with html or plain text.
   * @param int $contact_id
   *   The contact ID.
   * @param string $hash The security hash of the unsub event
   *
   * @return string
   *   The processed string
   */
  public static function &replaceUnsubscribeTokens(
    $str,
    &$domain,
    &$groups,
    $html,
    $contact_id,
    $hash
  ) {
    if (self::token_match('unsubscribe', 'group', $str)) {
      if (!empty($groups)) {
        $config = CRM_Core_Config::singleton();
        $base = CRM_Utils_System::baseURL();

        // FIXME: an ugly hack for CRM-2035, to be dropped once CRM-1799 is implemented
        $dao = new CRM_Contact_DAO_Group();
        $dao->find();
        while ($dao->fetch()) {
          if (substr($dao->visibility, 0, 6) == 'Public') {
            $visibleGroups[] = $dao->id;
          }
        }
        $value = implode(', ', $groups);
        self::token_replace('unsubscribe', 'group', $value, $str);
      }
    }
    return $str;
  }

  /**
   * Replace resubscribe tokens.
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param object $domain
   *   The domain BAO.
   * @param array $groups
   *   The groups (if any) being resubscribed.
   * @param bool $html
   *   Replace tokens with html or plain text.
   * @param int $contact_id
   *   The contact ID.
   * @param string $hash The security hash of the resub event
   *
   * @return string
   *   The processed string
   */
  public static function &replaceResubscribeTokens(
    $str, &$domain, &$groups, $html,
    $contact_id, $hash
  ) {
    if (self::token_match('resubscribe', 'group', $str)) {
      if (!empty($groups)) {
        $value = implode(', ', $groups);
        self::token_replace('resubscribe', 'group', $value, $str);
      }
    }
    return $str;
  }

  /**
   * Replace subscription-confirmation-request tokens
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param string $group
   *   The name of the group being subscribed.
   * @param $url
   * @param bool $html
   *   Replace tokens with html or plain text.
   *
   * @return string
   *   The processed string
   */
  public static function &replaceSubscribeTokens($str, $group, $url, $html) {
    if (self::token_match('subscribe', 'group', $str)) {
      self::token_replace('subscribe', 'group', $group, $str);
    }
    if (self::token_match('subscribe', 'url', $str)) {
      self::token_replace('subscribe', 'url', $url, $str);
    }
    return $str;
  }

  /**
   * Replace subscription-invitation tokens
   *
   * @param string $str
   *   The string with tokens to be replaced.
   *
   * @return string
   *   The processed string
   */
  public static function &replaceSubscribeInviteTokens($str) {
    if (preg_match('/\{action\.subscribeUrl\}/', $str)) {
      $url = CRM_Utils_System::url('civicrm/mailing/subscribe',
        'reset=1',
        TRUE, NULL, FALSE, TRUE
      );
      $str = preg_replace('/\{action\.subscribeUrl\}/', $url, $str);
    }

    if (preg_match('/\{action\.subscribeUrl.\d+\}/', $str, $matches)) {
      foreach ($matches as $key => $value) {
        $gid = substr($value, 21, -1);
        $url = CRM_Utils_System::url('civicrm/mailing/subscribe',
          "reset=1&gid={$gid}",
          TRUE, NULL, FALSE, TRUE
        );
        $str = preg_replace('/' . preg_quote($value) . '/', $url, $str);
      }
    }

    if (preg_match('/\{action\.subscribe.\d+\}/', $str, $matches)) {
      foreach ($matches as $key => $value) {
        $gid = substr($value, 18, -1);
        $config = CRM_Core_Config::singleton();
        $domain = CRM_Core_BAO_MailSettings::defaultDomain();
        $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
        // we add the 0.0000000000000000 part to make this match the other email patterns (with action, two ids and a hash)
        $str = preg_replace('/' . preg_quote($value) . '/', "mailto:{$localpart}s.{$gid}.0.0000000000000000@$domain", $str);
      }
    }
    return $str;
  }

  /**
   * Replace welcome/confirmation tokens
   *
   * @param string $str
   *   The string with tokens to be replaced.
   * @param string $group
   *   The name of the group being subscribed.
   * @param bool $html
   *   Replace tokens with html or plain text.
   *
   * @return string
   *   The processed string
   */
  public static function &replaceWelcomeTokens($str, $group, $html) {
    if (self::token_match('welcome', 'group', $str)) {
      self::token_replace('welcome', 'group', $group, $str);
    }
    return $str;
  }

  /**
   * Find unprocessed tokens (call this last)
   *
   * @param string $str
   *   The string to search.
   *
   * @return array
   *   Array of tokens that weren't replaced
   */
  public static function &unmatchedTokens(&$str) {
    //preg_match_all('/[^\{\\\\]\{(\w+\.\w+)\}[^\}]/', $str, $match);
    preg_match_all('/\{(\w+\.\w+)\}/', $str, $match);
    return $match[1];
  }

  /**
   * Find and replace tokens for each component.
   *
   * @param string $str
   *   The string to search.
   * @param array $contact
   *   Associative array of contact properties.
   * @param array $components
   *   A list of tokens that are known to exist in the email body.
   *
   * @param bool $escapeSmarty
   * @param bool $returnEmptyToken
   *
   * @return string
   *   The processed string
   */
  public static function &replaceComponentTokens(&$str, $contact, $components, $escapeSmarty = FALSE, $returnEmptyToken = TRUE) {
    if (!is_array($components) || empty($contact)) {
      return $str;
    }

    foreach ($components as $name => $tokens) {
      if (!is_array($tokens) || empty($tokens)) {
        continue;
      }

      foreach ($tokens as $token) {
        if (self::token_match($name, $token, $str) && isset($contact[$name . '.' . $token])) {
          self::token_replace($name, $token, $contact[$name . '.' . $token], $str, $escapeSmarty);
        }
        elseif (!$returnEmptyToken) {
          //replacing empty token
          self::token_replace($name, $token, "", $str, $escapeSmarty);
        }
      }
    }
    return $str;
  }

  /**
   * Get array of string tokens.
   *
   * @param string $string
   *   The input string to parse for tokens.
   *
   * @return array
   *   array of tokens mentioned in field
   */
  public static function getTokens($string) {
    $matches = [];
    $tokens = [];
    preg_match_all('/(?<!\{|\\\\)\{(\w+\.\w+)\}(?!\})/',
      $string,
      $matches,
      PREG_PATTERN_ORDER
    );

    if ($matches[1]) {
      foreach ($matches[1] as $token) {
        list($type, $name) = preg_split('/\./', $token, 2);
        if ($name && $type) {
          if (!isset($tokens[$type])) {
            $tokens[$type] = [];
          }
          $tokens[$type][] = $name;
        }
      }
    }
    return $tokens;
  }

  /**
   * Function to determine which values to retrieve to insert into tokens. The heavy resemblance between this function
   * and getTokens appears to be historical rather than intentional and should be reviewed
   * @param $string
   * @return array
   *   fields to pass in as return properties when populating token
   */
  public static function getReturnProperties(&$string) {
    $returnProperties = [];
    $matches = [];
    preg_match_all('/(?<!\{|\\\\)\{(\w+\.\w+)\}(?!\})/',
      $string,
      $matches,
      PREG_PATTERN_ORDER
    );
    if ($matches[1]) {
      foreach ($matches[1] as $token) {
        list($type, $name) = preg_split('/\./', $token, 2);
        if ($name) {
          $returnProperties["{$name}"] = 1;
        }
      }
    }

    return $returnProperties;
  }

  /**
   * Gives required details of contacts in an indexed array format so we
   * can iterate in a nice loop and do token evaluation
   *
   * @param array $contactIDs
   * @param array $returnProperties
   *   Of required properties.
   * @param bool $skipOnHold Don't return on_hold contact info also.
   *   Don't return on_hold contact info also.
   * @param bool $skipDeceased Don't return deceased contact info.
   *   Don't return deceased contact info.
   * @param array $extraParams
   *   Extra params.
   * @param array $tokens
   *   The list of tokens we've extracted from the content.
   * @param null $className
   * @param int $jobID
   *   The mailing list jobID - this is a legacy param.
   *
   * @return array
   */
  public static function getTokenDetails(
    $contactIDs,
    $returnProperties = NULL,
    $skipOnHold = TRUE,
    $skipDeceased = TRUE,
    $extraParams = NULL,
    $tokens = [],
    $className = NULL,
    $jobID = NULL
  ) {

    $params = [];
    foreach ($contactIDs as $contactID) {
      $params[] = [
        CRM_Core_Form::CB_PREFIX . $contactID,
        '=',
        1,
        0,
        0,
      ];
    }

    // fix for CRM-2613
    if ($skipDeceased) {
      $params[] = ['is_deceased', '=', 0, 0, 0];
    }

    //fix for CRM-3798
    if ($skipOnHold) {
      $params[] = ['on_hold', '=', 0, 0, 0];
    }

    if ($extraParams) {
      $params = array_merge($params, $extraParams);
    }

    // if return properties are not passed then get all return properties
    if (empty($returnProperties)) {
      $fields = array_merge(array_keys(CRM_Contact_BAO_Contact::exportableFields()),
        ['display_name', 'checksum', 'contact_id']
      );
      foreach ($fields as $val) {
        // The unavailable fields are not available as tokens, do not have a one-2-one relationship
        // with contacts and are expensive to resolve.
        // @todo see CRM-17253 - there are some other fields (e.g note) that should be excluded
        // and upstream calls to this should populate return properties.
        $unavailableFields = ['group', 'tag'];
        if (!in_array($val, $unavailableFields)) {
          $returnProperties[$val] = 1;
        }
      }
    }

    $custom = [];
    foreach ($returnProperties as $name => $dontCare) {
      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);
      if ($cfID) {
        $custom[] = $cfID;
      }
    }

    $details = CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, NULL, NULL, 0, count($contactIDs), TRUE, FALSE, TRUE, CRM_Contact_BAO_Query::MODE_CONTACTS, NULL, TRUE);

    $contactDetails = &$details[0];

    foreach ($contactIDs as $contactID) {
      if (array_key_exists($contactID, $contactDetails)) {
        if (!empty($contactDetails[$contactID]['preferred_communication_method'])
        ) {
          $communicationPreferences = [];
          foreach ($contactDetails[$contactID]['preferred_communication_method'] as $val) {
            if ($val) {
              $communicationPreferences[$val] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', 'preferred_communication_method', $val);
            }
          }
          $contactDetails[$contactID]['preferred_communication_method'] = implode(', ', $communicationPreferences);
        }

        foreach ($custom as $cfID) {
          if (isset($contactDetails[$contactID]["custom_{$cfID}"])) {
            $contactDetails[$contactID]["custom_{$cfID}"] = CRM_Core_BAO_CustomField::displayValue($contactDetails[$contactID]["custom_{$cfID}"], $cfID);
          }
        }

        // special case for greeting replacement
        foreach ([
          'email_greeting',
          'postal_greeting',
          'addressee',
        ] as $val) {
          if (!empty($contactDetails[$contactID][$val])) {
            $contactDetails[$contactID][$val] = $contactDetails[$contactID]["{$val}_display"];
          }
        }
      }
    }

    // also call a hook and get token details
    CRM_Utils_Hook::tokenValues($details[0],
      $contactIDs,
      $jobID,
      $tokens,
      $className
    );
    return $details;
  }

  /**
   * Call hooks on tokens for anonymous users - contact id is set to 0 - this allows non-contact
   * specific tokens to be rendered
   *
   * @param array $contactIDs
   *   This should always be array(0) or its not anonymous - left to keep signature same.
   *   as main fn
   * @param string $returnProperties
   * @param bool $skipOnHold
   * @param bool $skipDeceased
   * @param string $extraParams
   * @param array $tokens
   * @param string $className
   *   Sent as context to the hook.
   * @param string $jobID
   * @return array
   *   contactDetails with hooks swapped out
   */
  public static function getAnonymousTokenDetails($contactIDs = [
    0,
  ],
                                           $returnProperties = NULL,
                                           $skipOnHold = TRUE,
                                           $skipDeceased = TRUE,
                                           $extraParams = NULL,
                                           $tokens = [],
                                           $className = NULL,
                                           $jobID = NULL) {
    $details = [0 => []];
    // also call a hook and get token details
    CRM_Utils_Hook::tokenValues($details[0],
      $contactIDs,
      $jobID,
      $tokens,
      $className
    );
    return $details;
  }

  /**
   * Get Membership Token Details.
   * @param array $membershipIDs
   *   Array of membership IDS.
   */
  public static function getMembershipTokenDetails($membershipIDs) {
    $memberships = civicrm_api3('membership', 'get', [
      'options' => ['limit' => 0],
      'membership_id' => ['IN' => (array) $membershipIDs],
    ]);
    return $memberships['values'];
  }

  /**
   * Replace existing greeting tokens in message/subject.
   *
   * This function operates by reference, modifying the first parameter. Other
   * methods for token replacement in this class return the modified string.
   * This leads to inconsistency in how these methods must be applied.
   *
   * @TODO Remove that inconsistency in usage.
   *
   * ::replaceContactTokens() may need to be called after this method, to
   * replace tokens supplied from this method.
   *
   * @param string $tokenString
   * @param array $contactDetails
   * @param int $contactId
   * @param string $className
   * @param bool $escapeSmarty
   */
  public static function replaceGreetingTokens(&$tokenString, $contactDetails = NULL, $contactId = NULL, $className = NULL, $escapeSmarty = FALSE) {

    if (!$contactDetails && !$contactId) {
      return;
    }

    // check if there are any tokens
    $greetingTokens = self::getTokens($tokenString);

    if (!empty($greetingTokens)) {
      // first use the existing contact object for token replacement
      if (!empty($contactDetails)) {
        $tokenString = CRM_Utils_Token::replaceContactTokens($tokenString, $contactDetails, TRUE, $greetingTokens, TRUE, $escapeSmarty);
      }

      self::removeNullContactTokens($tokenString, $contactDetails, $greetingTokens);
      // check if there are any unevaluated tokens
      $greetingTokens = self::getTokens($tokenString);

      // $greetingTokens not empty, means there are few tokens which are not
      // evaluated, like custom data etc
      // so retrieve it from database
      if (!empty($greetingTokens) && array_key_exists('contact', $greetingTokens)) {
        $greetingsReturnProperties = array_flip(CRM_Utils_Array::value('contact', $greetingTokens));
        $greetingsReturnProperties = array_fill_keys(array_keys($greetingsReturnProperties), 1);
        $contactParams = ['contact_id' => $contactId];

        $greetingDetails = self::getTokenDetails($contactParams,
          $greetingsReturnProperties,
          FALSE, FALSE, NULL,
          $greetingTokens,
          $className
        );

        // again replace tokens
        $tokenString = CRM_Utils_Token::replaceContactTokens($tokenString,
          $greetingDetails,
          TRUE,
          $greetingTokens,
          TRUE,
          $escapeSmarty
        );
      }

      // check if there are still any unevaluated tokens
      $remainingTokens = self::getTokens($tokenString);

      // $greetingTokens not empty, there are customized or hook tokens to replace
      if (!empty($remainingTokens)) {
        // Fill the return properties array
        $greetingTokens = $remainingTokens;
        reset($greetingTokens);
        $greetingsReturnProperties = [];
        foreach ($greetingTokens as $value) {
          $props = array_flip($value);
          $props = array_fill_keys(array_keys($props), 1);
          $greetingsReturnProperties = $greetingsReturnProperties + $props;
        }
        $contactParams = ['contact_id' => $contactId];
        $greetingDetails = self::getTokenDetails($contactParams,
          $greetingsReturnProperties,
          FALSE, FALSE, NULL,
          $greetingTokens,
          $className
        );
        // Prepare variables for calling replaceHookTokens
        $categories = array_keys($greetingTokens);
        list($contact) = $greetingDetails;
        // Replace tokens defined in Hooks.
        $tokenString = CRM_Utils_Token::replaceHookTokens($tokenString, $contact[$contactId], $categories);
      }
    }
  }

  /**
   * At this point, $contactDetails has loaded the contact from the DAO. Any
   * (non-custom) missing fields are null.  By removing them, we can avoid
   * expensive calls to CRM_Contact_BAO_Query.
   *
   * @param string $tokenString
   * @param array $contactDetails
   * @param array $greetingTokens
   */
  private static function removeNullContactTokens(&$tokenString, $contactDetails, &$greetingTokens) {
    $greetingTokensOriginal = $greetingTokens;
    $contactFieldList = CRM_Contact_DAO_Contact::fields();
    // Sometimes contactDetails are in a multidemensional array, sometimes a
    // single-dimension array.
    if (array_key_exists(0, $contactDetails) && is_array($contactDetails[0])) {
      $contactDetails = current($contactDetails[0]);
    }
    $nullFields = array_keys(array_diff_key($contactFieldList, $contactDetails));

    // Handle legacy tokens
    foreach (self::legacyContactTokens() as $oldToken => $newToken) {
      if (CRM_Utils_Array::key($newToken, $nullFields)) {
        $nullFields[] = $oldToken;
      }
    }

    // Remove null contact fields from $greetingTokens
    $greetingTokens['contact'] = array_diff($greetingTokens['contact'], $nullFields);

    // Also remove them from $tokenString
    $removedTokens = array_diff($greetingTokensOriginal['contact'], $greetingTokens['contact']);
    // Handle legacy tokens again, sigh
    if (!empty($removedTokens)) {
      foreach ($removedTokens as $token) {
        if (CRM_Utils_Array::value($token, self::legacyContactTokens()) !== NULL) {
          $removedTokens[] = CRM_Utils_Array::value($token, self::legacyContactTokens());
        }
      }
      foreach ($removedTokens as $token) {
        $tokenString = str_replace("{contact.$token}", '', $tokenString);
      }
    }
  }

  /**
   * @param $tokens
   *
   * @return array
   */
  public static function flattenTokens(&$tokens) {
    $flattenTokens = [];

    foreach ([
      'html',
      'text',
      'subject',
    ] as $prop) {
      if (!isset($tokens[$prop])) {
        continue;
      }
      foreach ($tokens[$prop] as $type => $names) {
        if (!isset($flattenTokens[$type])) {
          $flattenTokens[$type] = [];
        }
        foreach ($names as $name) {
          $flattenTokens[$type][$name] = 1;
        }
      }
    }

    return $flattenTokens;
  }

  /**
   * Replace all user tokens in $str
   *
   * @param string $str
   *   The string with tokens to be replaced.
   *
   * @param null $knownTokens
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function &replaceUserTokens($str, $knownTokens = NULL, $escapeSmarty = FALSE) {
    $key = 'user';
    if (!$knownTokens ||
      !isset($knownTokens[$key])
    ) {
      return $str;
    }

    $str = preg_replace_callback(
      self::tokenRegex($key),
      function ($matches) use ($escapeSmarty) {
        return CRM_Utils_Token::getUserTokenReplacement($matches[1], $escapeSmarty);
      },
      $str
    );
    return $str;
  }

  /**
   * @param $token
   * @param bool $escapeSmarty
   *
   * @return string
   */
  public static function getUserTokenReplacement($token, $escapeSmarty = FALSE) {
    $value = '';

    list($objectName, $objectValue) = explode('-', $token, 2);

    switch ($objectName) {
      case 'permission':
        $value = CRM_Core_Permission::permissionEmails($objectValue);
        break;

      case 'role':
        $value = CRM_Core_Permission::roleEmails($objectValue);
        break;
    }

    if ($escapeSmarty) {
      $value = self::tokenEscapeSmarty($value);
    }

    return $value;
  }

  protected static function _buildContributionTokens() {
    $key = 'contribution';
    if (self::$_tokens[$key] == NULL) {
      self::$_tokens[$key] = array_keys(array_merge(CRM_Contribute_BAO_Contribution::exportableFields('All'),
        ['campaign', 'financial_type'],
        self::getCustomFieldTokens('Contribution')
      ));
    }
  }

  /**
   * Store membership tokens on the static _tokens array.
   */
  protected static function _buildMembershipTokens() {
    $key = 'membership';
    if (!isset(self::$_tokens[$key]) || self::$_tokens[$key] == NULL) {
      $membershipTokens = [];
      $tokens = CRM_Core_SelectValues::membershipTokens();
      foreach ($tokens as $token => $dontCare) {
        $membershipTokens[] = substr($token, (strpos($token, '.') + 1), -1);
      }
      self::$_tokens[$key] = $membershipTokens;
    }
  }

  /**
   * Replace tokens for an entity.
   * @param string $entity
   * @param array $entityArray
   *   (e.g. in format from api).
   * @param string $str
   *   String to replace in.
   * @param array $knownTokens
   *   Array of tokens present.
   * @param bool $escapeSmarty
   * @return string
   *   string with replacements made
   */
  public static function replaceEntityTokens($entity, $entityArray, $str, $knownTokens = [], $escapeSmarty = FALSE) {
    if (!$knownTokens || empty($knownTokens[$entity])) {
      return $str;
    }

    $fn = 'get' . ucfirst($entity) . 'TokenReplacement';
    $fn = is_callable(['CRM_Utils_Token', $fn]) ? $fn : 'getApiTokenReplacement';
    // since we already know the tokens lets just use them & do str_replace which is faster & simpler than preg_replace
    foreach ($knownTokens[$entity] as $token) {
      $replacement = self::$fn($entity, $token, $entityArray);
      if ($escapeSmarty) {
        $replacement = self::tokenEscapeSmarty($replacement);
      }
      $str = str_replace('{' . $entity . '.' . $token . '}', $replacement, $str);
    }
    return preg_replace('/\\\\|\{(\s*)?\}/', ' ', $str);
  }

  /**
   * @param int $caseId
   * @param int $str
   * @param array $knownTokens
   * @param bool $escapeSmarty
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  public static function replaceCaseTokens($caseId, $str, $knownTokens = [], $escapeSmarty = FALSE) {
    if (!$knownTokens || empty($knownTokens['case'])) {
      return $str;
    }
    $case = civicrm_api3('case', 'getsingle', ['id' => $caseId]);
    return self::replaceEntityTokens('case', $case, $str, $knownTokens, $escapeSmarty);
  }

  /**
   * Generic function for formatting token replacement for an api field
   *
   * @param string $entity
   * @param string $token
   * @param array $entityArray
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  public static function getApiTokenReplacement($entity, $token, $entityArray) {
    if (!isset($entityArray[$token])) {
      return '';
    }
    $field = civicrm_api3($entity, 'getfield', ['action' => 'get', 'name' => $token, 'get_options' => 'get']);
    $field = $field['values'];
    $fieldType = CRM_Utils_Array::value('type', $field);
    // Boolean fields
    if ($fieldType == CRM_Utils_Type::T_BOOLEAN && empty($field['options'])) {
      $field['options'] = [ts('No'), ts('Yes')];
    }
    // Match pseudoconstants
    if (!empty($field['options'])) {
      $ret = [];
      foreach ((array) $entityArray[$token] as $val) {
        $ret[] = $field['options'][$val];
      }
      return implode(', ', $ret);
    }
    // Format date fields
    elseif ($entityArray[$token] && $fieldType == CRM_Utils_Type::T_DATE) {
      return CRM_Utils_Date::customFormat($entityArray[$token]);
    }
    return implode(', ', (array) $entityArray[$token]);
  }

  /**
   * Replace Contribution tokens in html.
   *
   * @param string $str
   * @param array $contribution
   * @param bool|string $html
   * @param string $knownTokens
   * @param bool|string $escapeSmarty
   *
   * @return mixed
   */
  public static function replaceContributionTokens($str, &$contribution, $html = FALSE, $knownTokens = NULL, $escapeSmarty = FALSE) {
    $key = 'contribution';
    if (!$knownTokens || !CRM_Utils_Array::value($key, $knownTokens)) {
      //early return
      return $str;
    }
    self::_buildContributionTokens();

    // here we intersect with the list of pre-configured valid tokens
    // so that we remove anything we do not recognize
    // I hope to move this step out of here soon and
    // then we will just iterate on a list of tokens that are passed to us

    $str = preg_replace_callback(
      self::tokenRegex($key),
      function ($matches) use (&$contribution, $html, $escapeSmarty) {
        return CRM_Utils_Token::getContributionTokenReplacement($matches[1], $contribution, $html, $escapeSmarty);
      },
      $str
    );

    $str = preg_replace('/\\\\|\{(\s*)?\}/', ' ', $str);
    return $str;
  }

  /**
   * We have a situation where we are rendering more than one token in each field because we are combining
   * tokens from more than one contribution when pdf thank you letters are grouped (CRM-14367)
   *
   * The replaceContributionToken doesn't handle receive_date correctly in this scenario because of the formatting
   * it applies (other tokens are OK including date fields)
   *
   * So we sort this out & then call the main function. Note that we are not escaping smarty on this fields like the main function
   * does - but the fields is already being formatted through a date function
   *
   * @param string $separator
   * @param string $str
   * @param array $contribution
   * @param bool|string $html
   * @param string $knownTokens
   * @param bool|string $escapeSmarty
   *
   * @return string
   */
  public static function replaceMultipleContributionTokens($separator, $str, &$contribution, $html = FALSE, $knownTokens = NULL, $escapeSmarty = FALSE) {
    if (empty($knownTokens['contribution'])) {
      return $str;
    }

    if (in_array('receive_date', $knownTokens['contribution'])) {
      $formattedDates = [];
      $dates = explode($separator, $contribution['receive_date']);
      foreach ($dates as $date) {
        $formattedDates[] = CRM_Utils_Date::customFormat($date, NULL, ['j', 'm', 'Y']);
      }
      $str = str_replace("{contribution.receive_date}", implode($separator, $formattedDates), $str);
      unset($knownTokens['contribution']['receive_date']);
    }
    return self::replaceContributionTokens($str, $contribution, $html, $knownTokens, $escapeSmarty);
  }

  /**
   * Get replacement strings for any membership tokens (only a small number of tokens are implemnted in the first instance
   * - this is used by the pdfLetter task from membership search
   * @param string $entity
   *   should always be "membership"
   * @param string $token
   *   field name
   * @param array $membership
   *   An api result array for a single membership.
   * @return string token replacement
   */
  public static function getMembershipTokenReplacement($entity, $token, $membership) {
    self::_buildMembershipTokens();
    switch ($token) {
      case 'type':
        $value = $membership['membership_name'];
        break;

      case 'status':
        $statuses = CRM_Member_BAO_Membership::buildOptions('status_id');
        $value = $statuses[$membership['status_id']];
        break;

      case 'fee':
        try {
          $value = civicrm_api3('membership_type', 'getvalue', [
            'id' => $membership['membership_type_id'],
            'return' => 'minimum_fee',
          ]);
          $value = CRM_Utils_Money::format($value, NULL, NULL, TRUE);
        }
        catch (CiviCRM_API3_Exception $e) {
          // we can anticipate we will get an error if the minimum fee is set to 'NULL' because of the way the
          // api handles NULL (4.4)
          $value = 0;
        }
        break;

      default:
        if (in_array($token, self::$_tokens[$entity])) {
          $value = $membership[$token];
          if (CRM_Utils_String::endsWith($token, '_date')) {
            $value = CRM_Utils_Date::customFormat($value);
          }
        }
        else {
          // ie unchanged
          $value = "{$entity}.{$token}";
        }
        break;
    }

    return $value;
  }

  /**
   * @param $token
   * @param $contribution
   * @param bool $html
   * @param bool $escapeSmarty
   *
   * @return mixed|string
   */
  public static function getContributionTokenReplacement($token, &$contribution, $html = FALSE, $escapeSmarty = FALSE) {
    self::_buildContributionTokens();

    switch ($token) {
      case 'total_amount':
      case 'net_amount':
      case 'fee_amount':
      case 'non_deductible_amount':
        $value = CRM_Utils_Money::format(CRM_Utils_Array::retrieveValueRecursive($contribution, $token));
        break;

      case 'receive_date':
        $value = CRM_Utils_Array::retrieveValueRecursive($contribution, $token);
        $value = CRM_Utils_Date::customFormat($value, NULL, ['j', 'm', 'Y']);
        break;

      default:
        if (!in_array($token, self::$_tokens['contribution'])) {
          $value = "{contribution.$token}";
        }
        else {
          $value = CRM_Utils_Array::retrieveValueRecursive($contribution, $token);
        }
        break;
    }

    if ($escapeSmarty) {
      $value = self::tokenEscapeSmarty($value);
    }
    return $value;
  }

  /**
   * @return array
   *   [legacy_token => new_token]
   */
  public static function legacyContactTokens() {
    return [
      'individual_prefix' => 'prefix_id',
      'individual_suffix' => 'suffix_id',
      'gender' => 'gender_id',
      'communication_style' => 'communication_style_id',
    ];
  }

  /**
   * Get all custom field tokens of $entity
   *
   * @param string $entity
   * @param bool $usedForTokenWidget
   *
   * @return array
   *   return custom field tokens in array('custom_N' => 'label') format
   */
  public static function getCustomFieldTokens($entity, $usedForTokenWidget = FALSE) {
    $customTokens = [];
    $tokenName = $usedForTokenWidget ? "{contribution.custom_%d}" : "custom_%d";
    foreach (CRM_Core_BAO_CustomField::getFields($entity) as $id => $info) {
      $customTokens[sprintf($tokenName, $id)] = $info['label'];
    }

    return $customTokens;
  }

  /**
   * Formats a token list for the select2 widget
   *
   * @param $tokens
   * @return array
   */
  public static function formatTokensForDisplay($tokens) {
    $sorted = $output = [];

    // Sort in ascending order by ignoring word case
    natcasesort($tokens);

    // Attempt to place tokens into optgroups
    // @todo These groupings could be better and less hackish. Getting them pre-grouped from upstream would be nice.
    foreach ($tokens as $k => $v) {
      // Check to see if this token is already in a group e.g. for custom fields
      $split = explode(' :: ', $v);
      if (!empty($split[1])) {
        $sorted[$split[1]][] = ['id' => $k, 'text' => $split[0]];
      }
      // Group by entity
      else {
        $split = explode('.', trim($k, '{}'));
        if (isset($split[1])) {
          $entity = array_key_exists($split[1], CRM_Core_DAO_Address::export()) ? 'Address' : ucfirst($split[0]);
        }
        else {
          $entity = 'Contact';
        }
        $sorted[ts($entity)][] = ['id' => $k, 'text' => $v];
      }
    }

    ksort($sorted);
    foreach ($sorted as $k => $v) {
      $output[] = ['text' => $k, 'children' => $v];
    }

    return $output;
  }

}
