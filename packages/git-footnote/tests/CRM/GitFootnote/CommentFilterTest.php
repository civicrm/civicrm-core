<?php
namespace CRM\GitFootnote;

class CommentFilterTest extends \PHPUnit_Framework_TestCase {

  /**
   * Data provider for basic test-cases which exercise parsing logic without
   * any support from web services
   */
  function commentCases() {
    // each test case has these parts: inputMessageBody, expectedMessageBody, expectedFootnotes
    $cases = array();
    $cases[] = array(
      "",
      "",
    );
    $cases[] = array(
      "Hello",
      "Hello",
    );
    $cases[] = array(
      "Hello #123",
      "Hello #123",
    );
    $cases[] = array(
      "#hello",
      "",
    );
    $cases[] = array(
      "#comment-1\nnormal-1\n#comment-2\nnormal-2",
      "normal-1\nnormal-2",
    );
    $cases[] = array(
      "normal-1\n#comment-1\nnormal-2\n#comment-2",
      "normal-1\nnormal-2",
    );
    $cases[] = array(
      "normal-1\n\n#comment-1\n\nnormal-2\n#comment-2",
      "normal-1\n\n\nnormal-2",
    );
    $cases[] = array(
      "normal-1\n\n#comment-1\n\nnormal-2\n\n#comment-2",
      "normal-1\n\n\nnormal-2\n",
    );
    $cases[] = array(
      "normal-#1\n#comment-#1\nnormal-#2\n#comment-#2",
      "normal-#1\nnormal-#2",
    );
    return $cases;
  }

  /**
   * @dataProvider commentCases
   * @param string $messageBody
   * @param array $expectedNotes footnotes that should be produced
   */
  function testCommentCases($messageBody, $expectedBody) {
    $message = new CommitMessage($messageBody);
    $filter = new CommentFilter();
    $filter->filter($message);
    $this->assertEquals($expectedBody, $message->getMessage());
  }

}
