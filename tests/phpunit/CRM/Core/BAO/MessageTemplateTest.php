<?php

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\MessageTemplate;
use Civi\Api4\Translation;
use Civi\Token\TokenProcessor;

/**
 * Class CRM_Core_BAO_MessageTemplateTest
 * @group headless
 */
class CRM_Core_BAO_MessageTemplateTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Post test cleanup.
   */
  public function tearDown():void {
    $this->quickCleanup(['civicrm_address', 'civicrm_phone', 'civicrm_im', 'civicrm_website', 'civicrm_openid', 'civicrm_email', 'civicrm_translation'], TRUE);
    parent::tearDown();
    Civi::cache('metadata')->clear();
    unset($GLOBALS['tsLocale'], $GLOBALS['dbLocale'], $GLOBALS['civicrmLocale']);
  }

  public function testRenderTemplate(): void {
    $contactId = $this->individualCreate([
      'first_name' => 'Abba',
      'last_name' => 'Baab',
      'prefix_id' => NULL,
      'suffix_id' => NULL,
    ]);
    $rendered = CRM_Core_BAO_MessageTemplate::renderTemplate([
      'workflow' => 'case_activity',
      'tokenContext' => [
        'contactId' => $contactId,
      ],
      'messageTemplate' => [
        'msg_subject' => 'Hello testRenderTemplate {contact.display_name}!',
        'msg_text' => 'Hello testRenderTemplate {contact.display_name}!',
        'msg_html' => '<p>Hello testRenderTemplate {contact.display_name}!</p>',
      ],
    ]);
    $this->assertEquals('Hello testRenderTemplate Abba Baab!', $rendered['subject']);
    $this->assertEquals('Hello testRenderTemplate Abba Baab!', $rendered['text']);
    $this->assertStringContainsString('<p>Hello testRenderTemplate Abba Baab!</p>', $rendered['html']);
  }

  public function getLocaleConfigurations(): array {
    $yesPartials = ['partial_locales' => TRUE, 'uiLanguages' => ['en_US']];
    $noPartials = ['partial_locales' => FALSE, 'uiLanguages' => ['en_US'], 'format_locale' => 'en_US'];

    $allTemplates = [];
    $allTemplates['*'] = ['subject' => 'Hello', 'html' => 'Looky there!', 'text' => '{contribution.total_amount}'];
    $allTemplates['fr_FR'] = ['subject' => 'Bonjour', 'html' => 'Voila!', 'text' => '{contribution.total_amount}'];
    $allTemplates['fr_CA'] = ['subject' => 'Bonjour Canada', 'html' => 'Voila! Canada', 'text' => '{contribution.total_amount}'];
    $allTemplates['es_PR'] = ['subject' => 'Buenos dias', 'html' => 'Listo', 'text' => '{contribution.total_amount}'];
    $allTemplates['th_TH'] = ['subject' => 'สวัสดี', 'html' => 'ดังนั้น', 'text' => '{contribution.total_amount}'];

    $onlyTemplates = function(array $locales) use ($allTemplates) {
      return CRM_Utils_Array::subset($allTemplates, $locales);
    };

    $rendered = [];
    // $rendered['*'] = ['subject' => 'Hello', 'html' => 'Looky there!', 'text' => '$ 100.00'];
    $rendered['*'] = ['subject' => 'Hello', 'html' => 'Looky there!', 'text' => '$100.00'];
    $rendered['fr_FR'] = ['subject' => 'Bonjour', 'html' => 'Voila!', 'text' => '100,00 $US'];
    $rendered['fr_CA'] = ['subject' => 'Bonjour Canada', 'html' => 'Voila! Canada', 'text' => '100,00 $ US'];
    $rendered['es_PR'] = ['subject' => 'Buenos dias', 'html' => 'Listo', 'text' => '100.00 $US'];
    $rendered['th_TH'] = ['subject' => 'สวัสดี', 'html' => 'ดังนั้น', 'text' => 'US$100.00'];

    $result = [/* settings, templates, preferredLanguage, expectMessage */];

    $result['fr_FR matches fr_FR (all-tpls; yes-partials)'] = [$yesPartials, $allTemplates, 'fr_FR', $rendered['fr_FR']];
    $result['fr_FR matches fr_FR (all-tpls; no-partials)'] = [$noPartials, $allTemplates, 'fr_FR', $rendered['fr_FR']];
    $result['fr_FR falls back to fr_CA (ltd-tpls; yes-partials)'] = [$yesPartials, $onlyTemplates(['*', 'fr_CA']), 'fr_FR', $rendered['fr_CA']];
    $result['fr_FR falls back to fr_CA (ltd-tpls; no-partials)'] = [$noPartials, $onlyTemplates(['*', 'fr_CA']), 'fr_FR', $rendered['fr_CA']];

    $result['fr_CA matches fr_CA (all-tpls; yes-partials)'] = [$yesPartials, $allTemplates, 'fr_CA', $rendered['fr_CA']];
    $result['fr_CA matches fr_CA (all-tpls; no-partials)'] = [$noPartials, $allTemplates, 'fr_CA', $rendered['fr_CA']];
    $result['fr_CA falls back to fr_FR (ltd-tpls; yes-partials)'] = [$yesPartials, $onlyTemplates(['*', 'fr_FR']), 'fr_CA', $rendered['fr_FR']];
    $result['fr_CA falls back to fr_FR (ltd-tpls; no-partials)'] = [$noPartials, $onlyTemplates(['*', 'fr_FR']), 'fr_CA', $rendered['fr_FR']];

    $result['th_TH matches th_TH (all-tpls; yes-partials)'] = [$yesPartials, $allTemplates, 'th_TH', $rendered['th_TH']];
    $result['th_TH falls back to system default (all-tpls; no-partials)'] = [$noPartials, $allTemplates, 'th_TH', $rendered['*']];
    // ^^ The essence of the `partial_locales` setting -- whether partially-supported locales (th_TH) use mixed-mode or fallback to completely diff locale.
    $result['th_TH falls back to system default (ltd-tpls; yes-partials)'] = [$yesPartials, $onlyTemplates(['*']), 'th_TH', $rendered['*']];
    $result['th_TH falls back to system default (ltd-tpls; no-partials)'] = [$noPartials, $onlyTemplates(['*']), 'th_TH', $rendered['*']];

    return $result;
  }

  /**
   * Test that translated strings are rendered for templates where they exist.
   *
   * This system has a relatively open localization policy where any translation can be used,
   * even if the system doesn't allow it in the web UI. Ex: The sysadmin has configured 'fr_FR'
   * strings. The user has requested 'fr_CA', and we'll fallback to 'fr_CA'.
   *
   * @throws \CRM_Core_Exception
   * @group locale
   * @dataProvider getLocaleConfigurations
   */
  public function testRenderTranslatedTemplate($settings, $templates, $preferredLanguage, $expectRendered): void {
    if (empty($settings['partial_locales']) && count(\CRM_Core_I18n::languages(FALSE)) <= 1) {
      $this->markTestIncomplete('Full testing of localization requires l10n data.');
    }
    $cleanup = \CRM_Utils_AutoClean::swapSettings($settings);

    $this->individualCreate(['preferred_language' => $preferredLanguage]);
    $contributionID = $this->contributionCreate(['contact_id' => $this->ids['Contact']['individual_0']]);
    $messageTemplateID = MessageTemplate::get()
      ->addWhere('is_default', '=', 1)
      ->addWhere('workflow_name', '=', 'contribution_online_receipt')
      ->addSelect('id')
      ->execute()->first()['id'];

    foreach ($templates as $tplLocale => $tplData) {
      if ($tplLocale === '*') {
        MessageTemplate::update()
          ->addWhere('id', '=', $messageTemplateID)
          ->setValues([
            'msg_subject' => $tplData['subject'],
            'msg_html' => $tplData['html'],
            'msg_text' => $tplData['text'],
          ])
          ->execute();
      }
      else {
        Translation::save()->setRecords([
          ['entity_field' => 'msg_subject', 'string' => $tplData['subject']],
          ['entity_field' => 'msg_html', 'string' => $tplData['html']],
          ['entity_field' => 'msg_text', 'string' => $tplData['text']],
        ])->setDefaults([
          'entity_table' => 'civicrm_msg_template',
          'entity_id' => $messageTemplateID,
          'status_id:name' => 'active',
          'language' => $tplLocale,
        ])->execute();
      }
    }

    $myMessageTemplate = MessageTemplate::get()
      ->addWhere('is_default', '=', 1)
      ->addWhere('workflow_name', '=', 'contribution_online_receipt')
      ->addSelect('id', 'msg_subject', 'msg_html', 'msg_text')
      ->setLanguage($preferredLanguage)
      ->setTranslationMode('fuzzy')
      ->execute()->first();

    // In our examples, subject+html are constant values, but text has tokens.
    $this->assertEquals($expectRendered['subject'], $myMessageTemplate['msg_subject']);
    $this->assertEquals($expectRendered['html'], $myMessageTemplate['msg_html']);
    $this->assertNotEquals($expectRendered['text'], $myMessageTemplate['msg_text']);

    $rendered = CRM_Core_BAO_MessageTemplate::renderTemplate([
      'workflow' => 'contribution_online_receipt',
      'tokenContext' => [
        'contactId' => $this->ids['Contact']['individual_0'],
        'contributionId' => $contributionID,
      ],
    ]);
    $this->assertEquals(
      CRM_Utils_Array::subset($expectRendered, ['subject', 'html', 'text']),
      CRM_Utils_Array::subset($rendered, ['subject', 'html', 'text'])
    );
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testSendTemplate_RenderMode_OpenTemplate(): void {
    $contactId = $this->individualCreate([
      'first_name' => 'Abba',
      'last_name' => 'Baab',
      'prefix_id' => NULL,
      'suffix_id' => NULL,
    ]);
    [$sent, $subject, $messageText, $messageHtml] = CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'workflow' => 'case_activity',
        'contactId' => $contactId,
        'from' => 'admin@example.com',
        // No 'toEmail'/'toName' address => not sendable, but still returns rendered value.
        'attachments' => NULL,
        'messageTemplate' => [
          'msg_subject' => 'Hello testSendTemplate_RenderMode_OpenTemplate {contact.display_name}!',
          'msg_text' => 'Hello testSendTemplate_RenderMode_OpenTemplate {contact.display_name}!',
          'msg_html' => '<p>Hello testSendTemplate_RenderMode_OpenTemplate {contact.display_name}!</p>',
        ],
      ]
    );
    $this->assertEquals(FALSE, $sent);
    $this->assertEquals('Hello testSendTemplate_RenderMode_OpenTemplate Abba Baab!', $subject);
    $this->assertEquals('Hello testSendTemplate_RenderMode_OpenTemplate Abba Baab!', $messageText);
    $this->assertStringContainsString('<p>Hello testSendTemplate_RenderMode_OpenTemplate Abba Baab!</p>', $messageHtml);
  }

  public function testSendTemplate_RenderMode_DefaultTpl(): void {
    CRM_Core_Transaction::create(TRUE)->run(function(CRM_Core_Transaction $tx) {
      $tx->rollback();

      MessageTemplate::update()
        ->addWhere('workflow_name', '=', 'case_activity')
        ->addWhere('is_reserved', '=', 0)
        ->setValues([
          'msg_subject' => 'Hello testSendTemplate_RenderMode_Default {contact.display_name}!',
          'msg_text' => 'Hello testSendTemplate_RenderMode_Default {contact.display_name}!',
          'msg_html' => '<p>Hello testSendTemplate_RenderMode_Default {contact.display_name}!</p>',
        ])
        ->execute();

      $contactId = $this->individualCreate([
        'first_name' => 'Abba',
        'last_name' => 'Baab',
        'prefix_id' => NULL,
        'suffix_id' => NULL,
      ]);

      [$sent, $subject, $messageText, $messageHtml] = CRM_Core_BAO_MessageTemplate::sendTemplate(
        [
          'workflow' => 'case_activity',
          'contactId' => $contactId,
          'from' => 'admin@example.com',
          // No 'toEmail'/'toName' address => not sendable, but still returns rendered value.
          'attachments' => NULL,
        ]
      );
      $this->assertEquals(FALSE, $sent);
      $this->assertEquals('Hello testSendTemplate_RenderMode_Default Abba Baab!', $subject);
      $this->assertEquals('Hello testSendTemplate_RenderMode_Default Abba Baab!', $messageText);
      $this->assertStringContainsString('<p>Hello testSendTemplate_RenderMode_Default Abba Baab!</p>', $messageHtml);
    });
  }

  public function testSendTemplateRenderModeTokenContext(): void {
    CRM_Core_Transaction::create(TRUE)->run(function(CRM_Core_Transaction $tx) {
      $tx->rollback();

      MessageTemplate::update()
        ->addWhere('workflow_name', '=', 'case_activity')
        ->addWhere('is_reserved', '=', 0)
        ->setValues([
          'msg_subject' => 'Hello {contact.display_name} about {activity.subject}!',
          'msg_text' => 'Hello {contact.display_name} about {activity.subject}!',
          'msg_html' => '<p>Hello {contact.display_name} about {activity.subject}!</p>',
        ])
        ->execute();

      $contactId = $this->individualCreate([
        'first_name' => 'Abba',
        'last_name' => 'Baab',
        'prefix_id' => NULL,
        'suffix_id' => NULL,
      ]);
      $activityId = $this->activityCreate(['subject' => 'Something Something'])['id'];

      [$sent, $subject, $messageText, $messageHtml] = CRM_Core_BAO_MessageTemplate::sendTemplate(
        [
          'workflow' => 'case_activity',
          'tokenContext' => [
            'contactId' => $contactId,
            'activityId' => $activityId,
          ],
          'from' => 'admin@example.com',
          // No 'toEmail'/'toName' address => not sendable, but still returns rendered value.
          'attachments' => NULL,
        ]
      );
      $this->assertEquals(FALSE, $sent);
      $this->assertEquals('Hello Abba Baab about Something Something!', $subject);
      $this->assertEquals('Hello Abba Baab about Something Something!', $messageText);
      $this->assertStringContainsString('<p>Hello Abba Baab about Something Something!</p>', $messageHtml);
    });
  }

  /**
   * Test message template send.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCaseActivityCopyTemplate():void {
    $client_id = $this->individualCreate();
    $contact_id = $this->individualCreate();

    $msg = \Civi\WorkflowMessage\WorkflowMessage::create('case_activity', [
      'modelProps' => [
        'contactId' => $contact_id,
        'contact' => ['role' => 'Sand grain counter'],
        'isCaseActivity' => 1,
        'clientId' => $client_id,
        // activityTypeName means label here not name, but it's ok because label is desired here (dev/core#1116-ok-label)
        'activityTypeName' => 'Follow up',
        'activityFields' => [
          [
            'label' => 'Case ID',
            'type' => 'String',
            'value' => '1234',
          ],
        ],
        'activitySubject' => 'Test 123',
        'idHash' => substr(sha1(CIVICRM_SITE_KEY . '1234'), 0, 7),
      ],
    ]);

    $this->assertEquals([], \Civi\Test\Invasive::get([$msg, '_extras']));

    [, $subject, $message] = $msg->sendTemplate([
      'workflow' => 'case_activity',
      'from' => 'admin@example.com',
      'toName' => 'Demo',
      'toEmail' => 'admin@example.com',
      'attachments' => NULL,
    ]);

    $this->assertEquals('[case #' . $msg->getIdHash() . '] Test 123', $subject);
    $this->assertStringContainsString('Your Case Role(s) : Sand grain counter', $message);
    $this->assertStringContainsString('Case ID : 1234', $message);
  }

  /**
   * Test APIv4 calculated field master_id
   */
  public function testMessageTemplateMasterID() {
    CRM_Core_Transaction::create(TRUE)->run(function(CRM_Core_Transaction $tx) {
      $tx->rollback();

      $messageTemplateID = MessageTemplate::get()
        ->addWhere('is_default', '=', 1)
        ->addWhere('workflow_name', '=', 'contribution_offline_receipt')
        ->addSelect('id')
        ->execute()->first()['id'];
      $messageTemplateIDReserved = MessageTemplate::get()
        ->addWhere('is_reserved', '=', 1)
        ->addWhere('workflow_name', '=', 'contribution_offline_receipt')
        ->addSelect('id')
        ->execute()->first()['id'];
      $master_id = MessageTemplate::get()
        ->addSelect('master_id')
        ->addWhere('id', '=', $messageTemplateID)
        ->execute()->first()['master_id'];
      $this->assertNull($master_id);

      MessageTemplate::update()
        ->addWhere('id', '=', $messageTemplateID)
        ->setValues([
          'msg_subject' => 'Hello world',
          'msg_text' => 'Hello world',
          'msg_html' => '<p>Hello world</p>',
        ])
        ->execute();
      $master_id = MessageTemplate::get()
        ->addSelect('master_id')
        ->addWhere('id', '=', $messageTemplateID)
        ->execute()->first()['master_id'];
      $this->assertEquals($master_id, $messageTemplateIDReserved);
    });
  }

  /**
   * Test rendering of domain tokens.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDomainTokens(): void {
    $values = $this->getDomainTokenData();
    $this->callAPISuccess('Domain', 'create', [
      'id' => CRM_Core_Config::domainID(),
      'description' => $values['description'],
    ]);
    $this->callAPISuccess('Address', 'create', array_merge($values['address'], ['contact_id' => 1]));
    $this->callAPISuccess('Email', 'create', array_merge(['email' => $values['email']], ['contact_id' => 1, 'is_primary' => 1]));
    $tokenString = '{domain.' . implode('} ~ {domain.', array_keys($values)) . '}';

    $messageContent = CRM_Core_BAO_MessageTemplate::renderTemplate([
      'workflow' => 'dummy',
      'messageTemplate' => [
        'msg_html' => $tokenString,
        // Check the space is stripped.
        'msg_subject' => $tokenString . ' ',
        'msg_text' => $tokenString,
      ],
    ]);

    $this->assertEquals('Default Domain Name ~  ~ <div class="location vcard"><span class="adr"><span class="street-address">Buckingham palace</span><br /><span class="extended-address">Up the road</span><br /><span class="locality">London</span>, <span class="postal-code">90210</span><br /></span></div> ~ crown@example.com ~ 1 ~ rather nice', $messageContent['html']);
    $this->assertEquals('Default Domain Name ~  ~ Buckingham palace
Up the road
London, 90210
 ~ crown@example.com ~ 1 ~ rather nice', $messageContent['text']);
    $this->assertEquals('Default Domain Name ~  ~ Buckingham palace Up the road London, 90210  ~ crown@example.com ~ 1 ~ rather nice', $messageContent['subject']);
  }

  /**
   * Test rendering of smarty tokens.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRenderTemplateSmarty(): void {
    $messageContent = CRM_Core_BAO_MessageTemplate::renderTemplate([
      'workflow' => 'dummy',
      'messageTemplate' => [
        'msg_html' => '{$tokenString}',
        // Check the space is stripped.
        'msg_subject' => '{$tokenString} ',
        'msg_text' => '{$tokenString}',
      ],
      'tplParams' => ['tokenString' => 'Something really witty'],
    ]);
    $this->assertEquals('Something really witty', $messageContent['text']);
    $this->assertEquals('Something really witty', $messageContent['html']);
    $this->assertEquals('Something really witty', $messageContent['subject']);
  }

  /**
   * Test rendering of smarty tokens.
   *
   */
  public function testRenderTemplateIgnoreSmarty(): void {
    $messageContent = CRM_Core_BAO_MessageTemplate::renderTemplate([
      'workflow' => 'dummy',
      'messageTemplate' => [
        'msg_html' => '{$tokenString}',
        // Check the space is stripped.
        'msg_subject' => '{$tokenString} ',
        'msg_text' => '{$tokenString}',
      ],
      'disableSmarty' => TRUE,
      'tplParams' => ['tokenString' => 'Something really witty'],
    ]);

    $this->assertEquals('{$tokenString}', $messageContent['text']);
    $this->assertEquals('{$tokenString}', $messageContent['html']);
    $this->assertEquals('{$tokenString}', $messageContent['subject']);
  }

  /**
   * Test rendering of contact tokens.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContactTokens(): void {
    // Freeze the time at the start of the test, so checksums don't suffer from second rollovers.
    putenv('TIME_FUNC=frozen');
    CRM_Utils_Time::setTime(date('Y-m-d H:i:s'));
    $this->hookClass->setHook('civicrm_tokenValues', [$this, 'hookTokenValues']);
    $this->hookClass->setHook('civicrm_tokens', [$this, 'hookTokens']);

    $this->createCustomGroupWithFieldsOfAllTypes([]);
    $tokenData = $this->getOldContactTokens();
    $address = $this->setupContactFromTokeData($tokenData);
    $advertisedTokens = CRM_Core_SelectValues::contactTokens();
    $this->assertEquals($this->getAdvertisedTokens(), $advertisedTokens);

    CRM_Core_Smarty::singleton()->assign('pre_assigned_smarty', 'wee');
    // This string contains the 4 types of possible replaces just to be sure they
    // work in combination.
    $tokenString = '{$pre_assigned_smarty}{$passed_smarty}
{domain.name}
{important_stuff.favourite_emoticon}
';
    foreach (array_keys($tokenData) as $key) {
      $tokenString .= "{$key}:{contact.{$key}}\n";
    }
    $messageContent = CRM_Core_BAO_MessageTemplate::renderTemplate([
      'workflow' => 'dummy',
      'messageTemplate' => [
        'msg_html' => $tokenString,
        // Check the space is stripped.
        'msg_subject' => $tokenString . ' ',
        'msg_text' => $tokenString,
      ],
      'tokenContext' => ['contactId' => $tokenData['contact_id']],
      'tplParams' => ['passed_smarty' => 'whoa'],
    ]);
    $expected = 'weewhoa
Default Domain Name
emo
';
    $expected .= $this->getExpectedContactOutput($address['id'], $tokenData, $messageContent['html']);
    $this->assertEquals($expected, $messageContent['html']);
    $textDifferences = [
      '<p>',
      '</p>',
      '<a href="http://civicrm.org" ',
      'target="_blank">',
      '</a>',
    ];
    foreach ($textDifferences as $html) {
      $expected = str_replace($html, '', $expected);
    }
    $this->assertEquals($expected, $messageContent['text']);
    $checksum_position = strpos($messageContent['subject'], 'cs=');
    $this->assertTrue($checksum_position !== FALSE);
    $fixedExpected = rtrim(str_replace("\n", ' ', $expected));
    $this->assertEquals(substr($fixedExpected, 0, $checksum_position), substr($messageContent['subject'], 0, $checksum_position));
    $returned_parts = explode('_', substr($messageContent['subject'], $checksum_position));
    $expected_parts = explode('_', substr($fixedExpected, $checksum_position));
    $this->assertEquals($expected_parts[0], $returned_parts[0]);
    $this->assertApproxEquals($expected_parts[1], $returned_parts[1], 2);
    $this->assertEquals($expected_parts[2], $returned_parts[2]);

    // reset time
    putenv('TIME_FUNC');
    CRM_Utils_Time::resetTime();
  }

  /**
   * Test that old contact tokens still work, as we add new-style support.
   */
  public function testLegacyTokens(): void {
    $contactID = $this->individualCreate(['gender_id' => 'Female', 'communication_style' => 1, 'preferred_communication_method' => 'Phone']);
    $mappings = [
      ['old' => '{contact.individual_prefix}', 'new' => '{contact.prefix_id:label}', 'output' => 'Mr.'],
      ['old' => '{contact.individual_suffix}', 'new' => '{contact.suffix_id:label}', 'output' => 'II'],
      ['old' => '{contact.gender}', 'new' => '{contact.gender_id:label}', 'output' => 'Female'],
      ['old' => '{contact.communication_style}', 'new' => '{contact.communication_style_id:label}', 'output' => 'Formal'],
      ['old' => '{contact.contact_id}', 'new' => '{contact.id}', 'output' => $contactID],
      ['old' => '{contact.email_greeting}', 'new' => '{contact.email_greeting_display}', 'output' => 'Dear Anthony'],
      ['old' => '{contact.postal_greeting}', 'new' => '{contact.postal_greeting_display}', 'output' => 'Dear Anthony'],
      ['old' => '{contact.addressee}', 'new' => '{contact.addressee_display}', 'output' => 'Mr. Anthony J. Anderson II'],
    ];

    foreach ($mappings as $mapping) {
      foreach (['old', 'new'] as $type) {
        $messageContent = CRM_Core_BAO_MessageTemplate::renderTemplate([
          'contactId' => $contactID,
          'messageTemplate' => [
            'msg_text' => $mapping[$type],
          ],
        ])['text'];
        $this->assertEquals($mapping['output'], $messageContent, 'could not resolve ' . $mapping[$type]);
      }
    }
  }

  /**
   * Implement token values hook.
   *
   * @param array $details
   */
  public function hookTokenValues(array &$details): void {
    foreach ($details as $index => $detail) {
      $details[$index]['important_stuff.favourite_emoticon'] = 'emo';
    }
  }

  /**
   * Test that unresolved tokens are not causing a fatal error in smarty.
   *
   * @throws \CRM_Core_Exception
   */
  public function testUnresolvedTokens(): void {
    CRM_Core_BAO_MessageTemplate::renderTemplate([
      'messageTemplate' => [
        'msg_text' => '{contact.blah}',
      ],
    ])['text'];
  }

  /**
   * Hook to advertise tokens.
   *
   * @param array $hookTokens
   */
  public function hookTokens(array &$hookTokens): void {
    $hookTokens['important_stuff'] = ['important_stuff.favourite_emoticon' => 'Best coolest emoticon'];
  }

  /**
   * Test the contact tokens rendered by the token processor.
   *
   * This test will be obsolete once the renderMessageTemplate
   * function uses the token processor - at that point the test above
   * will be testing the same thing.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContactTokensRenderedByTokenProcessor(): void {
    $this->createCustomGroupWithFieldsOfAllTypes([]);
    $tokenData = $this->getOldContactTokens();
    $address = $this->setupContactFromTokeData($tokenData);
    $tokenString = '';
    foreach (array_keys($tokenData) as $key) {
      $tokenString .= "{$key}:{contact.{$key}}\n";
    }
    $newStyleTokenString = '';
    foreach (array_keys($this->getAdvertisedTokens()) as $key) {
      $newStyleTokenString .= substr($key, 9, -1) . ' |' . $key . "\n";
    }
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), []);
    $tokenProcessor->addMessage('html', $tokenString, 'text/html');
    $tokenProcessor->addMessage('new', $newStyleTokenString, 'text/html');

    $tokenProcessor->addRow(['contactId' => $tokenData['contact_id']]);
    $tokenProcessor->evaluate();
    $rendered = '';
    foreach ($tokenProcessor->getRows() as $row) {
      $rendered = (string) $row->render('html');
      $newStyleRendered = $row->render('new');
    }
    $expected = $this->getExpectedContactOutput($address['id'], $tokenData, $rendered);
    $this->assertEquals($expected, $rendered);
    $this->assertEquals($this->getExpectedContactOutputNewStyle($address['id'], $tokenData, $newStyleRendered), $newStyleRendered);

  }

  /**
   * Gets the values needed to render domain tokens.
   *
   * This is keyed by all the available tokens and fills
   * them with sample data.
   *
   * @return array
   */
  protected function getDomainTokenData(): array {
    return [
      'name' => 'Default Domain Name',
      'phone' => 123,
      'address' => [
        'street_address' => 'Buckingham palace',
        'supplemental_address_1' => 'Up the road',
        'postal_code' => 90210,
        'geocode_1' => 789,
        'geocode_2' => 890,
        'city' => 'London',
      ],
      'email' => 'crown@example.com',
      'id' => CRM_Core_Config::domainID(),
      'description' => 'rather nice',
    ];
  }

  /**
   * Get the tokens we expect to see advertised.
   *
   * @return string[]
   */
  public function getAdvertisedTokens(): array {
    return [
      '{contact.contact_type:label}' => 'Contact Type',
      '{contact.do_not_email:label}' => 'Do Not Email',
      '{contact.do_not_phone:label}' => 'Do Not Phone',
      '{contact.do_not_mail:label}' => 'Do Not Mail',
      '{contact.do_not_sms:label}' => 'Do Not Sms',
      '{contact.do_not_trade:label}' => 'Do Not Trade',
      '{contact.is_opt_out:label}' => 'No Bulk Emails (User Opt Out)',
      '{contact.external_identifier}' => 'External Identifier',
      '{contact.sort_name}' => 'Sort Name',
      '{contact.display_name}' => 'Display Name',
      '{contact.nick_name}' => 'Nickname',
      '{contact.image_URL}' => 'Image Url',
      '{contact.preferred_communication_method:label}' => 'Preferred Communication Method',
      '{contact.preferred_language:label}' => 'Preferred Language',
      '{contact.preferred_mail_format:label}' => 'Preferred Mail Format',
      '{contact.hash}' => 'Contact Hash',
      '{contact.source}' => 'Contact Source',
      '{contact.first_name}' => 'First Name',
      '{contact.middle_name}' => 'Middle Name',
      '{contact.last_name}' => 'Last Name',
      '{contact.prefix_id:label}' => 'Individual Prefix',
      '{contact.suffix_id:label}' => 'Individual Suffix',
      '{contact.formal_title}' => 'Formal Title',
      '{contact.communication_style_id:label}' => 'Communication Style',
      '{contact.job_title}' => 'Job Title',
      '{contact.gender_id:label}' => 'Gender',
      '{contact.birth_date}' => 'Birth Date',
      '{contact.employer_id}' => 'Current Employer ID',
      '{contact.is_deleted:label}' => 'Contact is in Trash',
      '{contact.created_date}' => 'Created Date',
      '{contact.modified_date}' => 'Modified Date',
      '{contact.addressee_display}' => 'Addressee',
      '{contact.email_greeting_display}' => 'Email Greeting',
      '{contact.postal_greeting_display}' => 'Postal Greeting',
      '{contact.current_employer}' => 'Current Employer',
      '{contact.location_type_id:label}' => 'Address Location Type',
      '{contact.address_id}' => 'Address ID',
      '{contact.street_address}' => 'Street Address',
      '{contact.street_number}' => 'Street Number',
      '{contact.street_number_suffix}' => 'Street Number Suffix',
      '{contact.street_name}' => 'Street Name',
      '{contact.street_unit}' => 'Street Unit',
      '{contact.supplemental_address_1}' => 'Supplemental Address 1',
      '{contact.supplemental_address_2}' => 'Supplemental Address 2',
      '{contact.supplemental_address_3}' => 'Supplemental Address 3',
      '{contact.city}' => 'City',
      '{contact.postal_code_suffix}' => 'Postal Code Suffix',
      '{contact.postal_code}' => 'Postal Code',
      '{contact.geo_code_1}' => 'Latitude',
      '{contact.geo_code_2}' => 'Longitude',
      '{contact.address_name}' => 'Address Name',
      '{contact.master_id}' => 'Master Address ID',
      '{contact.county}' => 'County',
      '{contact.state_province}' => 'State/Province',
      '{contact.country}' => 'Country',
      '{contact.phone}' => 'Phone',
      '{contact.phone_ext}' => 'Phone Extension',
      '{contact.phone_type}' => 'Phone Type',
      '{contact.email}' => 'Email',
      '{contact.on_hold:label}' => 'On Hold',
      '{contact.signature_text}' => 'Signature Text',
      '{contact.signature_html}' => 'Signature Html',
      '{contact.provider_id:label}' => 'IM Provider',
      '{contact.im}' => 'IM Screen Name',
      '{contact.openid}' => 'OpenID',
      '{contact.world_region}' => 'World Region',
      '{contact.url}' => 'Website',
      '{contact.custom_9}' => 'Contact reference field :: Custom Group',
      '{contact.custom_7}' => 'Country :: Custom Group',
      '{contact.custom_8}' => 'Country-multi :: Custom Group',
      '{contact.custom_4}' => 'Enter integer here :: Custom Group',
      '{contact.custom_1}' => 'Enter text here :: Custom Group',
      '{contact.custom_6}' => 'My file :: Custom Group',
      '{contact.custom_2}' => 'Pick Color :: Custom Group',
      '{contact.custom_13}' => 'Pick Shade :: Custom Group',
      '{contact.custom_10}' => 'State :: Custom Group',
      '{contact.custom_11}' => 'State-multi :: Custom Group',
      '{contact.custom_5}' => 'test_link :: Custom Group',
      '{contact.custom_12}' => 'Yes No :: Custom Group',
      '{contact.custom_3}' => 'Test Date :: Custom Group',
      '{contact.checksum}' => 'Checksum',
      '{contact.id}' => 'Contact ID',
      '{important_stuff.favourite_emoticon}' => 'Best coolest emoticon',
    ];
  }

  /**
   * Get all the available tokens with usable data.
   *
   * This is the result rendered from CRM_Core_SelectValues::contactTokens();
   * and has been gathered by calling that function.
   *
   * I have hard-coded them so we have a record of what we have
   * seemingly committed to support.
   *
   * Note it will render additional custom fields if they exist.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getOldContactTokens(): array {
    return [
      'contact_type' => 'Individual',
      'do_not_email' => 1,
      'do_not_phone' => 0,
      'do_not_mail' => 1,
      'do_not_sms' => 1,
      'do_not_trade' => 1,
      'is_opt_out' => 1,
      'external_identifier' => 'blah',
      'sort_name' => 'Smith, Robert',
      'display_name' => 'Robert Smith',
      'nick_name' => 'Bob',
      'image_URL' => 'https://example.com',
      'preferred_communication_method' => 'Phone',
      'preferred_language' => 'fr_CA',
      'preferred_mail_format' => 'Both',
      'hash' => 'xyz',
      'contact_source' => 'Contact Source',
      'first_name' => 'Robert',
      'middle_name' => 'Frank',
      'last_name' => 'Smith',
      'individual_prefix' => 'Mr.',
      'individual_suffix' => 'II',
      'formal_title' => 'Dogsbody',
      'communication_style' => 'Formal',
      'job_title' => 'Busy person',
      'gender' => 'Female',
      'birth_date' => '1998-12-31',
      'current_employer_id' => $this->organizationCreate(),
      'contact_is_deleted' => 0,
      'created_date' => '2020-01-01',
      'modified_date' => '2020-01-01',
      'addressee' => '{contact.prefix_id:label}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.suffix_id:label}',
      'email_greeting' => 'Dear {contact.first_name}',
      'postal_greeting' => 'Dear {contact.first_name}',
      'current_employer' => 'Unit Test Organization',
      'location_type' => 'Main',
      'address_id' => Address::create(FALSE)->setValues(['street_address' => 'Street Address'])->execute()->first()['id'],
      'street_address' => 'Street Address',
      'street_number' => '123',
      'street_number_suffix' => 'S',
      'street_name' => 'Main St',
      'street_unit' => '45B',
      'supplemental_address_1' => 'Round the corner',
      'supplemental_address_2' => 'Up the road',
      'supplemental_address_3' => 'By the big tree',
      'city' => 'New York',
      'postal_code_suffix' => '4578',
      'postal_code' => '90210',
      'geo_code_1' => '48.858093',
      'geo_code_2' => '2.294694',
      'manual_geo_code' => TRUE,
      'address_name' => 'The white house',
      'master_id' => $this->callAPISuccess('Address', 'create', [
        'contact_id' => $this->individualCreate(),
        'street_address' => 'Street Address',
        'street_number' => '123',
        'street_number_suffix' => 'S',
        'street_name' => 'Main St',
        'street_unit' => '45B',
        'supplemental_address_1' => 'Round the corner',
        'supplemental_address_2' => 'Up the road',
        'supplemental_address_3' => 'By the big tree',
        'city' => 'New York',
        'postal_code_suffix' => '4578',
        'postal_code' => '90210',
        'location_type' => 'Main',
      ])['id'],
      'county' => 'Harris County',
      'state_province' => 'Texas',
      'country' => 'United States',
      'phone' => '123-456',
      'phone_ext' => '77',
      'phone_type_id' => 'Mobile',
      'phone_type' => 'Mobile',
      'email' => 'anthony_anderson@civicrm.org',
      'on_hold' => FALSE,
      'signature_text' => 'Yours sincerely',
      'signature_html' => '<p>Yours</p>',
      'im_provider' => 'Yahoo',
      'im' => 'IM Screen Name',
      'openid' => 'OpenID',
      'world_region' => 'World Region',
      'url' => 'http://civicrm.org',
      $this->getCustomFieldName('text') => 'Bobsled',
      $this->getCustomFieldName('select_string') => 'R',
      $this->getCustomFieldName('select_date') => '2021-01-20',
      $this->getCustomFieldName('int') => 999,
      $this->getCustomFieldName('link') => 'http://civicrm.org',
      $this->getCustomFieldName('file') => '',
      $this->getCustomFieldName('country') => 'New Zealand',
      $this->getCustomFieldName('multi_country') => ['France', 'Canada'],
      $this->getCustomFieldName('contact_reference') => $this->individualCreate(['first_name' => 'Spider', 'last_name' => 'Man']),
      $this->getCustomFieldName('state') => 'Queensland',
      $this->getCustomFieldName('multi_state') => ['Victoria', 'New South Wales'],
      $this->getCustomFieldName('boolean') => TRUE,
      $this->getCustomFieldName('checkbox') => 'P',
      $this->getCustomFieldName('contact_reference') => $this->individualCreate(['first_name' => 'Spider', 'last_name' => 'Man']),
      'checksum' => 'Checksum',
      'contact_id' => $this->individualCreate(['first_name' => 'Peter', 'last_name' => 'Parker']),
    ];
  }

  /**
   * @param array $tokenData
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  protected function setupContactFromTokeData(array $tokenData) {
    $this->callAPISuccess('Contact', 'create', $tokenData);
    $address = $this->callAPISuccess('Address', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Phone', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Email', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Website', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Im', 'create', [
      'is_primary' => TRUE,
      'name' => $tokenData['im'],
      'provider_id' => $tokenData['im_provider'],
      'contact_id' => $tokenData['contact_id'],
    ]);
    $this->callAPISuccess('OpenID', 'create', array_merge($tokenData, [
      'is_primary' => TRUE,
      'contact_id' => $tokenData['contact_id'],
      'openid' => $tokenData['openid'],
    ]));
    return $address;
  }

  /**
   * Get the expected rendered string.
   *
   * @param int $id
   * @param array $tokenData
   * @param string $actualOutput
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getExpectedContactOutput($id, array $tokenData, string $actualOutput): string {
    $checksum = substr($actualOutput, (strpos($actualOutput, 'cs=') + 3), 47);
    $contact = Contact::get(FALSE)->addWhere('id', '=', $tokenData['contact_id'])->setSelect(['modified_date', 'employer_id'])->execute()->first();
    $expected = 'contact_type:Individual
do_not_email:1
do_not_phone:
do_not_mail:1
do_not_sms:1
do_not_trade:1
is_opt_out:1
external_identifier:blah
sort_name:Smith, Robert
display_name:Mr. Robert Smith II
nick_name:Bob
image_URL:https://example.com
preferred_communication_method:Phone
preferred_language:fr_CA
preferred_mail_format:Both
hash:xyz
contact_source:Contact Source
first_name:Robert
middle_name:Frank
last_name:Smith
individual_prefix:Mr.
individual_suffix:II
formal_title:Dogsbody
communication_style:Formal
job_title:Busy person
gender:Female
birth_date:December 31st, 1998
current_employer_id:' . $contact['employer_id'] . '
contact_is_deleted:
created_date:January 1st, 2020
modified_date:' . CRM_Utils_Date::customFormat($contact['modified_date']) . '
addressee:Mr. Robert Frank Smith II
email_greeting:Dear Robert
postal_greeting:Dear Robert
current_employer:Unit Test Organization
location_type:Home
address_id:' . $id . '
street_address:Street Address
street_number:123
street_number_suffix:S
street_name:Main St
street_unit:45B
supplemental_address_1:Round the corner
supplemental_address_2:Up the road
supplemental_address_3:By the big tree
city:New York
postal_code_suffix:4578
postal_code:90210
geo_code_1:48.858093
geo_code_2:2.294694
manual_geo_code:1
address_name:The white house
master_id:' . $tokenData['master_id'] . '
county:
state_province:TX
country:United States
phone:123-456
phone_ext:77
phone_type_id:2
phone_type:Mobile
email:anthony_anderson@civicrm.org
on_hold:0
signature_text:Yours sincerely
signature_html:<p>Yours</p>
im_provider:1
im:IM Screen Name
openid:OpenID
world_region:America South, Central, North and Caribbean
url:http://civicrm.org
custom_1:Bobsled
custom_2:Red
custom_3:01/20/2021 12:00AM
custom_4:999
custom_5:<a href="http://civicrm.org" target="_blank">http://civicrm.org</a>
custom_6:
custom_7:New Zealand
custom_8:France, Canada
custom_9:Mr. Spider Man II
custom_10:Queensland
custom_11:Victoria, New South Wales
custom_12:Yes
custom_13:Purple
checksum:cs=' . $checksum . '
contact_id:' . $tokenData['contact_id'] . '
';
    return $expected;
  }

  /**
   * Get the expected rendered string.
   *
   * @param int $id
   * @param array $tokenData
   * @param string $actualOutput
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getExpectedContactOutputNewStyle($id, array $tokenData, string $actualOutput): string {
    $checksum = substr($actualOutput, (strpos($actualOutput, 'cs=') + 3), 47);
    $contact = Contact::get(FALSE)->addWhere('id', '=', $tokenData['contact_id'])->setSelect(['modified_date', 'employer_id'])->execute()->first();
    $expected = 'contact_type:label |Individual
do_not_email:label |Yes
do_not_phone:label |No
do_not_mail:label |Yes
do_not_sms:label |Yes
do_not_trade:label |Yes
is_opt_out:label |Yes
external_identifier |blah
sort_name |Smith, Robert
display_name |Mr. Robert Smith II
nick_name |Bob
image_URL |https://example.com
preferred_communication_method:label |Phone
preferred_language:label |French (Canada)
preferred_mail_format:label |Both
hash |xyz
source |Contact Source
first_name |Robert
middle_name |Frank
last_name |Smith
prefix_id:label |Mr.
suffix_id:label |II
formal_title |Dogsbody
communication_style_id:label |Formal
job_title |Busy person
gender_id:label |Female
birth_date |December 31st, 1998
employer_id |' . $contact['employer_id'] . '
is_deleted:label |No
created_date |January 1st, 2020
modified_date |' . CRM_Utils_Date::customFormat($contact['modified_date']) . '
addressee_display |Mr. Robert Frank Smith II
email_greeting_display |Dear Robert
postal_greeting_display |Dear Robert
current_employer |Unit Test Organization
location_type_id:label |Home
address_id |' . $id . '
street_address |Street Address
street_number |123
street_number_suffix |S
street_name |Main St
street_unit |45B
supplemental_address_1 |Round the corner
supplemental_address_2 |Up the road
supplemental_address_3 |By the big tree
city |New York
postal_code_suffix |4578
postal_code |90210
geo_code_1 |48.858093
geo_code_2 |2.294694
address_name |The white house
master_id |' . $tokenData['master_id'] . '
county |
state_province |TX
country |United States
phone |123-456
phone_ext |77
phone_type |Mobile
email |anthony_anderson@civicrm.org
on_hold:label |No
signature_text |Yours sincerely
signature_html |<p>Yours</p>
provider_id:label |Yahoo
im |IM Screen Name
openid |OpenID
world_region |America South, Central, North and Caribbean
url |http://civicrm.org
custom_9 |Mr. Spider Man II
custom_7 |New Zealand
custom_8 |France, Canada
custom_4 |999
custom_1 |Bobsled
custom_6 |
custom_2 |Red
custom_13 |Purple
custom_10 |Queensland
custom_11 |Victoria, New South Wales
custom_5 |<a href="http://civicrm.org" target="_blank">http://civicrm.org</a>
custom_12 |Yes
custom_3 |01/20/2021 12:00AM
checksum |cs=' . $checksum . '
id |' . $tokenData['contact_id'] . '
t_stuff.favourite_emoticon |
';
    return $expected;
  }

}
