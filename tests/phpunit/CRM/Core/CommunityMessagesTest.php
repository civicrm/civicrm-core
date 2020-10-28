<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Core_CommunityMessagesTest
 * @group headless
 */
class CRM_Core_CommunityMessagesTest extends CiviUnitTestCase {

  /**
   * The max difference between two times such that they should be
   * treated as equals (expressed in seconds).
   */
  const APPROX_TIME_EQUALITY = 4;

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * @var array
   * list of possible web responses
   */
  protected static $webResponses = NULL;

  /**
   * @return array
   */
  public static function initWebResponses() {
    if (self::$webResponses === NULL) {
      self::$webResponses = [
        'http-error' => [
          CRM_Utils_HttpClient::STATUS_DL_ERROR,
          NULL,
        ],
        'bad-json' => [
          CRM_Utils_HttpClient::STATUS_OK,
          '<html>this is not json!</html>',
        ],
        'invalid-ttl-document' => [
          CRM_Utils_HttpClient::STATUS_OK,
          json_encode([
            // not an integer!
            'ttl' => 'z',
            // not an integer!
            'retry' => 'z',
            'messages' => [
              [
                'markup' => '<h1>Invalid document</h1>',
              ],
            ],
          ]),
        ],
        'first-valid-response' => [
          CRM_Utils_HttpClient::STATUS_OK,
          json_encode([
            'ttl' => 600,
            'retry' => 600,
            'messages' => [
              [
                'markup' => '<h1>First valid response</h1>',
              ],
            ],
          ]),
        ],
        'second-valid-response' => [
          CRM_Utils_HttpClient::STATUS_OK,
          json_encode([
            'ttl' => 600,
            'retry' => 600,
            'messages' => [
              [
                'markup' => '<h1>Second valid response</h1>',
              ],
            ],
          ]),
        ],
        'two-messages' => [
          CRM_Utils_HttpClient::STATUS_OK,
          json_encode([
            'ttl' => 600,
            'retry' => 600,
            'messages' => [
              [
                'markup' => '<h1>One</h1>',
                'components' => ['CiviMail'],
              ],
              [
                'markup' => '<h1>Two</h1>',
                'components' => ['CiviMail'],
              ],
            ],
          ]),
        ],
        'two-messages-halfbadcomp' => [
          CRM_Utils_HttpClient::STATUS_OK,
          json_encode([
            'ttl' => 600,
            'retry' => 600,
            'messages' => [
              [
                'markup' => '<h1>One</h1>',
                'components' => ['NotARealComponent'],
              ],
              [
                'markup' => '<h1>Two</h1>',
                'components' => ['CiviMail'],
              ],
            ],
          ]),
        ],
      ];
    }
    return self::$webResponses;
  }

  public function setUp() {
    parent::setUp();
    $this->cache = new CRM_Utils_Cache_Arraycache([]);
    self::initWebResponses();
  }

  public function tearDown() {
    parent::tearDown();
    CRM_Utils_Time::resetTime();
  }

  /**
   * A list of bad web-responses; in general, whenever the downloader
   * encounters one of these bad responses, it should ignore the
   * document, retain the old data, and retry again later.
   *
   * @return array
   */
  public function badWebResponses() {
    self::initWebResponses();
    $result = [
      [self::$webResponses['http-error']],
      [self::$webResponses['bad-json']],
      [self::$webResponses['invalid-ttl-document']],
    ];
    return $result;
  }

