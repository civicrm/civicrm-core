<?php

/**
 * Class CRM_Utils_CryptTest
 * @group headless
 */
class CRM_Utils_CryptTest extends CiviUnitTestCase {

  public function testMcryptToOpenSSL() {
    $testString = 'This is a test encrpytion';
    if (function_exists('mcrypt_module_open') && defined('CIVICRM_SITE_KEY')) {
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
      // ECB mode - iv not needed - CRM-8198
      $iv = '00000000000000000000000000000000';
      $ks = mcrypt_enc_get_key_size($td);
      $key = substr(sha1(CIVICRM_SITE_KEY), 0, $ks);
      mcrypt_generic_init($td, $key, $iv);
      $string = mcrypt_generic($td, $testString);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
      $mcryptString = base64_encode($string);
      if (function_exists('openssl_encrypt')) {
        $decrptyedMcryptString = CRM_Utils_Crypt::decrypt($mcryptString, TRUE);
        $opensslEncrpyt = CRM_Utils_Crypt::encrypt($decrptyedMcryptString);
        $opensslDecryted = CRM_Utils_Crypt::decrypt($opensslEncrpyt);
        $this->assertEquals($testString, $opensslDecryted);
      }
      else {
        $this->fail('OpenSSL module not avaliable');
      }
    }
    else {
      $this->fail('mcrypt is not enabled or site_key is not defined');
    }
  }

}
