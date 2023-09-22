<?php

if (PHP_SAPI !== 'cli-server') {
  throw new \Exception(sprintf("Cannot redirect. The script %s must be launched PHP standalone mode (ex: %s).",
    basename(__FILE__), 'DEST=http://example.com/ php -S localhost:3000'));
}

function buildInputUrl($s) {
  $ssl = !empty($s['HTTPS']) && strtolower($s['HTTPS']) != 'off';
  $url = ($ssl ? 'https' : 'http') . '://' . $s['HTTP_HOST'] . $s['REQUEST_URI'];
  return $url;
}

function buildRedirectUrl($get) {
  $url = getenv('DEST');
  if (empty($url)) {
    throw new \Exception(sprintf("Cannot redirect. The script %s requires environment variable %s.",
      basename(__FILE__), 'DEST'));
  }
  $query = http_build_query($get);
  $delim = strpos($url, '?') === FALSE ? '?' : '&';
  return $url . ($query === '' ? '' : $delim . $query);
}

$inputUrl = buildInputUrl($_SERVER);
$redirectUrl = buildRedirectUrl($_GET);
error_log(sprintf("Redirect:\n  from: %s\n  to: %s\n", $inputUrl, $redirectUrl));
header('Location: ' . $redirectUrl);
