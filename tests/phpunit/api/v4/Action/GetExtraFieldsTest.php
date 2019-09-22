<?php

namespace api\v4\Action;

use api\v4\UnitTestCase;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class GetExtraFieldsTest extends UnitTestCase {

  public function testBAOFieldsWillBeReturned() {
    $returnedFields = Contact::getFields()
      ->execute()
      ->getArrayCopy();

    $baseFields = \CRM_Contact_BAO_Contact::fields();
    $baseFieldNames = array_column($baseFields, 'name');
    $returnedFieldNames = array_column($returnedFields, 'name');
    $notReturned = array_diff($baseFieldNames, $returnedFieldNames);

    $this->assertEmpty($notReturned);
  }

}
