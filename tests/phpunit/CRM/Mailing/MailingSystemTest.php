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
 * Test that content produced by CiviMail looks the way it's expected.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

/**
 * Class CRM_Mailing_MailingSystemTest.
 *
 * This class tests the deprecated code that we are moving
 * away from supporting.
 *
 * MailingSystemTest checks that overall composition and delivery of
 * CiviMail blasts works. It extends CRM_Mailing_MailingSystemTestBase
 * which provides the general test scenarios -- but this variation
 * checks that certain internal events/hooks fire.
 *
 * MailingSystemTest is the counterpart to FlexMailerSystemTest.
 *
 * @group headless
 * @group civimail
 * @see \Civi\FlexMailer\FlexMailerSystemTest
 */
class CRM_Mailing_MailingSystemTest extends CRM_Mailing_MailingSystemTestBase {

  private $counts;

  private $checkMailParamsContext = TRUE;

  /**
   * Set up the deprecated bao support.
   */
  public function setUp(): void {
    parent::setUp();
    $hooks = \CRM_Utils_Hook::singleton();
    $hooks->setHook('civicrm_alterMailParams',
      [$this, 'hook_alterMailParams']);
  }

  /**
   * @see CRM_Utils_Hook::alterMailParams
   */
  public function hook_alterMailParams(): void {
    $this->counts['hook_alterMailParams'] = 1;
  }

  /**
   * Post test cleanup.
   */
  public function tearDown(): void {
    global $dbLocale;
    if ($dbLocale) {
      $this->disableMultilingual();
    }
    parent::tearDown();
    $this->assertNotEmpty($this->counts['hook_alterMailParams']);
  }

  /**
   * Test legacy mailer preview functionality.
   */
  public function testMailerPreviewExtraScheme(): void {
    $contactID = $this->individualCreate();
    $displayName = $this->callAPISuccess('contact', 'get', ['id' => $contactID]);
    $displayName = $displayName['values'][$contactID]['display_name'];
    $this->assertNotEmpty($displayName);

    $params = $this->_params;
    /** @noinspection HttpUrlsUsage */
    $params['body_html'] = '<a href="http://{action.forward}">Forward this email written in ckeditor</a>';
    $params['api.Mailing.preview'] = [
      'id' => '$value.id',
      'contact_id' => $contactID,
    ];
    $params['options']['force_rollback'] = 1;

    $result = $this->callAPISuccess('mailing', 'create', $params);
    $previewResult = $result['values'][$result['id']]['api.Mailing.preview'];
    $this->assertMatchesRegularExpression('!>Forward this email written in ckeditor</a>!', $previewResult['values']['body_html']);
    $this->assertMatchesRegularExpression('!<a href="([^"]+)civicrm/mailing/forward&amp;reset=1&amp;jid=&amp;qid=&amp;h=\w*">!', $previewResult['values']['body_html']);
    $this->assertStringNotContainsString("http://http://", $previewResult['values']['body_html']);
  }

  // ---- Boilerplate ----

  // The remainder of this class contains dummy stubs which make it easier to
  // work with the tests in an IDE.

  /**
   * Generate a fully-formatted mailing (with body_html content).
   *
   * @dataProvider urlTrackingExamples
   *
   * @throws \CRM_Core_Exception
   */
  public function testUrlTracking(
    $inputHtml,
    $htmlUrlRegex,
    $textUrlRegex,
    $params
  ): void {
    parent::testUrlTracking($inputHtml, $htmlUrlRegex, $textUrlRegex, $params);
  }

  public function testBasicHeaders(): void {
    parent::testBasicHeaders();
  }

  public function testText(): void {
    parent::testText();
  }

  public function testHtmlWithOpenTracking(): void {
    parent::testHtmlWithOpenTracking();
  }

  public function testHtmlWithOpenAndUrlTracking(): void {
    parent::testHtmlWithOpenAndUrlTracking();
  }

  /**
   * Test to check Activity being created on mailing Job.
   *
   */
  public function testMailingActivityCreate(): void {
    $subject = uniqid('testMailingActivityCreate');
    $this->runMailingSuccess([
      'subject' => $subject,
      'body_html' => 'Test Mailing Activity Create',
      'scheduled_id' => $this->individualCreate(),
    ]);

    $this->callAPISuccessGetCount('activity', [
      'activity_type_id' => 'Bulk Email',
      'status_id' => 'Completed',
      'subject' => $subject,
    ], 1);
  }

  /**
   * Test the auto-respond email, including token presence.
   */
  public function testMailingReplyAutoRespond(): void {
    // Because our parent class marks the _groupID as private, we can't use that :-(
    $group_1 = $this->groupCreate([
      'name' => 'Test Group Mailing Reply',
      'title' => 'Test Group Mailing Reply',
    ]);
    $this->createContactsInGroup(1, $group_1);
    $this->callAPISuccess('Address', 'create', ['street_address' => 'Sesame Street', 'contact_id' => 1]);

    // Also _mut is private to the parent, so we have to make our own:
    $mut = new CiviMailUtils($this, TRUE);

    $replyComponent = $this->callAPISuccess('MailingComponent', 'get', ['id' => CRM_Mailing_PseudoConstant::defaultComponent('Reply', ''), 'sequential' => 1])['values'][0];
    $replyComponent['body_html'] .= ' {domain.address} ';
    $replyComponent['body_txt'] = ($replyComponent['body_txt'] ?? '') . ' {domain.address} ';
    $this->callAPISuccess('MailingComponent', 'create', $replyComponent);

    // Create initial mailing to the group.
    $mailingParams = [
      'name'           => 'Mailing Reply: mailing ',
      'subject'        => 'Mailing Reply: test',
      'created_id'     => 1,
      'groups'         => ['include' => [$group_1]],
      'scheduled_date' => 'now',
      'body_text'      => 'Please just {action.unsubscribeUrl}',
      'auto_responder' => 1,
      'reply_id'       => $replyComponent['id'],
    ];

    // The following code is exactly the same as runMailingSuccess() except that we store the ID of the mailing.
    $mailing_1 = $this->callAPISuccess('Mailing', 'create', $mailingParams);
    $mut->assertRecipients([]);
    $this->callAPISuccess('job', 'process_mailing', ['runInNonProductionEnvironment' => TRUE]);

    $allMessages = $mut->getAllMessages('ezc');
    $this->assertCount(1, $allMessages);

    // So far so good.
    $message = end($allMessages);
    $this->assertInstanceOf(ezcMailText::class, $message->body);
    $this->assertEquals('plain', $message->body->subType);
    $this->assertEquals(1, preg_match(
      '@mailing/unsubscribe.*jid=(\d+)&qid=(\d+)&h=([0-9a-z]+)@',
      $message->body->text,
      $matches
    ));

    $this->checkMailParamsContext = FALSE;

    CRM_Mailing_Event_BAO_MailingEventReply::reply(
      $matches[1],
      $matches[2],
      $matches[3]
    );
    $mut->checkMailLog([
      'Please Send Inquiries to Our Contact Email Address',
      'Sesame Street',
      'do-not-reply@chaos.org',
      'info@EXAMPLE.ORG',
      'mail1@nul.example.com',
    ], ['{domain.address}']);
    $this->callAPISuccess('Mailing', 'delete', ['id' => $mailing_1['id']]);
    $this->callAPISuccess('Group', 'delete', ['id' => $group_1]);
  }

}
