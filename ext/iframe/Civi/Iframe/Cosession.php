<?php
namespace Civi\Iframe;

use CRM_Iframe_ExtensionUtil as E;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The co-session provides a long (real) session built on top of short (fake) sessions.
 *
 * Within an iframe context, cookies are unreliable. The CMS creates cookies and sessions,
 * but they only work for 1 page-load. On the next page-load, you're in a new session. These
 * short sessions are kind of pointless - but they're baked into each CMS (*cumbersome to fine-tune*).
 * To achieve session-like behavior, we need to propagate request-parameters instead.
 *
 * For the "iframe co-session", we sync the short-lived CMS sessions with a long-lived co-session.
 *
 * - The co-session is stored separately (via `Civi::cache('session')`)
 * - The co-session is activated by a request-parameter (`?_cosession={JWT}`) instead of a cookie.
 * - The request-parameter is outputted at key moments (e.g. `hook_buildForm`) so that it propagates
 *   to subsequent requests.
 * - As the request begins (*as the CMS session starts*), we import data from the co-session.
 * - As the request finishes (*as the CMS session ends*), we export data back to the co-session.
 *
 * @service iframe.cosession
 */
class Cosession extends AutoService implements EventSubscriberInterface {

  protected $ttl = '+3 hour';

  public static function getSubscribedEvents(): array {
    return [
      '&civi.invoke.auth' => ['onInvoke', 100],
      '&civi.session.storeObjects' => ['export', 0],
      '&hook_civicrm_buildForm' => ['onBuildForm', 0],
      '&hook_civicrm_alterRedirect' => ['onRedirect', 0],
      '&hook_civicrm_activeTheme' => ['pickTheme', 0],
    ];
  }

  /**
   * @var \Civi\Crypto\CryptoJwt
   * @inject crypto.jwt
   */
  protected $jwt;

  protected ?string $sessionId = NULL;

  public function findCreateSessionId(): ?string {
    // We only want to execute this code if CIVICRM_IFRAME is both defined and is truthy (ie. true/1)
    if (!(defined('CIVICRM_IFRAME') && CIVICRM_IFRAME)) {
      return NULL;
    }

    // We store a copy of sessionId on $this. However, there are some unclear situations
    // where $this is recreated in the middle of a request (e.g. toward the end
    // of "civicrm/contribution/transact" flow). This helper allows rereading multiple times.

    if ($this->sessionId === NULL) {
      defined('DBG') && $cleanup = dbg_scope('getSessionId');
      // TODO: Accept _cosession if one of these criteria are met:
      //   - POST to quickform (non-conflicted referer)
      //   - POST to AJAX (non-conflicted referer)
      //   - GET for an approved landing-page
      //     ("Approved" ==> Per-route? Or is distinct to th page-flow/step/JWT?)
      //   - What about GET for AJAX?
      // OR: If it's a GET for approved landing-page, then force-change the underlying sessionId?
      //     In effect, that invalidates all existing tokens. Mitigates known-session-id attacks?
      // MAYBE: Store the entry-point where we started the session. If session ever fails, give link back.
      if (isset($_REQUEST['_cosession'])) {
        $this->sessionId = $this->parseToken($_REQUEST['_cosession']);
        // TODO: In non-debug mode, perhaps we catch JWT exceptions, log them, and redirect/restart - like when you have invalid qfKey.
      }
      else {
        $this->sessionId = $this->createSessionId();
        $_REQUEST['_cosession'] = $this->createToken($this->sessionId);
      }
    }
    return $this->sessionId;
  }

  public function onInvoke(array $path) {
    if (!defined('CIVICRM_IFRAME') || !$this->isEmbeddable(implode('/', $path))) {
      return;
    }

    defined('DBG') && $cleanup = dbg_scope('onInvoke');

    $this->findCreateSessionId();

    $session = \CRM_Core_Session::singleton();
    if ($session->isEmpty()) {
      $session->initialize();
    }
    $this->import();

    // In principle, this will propagate to AJAX subrequests...
    // ...but currently all our JS broken due to CIVICRM_UF_BASEURL override...

    // Defer resolution of sessionId as long as possible
    \CRM_Core_Region::instance('page-header')->add([
      'callback' => function() {
        $token = $this->createToken($this->sessionId);
        $script = sprintf('CRM.$.ajaxSetup({data: {_cosession: %s}});', json_encode($token));
        return "<script type='text/javascript'>\n$script\n</script>";
      },
    ]);
  }

