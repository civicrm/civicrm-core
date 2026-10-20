<?php

/**
 * Class CRM_Core_IDSTest
 * @group headless
 */
class CRM_Core_IDSTest extends CiviUnitTestCase {

  /**
   * Params sent by the standaloneusers forms.
   *
   * @return array
   */
  public static function standaloneUserParams(): array {
    return [
      'User.login' => [
        [
          'identifier' => 'admin',
          'password' => 'hunter2',
          'originalUrl' => 'https://example.org/civicrm/login',
          'rememberMe' => TRUE,
          'rememberJWT' => 'eyJhbGciOiJIUzI1NiJ9.e30.abc',
        ],
        [
          'identifier' => 'admin',
          'password' => '[redacted]',
          'originalUrl' => 'https://example.org/civicrm/login',
          'rememberMe' => TRUE,
          'rememberJWT' => '[redacted]',
        ],
      ],
      'User.passwordReset' => [
        ['token' => 'one-time-reset-token', 'password' => 'hunter2'],
        ['token' => '[redacted]', 'password' => '[redacted]'],
      ],
      'User.update' => [
        [
          'actorPassword' => 'oldPassword',
          'values' => ['password' => 'newPassword'],
          'where' => [['id', '=', 1]],
        ],
        [
          'actorPassword' => '[redacted]',
          'values' => ['password' => '[redacted]'],
          'where' => [['id', '=', 1]],
        ],
      ],
    ];
  }

  /**
   * @dataProvider standaloneUserParams
   */
  public function testRedactSecrets(array $params, array $expected): void {
    $redacted = CRM_Core_IDS::redactSecrets(json_encode($params));
    $this->assertEquals($expected, json_decode($redacted, TRUE));
  }

}
