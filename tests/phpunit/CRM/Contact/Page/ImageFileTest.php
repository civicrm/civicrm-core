<?php

/**
 * @group headless
 */
class CRM_Contact_Page_ImageFileTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_contact']);
    parent::tearDown();
  }

  public function testIsValidPhotoNameRejectsDirectorySeparators(): void {
    $page = new CRM_Contact_Page_ImageFileTest_Page();

    $this->assertTrue($page->isValidPhotoNamePublic('contact.jpg'));
    $this->assertFalse($page->isValidPhotoNamePublic('../contact.jpg'));
    $this->assertFalse($page->isValidPhotoNamePublic('..\\contact.jpg'));
  }

  public function testGetContactIDsForPhotoRequiresPhotoQueryParameterMatch(): void {
    $expectedContactID = $this->createContactWithImageUrl('https://example.org/civicrm/contact/imagefile?photo=contact.jpg');
    $this->createContactWithImageUrl('https://example.org/civicrm/contact/imagefile?photo=other-contact.jpg');
    $this->createContactWithImageUrl('https://example.org/files/contact.jpg');

    $page = new CRM_Contact_Page_ImageFileTest_Page();

    $this->assertEquals([$expectedContactID], $page->getContactIDsForPhotoPublic('contact.jpg'));
  }

  public function testGetContactIDsForPhotoEscapesSqlLikeWildcards(): void {
    $expectedContactID = $this->createContactWithImageUrl('https://example.org/civicrm/contact/imagefile?photo=contact_.jpg');
    $this->createContactWithImageUrl('https://example.org/civicrm/contact/imagefile?photo=contactx.jpg');

    $page = new CRM_Contact_Page_ImageFileTest_Page();

    $this->assertEquals([$expectedContactID], $page->getContactIDsForPhotoPublic('contact_.jpg'));
  }

  private function createContactWithImageUrl(string $imageUrl): int {
    $contactID = (int) $this->individualCreate();
    CRM_Core_DAO::executeQuery('UPDATE civicrm_contact SET image_url = %1 WHERE id = %2', [
      1 => [$imageUrl, 'String'],
      2 => [$contactID, 'Integer'],
    ]);
    return $contactID;
  }

}

class CRM_Contact_Page_ImageFileTest_Page extends CRM_Contact_Page_ImageFile {

  public function isValidPhotoNamePublic($photo): bool {
    return $this->isValidPhotoName($photo);
  }

  public function getContactIDsForPhotoPublic($photo): array {
    return $this->getContactIDsForPhoto($photo);
  }

}
