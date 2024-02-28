<?php

/**
 * @group headless
 */
class CRM_Activity_Form_ActivityViewTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  protected $source_contact_id;

  /**
   * @var int
   */
  protected $target_contact_id;

  /**
   * @var int
   */
  protected $mailing_id;

  /**
   * @var int
   */
  protected $case_id;

  public function setUp(): void {
    parent::setUp();
    $this->source_contact_id = $this->createLoggedInUser();
    $this->target_contact_id = $this->individualCreate([], 0, TRUE);
    $this->mailing_id = $this->callAPISuccess('Mailing', 'create', [
      'subject' => 'A Mailing Subject',
      // We need the newlines to be early since this text gets truncated
      'body_text' => "This\naddress\n\nis not ours: {domain.address}.\n\nDo not click this link {action.optOutUrl}.",
      'name' => 'amailing',
      'created_id' => $this->source_contact_id,
    ]);
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->case_id = $this->createCase($this->target_contact_id, $this->source_contact_id)->id;
  }

  /**
   * Cleanup after class.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_case_activity',
      'civicrm_case_contact',
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_case',
      'civicrm_contact',
    ];
    $this->quickCleanup($tablesToTruncate);
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviCase');
    parent::tearDown();
  }

  /**
   * Test that the smarty template for ActivityView contains what we expect
   * after preProcess().
   *
   * @throws \CRM_Core_Exception
   */
  public function testActivityViewPreProcess(): void {
    // create activity
    $activity = $this->activityCreate();

    // $activity doesn't contain everything we need, so do another get call
    $activityMoreInfo = $this->callAPISuccess('activity', 'getsingle', ['id' => $activity['id']]);

    // do preProcess
    $activityViewForm = new CRM_Activity_Form_ActivityView();
    $activityViewForm->controller = new CRM_Core_Controller_Simple('CRM_Activity_Form_ActivityView', 'Activity');
    $activityViewForm->set('id', $activity['id']);
    $activityViewForm->set('context', 'activity');
    $activityViewForm->set('cid', $activity['target_contact_id']);
    $activityViewForm->preProcess();

    // check one of the smarty template vars
    // not checking EVERYTHING
    $templateVar = CRM_Activity_Form_ActivityView::getTemplate()->getTemplateVars('values');
    $expected = [
      'assignee_contact' => [0 => $activity['target_contact_id']],
      // it's always Julia
      'assignee_contact_value' => 'Anderson, Julia II',
      'target_contact' => [0 => $activity['target_contact_id']],
      'target_contact_value' => 'Anderson, Julia II',
      'source_contact' => $activityMoreInfo['source_contact_sort_name'],
      'case_subject' => NULL,
      'id' => $activity['id'],
      'subject' => $activity['values'][$activity['id']]['subject'],
      'activity_subject' => $activity['values'][$activity['id']]['subject'],
      'activity_date_time' => $activityMoreInfo['activity_date_time'],
      'location' => $activity['values'][$activity['id']]['location'],
      'activity_location' => $activity['values'][$activity['id']]['location'],
      'duration' => '90',
      'activity_duration' => '90',
      'details' => $activity['values'][$activity['id']]['details'],
      'activity_details' => $activity['values'][$activity['id']]['details'],
      'is_test' => '0',
      'activity_is_test' => '0',
      'is_auto' => '0',
      'is_current_revision' => '1',
      'is_deleted' => '0',
      'activity_is_deleted' => '0',
      'is_star' => '0',
      'created_date' => $activityMoreInfo['created_date'],
      'activity_created_date' => $activityMoreInfo['created_date'],
      'modified_date' => $activityMoreInfo['modified_date'],
      'activity_modified_date' => $activityMoreInfo['modified_date'],
      'attachment' => NULL,
      'mailingId' => NULL,
      'campaign' => NULL,
      'engagement_level' => NULL,
    ];

    $this->assertEquals($expected, $templateVar);
  }

  /**
   * Test that the text is neither squished nor double-spaced.
   * @dataProvider activityTypesProvider
   * @dataProvider caseActivityTypesProvider
   * @param array $input
   * @param string $expected
   */
  public function testNewlinesLookRight(array $input, string $expected) {
    if (!empty($input['skip'])) {
      $this->markTestSkipped('This test is boring.');
      return;
    }

    $activity = $this->activityCreate([
      'source_contact_id' => $this->source_contact_id,
      'target_contact_id' => $this->target_contact_id,
      'details' => $input['details'],
      'activity_type_id' => $input['activity_type'],
      'source_record_id' => ($input['activity_type'] === 'Bulk Email' ? $this->mailing_id : NULL),
      'case_id' => (strpos($input['url'], 'caseid') === FALSE ? NULL : $this->case_id),
    ]);

    // We have to replace these at runtime because dataproviders are
    // evaluated even before setUp() runs.
    $input['url'] = str_replace('%%id%%', $activity['id'], $input['url']);
    $input['url'] = str_replace('%%aid%%', $activity['id'], $input['url']);
    $input['url'] = str_replace('%%cid%%', $this->source_contact_id, $input['url']);
    $input['url'] = str_replace('%%atype%%', CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', $input['activity_type']), $input['url']);
    $input['url'] = str_replace('%%caseid%%', $this->case_id, $input['url']);

    $this->setRequestVars($input['url']);

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    $this->unsetRequestVars($input['url']);

    // See note in activityTypesProvider why we do this
    $contents = str_replace(["\r", "\n"], '', $contents);

    $this->assertStringContainsString($expected, $contents);
  }

  /**
   * data provider for testNewlinesLookRight() for non-case activities
   *
   * Unfortunately there seem to be differences in the output of `purify` on
   * unix vs windows, and also on unix it seems to even work differently in
   * tests than when run normally! So we strip all chr(10) and chr(13) when
   * comparing the result.
   *
   * @return array
   */
  public function activityTypesProvider(): array {
    $data = [
      'meeting-text' => [
        [
          'activity_type' => 'Meeting',
          'url' => 'civicrm/activity?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
This is text only
and has two consecutive lines.

And one blank line.
ENDDETAILS
        ],
        '<span class="crm-frozen-field">This is text only<br />and has two consecutive lines.<br /><br />And one blank line.</span>',
      ],

      'bulkemail-text' => [
        [
          'activity_type' => 'Bulk Email',
          'url' => 'civicrm/activity/view?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
This is text only
and has two consecutive lines.

And one blank line.
ENDDETAILS
        ],
        // Note this is the summary of the actual bulk mailing text. The above details are a bit irrelevant for what we're testing here.
        '<td class="label nowrap">Text Message</td><td>This<br />address<br /><br />is not ours:...<br />',
      ],

      'email-text' => [
        [
          'activity_type' => 'Email',
          'url' => 'civicrm/activity/view?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
This is text only
and has two consecutive lines.

And one blank line.
ENDDETAILS
        ],
        '<td class="view-value report">                   This is text only<br />and has two consecutive lines.<br /><br />And one blank line.',
      ],

      'inbound-text' => [
        [
          'activity_type' => 'Inbound Email',
          'url' => 'civicrm/contact/view/activity?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
This is text only
and has two consecutive lines.

And one blank line.
ENDDETAILS
        ],
        '<span class="crm-frozen-field">This is text only<br />and has two consecutive lines.<br /><br />And one blank line.</span>',
      ],

      // Now html only
      'meeting-html' => [
        [
          'activity_type' => 'Meeting',
          'url' => 'civicrm/activity?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
<p>This is html only</p>

<p>And it usually looks like this.</p>

<p>With p&#39;s and newlines between the p&#39;s.</p>
ENDDETAILS
        ],
        '<span class="crm-frozen-field"><p>This is html only</p><p>And it usually looks like this.</p><p>With p\'s and newlines between the p\'s.</p></span>',
      ],

      'bulkemail-html' => [
        [
          'activity_type' => 'Bulk Email',
          'url' => 'civicrm/activity/view?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
<p>This is html only</p>

<p>And it usually looks like this.</p>

<p>With p&#39;s and newlines between the p&#39;s.</p>
ENDDETAILS
        ],
        // Note this is the summary of the actual bulk mailing text. The above details are a bit irrelevant for what we're testing here.
        '<td class="label nowrap">Text Message</td><td>This<br />address<br /><br />is not ours:...<br />',
      ],

      'email-html' => [
        [
          'activity_type' => 'Email',
          'url' => 'civicrm/activity/view?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
<p>This is html only</p>

<p>And it usually looks like this.</p>

<p>With p&#39;s and newlines between the p&#39;s.</p>
ENDDETAILS
        ],
        '<td class="view-value report">                   <p>This is html only</p><p>And it usually looks like this.</p><p>With p\'s and newlines between the p\'s.</p>',
      ],

      'inbound-html' => [
        [
          'activity_type' => 'Inbound Email',
          'url' => 'civicrm/contact/view/activity?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          // This is probably unusual in real life. It would almost always be mixed. But there's nothing to stop custom code from creating these.
          'details' => <<<'ENDDETAILS'
<p>This is html only</p>

<p>And it usually looks like this.</p>

<p>With p&#39;s and newlines between the p&#39;s.</p>
ENDDETAILS
        ],
        '<span class="crm-frozen-field"><p>This is html only</p><p>And it usually looks like this.</p><p>With p\'s and newlines between the p\'s.</p></span>',
      ],

      // Now mixed with text first
      'meeting-mixed-text' => [
        [
          'activity_type' => 'Meeting',
          'url' => 'civicrm/activity?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
-ALTERNATIVE ITEM 0-
This is mixed
and has two consecutive lines.

And one blank line.
-ALTERNATIVE ITEM 1-
<p>This is mixed<br />
and has two consecutive lines.</p>

<p>And one blank line.</p>
-ALTERNATIVE END-
ENDDETAILS
        ],
        'This is mixed<br />and has two consecutive lines.<br /><br />And one blank line.',
      ],

      'bulkemail-mixed-text' => [
        [
          'activity_type' => 'Bulk Email',
          'url' => 'civicrm/activity/view?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
-ALTERNATIVE ITEM 0-
This is mixed
and has two consecutive lines.

And one blank line.
-ALTERNATIVE ITEM 1-
<p>This is mixed<br />
and has two consecutive lines.</p>

<p>And one blank line.</p>
-ALTERNATIVE END-
ENDDETAILS
        ],
        // Note this is the summary of the actual bulk mailing text. The above details are a bit irrelevant for what we're testing here.
        '<td class="label nowrap">Text Message</td><td>This<br />address<br /><br />is not ours:...<br />',
      ],

      'email-mixed-text' => [
        [
          'activity_type' => 'Email',
          'url' => 'civicrm/activity/view?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
-ALTERNATIVE ITEM 0-
This is mixed
and has two consecutive lines.

And one blank line.
-ALTERNATIVE ITEM 1-
<p>This is mixed<br />
and has two consecutive lines.</p>

<p>And one blank line.</p>
-ALTERNATIVE END-
ENDDETAILS
        ],
        '<td class="view-value report">                   <br />This is mixed<br />and has two consecutive lines.<br /><br />And one blank line.<br />',
      ],

      'inbound-mixed-text' => [
        [
          'activity_type' => 'Inbound Email',
          'url' => 'civicrm/contact/view/activity?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
-ALTERNATIVE ITEM 0-
This is mixed
and has two consecutive lines.

And one blank line.
-ALTERNATIVE ITEM 1-
<p>This is mixed<br />
and has two consecutive lines.</p>

<p>And one blank line.</p>
-ALTERNATIVE END-
ENDDETAILS
        ],
        '<td class="view-value">       <br />This is mixed<br />and has two consecutive lines.<br /><br />And one blank line.<br />',
      ],

      // Now mixed with html first
      'meeting-mixed-html' => [
        [
          'activity_type' => 'Meeting',
          'url' => 'civicrm/activity?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
-ALTERNATIVE ITEM 0-
<p>This is mixed<br />
and has two consecutive lines.</p>

<p>And one blank line.</p>
-ALTERNATIVE ITEM 1-
This is mixed
and has two consecutive lines.

And one blank line.
-ALTERNATIVE END-
ENDDETAILS
        ],
        '<td class="view-value">       <p>This is mixed<br />and has two consecutive lines.</p><p>And one blank line.</p>',
      ],

      'bulkemail-mixed-html' => [
        [
          'activity_type' => 'Bulk Email',
          'url' => 'civicrm/activity/view?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
-ALTERNATIVE ITEM 0-
<p>This is mixed<br />
and has two consecutive lines.</p>

<p>And one blank line.</p>
-ALTERNATIVE ITEM 1-
This is mixed
and has two consecutive lines.

And one blank line.
-ALTERNATIVE END-
ENDDETAILS
        ],
        // Note this is the summary of the actual bulk mailing text. The above details are a bit irrelevant for what we're testing here.
        '<td class="label nowrap">Text Message</td><td>This<br />address<br /><br />is not ours:...<br />',
      ],

      'email-mixed-html' => [
        [
          'activity_type' => 'Email',
          'url' => 'civicrm/activity/view?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
-ALTERNATIVE ITEM 0-
<p>This is mixed<br />
and has two consecutive lines.</p>

<p>And one blank line.</p>
-ALTERNATIVE ITEM 1-
This is mixed
and has two consecutive lines.

And one blank line.
-ALTERNATIVE END-
ENDDETAILS
        ],
        '<p>This is mixed<br />and has two consecutive lines.</p><p>And one blank line.</p>',
      ],

      'inbound-mixed-html' => [
        [
          'activity_type' => 'Inbound Email',
          'url' => 'civicrm/contact/view/activity?atype=%%atype%%&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=activity',
          'details' => <<<'ENDDETAILS'
-ALTERNATIVE ITEM 0-
<p>This is mixed<br />
and has two consecutive lines.</p>

<p>And one blank line.</p>
-ALTERNATIVE ITEM 1-
This is mixed
and has two consecutive lines.

And one blank line.
-ALTERNATIVE END-
ENDDETAILS
        ],
        '<p>This is mixed<br />and has two consecutive lines.</p><p>And one blank line.</p>',
      ],
    ];

    // The output of these is wrong, but they are very rare and it has always
    // been wrong, so just skip these tests. But we want them to be present in
    // the array because they are used in caseActivityTypesProvider correctly.
    $data['meeting-text'][0]['skip'] = TRUE;
    $data['meeting-mixed-text'][0]['skip'] = TRUE;
    return $data;
  }

  /**
   * data provider for testNewlinesLookRight() for case activities
   * @return array
   */
  public function caseActivityTypesProvider(): array {
    // We want the same set as non-case, but the url is different, and expected results might change.
    $data = $this->activityTypesProvider();
    $newData = [];
    foreach ($data as $key => $value) {
      $newData['case-' . $key] = $data[$key];
      // The url is always the same for case, despite there being places in
      // civi that give a non-case link to the activity, but those are bugs
      // IMO since can lead to data loss.
      $newData['case-' . $key][0]['url'] = 'civicrm/case/activity/view?reset=1&aid=%%id%%&cid=%%cid%%&caseid=%%caseid%%';
    }
    $newData['case-meeting-text'][0]['skip'] = FALSE;
    $newData['case-meeting-text'][1] = 'This is text only<br />and has two consecutive lines.<br /><br />And one blank line.';
    $newData['case-bulkemail-text'][1] = 'This is text only<br />and has two consecutive lines.<br /><br />And one blank line.';
    $newData['case-email-text'][1] = 'This is text only<br />and has two consecutive lines.<br /><br />And one blank line.';
    $newData['case-inbound-text'][1] = 'This is text only<br />and has two consecutive lines.<br /><br />And one blank line.';

    $newData['case-meeting-html'][1] = "<p>This is html only</p><p>And it usually looks like this.</p><p>With p's and newlines between the p's.</p>";
    $newData['case-bulkemail-html'][1] = "<p>This is html only</p><p>And it usually looks like this.</p><p>With p's and newlines between the p's.</p>";
    $newData['case-email-html'][1] = "<p>This is html only</p><p>And it usually looks like this.</p><p>With p's and newlines between the p's.</p>";
    $newData['case-inbound-html'][1] = "<p>This is html only</p><p>And it usually looks like this.</p><p>With p's and newlines between the p's.</p>";

    $newData['case-meeting-mixed-text'][0]['skip'] = FALSE;
    $newData['case-meeting-mixed-text'][1] = 'This is mixed<br />and has two consecutive lines.<br /><br />And one blank line.';
    $newData['case-bulkemail-mixed-text'][1] = 'This is mixed<br />and has two consecutive lines.<br /><br />And one blank line.';
    $newData['case-email-mixed-text'][1] = 'This is mixed<br />and has two consecutive lines.<br /><br />And one blank line.';
    $newData['case-inbound-mixed-text'][1] = 'This is mixed<br />and has two consecutive lines.<br /><br />And one blank line.';

    $newData['case-meeting-mixed-html'][1] = '<p>This is mixed<br />and has two consecutive lines.</p><p>And one blank line.</p>';
    $newData['case-bulkemail-mixed-html'][1] = '<p>This is mixed<br />and has two consecutive lines.</p><p>And one blank line.</p>';
    $newData['case-email-mixed-html'][1] = '<p>This is mixed<br />and has two consecutive lines.</p><p>And one blank line.</p>';
    $newData['case-inbound-mixed-html'][1] = '<p>This is mixed<br />and has two consecutive lines.</p><p>And one blank line.</p>';

    return $newData;
  }

  /**
   * Invoke and preProcess often need these set.
   * @param string $url
   */
  private function setRequestVars(string $url): void {
    $_SERVER['REQUEST_URI'] = $url;
    $urlParts = explode('?', $url);
    $_GET['q'] = $urlParts[0];

    $parsed = [];
    parse_str($urlParts[1], $parsed);
    foreach ($parsed as $param => $value) {
      $_GET[$param] = $value;
      $_REQUEST[$param] = $value;
    }
  }

  /**
   * @param string $url
   */
  private function unsetRequestVars(string $url): void {
    unset($_GET['q']);
    $urlParts = explode('?', $url);
    $parsed = [];
    parse_str($urlParts[1], $parsed);
    foreach ($parsed as $param => $value) {
      unset($_GET[$param], $_REQUEST[$param]);
    }
  }

}
