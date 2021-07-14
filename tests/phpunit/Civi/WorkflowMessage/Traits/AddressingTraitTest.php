<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\WorkflowMessage\Traits;

use Civi\WorkflowMessage\GenericWorkflowMessage;

class AddressingTraitTest extends \CiviUnitTestCase {

  protected function setUp(): void {
    $this->useTransaction();
    parent::setUp();
  }

  /**
   * @return \Civi\WorkflowMessage\GenericWorkflowMessage
   */
  protected static function createExample() {
    return new class() extends GenericWorkflowMessage {

      const WORKFLOW = 'ex_address_wf';

      const GROUP = 'ex_address_grp';
    };
  }

  /**
   * Set email addresses using fluent methods (setTo(), setCc(), etc).
   */
  public function testFluentSetup() {
    $wfm = $this->createExample()
      // Setters support array or string inputs. All address fields support the same formats.
      ->setTo(['name' => 'Foo', 'email' => 'foo@example.com'])
      ->setReplyTo('Nobody <nobody@example.com>')
      ->setCc([['email' => 'cc1@example.com'], ['name' => 'Bob', 'email' => 'cc2@example.com']]);

    $this->assertEquals(['email' => 'nobody@example.com', 'name' => 'Nobody'], $wfm->getReplyTo('record'));

    $export = $wfm->export('envelope');

    $this->assertEquals('Foo', $export['toName']);
    $this->assertEquals('foo@example.com', $export['toEmail']);
    $this->assertEquals('cc1@example.com, Bob <cc2@example.com>', $export['cc']);
    $this->assertEquals('Nobody <nobody@example.com>', $export['replyTo']);
  }

  /**
   * Set email addresses using fluent methods (setTo(), setCc(), etc).
   */
  public function testFluentAdder() {
    $wfm = $this->createExample()
      // Setters support array or string inputs. All address fields support the same formats.
      ->setCc([['email' => 'cc1@example.com'], ['name' => 'Bob', 'email' => 'cc2@example.com']])
      ->addCc('"Third" <cc3@example.com>')
      ->addCc(['email' => 'cc4@example.com', 'name' => 'Fourth\'s']);

    $this->assertEquals('cc1@example.com, Bob <cc2@example.com>, Third <cc3@example.com>, "Fourth\'s" <cc4@example.com>', $wfm->getCc('rfc822'));
  }

  /**
   * Set email addresses using model-properties.
   */
  public function testModelPropsSetup() {
    $wfm = $this->createExample()
      ->import('modelProps', [
        // modelProps support array or string inputs. All address fields support the same formats.
        'to' => ['name' => 'Foo', 'email' => 'foo@example.com'],
        'replyTo' => 'Nobody <nobody@example.com>',
        'cc' => [['email' => 'cc1@example.com'], ['name' => 'Bob', 'email' => 'cc2@example.com']],
      ]);

    $this->assertEquals(['email' => 'nobody@example.com', 'name' => 'Nobody'], $wfm->getReplyTo('record'));

    $export = $wfm->export('envelope');

    $this->assertEquals('Foo', $export['toName']);
    $this->assertEquals('foo@example.com', $export['toEmail']);
    $this->assertEquals('cc1@example.com, Bob <cc2@example.com>', $export['cc']);
    $this->assertEquals('Nobody <nobody@example.com>', $export['replyTo']);
  }

  /**
   * Set email addresses using sendTemplate()'s envelope format.
   */
  public function testEnvelopeSetup() {
    $ex = $this->createExample();

    $envelopeArray = [
      'toName' => "It's Me",
      'toEmail' => 'me@example.com',
      'from' => '<from@example.com>',
      'replyTo' => '"Reply To Me" <replyto@example.com>',
      'cc' => 'cc1@example.com, <cc2@example.com>, "Third" <cc3@example.com>',
    ];

    $ex->import('envelope', $envelopeArray);
    $this->assertEquals(['name' => "It's Me", 'email' => 'me@example.com'], $ex->getTo('record'));
    $this->assertEquals(['name' => NULL, 'email' => 'from@example.com'], $ex->getFrom('record'));
    $this->assertEquals(['name' => 'Reply To Me', 'email' => 'replyto@example.com'], $ex->getReplyTo('record'));
    $this->assertEquals([
      ['name' => NULL, 'email' => 'cc1@example.com'],
      ['name' => NULL, 'email' => 'cc2@example.com'],
      ['name' => 'Third', 'email' => 'cc3@example.com'],
    ], $ex->getCc('records'));

    $actualExport = $ex->export('envelope');
    foreach ($envelopeArray as $key => $value) {
      $this->assertEquals($value, $actualExport[$key], "Key '$key' should match");
    }
  }

