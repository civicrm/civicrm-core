<?php
/*
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          http://recaptcha.net/plugins/php/
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin/create
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
 * AUTHORS:
 *   Mike Crawford
 *   Ben Maurer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The reCAPTCHA server URL's
 */
define("RECAPTCHA_API_SERVER", "http://www.google.com/recaptcha/api.js");
define("RECAPTCHA_API_SECURE_SERVER", "https://www.google.com/recaptcha/api.js");
define("RECAPTCHA_VERIFY_SERVER", "https://www.google.com");

use GuzzleHttp\Client;

/**
 * Encodes the given data into a query string format
 * @param $data - array of string elements to be encoded
 * @return string - encoded request
 */
function _recaptcha_qsencode ($data) {
  $req = "";
  foreach ($data as $key => $value) {
    $req .= $key . '=' . urlencode(stripslashes($value)) . '&';
  }

  // Cut the last '&'
  $req = substr($req, 0, strlen($req) - 1);
  return $req;
}

/**
 * Submits an HTTP POST to a reCAPTCHA server
 * @param string $host
 * @param string $path
 * @param array $data
 * @return string response
 */
function _recaptcha_http_post($host, $path, $data) {
  $client = new Client();
  try {
    $response = $client->request('POST', $host . '/' . $path, ['query' => $data, 'timeout' => \Civi::settings()->get('http_timeout')]);
  }
  catch (Exception $e) {
    return '';
  }
  return (string) $response->getBody();
}

/**
 * Gets the challenge HTML (javascript and non-javascript version).
 * This is called from the browser, and the resulting reCAPTCHA HTML widget
 * is embedded within the HTML form it was called from.
 * @param string $pubkey A public key for reCAPTCHA
 * @param string $error The error given by reCAPTCHA (optional, default is null)
 * @param boolean $use_ssl Should the request be made over ssl? (optional, default is false)
 *
 * @return string - The HTML to be embedded in the user's form.
 */
function recaptcha_get_html ($pubkey, $error = NULL, $use_ssl = FALSE) {
  if ($pubkey == NULL || $pubkey == '') {
    die("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
  }

  if ($use_ssl) {
    $server = RECAPTCHA_API_SECURE_SERVER;
  }
  else {
    $server = RECAPTCHA_API_SERVER;
  }

  $errorpart = "";
  if ($error) {
    $errorpart = "&amp;error=" . $error;
  }
  return '<div class="g-recaptcha" data-sitekey="' . $pubkey . '"></div>
                <script type="text/javascript" src="' . $server . '"></script>

	<noscript>
		<iframe src="' . $server . '" height="300" width="500" frameborder="0"></iframe><br/>
		<div class="g-recaptcha" data-sitekey="' . $pubkey . '"></div>
	</noscript>';
}

/**
 * A ReCaptchaResponse is returned from recaptcha_check_answer()
 */
class ReCaptchaResponse {
  public $is_valid;
  public $error;

}

/**
 * Calls an HTTP POST function to verify if the user's guess was correct
 * @param string $privkey
 * @param string $remoteip
 * @param string $response
 * @param array $extra_params an array of extra variables to post to the server
 * @return ReCaptchaResponse
 */
function recaptcha_check_answer ($privkey, $remoteip, $response, $extra_params = array()) {
  if ($privkey == NULL || $privkey == '') {
    die("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
  }

  if ($remoteip == NULL || $remoteip == '') {
    die("For security reasons, you must pass the remote ip to reCAPTCHA");
  }

  //discard spam submissions
  if ($response == NULL || strlen($response) == 0) {
    $recaptcha_response = new ReCaptchaResponse();
    $recaptcha_response->is_valid = FALSE;
    $recaptcha_response->error = 'incorrect-captcha-sol';
    return $recaptcha_response;
  }

  $validationResponse = _recaptcha_http_post(RECAPTCHA_VERIFY_SERVER, "/recaptcha/api/siteverify",
          [
            'secret' => $privkey,
            'remoteip' => $remoteip,
            'response' => $response,
          ] + $extra_params
        );

  $answers = json_decode($validationResponse, TRUE);
  $recaptcha_response = new ReCaptchaResponse();

  $recaptcha_response->is_valid = $answers['success'] ?? NULL;
  $recaptcha_response->error = $answers['error-codes'] ?? NULL;
  return $recaptcha_response;

}

/**
 * gets a URL where the user can sign up for reCAPTCHA. If your application
 * has a configuration page where you enter a key, you should provide a link
 * using this function.
 * @param string $domain The domain where the page is hosted
 * @param string $appname The name of your application
 */
function recaptcha_get_signup_url ($domain = NULL, $appname = NULL) {
  return "https://www.google.com/recaptcha/admin/create?" . _recaptcha_qsencode(array('domains' => $domain, 'app' => $appname));
}

function _recaptcha_aes_pad($val) {
  $block_size = 16;
  $numpad = $block_size - (strlen($val) % $block_size);
  return str_pad($val, strlen($val) + $numpad, chr($numpad));
}

/**
 * Mailhide related code
 */
function _recaptcha_aes_encrypt($val, $ky) {
  if (!function_exists("mcrypt_encrypt")) {
    die("To use reCAPTCHA Mailhide, you need to have the mcrypt php module installed.");
  }
  $mode = MCRYPT_MODE_CBC;
  $enc = MCRYPT_RIJNDAEL_128;
  $val = _recaptcha_aes_pad($val);
  return mcrypt_encrypt($enc, $ky, $val, $mode, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");
}

function _recaptcha_mailhide_urlbase64 ($x) {
  return strtr(base64_encode($x), '+/', '-_');
}

/**
 * gets the reCAPTCHA Mailhide url for a given email, public key and private key
 */
function recaptcha_mailhide_url($pubkey, $privkey, $email) {
  if ($pubkey == '' || $pubkey == NULL || $privkey == "" || $privkey == NULL) {
    die("To use reCAPTCHA Mailhide, you have to sign up for a public and private key, " .
             "you can do so at <a href='http://www.google.com/recaptcha/mailhide/apikey'>http://www.google.com/recaptcha/mailhide/apikey</a>");
  }

  $ky = pack('H*', $privkey);
  $cryptmail = _recaptcha_aes_encrypt($email, $ky);

  return "http://www.google.com/recaptcha/mailhide/d?k=" . $pubkey . "&c=" . _recaptcha_mailhide_urlbase64($cryptmail);
}

/**
 * gets the parts of the email to expose to the user.
 * eg, given johndoe@example,com return ["john", "example.com"].
 * the email is then displayed as john...@example.com
 */
function _recaptcha_mailhide_email_parts ($email) {
  $arr = preg_split("/@/", $email);

  if (strlen($arr[0]) <= 4) {
    $arr[0] = substr($arr[0], 0, 1);
  }
  elseif (strlen($arr[0]) <= 6) {
    $arr[0] = substr($arr[0], 0, 3);
  }
  else {
    $arr[0] = substr($arr[0], 0, 4);
  }
  return $arr;
}

/**
 * Gets html to display an email address given a public an private key.
 * to get a key, go to:
 *
 * http://www.google.com/recaptcha/mailhide/apikey
 */
function recaptcha_mailhide_html($pubkey, $privkey, $email) {
  $emailparts = _recaptcha_mailhide_email_parts($email);
  $url = recaptcha_mailhide_url($pubkey, $privkey, $email);

  return htmlentities($emailparts[0]) . "<a href='" . htmlentities($url) .
        "' onclick=\"window.open('" . htmlentities($url) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Reveal this e-mail address\">...</a>@" . htmlentities($emailparts[1]);

}
