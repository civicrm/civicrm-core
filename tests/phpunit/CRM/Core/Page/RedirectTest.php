<?php

/**
 * Class CRM_Core_Page_RedirectTest
 * @group headless
 */
class CRM_Core_Page_RedirectTest extends CiviUnitTestCase {
  /**
   * Get example data.
   *
   * @return array
   */

  /**
   * @return array
   */
  public function examples() {
    $cases = array();
    // $cases[] = array(string $requestPath, string $requestArgs, string $pageArgs, string $expectedUrl)

    // Note: CRM_Utils_System::url() and CRM_Utils_System::redirect() represent the
    // URL in "htmlized" format, so the $expectedUrl is "htmlized".

    $cases[] = array('', '', 'url=civicrm/dashboard', '/index.php?q=civicrm/dashboard');
    $cases[] = array('', '', 'url=civicrm/dashboard,mode=256', '/index.php?q=civicrm/dashboard');
    $cases[] = array('', '', 'url=civicrm/a/#/foo/bar', '/index.php?q=civicrm/a/#/foo/bar');
    $cases[] = array('', '', 'url=civicrm/foo/bar?whiz=1&bang=2', '/index.php?q=civicrm/foo/bar&amp;whiz=1&amp;bang=2');
    $cases[] = array('', '', 'url=civicrm/foo?unknown=%%unknown%%', '/index.php?q=civicrm/foo&amp;unknown=');
    $cases[] = array('civicrm/foo/bar', '', 'url=civicrm/a/#/%%2%%', '/index.php?q=civicrm/a/#/bar');

    $cases[] = array(
      '',
      'gid=2&reset=1',
      'url=civicrm/newfoo/%%gid%%?reset=%%reset%%',
      '/index.php?q=civicrm/newfoo/2&amp;reset=1',
    );

    return $cases;
  }

  /**
   * Note: Expected URL is htmlized because that's what CRM_Utils_System::url()
   * and CRM_Utils_System::redirect() work with.
   *
   * @param string $requestPath
   *   Eg "civicrm/requested/path".
   * @param string $requestArgs
   *   Eg "foo=bar&whiz=bang".
   * @param string $pageArgs
   *   Eg "url=civicrm/foo/bar?whiz=bang".
   * @param string $expectedUrl
   *   Eg "/index.php?q=civicrm/foo/bar&whiz=bang".
   * @dataProvider examples
   */
  public function testCreateUrl($requestPath, $requestArgs, $pageArgs, $expectedUrl) {
    $parsedRequestPath = explode('/', $requestPath);
    parse_str($requestArgs, $parsedRequestArgs);
    $parsedPageArgs = CRM_Core_Menu::getArrayForPathArgs($pageArgs);
    $actualUrl = CRM_Core_Page_Redirect::createUrl($parsedRequestPath, $parsedRequestArgs, $parsedPageArgs, FALSE);
    $this->assertEquals($expectedUrl, $actualUrl);
  }

}
