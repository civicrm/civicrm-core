<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AfformAuthxPermissionsTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  private $formName = 'authx_perm_test_form';

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->install('org.civicrm.search_kit')->apply();
  }

  public function tearDown(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    \Civi::$statics = [];
    Afform::revert(FALSE)->addWhere('name', 'LIKE', 'authx_perm_test%')->execute();
    parent::tearDown();
  }

  /**
   * Test that a user with 'administer afform' permission but without
   * 'all CiviCRM permissions and ACLs' cannot set or change the value of 'authx_timeout' or 'authx_redirect'.
   */
  public function testAuthxSettingsPermissions(): void {
    // 1. Create a form as superuser with checkPermissions = FALSE
    Afform::create(FALSE)
      ->addValue('name', $this->formName)
      ->addValue('title', 'Authx Perm Test Form')
      ->addValue('authx_timeout', 10)
      ->addValue('authx_redirect', 'civicrm/custom-redirect')
      ->execute();

    // 2. Set user permissions to 'administer afform' (without 'all CiviCRM permissions and ACLs')
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'administer afform',
      'manage own afform',
    ];

    // 3. User with 'administer afform' tries to change authx_timeout and authx_redirect
    Afform::save(TRUE)
      ->setRecords([
        [
          'name' => $this->formName,
          'title' => 'Authx Perm Test Form Updated',
          'authx_timeout' => 20,
          'authx_redirect' => 'civicrm/unauthorized-redirect',
        ],
      ])
      ->execute();

    // 4. Restore superuser permissions and verify authx_timeout & authx_redirect were NOT changed
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;

    $saved = Afform::get(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute()->single();

    $this->assertEquals('Authx Perm Test Form Updated', $saved['title']);
    $this->assertEquals(10, $saved['authx_timeout']);
    $this->assertEquals('civicrm/custom-redirect', $saved['authx_redirect']);
  }

  /**
   * Test anonymous-user access to prefill and submit a form via msg_token,
   * verifying authx_timeout and authx_redirect settings.
   */
  public function testMsgTokenAnonymousAccess(): void {
    // 1. Create test contact
    $contact = \Civi\Api4\Contact::create(FALSE)
      ->addValue('first_name', 'Anonymous')
      ->addValue('last_name', 'TokenUser')
      ->execute()
      ->first();
    $contactId = $contact['id'];

    // 2. Create form with msg_token placement, authx_timeout, and authx_redirect
    $msgFormName = $this->formName . '_msg_token';
    $serverRoute = 'civicrm/authx-msg-token-test';
    $timeoutDays = 5;
    $redirectUrl = 'civicrm/custom-redirect-page';

    $layout = <<<EOHTML
<af-form>
  <af-entity type="Individual" name="Individual1" actions="{create: true, update: true}" security="FBAC" url-autofill="1" autofill="user" />
  <fieldset af-fieldset="Individual1">
    <af-field name="first_name"></af-field>
    <af-field name="last_name"></af-field>
  </fieldset>
</af-form>
EOHTML;

    Afform::create(FALSE)
      ->addValue('name', $msgFormName)
      ->addValue('title', 'Msg Token Test Form')
      ->addValue('server_route', $serverRoute)
      ->addValue('placement', ['msg_token_single'])
      ->addValue('permission', '@afformPageToken')
      ->addValue('authx_timeout', $timeoutDays)
      ->addValue('authx_redirect', $redirectUrl)
      ->addValue('layout', $layout)
      ->execute();

    unset(\Civi::$statics[Tokens::class]);

    // 3. Verify token forms list includes the new form and create token URL
    $tokenForms = Tokens::getTokenForms();
    $this->assertArrayHasKey($msgFormName, $tokenForms);
    $afformMeta = $tokenForms[$msgFormName];

    $url = Tokens::createUrl($afformMeta, $contactId, ['Individual1' => $contactId]);
    $this->assertNotEmpty($url);

    // Parse URL query parameters
    $urlParts = parse_url($url);
    parse_str($urlParts['query'] ?? '', $queryParams);

    $this->assertArrayHasKey('_aff', $queryParams);
    $this->assertArrayHasKey('_authxRedir', $queryParams);
    $this->assertEquals($redirectUrl, $queryParams['_authxRedir']);

    // Verify JWT payload and expiration based on authx_timeout
    $bearerToken = $queryParams['_aff'];
    $this->assertStringStartsWith('Bearer ', $bearerToken);
    $jwtString = substr($bearerToken, 7);

    /** @var \Civi\Crypto\CryptoJwt $jwt */
    $jwt = \Civi::service('crypto.jwt');
    $claims = $jwt->decode($jwtString);

    $this->assertEquals('cid:' . $contactId, $claims['sub']);
    $this->assertEquals('afform', $claims['scope']);
    $this->assertEquals($msgFormName, $claims['afform']);

    $expectedExp = \CRM_Utils_Time::time() + ($timeoutDays * 24 * 60 * 60);
    $this->assertEqualsWithDelta($expectedExp, $claims['exp'], 10);

    // 4. Verify fallback to default checksum_timeout when authx_timeout is not set
    \Civi::settings()->set('checksum_timeout', 7);

    $noTimeoutFormName = $this->formName . '_no_timeout';
    Afform::create(FALSE)
      ->addValue('name', $noTimeoutFormName)
      ->addValue('title', 'No Timeout Form')
      ->addValue('server_route', 'civicrm/authx-no-timeout')
      ->addValue('placement', ['msg_token_single'])
      ->addValue('permission', '@afformPageToken')
      ->addValue('layout', $layout)
      ->execute();

    unset(\Civi::$statics[Tokens::class]);
    $noTimeoutForm = Tokens::getTokenForms()[$noTimeoutFormName];
    $noTimeoutUrl = Tokens::createUrl($noTimeoutForm, $contactId);
    $noTimeoutParts = parse_url($noTimeoutUrl);
    parse_str($noTimeoutParts['query'] ?? '', $noTimeoutParams);

    $this->assertArrayNotHasKey('_authxRedir', $noTimeoutParams);

    $noTimeoutJwt = substr($noTimeoutParams['_aff'], 7);
    $noTimeoutClaims = $jwt->decode($noTimeoutJwt);
    $defaultTimeout = \Civi::settings()->get('checksum_timeout');
    $expectedDefaultExp = \CRM_Utils_Time::time() + ($defaultTimeout * 24 * 60 * 60);
    $this->assertEqualsWithDelta($expectedDefaultExp, $noTimeoutClaims['exp'], 10);

    // 5. Test anonymous user access (prefill & submit) with token authentication
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];

    $authenticator = \Civi::service('authx.authenticator');
    $authenticator->setRejectMode('exception');

    $event = \Civi\Core\Event\GenericHookEvent::create(['args' => explode('/', $serverRoute)]);
    $authResult = $authenticator->auth($event, [
      'flow' => 'afformpage',
      'cred' => $bearerToken,
      'useSession' => FALSE,
    ]);
    $this->assertTrue($authResult);
    $this->assertEquals($contactId, \CRM_Core_Session::getLoggedInContactID());

    // Anonymous user prefill with checkPermissions = TRUE
    $prefillResult = Afform::prefill(TRUE)
      ->setName($msgFormName)
      ->setFillMode('form')
      ->execute()
      ->indexBy('name');

    $this->assertArrayHasKey('Individual1', $prefillResult);
    $this->assertEquals('Anonymous', $prefillResult['Individual1']['values'][0]['fields']['first_name']);
    $this->assertEquals('TokenUser', $prefillResult['Individual1']['values'][0]['fields']['last_name']);

    // Anonymous user submit with checkPermissions = TRUE
    $submission = [
      [
        'fields' => [
          'first_name' => 'UpdatedAnon',
          'last_name' => 'SubmittedUser',
        ],
      ],
    ];
    Afform::submit(TRUE)
      ->setName($msgFormName)
      ->setValues(['Individual1' => $submission])
      ->execute();

    // Verify contact was updated in database
    unset(\Civi::$statics[Tokens::class]);
    $updatedContact = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('first_name', 'last_name')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->single();

    $this->assertEquals('UpdatedAnon', $updatedContact['first_name']);
    $this->assertEquals('SubmittedUser', $updatedContact['last_name']);
  }

}
