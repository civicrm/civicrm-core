<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Core_CommunityMessagesTest extends CiviUnitTestCase {

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * @var array list of possible web responses
   */
  protected $webResponses;

  public function setUp() {
    parent::setUp();

    $this->cache = new CRM_Utils_Cache_Arraycache(array());

    $this->webResponses = array(
      'http-error' => array(
        CRM_Utils_HttpClient::STATUS_DL_ERROR,
        NULL
      ),
      'bad-json' => array(
        CRM_Utils_HttpClient::STATUS_OK,
        '<html>this is not json!</html>'
      ),
      'invalid-ttl-document' => array(
        CRM_Utils_HttpClient::STATUS_OK,
        json_encode(array(
          'ttl' => 'z', // not an integer!
          'retry' => 'z', // not an integer!
          'messages' => array(
            array(
              'markup' => '<h1>Invalid document</h1>',
            ),
          ),
        ))
      ),
      'hello-world' => array(
        CRM_Utils_HttpClient::STATUS_OK,
        json_encode(array(
          'ttl' => 600,
          'retry' => 600,
          'messages' => array(
            array(
              'markup' => '<h1>Hello world</h1>',
            ),
          ),
        ))
      ),
      'salut-a-tout' => array(
        CRM_Utils_HttpClient::STATUS_OK,
        json_encode(array(
          'ttl' => 600,
          'retry' => 600,
          'messages' => array(
            array(
              'markup' => '<h1>Salut a tout</h1>',
            ),
          ),
        ))
      ),
    );
  }

  public function tearDown() {
    parent::tearDown();
    CRM_Utils_Time::resetTime();
  }

  public function testGetDocument_disabled() {
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest(),
      FALSE
    );
    $doc = $communityMessages->getDocument();
    $this->assertTrue(NULL === $doc);
  }

  /**
   * Download a document; after the set expiration period, download again.
   */
  public function testGetDocument_NewOK_CacheOK_UpdateOK() {
    // first try, good response
    CRM_Utils_Time::setTime('2013-03-01 10:00:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['hello-world'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc1['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 10:10:00'), $doc1['expires']);

    // second try, $doc1 hasn't expired yet, so still use it
    CRM_Utils_Time::setTime('2013-03-01 10:09:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest()
    );
    $doc2 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc2['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 10:10:00'), $doc2['expires']);

    // third try, $doc1 expired, update it
    CRM_Utils_Time::setTime('2013-03-01 12:00:02'); // more than 2 hours later (DEFAULT_RETRY)
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['salut-a-tout'])
    );
    $doc3 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Salut a tout</h1>', $doc3['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 12:10:02'), $doc3['expires']);
  }

  /**
   * First download attempt fails. Store the NACK and retry after
   * the default time period (DEFAULT_RETRY).
   */
  public function testGetDocument_NewFailure_CacheOK_UpdateOK() {
    // first try, bad response
    CRM_Utils_Time::setTime('2013-03-01 10:00:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['http-error'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals(array(), $doc1['messages']);
    $this->assertTrue($doc1['expires'] > CRM_Utils_Time::getTimeRaw());

    // second try, $doc1 hasn't expired yet, so still use it
    CRM_Utils_Time::setTime('2013-03-01 10:09:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest()
    );
    $doc2 = $communityMessages->getDocument();
    $this->assertEquals(array(), $doc2['messages']);
    $this->assertEquals($doc1['expires'], $doc2['expires']);

    // third try, $doc1 expired, try again, get a good response
    CRM_Utils_Time::setTime('2013-03-01 12:00:02'); // more than 2 hours later (DEFAULT_RETRY)
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['hello-world'])
    );
    $doc3 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc3['messages'][0]['markup']);
    $this->assertTrue($doc3['expires'] > CRM_Utils_Time::getTimeRaw());
  }

  /**
   * First download of new doc is OK.
   * The update fails.
   * The failure cached.
   * The failure eventually expires and new update succeeds.
   */
  public function testGetDocument_NewOK_UpdateFailure_CacheOK_UpdateOK() {
    // first try, good response
    CRM_Utils_Time::setTime('2013-03-01 10:00:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['hello-world'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc1['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 10:10:00'), $doc1['expires']);

    // second try, $doc1 has expired; bad response; keep old data
    CRM_Utils_Time::setTime('2013-03-01 12:00:02'); // more than 2 hours later (DEFAULT_RETRY)
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['http-error'])
    );
    $doc2 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc2['messages'][0]['markup']);
    $this->assertTrue($doc2['expires'] > CRM_Utils_Time::getTimeRaw());

    // third try, $doc2 hasn't expired yet; no request; keep old data
    CRM_Utils_Time::setTime('2013-03-01 12:09:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectNoHttpRequest()
    );
    $doc3 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc3['messages'][0]['markup']);
    $this->assertEquals($doc2['expires'], $doc3['expires']);

    // fourth try, $doc2 has expired yet; new request; replace data
    CRM_Utils_Time::setTime('2013-03-01 12:10:02');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['salut-a-tout'])
    );
    $doc4 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Salut a tout</h1>', $doc4['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 12:20:02'), $doc4['expires']);
  }

  public function testGetDocument_NewOK_UpdateParseError() {
    // first try, good response
    CRM_Utils_Time::setTime('2013-03-01 10:00:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['hello-world'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc1['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 10:10:00'), $doc1['expires']);

    // second try, $doc1 has expired; bad response; keep old data
    CRM_Utils_Time::setTime('2013-03-01 12:00:02'); // more than 2 hours later (DEFAULT_RETRY)
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['bad-json'])
    );
    $doc2 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc2['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 12:10:02'), $doc2['expires']);
  }

  public function testGetDocument_NewOK_UpdateInvalidDoc() {
    // first try, good response
    CRM_Utils_Time::setTime('2013-03-01 10:00:00');
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['hello-world'])
    );
    $doc1 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc1['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 10:10:00'), $doc1['expires']);

    // second try, $doc1 has expired; bad response; keep old data
    CRM_Utils_Time::setTime('2013-03-01 12:00:02'); // more than 2 hours later (DEFAULT_RETRY)
    $communityMessages = new CRM_Core_CommunityMessages(
      $this->cache,
      $this->expectOneHttpRequest($this->webResponses['invalid-ttl-document'])
    );
    $doc2 = $communityMessages->getDocument();
    $this->assertEquals('<h1>Hello world</h1>', $doc2['messages'][0]['markup']);
    $this->assertEquals(strtotime('2013-03-01 12:10:02'), $doc2['expires']);
  }

  /**
   * Generate a mock HTTP client with the expectation that it is never called.
   *
   * @return CRM_Utils_HttpClient|PHPUnit_Framework_MockObject_MockObject
   */
  protected function expectNoHttpRequest() {
    $client = $this->getMock('CRM_Utils_HttpClient');
    $client->expects($this->never())
      ->method('get');
    return $client;
  }

  /**
   * Generate a mock HTTP client with the expectation that it is called once.
   *
   * @return CRM_Utils_HttpClient|PHPUnit_Framework_MockObject_MockObject
   */
  protected function expectOneHttpRequest($response) {
    $client = $this->getMock('CRM_Utils_HttpClient');
    $client->expects($this->once())
      ->method('get')
      ->will($this->returnValue($response));
    return $client;
  }
}
