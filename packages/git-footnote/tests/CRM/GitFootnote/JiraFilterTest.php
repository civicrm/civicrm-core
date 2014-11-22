<?php
namespace CRM\GitFootnote;

class JiraFilterTest extends \PHPUnit_Framework_TestCase {

  /**
   * @param \Jira_Api|NULL $jiraApi
   * @return \CRM\GitFootnote\JiraFilter
   */
  function createJiraFilter($jiraApi = NULL) {
    return new JiraFilter('/^CRM-[0-9]+$/', 'http://example.com/jira', $jiraApi);
  }

  /**
   * Data provider for basic test-cases which exercise parsing logic without
   * any support from web services
   */
  function offlineCases() {
    // each test case has these parts: inputMessageBody, expectedMessageBody, expectedFootnotes
    $cases = array();
    $cases[] = array(
      "",
      "",
      array()
    );
    $cases[] = array(
      "Hello",
      "Hello",
      array()
    );
    $cases[] = array(
      "Hello world",
      "Hello world",
      array()
    );
    $cases[] = array(
      "CRM-1234",
      "CRM-1234 - http://example.com/jira/browse/CRM-1234",
      array()
    );
    $cases[] = array(
      "Hello world CRM-1234",
      "Hello world CRM-1234",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234")
    );
    $cases[] = array(
      " Hello world CRM-1234",
      " Hello world CRM-1234",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234")
    );
    $cases[] = array(
      "Hello world CRM-1234! ",
      "Hello world CRM-1234! ",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234")
    );
    $cases[] = array(
      "CRM-1234 Hello world",
      "CRM-1234 Hello world",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234")
    );
    $cases[] = array(
      "fix CRM-1234",
      "fix CRM-1234 - http://example.com/jira/browse/CRM-1234",
      array(),
    );
    $cases[] = array(
      "CRM-1234 CRM-567",
      "CRM-1234 CRM-567",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234", "CRM-567:\n  http://example.com/jira/browse/CRM-567")
    );
    $cases[] = array(
      "Follow up to CRM-1234/github #24",
      "Follow up to CRM-1234/github #24",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234")
    );
    $cases[] = array(
      "fix CRM-1234/CRM-567",
      "fix CRM-1234/CRM-567",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234", "CRM-567:\n  http://example.com/jira/browse/CRM-567")
    );
    $cases[] = array(
      "Hello\nCRM-1234",
      "Hello\nCRM-1234",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234")
    );
    $cases[] = array(
      "CRM-1234\nHello",
      "CRM-1234\nHello",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234")
    );
    $cases[] = array(
      "CRM-1234\nCRM-567",
      "CRM-1234\nCRM-567",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234", "CRM-567:\n  http://example.com/jira/browse/CRM-567")
    );
    $cases[] = array(
      "Hello, CRM-1234... is... like CRM-567!",
      "Hello, CRM-1234... is... like CRM-567!",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234", "CRM-567:\n  http://example.com/jira/browse/CRM-567")
    );
    $cases[] = array(
      "Hello, CRM-1234, CRM-567; but not CRM-78901",
      "Hello, CRM-1234, CRM-567; but not CRM-78901",
      array("CRM-1234:\n  http://example.com/jira/browse/CRM-1234", "CRM-567:\n  http://example.com/jira/browse/CRM-567", "CRM-78901:\n  http://example.com/jira/browse/CRM-78901")
    );
    $cases[] = array(
      "ACRM-1234 Hello",
      "ACRM-1234 Hello",
      array()
    );
    $cases[] = array(
      "Sometimes we might put in the full URL -- http://example.com/jira/browse/CRM-13872 -- because we don't know about git-footnote",
      "Sometimes we might put in the full URL -- http://example.com/jira/browse/CRM-13872 -- because we don't know about git-footnote",
      array() // Don't really care whether footnote is reproduced at bottom; just want to know it doesn't crash
    );
    $cases[] = array(
      "CRM-13872 - Sometimes we might put in the full URL -- http://example.com/jira/browse/CRM-13872 -- because we don't know about git-footnote",
      "CRM-13872 - Sometimes we might put in the full URL -- http://example.com/jira/browse/CRM-13872 -- because we don't know about git-footnote",
      array() // Don't really care whether footnote is reproduced at bottom; just want to know it doesn't crash
    );

    // "git commit --amend" to add extra ticket reference
    $cases[] = array(
      "Hello, CRM-1234... and also... CRM-567!\n\n----------------------------------------\n* CRM-1234: http://example.com/jira/browse/CRM-1234",
      "Hello, CRM-1234... and also... CRM-567!\n\n----------------------------------------\n* CRM-1234: http://example.com/jira/browse/CRM-1234",
      array("CRM-567:\n  http://example.com/jira/browse/CRM-567")
      // Current behavior isn't great because we wind up with two horizontal bars. Would be better to parse old footnotes when instantiating CommitMessage
      //"Hello, CRM-1234... and also... CRM-567!\n\n----------------------------------------\n* CRM-1234: http://example.com/jira/browse/CRM-1234",
      //"Hello, CRM-1234... and also... CRM-567!",
      //array(("CRM-1234:\n  http://example.com/jira/browse/CRM-1234", "CRM-567:\n  http://example.com/jira/browse/CRM-567")
    );
    return $cases;
  }