  /**
   * @param $themeKey
   * @param $context
   * @return void
   * @see \CRM_Utils_Hook::activeTheme()
   */
  public function pickTheme(&$themeKey, $context): void {
    if (!defined('CIVICRM_IFRAME')) {
      return;
    }

    $setting = \Civi::settings()->get('iframe_theme');
    if ($setting && $setting !== 'default') {
      $themeKey = $setting;
    }
  }

  /**
   * Determine whether the request is allowed within an iframe iframe.
   *
   * @param string $path
   *  Ex: 'civicrm/foo/bar'
   * @return bool
   *   TRUE if this path is embeddable
   */
  public function isEmbeddable(string $path): bool {
    if (preg_match(';^civicrm/ajax/;', $path)) {
      return TRUE;
    }

    $route = \CRM_Core_Invoke::getItem($path);
    return !empty($route['is_public']) && !empty($route['is_active']);
  }

  /**
   * @see \CRM_Utils_Hook::buildForm()
   */
  public function onBuildForm($formName, $form) {
    if (!$this->findCreateSessionId()) {
      return;
    }
    defined('DBG') && $cleanup = dbg_scope('onBuildForm');
    $form->addElement('hidden', '_cosession', $this->createToken($this->sessionId));
  }

  public function onRedirect(\Psr\Http\Message\UriInterface &$redirectUrl, &$context) {
    if (!$this->findCreateSessionId()) {
      return;
    }

    defined('DBG') && $cleanup = dbg_scope('onRedirect');
    $embedUrl = \Civi::url('iframe://');
    if (\CRM_Utils_Url::isChildOf($redirectUrl, $embedUrl)) {
      $token = $this->createToken($this->sessionId);
      $redirectUrl = $redirectUrl->withQuery($redirectUrl->getQuery() . '&_cosession=' . urlencode($token));
    }
  }

  /**
   * Get the long-lived co-session. Import data into the short-lived CMS session.
   */
  public function import() {
    if (!$this->findCreateSessionId()) {
      return;
    }
    defined('DBG') && $cleanup = dbg_scope('import');
    $sessionData = \Civi::cache('session')->get('co_' . $this->sessionId);
    foreach ($sessionData ?: [] as $key => $value) {
      $_SESSION[$key] = $value;
    }
  }

  /**
   * Export data from the short-lived CMS session. Save it to the co-session.
   */
  public function export() {
    if (!$this->findCreateSessionId()) {
      return;
    }
    defined('DBG') && $cleanup = dbg_scope('export');
    \Civi::cache('session')->set('co_' . $this->sessionId, $_SESSION);
    // TODO: we should probably clean-up the CMS session to avoid leaks.
    // But need to explore/experiment to find the best moment.
  }

  protected function createToken($sessionId): string {
    return $this->jwt->encode([
      'scope' => 'session',
      'sessionId' => $sessionId,
      'sessionIp' => \CRM_Utils_System::ipAddress(),
      'exp' => \CRM_Utils_Time::strtotime($this->ttl),
    ]);
  }

  protected function parseToken(string $token): string {
    $claims = $this->jwt->decode($token);
    if ($claims['scope'] !== 'session') {
      throw new \CRM_Core_Exception("Invalid session token. Missing scope=session");
    }
    if ($claims['sessionIp'] !== \CRM_Utils_System::ipAddress()) {
      throw new \CRM_Core_Exception("Invalid session token. IP address changed");
    }
    return $claims['sessionId'];
  }

  /**
   * @return string
   */
  protected function createSessionId(): string {
    return \CRM_Utils_String::createRandom(32, \CRM_Utils_String::ALPHANUMERIC);
  }

  protected function rotateSessionId(): void {
    $oldId = $this->sessionId;
    $newId = $this->createSessionId();
    $storage = \Civi::cache('session');
    $storage->set($newId, $storage->get($oldId));
    $storage->delete($oldId);
    $this->sessionId = $newId;
  }

}
