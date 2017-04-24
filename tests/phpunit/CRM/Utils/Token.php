<?php

/**
 * Class CRM_Utils_TokenTest
 * @group headless
 */
class CRM_Utils_TokenTest extends CiviUnitTestCase {

  /**
   * Basic test on getTokenDetails function.
   */
  public function testGetTokenDetails() {
    $contactID = $this->individualCreate(array('preferred_communication_method' => array('Phone', 'Fax')));
    $resolvedTokens = CRM_Utils_Token::getTokenDetails(array($contactID));
    $this->assertEquals('Phone, Fax', $resolvedTokens[0][$contactID]['preferred_communication_method']);
  }

}