  public function testIsEnabled() {
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest()
    );
    $this->assertTrue($communityMessages->isEnabled());
  }

  public function testIsEnabled_false() {
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest(),
      FALSE
    );
    $this->assertFalse($communityMessages->isEnabled());
  }

  /**
   * Download a document; after the set expiration period, download again.
   */
  public function testGetDocument_NewOK_CacheOK_UpdateOK() {
    // first try, good response
    CRM_Utils_Time::setTime('2013-03-01 10:00:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest(self::$webResponses['first-valid-response'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals('<h1>First valid response</h1>', $doc1['messages'][0]['markup']);
    $this->assertApproxEquals(strtotime('2013-03-01 10:10:00'), $doc1['expires'], self::APPROX_TIME_EQUALITY);

    // second try, $doc1 hasn't expired yet, so still use it
    CRM_Utils_Time::setTime('2013-03-01 10:09:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest()
    );
    $doc2 = $communityMessages->getDocument();
    $this->assertEquals('<h1>First valid response</h1>', $doc2['messages'][0]['markup']);
    $this->assertApproxEquals(strtotime('2013-03-01 10:10:00'), $doc2['expires'], self::APPROX_TIME_EQUALITY);

    // third try, $doc1 expired, update it
    // more than 2 hours later (DEFAULT_RETRY)
    CRM_Utils_Time::setTime('2013-03-01 12:00:02');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest(self::$webResponses['second-valid-response'])
    );
    $doc3 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Second valid response</h1>', $doc3['messages'][0]['markup']);
    $this->assertApproxEquals(strtotime('2013-03-01 12:10:02'), $doc3['expires'], self::APPROX_TIME_EQUALITY);
  }

  /**
   * First download attempt fails (due to some bad web request).
   * Store the NACK and retry after the default time period (DEFAULT_RETRY).
   *
   * @dataProvider badWebResponses
   * @param array $badWebResponse
   *   Description of a web request that returns some kind of failure.
   */
  public function testGetDocument_NewFailure_CacheOK_UpdateOK($badWebResponse) {
    $this->assertNotEmpty($badWebResponse);

    // first try, bad response
    CRM_Utils_Time::setTime('2013-03-01 10:00:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($badWebResponse)
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals([], $doc1['messages']);
    $this->assertTrue($doc1['expires'] > CRM_Utils_Time::getTimeRaw());

    // second try, $doc1 hasn't expired yet, so still use it
    CRM_Utils_Time::setTime('2013-03-01 10:09:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest()
    );
    $doc2 = $communityMessages->getDocument();
    $this->assertEquals([], $doc2['messages']);
    $this->assertEquals($doc1['expires'], $doc2['expires']);

    // third try, $doc1 expired, try again, get a good response
    // more than 2 hours later (DEFAULT_RETRY)
    CRM_Utils_Time::setTime('2013-03-01 12:00:02');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest(self::$webResponses['first-valid-response'])
    );
    $doc3 = $communityMessages->getDocument();
    $this->assertEquals('<h1>First valid response</h1>', $doc3['messages'][0]['markup']);
    $this->assertTrue($doc3['expires'] > CRM_Utils_Time::getTimeRaw());
  }

  /**
   * First download of new doc is OK.
   * The update fails (due to some bad web response).
   * The old data is retained in the cache.
   * The failure eventually expires.
   * A new update succeeds.
   *
   * @dataProvider badWebResponses
   * @param array $badWebResponse
   *   Description of a web request that returns some kind of failure.
   */
  public function testGetDocument_NewOK_UpdateFailure_CacheOK_UpdateOK($badWebResponse) {
    $this->assertNotEmpty($badWebResponse);

    // first try, good response
    CRM_Utils_Time::setTime('2013-03-01 10:00:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest(self::$webResponses['first-valid-response'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals('<h1>First valid response</h1>', $doc1['messages'][0]['markup']);
    $this->assertApproxEquals(strtotime('2013-03-01 10:10:00'), $doc1['expires'], self::APPROX_TIME_EQUALITY);

    // second try, $doc1 has expired; bad response; keep old data
    // more than 2 hours later (DEFAULT_RETRY)
    CRM_Utils_Time::setTime('2013-03-01 12:00:02');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($badWebResponse)
    );
    $doc2 = $communityMessages->getDocument();
    $this->assertEquals('<h1>First valid response</h1>', $doc2['messages'][0]['markup']);
    $this->assertTrue($doc2['expires'] > CRM_Utils_Time::getTimeRaw());

    // third try, $doc2 hasn't expired yet; no request; keep old data
    CRM_Utils_Time::setTime('2013-03-01 12:09:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest()
    );
    $doc3 = $communityMessages->getDocument();
    $this->assertEquals('<h1>First valid response</h1>', $doc3['messages'][0]['markup']);
    $this->assertEquals($doc2['expires'], $doc3['expires']);

    // fourth try, $doc2 has expired yet; new request; replace data
    CRM_Utils_Time::setTime('2013-03-01 12:10:05');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest(self::$webResponses['second-valid-response'])
    );
    $doc4 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Second valid response</h1>', $doc4['messages'][0]['markup']);
    $this->assertApproxEquals(strtotime('2013-03-01 12:20:05'), $doc4['expires'], self::APPROX_TIME_EQUALITY);
  }

  /**
   * Randomly pick among two options.
   */
  public function testPick_rand() {
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest(self::$webResponses['two-messages'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals('<h1>One</h1>', $doc1['messages'][0]['markup']);
    $this->assertEquals('<h1>Two</h1>', $doc1['messages'][1]['markup']);

    // randomly pick many times
    $trials = 80;
    // array($message => $count)
    $freq = [];
    for ($i = 0; $i < $trials; $i++) {
      $message = $communityMessages->pick();
      $freq[$message['markup']] = CRM_Utils_Array::value($message['markup'], $freq, 0) + 1;
    }

    // assert the probabilities
    $this->assertApproxEquals(0.5, $freq['<h1>One</h1>'] / $trials, 0.3);
    $this->assertApproxEquals(0.5, $freq['<h1>Two</h1>'] / $trials, 0.3);
    $this->assertEquals($trials, $freq['<h1>One</h1>'] + $freq['<h1>Two</h1>']);
  }

  /**
   * When presented with two options using component filters, always
   * choose the one which references an active component.
   */
  public function testPick_componentFilter() {
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest(self::$webResponses['two-messages-halfbadcomp'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals('<h1>One</h1>', $doc1['messages'][0]['markup']);
    $this->assertEquals('<h1>Two</h1>', $doc1['messages'][1]['markup']);

    // randomly pick many times
    $trials = 10;
    // array($message => $count)
    $freq = [];
    for ($i = 0; $i < $trials; $i++) {
      $message = $communityMessages->pick();
      $freq[$message['markup']] = CRM_Utils_Array::value($message['markup'], $freq, 0) + 1;
    }

    $this->assertEquals($trials, $freq['<h1>Two</h1>']);
  }

  public function testEvalMarkup() {
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest()
    );
    $this->assertEquals('cms=UnitTests cms=UnitTests', $communityMessages->evalMarkup('cms=%%uf%% cms={{uf}}'));
  }

  /**
   * Generate a mock HTTP client with the expectation that it is never called.
   *
   * @return CRM_Utils_HttpClient|PHPUnit\Framework\MockObject\MockObject
   */
  protected function expectNoHttpRequest() {
    $client = $this->getMockBuilder('CRM_Utils_HttpClient')->getMock();
    $client->expects($this->never())
      ->method('get');
    return $client;
  }

  /**
   * Generate a mock HTTP client with the expectation that it is called once.
   *
   * @param $response
   *
   * @return CRM_Utils_HttpClient|PHPUnit\Framework\MockObject\MockObject
   */
  protected function expectOneHttpRequest($response) {
    $client = $this->getMockBuilder('CRM_Utils_HttpClient')->getMock();
    $client->expects($this->once())
      ->method('get')
      ->will($this->returnValue($response));
    return $client;
  }

}
