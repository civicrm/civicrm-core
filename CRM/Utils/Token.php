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

use Civi\Token\TokenProcessor;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class to abstract token replacement.
 */
class CRM_Utils_Token {
  public static $_requiredTokens = NULL;

  public static $_tokens = [
    'action' => [
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
      'key',
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
  public static function token_replace($type, $var, $value, &$str, $escapeSmarty = FALSE) {
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
   * Get the regex for token replacement
   *
   * @param string $token_type
   *   A string indicating the the type of token to be used in the expression.
   *
   * @return string
   *   regular expression suitable for using in preg_replace
   */
  private static function tokenRegex(string $token_type) {
    return '/(?<!\{|\\\\)\{' . $token_type . '\.([\w]+(:|\.)?\w*(\-[\w\s]+)?)\}(?!\})/';
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
   * @deprecated since ages ago will be removed around 5.90
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
  public static function replaceDomainTokens(
    $str,
    $domain,
    $html = FALSE,
    $knownTokens = NULL,
    $escapeSmarty = FALSE
  ) {
    CRM_Core_Error::deprecatedFunctionWarning('use the Token Processor');
    $key = 'domain';
    if (
      !$knownTokens || empty($knownTokens[$key])
    ) {
      return $str;
    }

    $str = preg_replace_callback(
      self::tokenRegex($key),
      function ($matches) use ($domain, $html, $escapeSmarty) {
        return CRM_Utils_Token::getDomainTokenReplacement($matches[1], $domain, $html, $escapeSmarty);
      },
      $str
    );
    return $str;
  }

  /**
   * Do not use.
   *
   * @deprecated
   *
   * @param string $token
   * @param CRM_Core_BAO_Domain $domain
   * @param bool $html
   * @param bool $escapeSmarty
   *
   * @return null|string
   */
  public static function getDomainTokenReplacement($token, $domain, $html = FALSE, $escapeSmarty = FALSE): ?string {
    $tokens = CRM_Core_DomainTokens::getDomainTokenValues($domain->id, $html);
    $value = $tokens[$token] ?? "{domain.$token}";
    if ($escapeSmarty) {
      $value = self::tokenEscapeSmarty($value);
    }
    return $value;
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
   *
   * @deprecated
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

      // Key is the ID, or the hash when the hash URLs setting is enabled
      case 'key':
        $value = $mailing->id;
        if ($hash = CRM_Mailing_BAO_Mailing::getMailingHash($value)) {
          $value = $hash;
        }
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
   * @deprecated
   *
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
      $value = $addresses[$token] ?? NULL;

      if ($value == NULL) {
        $value = $urls[$token] ?? NULL;
      }

      if ($value && $html) {
        // fix for CRM-2318
        if (substr($token, -3) != 'Url') {
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
   * @deprecated
   *
   * @param bool $escapeSmarty
   *
   * @return string
   *   The processed string
   */
  public static function replaceContactTokens(
    $str,
    &$contact,
    $html = FALSE,
    $knownTokens = NULL,
    $returnBlankToken = FALSE,
    $escapeSmarty = FALSE
  ) {
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
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
   * Do Not use.
   *
   * Only core usage is from a deprecated unused function and
   * from deprecated BAO_Mailing code (to be replaced by flexmailer).
   *
   * @deprecated
   *
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
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
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

    if (!in_array(str_replace(':label', '', $token), self::$_tokens['contact'])) {
      $noReplace = TRUE;
    }
    elseif ($token == 'checksum') {
      $hash = $contact['hash'] ?? NULL;
      $contactID = CRM_Utils_Array::retrieveValueRecursive($contact, 'contact_id');
      $cs = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactID,
        NULL,
        NULL,
        $hash
      );
      $value = "cs={$cs}";
    }
    else {
      $value = (array) CRM_Utils_Array::retrieveValueRecursive($contact, str_replace(':label', '', $token));

      foreach ($value as $index => $item) {
        $value[$index] = self::convertPseudoConstantsUsingMetadata($value[$index], str_replace(':label', '', $token));
      }
      $value = implode(', ', $value);
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
   * Do not use - unused in core.
   *
   * Replace all the hook tokens in $str with information from
   * $contact.
   *
   * @deprecated
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
    $categories = NULL,
    $html = FALSE,
    $escapeSmarty = FALSE
  ) {
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
    if (!$categories) {
      $categories = self::getTokenCategories();
    }
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
   * Get the categories required for rendering tokens.
   *
   * @deprecated since 5.78 will be removed around 5.90.
   *
   * @return array
   */
  public static function getTokenCategories(): array {
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
    if (!isset(\Civi::$statics[__CLASS__]['token_categories'])) {
      $tokens = [];
      \CRM_Utils_Hook::tokens($tokens);
      \Civi::$statics[__CLASS__]['token_categories'] = array_keys($tokens);
    }
    return \Civi::$statics[__CLASS__]['token_categories'];
  }

  /**
   * Do not use, unused in core.
   *
   * @deprecated
   *
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
    $value = $contact["{$category}.{$token}"] ?? NULL;

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
   * @deprecated
   *
   * @param string $str ref to the string that will be scanned and modified
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
   * @param null $youHaveBeenRickRolled
   *   The domain BAO.
   * @param array $groups
   *   The groups (if any) being resubscribed.
   *
   * @return string
   *   The processed string
   */
  public static function replaceResubscribeTokens(
    $str, $youHaveBeenRickRolled, $groups) {
    if (self::token_match('resubscribe', 'group', $str)) {
      if (!empty($groups)) {
        $value = implode(', ', $groups);
        self::token_replace('resubscribe', 'group', $value, $str);
      }
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
   * @deprecated since 5.65 will be removed around 5.71
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
    CRM_Core_Error::deprecatedFunctionWarning('use the token processor');
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
    preg_match_all('/(?<!\{|\\\\)\{(\w+\.\w+(:|.)?\w*)\}(?!\})/',
      $string,
      $matches,
      PREG_PATTERN_ORDER
    );

    if ($matches[1]) {
      foreach ($matches[1] as $token) {
        $parts = explode('.', $token, 3);
        $type = $parts[0];
        $name = $parts[1];
        $suffix = !empty($parts[2]) ? ('.' . $parts[2]) : '';
        if ($name && $type) {
          if (!isset($tokens[$type])) {
            $tokens[$type] = [];
          }
          $tokens[$type][] = $name . $suffix;
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
        [$type, $name] = preg_split('/\./', $token, 2);
        if ($name) {
          $returnProperties["{$name}"] = 1;
        }
      }
    }

    return $returnProperties;
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
   *
   * @deprecated
   */
  public static function getAnonymousTokenDetails($contactIDs = [0],
                                           $returnProperties = NULL,
                                           $skipOnHold = TRUE,
                                           $skipDeceased = TRUE,
                                           $extraParams = NULL,
                                           $tokens = [],
                                           $className = NULL,
                                           $jobID = NULL) {
    $details = [0 => []];
    CRM_Core_Error::deprecatedFunctionWarning('function no longer used - see flexmailer');
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
   *
   * @deprecated
   */
  public static function getMembershipTokenDetails($membershipIDs) {
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
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
    $context = $contactId ? ['contactId' => $contactId] : [];
    if ($contactDetails) {
      $context['contact'] = isset($contactDetails[0]) ? reset($contactDetails[0]) : $contactDetails;
    }
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'smarty' => FALSE,
      'class' => $className,
    ]);
    $tokenProcessor->addRow($context);
    $tokenProcessor->addMessage('greeting', $tokenString, 'text/plain');
    $tokenProcessor->evaluate();
    foreach ($tokenProcessor->getRows() as $row) {
      $tokenString = $row->render('greeting');
    }
  }

  /**
   * @param $tokens
   *
   * @return array
   */
  public static function flattenTokens(&$tokens) {
    $flattenTokens = [];

    foreach (['html', 'text', 'subject'] as $prop) {
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
   *
   * @deprecated since 6.3 will be removed around 6.15
   */
  public static function replaceUserTokens($str, $knownTokens = NULL, $escapeSmarty = FALSE) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
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
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');

    [$objectName, $objectValue] = explode('-', $token, 2);

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

  /**
   * @deprecated
   *
   * Do not use this function - it still needs full removal from active code
   * in CRM_Contribute_Form_Task_PDFLetter.
   */
  protected static function _buildContributionTokens() {
    CRM_Core_Error::deprecatedFunctionWarning('use the token processor');
    $key = 'contribution';

    if (!isset(Civi::$statics[__CLASS__][__FUNCTION__][$key])) {
      $tokens = array_merge(CRM_Contribute_BAO_Contribution::exportableFields('All'),
        ['campaign' => [], 'financial_type' => [], 'payment_instrument' => []],
        self::getCustomFieldTokens('Contribution'),
        [
          'financial_type_id:label',
          'financial_type_id:name',
          'contribution_page_id:label',
          'contribution_page_id:name',
          'payment_instrument_id:label',
          'payment_instrument_id:name',
          'is_test:label',
          'is_pay_later:label',
          'contribution_status_id:label',
          'contribution_status_id:name',
          'is_template:label',
          'campaign_id:label',
          'campaign_id:name',
        ]
      );
      foreach ($tokens as $token) {
        if (!empty($token['name'])) {
          $tokens[$token['name']] = [];
        }
        elseif (is_string($token) && str_contains($token, ':')) {
          $tokens[$token] = [];
        }
      }
      Civi::$statics[__CLASS__][__FUNCTION__][$key] = array_keys($tokens);
    }
    self::$_tokens[$key] = Civi::$statics[__CLASS__][__FUNCTION__][$key];
  }

  /**
   * Do not use.
   *
   * @deprecated
   *
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
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
    if (!$knownTokens || empty($knownTokens[$entity])) {
      return $str;
    }

    $fn = 'get' . ucfirst($entity) . 'TokenReplacement';
    $fn = is_callable(['CRM_Utils_Token', $fn]) ? $fn : 'getApiTokenReplacement';
    // since we already know the tokens lets just use them & do str_replace which is faster & simpler than preg_replace
    foreach ($knownTokens[$entity] as $token) {
      // We are now supporting the syntax case_type_id:label
      // so strip anything after the ':'
      // (we aren't supporting 'name' at this stage, so we can assume 'label'
      // test cover in TokenConsistencyTest.
      $parts = explode(':', $token);
      $replacement = self::$fn($entity, $parts[0], $entityArray);
      if ($escapeSmarty) {
        $replacement = self::tokenEscapeSmarty($replacement);
      }
      $str = str_replace('{' . $entity . '.' . $token . '}', $replacement, $str);
    }
    return preg_replace('/\\\\|\{(\s*)?\}/', ' ', $str);
  }

  /**
   * Generic function for formatting token replacement for an api field
   *
   * @deprecated
   *
   * @param string $entity
   * @param string $token
   * @param array $entityArray
   * @return string
   * @throws \CRM_Core_Exception
   */
  public static function getApiTokenReplacement($entity, $token, $entityArray) {
    CRM_Core_Error::deprecatedFunctionWarning('use the token processor');
    if (!isset($entityArray[$token])) {
      return '';
    }
    $field = civicrm_api3($entity, 'getfield', ['action' => 'get', 'name' => $token, 'get_options' => 'get']);
    $field = $field['values'];
    $fieldType = $field['type'] ?? NULL;
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
    elseif ($entityArray[$token] && in_array($fieldType, [CRM_Utils_Type::T_DATE, CRM_Utils_Type::T_TIMESTAMP, (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)])) {
      return CRM_Utils_Date::customFormat($entityArray[$token]);
    }
    return implode(', ', (array) $entityArray[$token]);
  }

  /**
   * Get replacement strings for any membership tokens (only a small number of tokens are implemnted in the first instance
   * - this is used by the pdfLetter task from membership search
   *
   * This is called via replaceEntityTokens.
   *
   * In the near term it will not be called at all from core as
   * the pdf letter task is updated to use the processor.
   *
   * @deprecated
   *
   * @param string $entity
   *   should always be "membership"
   * @param string $token
   *   field name
   * @param array $membership
   *   An api result array for a single membership.
   * @return string token replacement
   */
  public static function getMembershipTokenReplacement($entity, $token, $membership) {
    CRM_Core_Error::deprecatedFunctionWarning('use the token processor');
    $supportedTokens = [
      'id',
      'status',
      'status_id',
      'type',
      'membership_type_id',
      'start_date',
      'join_date',
      'end_date',
      'fee',
    ];
    switch ($token) {
      case 'type':
        // membership_type_id would only be requested if the calling
        // class is mapping it to '{membership:membership_type_id:label'}
      case 'membership_type_id':
        $value = $membership['membership_name'];
        break;

      case 'status':
        // status_id would only be requested if the calling
        // class is mapping it to '{membership:status_id:label'}
      case 'status_id':
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
        catch (CRM_Core_Exception $e) {
          // we can anticipate we will get an error if the minimum fee is set to 'NULL' because of the way the
          // api handles NULL (4.4)
          $value = 0;
        }
        break;

      default:
        if (in_array($token, $supportedTokens)) {
          $value = $membership[$token];
          if (str_ends_with($token, '_date')) {
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
   * Do not use - unused in core.
   *
   * @param $token
   * @param $contribution
   * @param bool $html
   * @param bool $escapeSmarty
   *
   * @deprecated
   *
   * @return mixed|string
   * @throws \CRM_Core_Exception
   */
  public static function getContributionTokenReplacement($token, $contribution, $html = FALSE, $escapeSmarty = FALSE) {
    CRM_Core_Error::deprecatedFunctionWarning('use the token processor');
    self::_buildContributionTokens();

    switch ($token) {
      case 'total_amount':
      case 'net_amount':
      case 'fee_amount':
      case 'non_deductible_amount':
        // FIXME: Is this ever a multi-dimensional array?  Why use retrieveValueRecursive()?
        $amount = CRM_Utils_Array::retrieveValueRecursive($contribution, $token);
        $currency = CRM_Utils_Array::retrieveValueRecursive($contribution, 'currency');
        $value = CRM_Utils_Money::format($amount, $currency);
        break;

      case 'receive_date':
      case 'receipt_date':
        $value = CRM_Utils_Array::retrieveValueRecursive($contribution, $token);
        $config = CRM_Core_Config::singleton();
        $value = CRM_Utils_Date::customFormat($value, $config->dateformatDatetime);
        break;

      case 'source':
        $value = CRM_Utils_Array::retrieveValueRecursive($contribution, 'contribution_source');
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
   * @deprecated
   *
   * Only used from deprecated functions not called by core.
   *
   * @return array
   *   [legacy_token => new_token]
   */
  public static function legacyContactTokens() {
    CRM_Core_Error::deprecatedFunctionWarning('use the token processor');
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
   * @deprecated
   *
   * @param string $entity
   * @return array
   *   return custom field tokens in array('custom_N' => 'label') format
   */
  public static function getCustomFieldTokens($entity) {
    CRM_Core_Error::deprecatedFunctionWarning('use the token processor');
    $customTokens = [];
    foreach (CRM_Core_BAO_CustomField::getFields($entity) as $id => $info) {
      $customTokens['custom_' . $id] = $info['label'] . ' :: ' . $info['groupTitle'];
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
          $entity = array_key_exists($split[1], CRM_Core_DAO_Address::export()) ? 'Address' : ucwords(str_replace('_', ' ', $split[0]));
        }
        else {
          $entity = 'Contact';
        }
        $sorted[_ts($entity)][] = ['id' => $k, 'text' => $v];
      }
    }

    ksort($sorted);
    foreach ($sorted as $k => $v) {
      $output[] = ['text' => $k, 'children' => $v];
    }

    return $output;
  }

  /**
   * @deprecated
   *
   * @param $value
   * @param $token
   *
   * @return bool|int|mixed|string|null
   */
  protected static function convertPseudoConstantsUsingMetadata($value, $token) {
    CRM_Core_Error::deprecatedFunctionWarning('token processor');
    // Convert pseudoconstants using metadata
    if ($value && is_numeric($value)) {
      $allFields = CRM_Contact_BAO_Contact::exportableFields('All');
      if (!empty($allFields[$token]['pseudoconstant'])) {
        $value = CRM_Core_PseudoConstant::getLabel('CRM_Contact_BAO_Contact', $token, $value);
      }
    }
    elseif ($value && str_ends_with($token, '_date')) {
      $value = CRM_Utils_Date::customFormat($value);
    }
    return $value;
  }

  /**
   * Get token deprecation information.
   *
   * @return array
   */
  public static function getTokenDeprecations(): array {
    return [
      'WorkFlowMessageTemplates' => [
        'contribution_invoice_receipt' => [
          '$display_name' => 'contact.display_name',
          '$dataArray' => ts('see default template for how to show this'),
          '$source' => 'contribution.source',
          '$invoice_number' => 'contribution.invoice_number',
          '$invoice_id' => 'contribution.invoice_id',
          '$defaultCurrency' => 'contribution.currency',
          '$amount' => 'contribution.total_amount',
          '$amountDue' => 'contribution.balance_amount',
          '$amountPaid' => 'contribution.paid_amount',
          '$invoice_date' => 'contribution.receive_date',
          '$refundedStatusId' => 'not available',
          '$pendingStatusId' => 'not available',
          '$cancelledStatusId' => 'not available',
          '$contribution_status_id' => 'contribution.contribution_status_id',
          '$contributionStatusName' => 'contribution.contribution_status_id:name',
          '$subTotal' => 'contribution.tax_amount',
          '$totalTaxAmount' => 'contribution.tax_amount',
          '$is_pay_later' => 'contribution.is_pay_later|bool',
          '$domain_street_address' => 'domain.street_address',
          '$domain_city' => 'domain.city',
          '$domain_phone' => 'domain.phone',
          '$domain_email' => 'domain.email',
          '$domain_supplemental_address_1' => 'domain.supplemental_address_1',
          '$domain_supplemental_address_2' => 'domain.supplemental_address_2',
          '$domain_supplemental_address_3' => 'domain.supplemental_address_3',
          '$domain_postal_code' => 'domain.postal_code',
          '$domain_state' => 'domain.state_province_id:abbr',
          '$domain_country' => 'domain.country_id:abbr',
          '$lineItem' => '$lineItems',
          '$billingName' => 'contribution.address_id.name',
        ],
        'contribution_online_receipt' => [
          '$contributeMode' => ts('no longer available / relevant'),
          '$totalTaxAmount' => 'contribution.tax_amount',
          '$first_name' => 'contact.first_name',
          '$last_name' => 'contact.last_name',
          '$displayName' => 'contact.display_name',
          '$dataArray' => ts('see default template for how to show this'),
          '$priceSetID' => ts('no longer available / relevant'),
          '$useForMember' => ts('no longer available / relevant'),
          '$contributionPageId' => 'contribution.contribution_page_id',
          '$lineItem' => '$lineItems',
          '$billingName' => 'contribution.address_id.name',
        ],
        'membership_offline_receipt' => [
          // receipt_text_renewal appears to be long gone.
          'receipt_text_renewal' => 'receipt_text',
          '$totalTaxAmount' => 'contribution.tax_amount',
          '$getTaxDetails' => ts('no longer available / relevant'),
          '$isAmountZero' => ts('no longer available / relevant'),
          '$dataArray' => ts('see default template for how to show this'),
          '$mem_start_date' => 'membership.start_date',
          '$mem_end_date' => 'membership.end_date',
          '$mem_join_date' => 'membership.join_date',
          '$membership_name' => 'membership.membership_type_id:name',
          '$mem_status' => 'membership.membership_status_id:name',
          '$contributionStatus' => 'contribution.contribution_status_id:name',
          '$contributionStatusID' => 'contribution.contribution_status_id',
          '$receive_date' => 'contribution.receive_date',
          '$formValues' => 'use relevant token/s',
          '$module' => 'unknown',
          '$currency' => 'contribution.currency',
          '$paidBy' => 'contribution.payment_instrument_id:label',
          '$lineItem' => '$lineItems',
          '$billingName' => 'contribution.address_id.name',
          '$address' => 'contribution.address_id.display',
        ],
        'membership_online_receipt' => [
          '$dataArray' => ts('see default template for how to show this'),
          '$mem_start_date' => 'membership.start_date',
          '$mem_end_date' => 'membership.end_date',
          '$mem_join_date' => 'membership.join_date',
          '$membership_name' => 'membership.membership_type_id:name',
          '$mem_type_id' => 'membership.membership_type_id',
          '$mem_status' => 'membership.membership_status_id:name',
          '$receive_date' => 'contribution.receive_date',
          '$currency' => 'contribution.currency',
          '$totalTaxAmount' => 'contribution.tax_amount',
          '$priceSetID' => ts('no longer available / relevant'),
          '$useForMember' => ts('no longer available / relevant'),
          '$contributionPageId' => 'contribution.contribution_page_id',
          '$lineItem' => '$lineItems',
          '$billingName' => 'contribution.address_id.name',
        ],
        'contribution_offline_receipt' => [
          '$totalTaxAmount' => 'contribution.tax_amount',
          '$getTaxDetails' => ts('no longer available / relevant'),
          '$paidBy' => 'contribution.payment_instrument_id:label',
          '$receive_date' => 'contribution.receive_date',
          '$thankyou_date' => 'contribution.thankyou_date',
          '$receipt_date' => 'contribution.receipt_date',
          '$cancel_date' => 'contribution.cancel_date',
          '$lineItem' => '$lineItems',
          '$billingName' => 'contribution.address_id.name',
          '$address' => 'contribution.address_id.display',
        ],
        'event_offline_receipt' => [
          '$contributeMode' => ts('no longer available / relevant'),
          '$getTaxDetails' => ts('no longer available / relevant'),
          '$isAmountZero' => ts('no longer available / relevant'),
          '$dataArray' => ts('see default template for how to show this'),
          '$paidBy' => 'contribution.payment_instrument_id:label',
          '$totalTaxAmount' => 'contribution.tax_amount',
          '$amount' => ts('see default template for how to show this'),
          '$checkNumber' => 'contribution.check_number',
          '$module' => ts('no longer available / relevant'),
          '$register_date' => 'participant.register_date',
          '$receive_date' => 'contribution.receive_date',
          '$is_pay_later' => 'contribution.is_pay_later',
          '$totalAmount' => 'contribution.total_amount',
          '$location' => 'event.location',
          '$isShowLocation' => 'event.is_show_location|boolean',
          '$event.participant_role' => 'participant.role_id:label',
          '$amount_level' => ts('see default template for how to show this'),
          'balanceAmount' => 'contribution.balance_amount',
          '$financialTypeName' => 'contribution.financial_type_id:label',
          '$contributionTypeName' => 'contribution.financial_type_id:label',
          '$trxn_id' => 'contribution.trxn_id',
          '$participant_status_id' => 'participant.status_id',
          '$participant.role' => 'participant.role_id:label',
          '$lineItem' => '$lineItems',
          '$billingName' => 'contribution.address_id.name',
          '$address' => 'contribution.address_id.display',
        ],
        'event_online_receipt' => [
          '`$participant.id`' => 'participant.id',
          '$dataArray' => ts('see default template for how to show this'),
          '$individual' => ts('see default template for how to show this'),
          '$amount' => ts('see default template for how to show this'),
          '$isAdditionalParticipant' => ts('see default template for how to show this'),
          '$location' => 'event.location',
          '$register_date' => 'participant.register_date',
          '$participant.role' => 'participant.role_id:label',
          '$totalAmount' => 'contribution.total_amount',
          '$totalTaxAmount' => 'contribution.tax_amount',
          '$event.participant_role' => 'participant.role_id:label',
          '$paidBy' => 'contribution.payment_instrument_id:label',
          '$title' => 'event.title',
          '$lineItem' => '$lineItems',
          '$participant_status' => 'participant.status_id:label',
          '$billingName' => 'contribution.address_id.name',
        ],
        'participant_transferred' => [
          '$location' => 'event.location',
          '$participant.role' => 'participant.role_id:label',
          '$event.participant_role' => 'participant.role_id:label',
          '$billingName' => 'contribution.address_id.name',
        ],
        'participant_cancelled' => [
          '$location' => 'event.location',
          '$participant.role' => 'participant.role_id:label',
          '$event.participant_role' => 'participant.role_id:label',
          '$billingName' => 'contribution.address_id.name',
        ],
        'participant_expired' => [
          '$location' => 'event.location',
          '$participant.role' => 'participant.role_id:label',
          '$event.participant_role' => 'participant.role_id:label',
          '$billingName' => 'contribution.address_id.name',
        ],
        'participant_confirm' => [
          '$location' => 'event.location',
          '$participant.role' => 'participant.role_id:label',
          '$event.participant_role' => 'participant.role_id:label',
          '$billingName' => 'contribution.address_id.name',
        ],
        'payment_or_refund_notification' => [
          '$location' => 'event.location',
          '$participant.role' => 'participant.role_id:label',
          '$event.participant_role' => 'participant.role_id:label',
          '$contactDisplayName' => 'contact.display_name',
          '$paymentsComplete' => 'contribution.balance_amount',
          '$billingName' => 'contribution.address_id.name',
        ],
        'pledge_acknowledgement' => [
          '$domain' => ts('no longer available / relevant'),
          '$contact' => ts('no longer available / relevant'),
          '$billingName' => 'contribution.address_id.name',
        ],
        'pledge_reminder' => [
          '$domain' => ts('no longer available / relevant'),
          '$contact' => ts('no longer available / relevant'),
          '$billingName' => 'contribution.address_id.name',
        ],
      ],
    ];
  }

}
