<?php

namespace Civi\Afform;

use Civi\Authx\CheckCredentialEvent;
use Civi\Authx\CheckPolicyEvent;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Civi\Crypto\Exception\CryptoException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Allow Afform-based pages to accept page-level access token
 *
 * Example:
 * - Create a JWT with `[scope => afform, afform => MY_FORM_NAME, sub=>cid:123]`.
 *   This is defined to support "Afform.prefill" and "Afform.submit" on behalf of contact #123.
 * - Navigate to `civicrm/my-form?_aff=Bearer+MY_JWT`
 * - Within the page-view, each AJAX call sets `X-Civi-Auth: MY_JWT`.
 *
 * @service civi.afform.page_token
 */
class PageTokenCredential extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    $events = [];
    $events['civi.invoke.auth'][] = ['onInvoke', 105];
    $events['civi.authx.checkCredential'][] = ['afformPageToken', -400];
    $events['civi.authx.checkPolicy'][] = ['afformPagePolicy', 400];
    return $events;
  }

  /**
   * If you visit a top-level page like "civicrm/my-custom-form?_aff=XXX", then
   * all embedded AJAX calls should "_authx=XXX".
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @return void
   */
  public function onInvoke(GenericHookEvent $e) {
    $token = $_SERVER['HTTP_X_CIVI_AUTH_AFFORM'] ?? $_REQUEST['_aff'] ?? NULL;

    if (empty($token)) {
      return;
    }

    if (!preg_match(';^[a-zA-Z0-9\.\-_ ]+$;', $token)) {
      throw new \CRM_Core_Exception("Malformed page token");
    }

    $authenticated = \Civi::service('authx.authenticator')->auth($e, [
      'flow' => 'afformpage',
      'cred' => $token,
      'siteKey' => NULL,
      'useSession' => FALSE,
    ]);

    _authx_redact(['_aff']);
    if (!$authenticated) {
      return;
    }

    \CRM_Core_Region::instance('page-header')->add([
      'callback' => function() use ($token) {
        $ajaxSetup = [
          'headers' => ['X-Civi-Auth-Afform' => $token],

          // Sending cookies is counter-productive. For same-origin AJAX, there doesn't seem to be an opt-out.
          // The main mitigating factor is that AuthX calls useFakeSession() for our use-case.
          // 'xhrFields' => ['withCredentials' => FALSE],
          // 'crossDomain' => TRUE,
        ];
        $script = sprintf('CRM.$.ajaxSetup(%s);', json_encode($ajaxSetup));
        return "<script type='text/javascript'>\n$script\n</script>";
      },
    ]);
  }

  /**
   * If we get a JWT with `[scope=>afform, afformName=>xyz]`, then setup
   * the current fake-session to allow limited page-views.
   *
   * @param \Civi\Authx\CheckCredentialEvent $check
   *
   * @return void
   */
  public function afformPageToken(CheckCredentialEvent $check) {
    if ($check->credFormat === 'Bearer') {
      try {
        $claims = \Civi::service('crypto.jwt')->decode($check->credValue);
        $scopes = isset($claims['scope']) ? explode(' ', $claims['scope']) : [];
        if (!in_array('afform', $scopes)) {
          // This is not an afform JWT. Proceed to check any other token sources.
          return;
        }
        if (empty($claims['exp'])) {
          $check->reject('Malformed JWT. Must specify an expiration time.');
        }
        if (empty($claims['sub']) || substr($claims['sub'], 0, 4) !== 'cid:') {
          $check->reject('Malformed JWT. Must specify the contact ID.');
        }
        else {
          $contactId = substr($claims['sub'], 4);
          if ($this->checkAllowedRoute($check->getRequestPath(), $claims)) {
            $check->accept(['contactId' => $contactId, 'credType' => 'jwt', 'jwt' => $claims]);
          }
          else {
            $check->reject('JWT specifies a different form or route');
          }
        }
      }
      catch (CryptoException $e) {
        if (str_contains($e->getMessage(), 'Expired token')) {
          $check->reject('Expired token');
        }

        // Not a valid AuthX JWT. Proceed to check any other token sources.
      }
    }

  }

  /**
   * When processing CRM_Core_Invoke, check to see if our token allows us to handle this request.
   *
   * @param string $route
   * @param array $jwt
   * @return bool
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function checkAllowedRoute(string $route, array $jwt): bool {
    $allowedForm = $jwt['afform'];

    $ajaxRoutes = $this->getAllowedRoutes();
    foreach ($ajaxRoutes as $regex => $routeInfo) {
      if (preg_match($regex, $route)) {
        $parsed = json_decode(\CRM_Utils_Request::retrieve('params', 'String'), 1);
        if (empty($parsed)) {
          \Civi::log()->warning("Malformed request. Routes matching $regex must submit params as JSON field.");
          return FALSE;
        }

        $extraFields = array_diff(array_keys($parsed), $routeInfo['allowFields']);
        if (!empty($extraFields)) {
          \Civi::log()->warning("Malformed request. Routes matching $regex only support these input fields: " . json_encode($routeInfo['allowFields']));
          return FALSE;
        }

        if (empty($routeInfo['checkRequest'])) {
          throw new \LogicException("Route ($regex) doesn't define checkRequest.");
        }
        $checkRequest = $routeInfo['checkRequest'];
        if (!$checkRequest($parsed, $jwt)) {
          \Civi::log()->warning("Malformed request. Requested form does not match allowed name ($allowedForm).");
          return FALSE;
        }

        return TRUE;
      }
    }

    // Actually, we may not need this? aiming for model where top page-request auth is irrelevant to subrequests...
    $allowedFormRoute = \Civi\Api4\Afform::get(FALSE)->addWhere('name', '=', $allowedForm)
      ->addSelect('name', 'server_route')
      ->execute()
      ->single();
    if ($allowedFormRoute['server_route'] === $route) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @return array[]
   */
  protected function getAllowedRoutes(): array {
    // These params are common to many Afform actions.
    $abstractProcessorParams = ['name', 'args', 'fillMode'];

    return [
      // ';civicrm/path/to/some/page;' => [
      //
      //    // All the fields that are allowed for this API call.
      //    // N.B. Fields like "chain" are NOT allowed.
      //    'allowFields' => ['field_1', 'field_2', ...]
      //
      //    // Inspect the API-request and assert that the JWT allows these values.
      //    // Generally, check that the JWT's allowed-form-name matches REST's actual-form-name.
      //    'checkRequest' => function(array $request, array $jwt): bool,
      //
      // ],

      ';^civicrm/ajax/api4/Afform/prefill$;' => [
        'allowFields' => $abstractProcessorParams,
        'checkRequest' => fn($request, $jwt) => ($request['name'] === $jwt['afform']),
      ],
      ';^civicrm/ajax/api4/Afform/submit$;' => [
        'allowFields' => [...$abstractProcessorParams, 'values'],
        'checkRequest' => fn($request, $jwt) => ($request['name'] === $jwt['afform']),
      ],
      ';^civicrm/ajax/api4/Afform/submitFile$;' => [
        'allowFields' => [...$abstractProcessorParams, 'token', 'modelName', 'fieldName', 'joinEntity', 'entityIndex', 'joinIndex'],
        'checkRequest' => fn($request, $jwt) => ($request['name'] === $jwt['afform']),
      ],
      ';^civicrm/ajax/api4/\w+/autocomplete$;' => [
        'allowFields' => ['fieldName', 'filters', 'formName', 'ids', 'input', 'page', 'values'],
        'checkRequest' => fn($request, $jwt) => ('afform:' . $jwt['afform']) === $request['formName'],
      ],
      // It's been hypothesized that we'll also need this. Haven't seen it yet.
      // ';^civicrm/ajax/api4/Afform/getFields;' => [
      //   'allowFields' => [],
      //   'checkRequest' => fn($expected, $request) => TRUE,
      // ],
    ];
  }

  /**
   * Afform page-links use a distinct "flow=>afformpage".
   * Define a built-in policy for how this flow works.
   *
   * Listens to civi.authx.checkPolicy (early on - before policy enforcement)
   */
  public function afformPagePolicy(CheckPolicyEvent $event): void {
    $jwt = $event->target->jwt; /* previously validated as per docblock */
    if ($event->target->flow === 'afformpage' && !empty($jwt['afform'])) {
      $event->policy['allowCreds'] = ['jwt'];
      $event->policy['guards'] = [];

      $validUserModes = \Civi\Authx\Meta::getUserModes();
      if (isset($jwt['userMode'], $validUserModes[$jwt['userMode']])) {
        $event->policy['userMode'] = $jwt['userMode'];
      }
      else {
        $event->policy['userMode'] = 'ignore';
      }
    }
  }

}