  public function testSingularValues() {
    $ex = $this->createExample();

    $singularFields = ['to', 'from', 'replyTo', 'cc', 'bcc'];

    $singularExamples = [];
    $singularExamples[] = [
      'rfc822' => 'Foo Bar <foo@example.com>',
      'record' => ['name' => 'Foo Bar', 'email' => 'foo@example.com'],
      'records' => [['name' => 'Foo Bar', 'email' => 'foo@example.com']],
    ];
    $singularExamples[] = [
      'rfc822' => 'foo@example.com',
      'record' => ['name' => NULL, 'email' => 'foo@example.com'],
      'records' => [['name' => NULL, 'email' => 'foo@example.com']],
    ];

    $this->assertNotEmpty($singularFields);
    foreach ($singularFields as $field) {
      $setter = 'set' . ucfirst($field);
      $getter = 'get' . ucfirst($field);

      $this->assertNotEmpty($singularExamples);
      foreach ($singularExamples as $equivalenceSet) {
        $this->assertNotEmpty($equivalenceSet);
        foreach (array_keys($equivalenceSet) as $inFormat) {
          foreach (array_keys($equivalenceSet) as $outFormat) {
            $ex->{$setter}(NULL);
            $this->assertEquals(NULL, $ex->{$getter}(), "Field ($field) should start at empty");

            $ex->{$setter}($equivalenceSet[$inFormat]);
            $this->assertEquals($equivalenceSet[$outFormat], $ex->{$getter}($outFormat), "Field ($field) should return equivalent result (method=setter, in=$inFormat, out=$outFormat)");

            if ($field !== 'to') {
              $export = $ex->export('envelope');
              $this->assertEquals($equivalenceSet['rfc822'], $export[$field], 'export() always produces header format');

              $ex->{$setter}(NULL);
              $this->assertEquals(NULL, $ex->{$getter}(), "Field ($field) should start at empty");

              $ex->import('envelope', [$field => $equivalenceSet[$inFormat]]);
              $this->assertEquals($equivalenceSet[$outFormat], $ex->{$getter}($outFormat), "Field ($field) should return equivalent result (method=import, in=$inFormat, out=$outFormat)");
            }
          }
        }
      }
    }
  }

  public function testPluralValues() {
    $ex = $this->createExample();

    $pluralFields = ['cc', 'bcc'];

    $pluralExamples = [];
    $pluralExamples[] = [
      'rfc822' => 'First <first@example.com>, "Second\'s Name" <second@example.com>, third@example.com',
      'records' => [
        ['name' => 'First', 'email' => 'first@example.com'],
        ['name' => "Second's Name", 'email' => 'second@example.com'],
        ['name' => NULL, 'email' => 'third@example.com'],
      ],
    ];
    $pluralExamples[] = [
      'rfc822' => '',
      'records' => [],
    ];

    $this->assertNotEmpty($pluralFields);
    foreach ($pluralFields as $field) {
      $setter = 'set' . ucfirst($field);
      $getter = 'get' . ucfirst($field);

      $this->assertNotEmpty($pluralExamples);
      foreach ($pluralExamples as $equivalenceSet) {
        $this->assertNotEmpty($equivalenceSet);
        foreach (array_keys($equivalenceSet) as $inFormat) {
          foreach (array_keys($equivalenceSet) as $outFormat) {
            $ex->{$setter}(NULL);
            $this->assertEquals(NULL, $ex->{$getter}(), "Field ($field) should start at empty");

            $ex->{$setter}($equivalenceSet[$inFormat]);
            $this->assertEquals($equivalenceSet[$outFormat], $ex->{$getter}($outFormat), "Field ($field) should return equivalent result (method=setter, in=$inFormat, out=$outFormat)");

            $ex->{$setter}(NULL);
            $this->assertEquals(NULL, $ex->{$getter}(), "Field ($field) should start at empty");

            $ex->import('envelope', [$field => $equivalenceSet[$inFormat]]);
            $this->assertEquals($equivalenceSet[$outFormat], $ex->{$getter}($outFormat), "Field ($field) should return equivalent result (method=import, in=$inFormat, out=$outFormat)");
          }
        }
      }
    }
  }

}
