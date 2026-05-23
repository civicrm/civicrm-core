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
    $expectedContactID = $this->individualCreate([
      'image_url' => 'https://example.org/civicrm/contact/imagefile?photo=contact.jpg',
    ]);
    $this->individualCreate([
      'image_url' => 'https://example.org/civicrm/contact/imagefile?photo=other-contact.jpg',
    ]);
    $this->individualCreate([
      'image_url' => 'https://example.org/files/contact.jpg',
    ]);

    $page = new CRM_Contact_Page_ImageFileTest_Page();

    $this->assertEquals([$expectedContactID], $page->getContactIDsForPhotoPublic('contact.jpg'));
  }

  public function testGetContactIDsForPhotoEscapesSqlLikeWildcards(): void {
    $expectedContactID = $this->individualCreate([
      'image_url' => 'https://example.org/civicrm/contact/imagefile?photo=contact_.jpg',
    ]);
    $this->individualCreate([
      'image_url' => 'https://example.org/civicrm/contact/imagefile?photo=contactx.jpg',
    ]);

    $page = new CRM_Contact_Page_ImageFileTest_Page();

    $this->assertEquals([$expectedContactID], $page->getContactIDsForPhotoPublic('contact_.jpg'));
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
