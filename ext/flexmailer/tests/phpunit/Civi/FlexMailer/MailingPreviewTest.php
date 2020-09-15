<?php
namespace Civi\FlexMailer;

/**
 * Class MailingPreviewTest
 *
 * @group headless
 */
class MailingPreviewTest extends \CiviUnitTestCase {

  protected $_groupID;
  protected $_email;
  protected $_apiversion = 3;
  protected $_params = array();
  protected $_entity = 'Mailing';
  protected $_contactID;

  /**
   * APIv3 result from creating an example footer
   * @var array
   */
  protected $footer;

  public function setUp() {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(array('org.civicrm.flexmailer'));
    }

    parent::setUp();

    \Civi::settings()->set('flexmailer_traditional', 'flexmailer');

    $this->useTransaction();
    // DGW
    \CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0;
    $this->_contactID = $this->individualCreate();
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
    $this->_params = array(
      'subject' => 'Hello {contact.display_name}',
      'body_text' => "This is {contact.display_name}.\nhttps://civicrm.org\nda=({domain.address}) optout=({action.optOutUrl}) subj=({mailing.subject})",
      'body_html' => "<p>This is {contact.display_name}.</p><p><a href='https://civicrm.org/'>CiviCRM.org</a></p><p>da=({domain.address}) optout=({action.optOutUrl}) subj=({mailing.subject})</p>",
      'name' => 'mailing name',
      'created_id' => $this->_contactID,
      'header_id' => '',
      'footer_id' => '',
    );

    $this->footer = civicrm_api3('MailingComponent', 'create', array(
      'name' => 'test domain footer',
      'component_type' => 'footer',
      'body_html' => '<p>From {domain.address}. To opt out, go to {action.optOutUrl}.</p>',
      'body_text' => 'From {domain.address}. To opt out, go to {action.optOutUrl}.',
    ));
  }

  public function tearDown() {
    // DGW
    \CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0;
    parent::tearDown();
  }

  public function testMailerPreview() {
    // BEGIN SAMPLE DATA
    $contactID = $this->individualCreate();
    $displayName = $this->callAPISuccess('contact', 'get',
      array('id' => $contactID));
    $displayName = $displayName['values'][$contactID]['display_name'];
    $this->assertTrue(!empty($displayName));

    $params = $this->_params;
    $params['api.Mailing.preview'] = array(
      'id' => '$value.id',
      'contact_id' => $contactID,
    );
    $params['options']['force_rollback'] = 1;
    // END SAMPLE DATA

    $maxIDs = $this->getMaxIds();
    $result = $this->callAPISuccess('mailing', 'create', $params);
    $this->assertMaxIds($maxIDs);

    $previewResult = $result['values'][$result['id']]['api.Mailing.preview'];
    $this->assertEquals("[CiviMail Draft] Hello $displayName",
      $previewResult['values']['subject']);

    $this->assertContains("This is $displayName", $previewResult['values']['body_text']);
    $this->assertContains("civicrm/mailing/optout", $previewResult['values']['body_text']);
    $this->assertContains("&jid=&qid=&h=fakehash", $previewResult['values']['body_text']);
    $this->assertContains("subj=(Hello ", $previewResult['values']['body_text']);

    $this->assertContains("<p>This is $displayName.</p>", $previewResult['values']['body_html']);
    $this->assertContains("civicrm/mailing/optout", $previewResult['values']['body_html']);
    $this->assertContains("&amp;jid=&amp;qid=&amp;h=fakehash", $previewResult['values']['body_html']);
    $this->assertContains("subj=(Hello ", $previewResult['values']['body_html']);

    $this->assertEquals('flexmailer', $previewResult['values']['_rendered_by_']);
  }

  public function testMailerPreviewWithoutId() {
    // BEGIN SAMPLE DATA
    $contactID = $this->createLoggedInUser();
    $displayName = $this->callAPISuccess('contact', 'get', ['id' => $contactID]);
    $displayName = $displayName['values'][$contactID]['display_name'];
    $this->assertTrue(!empty($displayName));
    $params = $this->_params;
    // END SAMPLE DATA

    $maxIDs = $this->getMaxIds();
    $previewResult = $this->callAPISuccess('mailing', 'preview', $params);
    $this->assertMaxIds($maxIDs);

    $this->assertEquals("[CiviMail Draft] Hello $displayName",
      $previewResult['values']['subject']);

    $this->assertContains("This is $displayName", $previewResult['values']['body_text']);
    $this->assertContains("civicrm/mailing/optout", $previewResult['values']['body_text']);
    $this->assertContains("&jid=&qid=&h=fakehash", $previewResult['values']['body_text']);
    $this->assertContains("subj=(Hello ", $previewResult['values']['body_text']);

    $this->assertContains("<p>This is $displayName.</p>", $previewResult['values']['body_html']);
    $this->assertContains("civicrm/mailing/optout", $previewResult['values']['body_html']);
    $this->assertContains("&amp;jid=&amp;qid=&amp;h=fakehash", $previewResult['values']['body_html']);
    $this->assertContains("subj=(Hello ", $previewResult['values']['body_html']);

    $this->assertEquals('flexmailer', $previewResult['values']['_rendered_by_']);
  }

  /**
   * @return array
   *   Array(string $table => int $maxID).
   */
  protected function getMaxIds() {
    return array(
      'civicrm_mailing' => \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing'),
      'civicrm_mailing_job' => \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_job'),
      'civicrm_mailing_group' => \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_group'),
      'civicrm_mailing_recipients' => \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_recipients'),
    );
  }

  /**
   * Assert that the given tables have the given extant IDs.
   *
   * @param array $expectMaxIds
   *   Array(string $table => int $maxId).
   */
  protected function assertMaxIds($expectMaxIds) {
    foreach ($expectMaxIds as $table => $maxId) {
      $this->assertDBQuery($expectMaxIds[$table], 'SELECT MAX(id) FROM ' . $table, [], "Table $table should have a maximum ID of $maxId");
    }
  }

}