  /**
   * @dataProvider offlineCases
   * @param string $messageBody
   * @param array $expectedNotes footnotes that should be produced
   */
  function testOfflineCases($messageBody, $expectedBody, $expectedNotes) {
    $message = new CommitMessage($messageBody);
    $this->createJiraFilter()->filter($message);
    $this->assertEquals($expectedBody, $message->getMessage());
    $this->assertEquals($expectedNotes, array_values($message->getNotes()));
  }

  /**
   * Content should stable across multiple executions
   *
   * @dataProvider offlineCases
   * @param string $messageBody
   * @param array $expectedNotes footnotes that should be produced
   */
  function testReprocessOfflineCases($messageBody, $expectedBody, $expectedNotes) {
    // Ignore $expectedBody, $expectedNotes; these are evaluated elsewhere (testOfflineCases).
    // This test is only about stability of output.

    $message = new CommitMessage($messageBody);
    $this->createJiraFilter()->filter($message);

    $message2 = new CommitMessage($message->toString());
    $this->createJiraFilter()->filter($message2);
    $this->assertEquals(rtrim($message2->toString(), "\n"), rtrim($message->toString(), "\n"));
  }

  /**
   * Data provider for test-cases which exercise involve web services
   */
  function onlineCases() {
    // mock web responses which can be used in different cases
    $webResponses['CRM-1234'] = $this->returnValue('{"expand":"names,schema","startAt":0,"maxResults":50,"total":1,"issues":[{"expand":"editmeta,renderedFields,transitions,changelog,operations","id":"12603","self":"http://example.com/jira/rest/api/2/issue/12603","key":"CRM-1234","fields":{"summary":"Four digit ticket"}}]}');
    $webResponses['CRM-567'] = $this->returnValue('{"expand":"names,schema","startAt":0,"maxResults":50,"total":1,"issues":[{"expand":"editmeta,renderedFields,transitions,changelog,operations","id":"12605","self":"http://example.com/jira/rest/api/2/issue/12605","key":"CRM-567","fields":{"summary":"Three digit ticket"}}]}');
    $webResponses['invalid'] = $this->throwException(new \Exception("JIRA Rest server returns unexpected result."));
    $webResponses['schema'] = $this->returnValue('[{"id":"progress","name":"Progress","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"progress","system":"progress"}},{"id":"summary","name":"Summary","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"string","system":"summary"}},{"id":"timetracking","name":"Time Tracking","custom":false,"orderable":true,"navigable":false,"searchable":true,"schema":{"type":"timetracking","system":"timetracking"}},{"id":"issuekey","name":"Key","custom":false,"orderable":false,"navigable":true,"searchable":false},{"id":"issuetype","name":"Issue Type","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"issuetype","system":"issuetype"}},{"id":"customfield_10110","name":"Code Sprint","custom":true,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"string","custom":"com.atlassian.jira.plugin.system.customfieldtypes:radiobuttons","customId":10110}},{"id":"votes","name":"Votes","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"array","items":"votes","system":"votes"}},{"id":"security","name":"Security Level","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"securitylevel","system":"security"}},{"id":"fixVersions","name":"Fix Version/s","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"array","items":"version","system":"fixVersions"}},{"id":"resolution","name":"Resolution","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"resolution","system":"resolution"}},{"id":"resolutiondate","name":"Resolved","custom":false,"orderable":false,"navigable":true,"searchable":true,"schema":{"type":"datetime","system":"resolutiondate"}},{"id":"timespent","name":"Time Spent","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"number","system":"timespent"}},{"id":"reporter","name":"Reporter","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"user","system":"reporter"}},{"id":"aggregatetimeoriginalestimate","name":"Σ Original Estimate","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"number","system":"aggregatetimeoriginalestimate"}},{"id":"updated","name":"Updated","custom":false,"orderable":false,"navigable":true,"searchable":true,"schema":{"type":"datetime","system":"updated"}},{"id":"created","name":"Created","custom":false,"orderable":false,"navigable":true,"searchable":true,"schema":{"type":"datetime","system":"created"}},{"id":"description","name":"Description","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"string","system":"description"}},{"id":"priority","name":"Priority","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"priority","system":"priority"}},{"id":"duedate","name":"Due Date","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"date","system":"duedate"}},{"id":"issuelinks","name":"Linked Issues","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"array","items":"issuelinks","system":"issuelinks"}},{"id":"watches","name":"Watchers","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"array","items":"watches","system":"watches"}},{"id":"worklog","name":"Log Work","custom":false,"orderable":true,"navigable":false,"searchable":true,"schema":{"type":"array","items":"worklog","system":"worklog"}},{"id":"customfield_10101","name":"Is MIH?","custom":true,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"string","custom":"com.atlassian.jira.plugin.system.customfieldtypes:radiobuttons","customId":10101}},{"id":"subtasks","name":"Sub-Tasks","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"array","items":"issuelinks","system":"subtasks"}},{"id":"status","name":"Status","custom":false,"orderable":false,"navigable":true,"searchable":true,"schema":{"type":"status","system":"status"}},{"id":"customfield_10090","name":"User friendly summary","custom":true,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"string","custom":"com.atlassian.jira.plugin.system.customfieldtypes:textfield","customId":10090}},{"id":"customfield_10091","name":"User friendly description","custom":true,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"string","custom":"com.atlassian.jira.plugin.system.customfieldtypes:textarea","customId":10091}},{"id":"labels","name":"Labels","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"array","items":"string","system":"labels"}},{"id":"customfield_10224","name":"Business Value","custom":true,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"number","custom":"com.atlassian.jira.plugin.system.customfieldtypes:float","customId":10224}},{"id":"customfield_10223","name":"Story Points","custom":true,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"number","custom":"com.atlassian.jira.plugin.system.customfieldtypes:float","customId":10223}},{"id":"workratio","name":"Work Ratio","custom":false,"orderable":false,"navigable":true,"searchable":true,"schema":{"type":"number","system":"workratio"}},{"id":"assignee","name":"Assignee","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"user","system":"assignee"}},{"id":"customfield_10221","name":"Epic/Theme","custom":true,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"array","items":"string","custom":"com.atlassian.jira.plugin.system.customfieldtypes:labels","customId":10221}},{"id":"attachment","name":"Attachment","custom":false,"orderable":true,"navigable":false,"searchable":true,"schema":{"type":"array","items":"attachment","system":"attachment"}},{"id":"customfield_10220","name":"Flagged","custom":true,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"array","items":"string","custom":"com.atlassian.jira.plugin.system.customfieldtypes:multicheckboxes","customId":10220}},{"id":"aggregatetimeestimate","name":"Σ Remaining Estimate","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"number","system":"aggregatetimeestimate"}},{"id":"versions","name":"Affects Version/s","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"array","items":"version","system":"versions"}},{"id":"project","name":"Project","custom":false,"orderable":false,"navigable":true,"searchable":true,"schema":{"type":"project","system":"project"}},{"id":"thumbnail","name":"Images","custom":false,"orderable":false,"navigable":true,"searchable":false},{"id":"timeestimate","name":"Remaining Estimate","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"number","system":"timeestimate"}},{"id":"aggregateprogress","name":"Σ Progress","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"progress","system":"aggregateprogress"}},{"id":"lastViewed","name":"Last Viewed","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"datetime","system":"lastViewed"}},{"id":"components","name":"Component/s","custom":false,"orderable":true,"navigable":true,"searchable":true,"schema":{"type":"array","items":"component","system":"components"}},{"id":"comment","name":"Comment","custom":false,"orderable":true,"navigable":false,"searchable":true,"schema":{"type":"array","items":"comment","system":"comment"}},{"id":"timeoriginalestimate","name":"Original Estimate","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"number","system":"timeoriginalestimate"}},{"id":"aggregatetimespent","name":"Σ Time Spent","custom":false,"orderable":false,"navigable":true,"searchable":false,"schema":{"type":"number","system":"aggregatetimespent"}}]');

    // each test case has these parts: inputMessageBody, expectedMessageBody, expectedFootnotes, mockedWebResponses
    $cases = array();
    $cases[] = array(
      "",
      "",
      array(),
      array()
    );
    $cases[] = array(
      "Hello",
      "Hello",
      array(),
      array()
    );
    $cases[] = array(
      "CRM-1234",
      "CRM-1234 - Four digit ticket\n\nhttp://example.com/jira/browse/CRM-1234",
      array(),
      array($webResponses['CRM-1234'], $webResponses['schema'])
    );
    $cases[] = array(
      "CRM-1234 fix",
      "CRM-1234 fix - Four digit ticket\n\nhttp://example.com/jira/browse/CRM-1234",
      array(),
      array($webResponses['CRM-1234'], $webResponses['schema'])
    );
    $cases[] = array(
      "fix CRM-1234",
      "fix CRM-1234 - Four digit ticket\n\nhttp://example.com/jira/browse/CRM-1234",
      array(),
      array($webResponses['CRM-1234'], $webResponses['schema'])
    );
    $cases[] = array(
      "CRM-1234 - Foo",
      "CRM-1234 - Foo",
      array("CRM-1234: Four digit ticket\n  http://example.com/jira/browse/CRM-1234"),
      array($webResponses['CRM-1234'], $webResponses['schema'])
    );
    $cases[] = array( // unknown ticket ID
      "CRM-78901",
      "CRM-78901 - http://example.com/jira/browse/CRM-78901",
      array(),
      array($webResponses['invalid'])
    );
    $cases[] = array(
      "Hello, CRM-1234... is... like CRM-567!",
      "Hello, CRM-1234... is... like CRM-567!",
      array("CRM-1234: Four digit ticket\n  http://example.com/jira/browse/CRM-1234", "CRM-567: Three digit ticket\n  http://example.com/jira/browse/CRM-567"),
      array($webResponses['CRM-1234'], $webResponses['schema'], $webResponses['CRM-567'])
    );
    return $cases;
  }

  /**
   * @dataProvider onlineCases
   * @param string $messageBody
   * @param array $expectedNotes footnotes that should be produced
   */
  function testOnlineCases($messageBody, $expectedBody, $expectedNotes, $mockWsReplies) {
    $message = new CommitMessage($messageBody);
    $jiraClient = $this->getMock('Jira_Api_Client_ClientInterface');
    foreach ($mockWsReplies as $idx => $mockWsReply) {
      $jiraClient->expects($this->at($idx))
        ->method('sendRequest')
        ->will($mockWsReply);
    }
    $jiraApi = new \Jira_Api(
      'http://example.com/jira',
      new \Jira_Api_Authentication_Anonymous(),
      $jiraClient
    );

    $this->createJiraFilter($jiraApi)->filter($message);
    $this->assertEquals($expectedBody, $message->getMessage());
    $this->assertEquals($expectedNotes, array_values($message->getNotes()));
  }
}
