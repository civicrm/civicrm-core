<?php

/**
 * Class CRM_Utils_Mail_FilteredPearMailerTest
 * @group headless
 */
class CRM_Utils_Mail_FilteredPearMailerTest extends CiviUnitTestCase {

  public function testFilter() {
    $mock = new class() extends \Mail {
      public $buf = [];

      public function send($recipients, $headers, $body) {
        $this->buf['recipients'] = $recipients;
        $this->buf['headers'] = $headers;
        $this->buf['body'] = $body;
        return 'all the fruits in the basket';
      }

    };

    $fm = new CRM_Utils_Mail_FilteredPearMailer('mock', [], $mock);
    $fm->addFilter('1000_apple', function ($mailer, &$recipients, &$headers, &$body) {
      $body .= ' with apples!';
    });
    $fm->addFilter('1000_banana', function ($mailer, &$recipients, &$headers, &$body) {
      $headers['Banana'] = 'Cavendish';
    });
    $r = $fm->send(['recip'], ['Subject' => 'Fruit loops'], 'body');

    $this->assertEquals('Fruit loops', $mock->buf['headers']['Subject']);
    $this->assertEquals('Cavendish', $mock->buf['headers']['Banana']);
    $this->assertEquals('body with apples!', $mock->buf['body']);
    $this->assertEquals('all the fruits in the basket', $r);
  }

  public function testFilter_shortCircuit() {
    $mock = new class() extends \Mail {

      public function send($recipients, $headers, $body) {
        return 'all the fruits in the basket';
      }

    };

    $fm = new CRM_Utils_Mail_FilteredPearMailer('mock', [], $mock);
    $fm->addFilter('1000_short_circuit', function ($mailer, &$recipients, &$headers, &$body) {
      return 'the triumph of veggies over fruits';
    });
    $r = $fm->send(['recip'], ['Subject' => 'Fruit loops'], 'body');
    $this->assertEquals('the triumph of veggies over fruits', $r);
  }

}
