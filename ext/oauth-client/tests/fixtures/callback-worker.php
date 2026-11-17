<?php

define('CIVICRM_TEST', 1);
$boot = shell_exec('cv php:boot --level=full');
if (!is_string($boot) || !str_contains($boot, '/*BEGINPHP*/')) {
  throw new RuntimeException('Unable to bootstrap callback worker.');
}
// phpcs:disable
eval($boot);
// phpcs:enable
require_once __DIR__ . '/CallbackProvider.php';

use Civi\OAuth\CallbackProvider;
use Civi\OAuth\OAuthException;
use GuzzleHttp\Psr7\Response;

$config = json_decode(file_get_contents($argv[1]), TRUE, 512, JSON_THROW_ON_ERROR);
CallbackProvider::register();
CallbackProvider::$responses = [
  new Response(200, ['Content-Type' => 'application/json'], json_encode($config['token'])),
  new Response(200, [], json_encode($config['jwks'])),
];
$tokenHooks = $returnHooks = 0;
Civi::dispatcher()->addListener('hook_civicrm_oauthToken', static function () use (&$tokenHooks) {
  $tokenHooks++;
});
Civi::dispatcher()->addListener('hook_civicrm_oauthReturn', static function () use (&$returnHooks) {
  $returnHooks++;
});
// Warm the request-local cache before the race.
Civi::service('oauth2.state')->load($config['state']);
file_put_contents($config['directory'] . '/ready-' . getmypid(), 'ready');
$deadline = microtime(TRUE) + 30;
while (!file_exists($config['directory'] . '/go')) {
  if (microtime(TRUE) > $deadline) {
    throw new RuntimeException('Callback barrier timed out.');
  }
  usleep(1000);
}
usleep((int) ($argv[2] ?? 0));
$_GET = $_REQUEST = ['state' => $config['state'], 'code' => 'mock-code'];
$status = 'accepted';
ob_start();
try {
  (new CRM_OAuth_Page_Return())->run();
}
catch (OAuthException $e) {
  $status = 'rejected';
}
finally {
  ob_end_clean();
}
echo json_encode([
  'pid' => getmypid(),
  'status' => $status,
  'tokenHooks' => $tokenHooks,
  'returnHooks' => $returnHooks,
  'requests' => count(CallbackProvider::$requests),
], JSON_THROW_ON_ERROR);
