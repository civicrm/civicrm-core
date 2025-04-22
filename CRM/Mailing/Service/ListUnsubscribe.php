<?php

/**
 * Apply a full range of `List-Unsubscribe` header options.
 *
 * @service civi.mailing.listUnsubscribe
 * @link https://datatracker.ietf.org/doc/html/rfc8058
 */
class CRM_Mailing_Service_ListUnsubscribe extends \Civi\Core\Service\AutoService implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  private ?string $urlFlags = NULL;

  public static function getMethods(): array {
    return [
      'mailto' => ts('Mailto'),
      'http' => ts('HTTP(S) Web-Form'),
      'oneclick' => ts('HTTP(S) One-Click'),
    ];
  }

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_alterMailParams' => ['alterMailParams', 1000],
    ];
  }

  /**
   * @see \CRM_Utils_Hook::alterMailParams()
   */
  public function alterMailParams(&$params, $context = NULL): void {
    // FIXME: Flexmailer (BasicHeaders) and BAO (getVerpAndUrlsAndHeaders) separately define
    // `List-Unsubscribe: <mailto:....>`. And they have separate invocations of alterMailParams.
    //
    // This code is a little ugly because it anticipates serving both code-paths.
    // But the BAO path should be properly killed. Doing so will allow you cleanup this code more.

    // SMS messages don't have List-Unsubscribe, so bail early.
    if (!array_key_exists('List-Unsubscribe', $params)) {
      return;
    }
    if (!in_array($context, ['civimail', 'flexmailer'])) {
      return;
    }

    $methods = Civi::settings()->get('civimail_unsubscribe_methods');
    if ($methods === ['mailto']) {
      return;
    }

    $sep = preg_quote(Civi::settings()->get('verpSeparator'), ';');
    $regex = ";^<mailto:[^>]*u{$sep}(\d+){$sep}(\d+){$sep}(\w*)@(.+)>$;";
    if (!preg_match($regex, $params['List-Unsubscribe'], $m)) {
      // This can happen when generating a preview of a mailing or bots
      // crawling public mailings with invalid checkums
      return;
    }

    if ($this->urlFlags === NULL) {
      $this->urlFlags = 'a';
      if (in_array('oneclick', $methods) && empty(parse_url(CIVICRM_UF_BASEURL, PHP_URL_PORT))) {
        // Yahoo etal require HTTPS for one-click URLs. Cron-runs can be a bit inconsistent wrt HTTP(S),
        // so we force-SSL for most production-style sites.
        $this->urlFlags .= 's';
      }
    }

    $listUnsubscribe = [];
    if (in_array('mailto', $methods)) {
      $listUnsubscribe[] = $params['List-Unsubscribe'];
    }
    if (array_intersect(['http', 'oneclick'], $methods)) {
      $listUnsubscribe[] = '<' . Civi::url('frontend://civicrm/mailing/unsubscribe', $this->urlFlags)->addQuery([
        'reset' => 1,
        'jid' => $m[1],
        'qid' => $m[2],
        'h' => $m[3],
      ]) . '>';
    }

    if (in_array('oneclick', $methods)) {
      $params['headers']['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
    }
    $params['headers']['List-Unsubscribe'] = implode(', ', $listUnsubscribe);
    unset($params['List-Unsubscribe']);
  }

}
