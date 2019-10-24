<?php
namespace Civi\API\Subscriber;

use Civi\API\Kernel;
use Civi\API\WhitelistRule;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The WhitelistSubscriber enforces security policies
 * based on API whitelists. This test combines a number
 * of different policies with different requests and
 * determines if the policies are correctly enforced.
 *
 * Testing breaks down into a few major elements:
 *  - A pair of hypothetical API entities, "Widget"
 *    and "Sprocket".
 *  - A library of possible Widget and Sprocket API
 *    calls (and their expected results).
 *  - A library of possible whitelist rules.
 *  - A list of test cases which attempt to execute
 *    each API call while applying different
 *    whitelist rules.
 *
 */
class WhitelistSubscriberTest extends \CiviUnitTestCase {

  protected function getFixtures() {
    $recs = [];

    $recs['widget'] = [
      1 => [
        'id' => 1,
        'widget_type' => 'foo',
        'provider' => 'george jetson',
        'title' => 'first widget',
        'comments' => 'this widget is the bomb',
      ],
      2 => [
        'id' => 2,
        'widget_type' => 'bar',
        'provider' => 'george jetson',
        'title' => 'second widget',
        'comments' => 'this widget is a bomb',
      ],
      3 => [
        'id' => 3,
        'widget_type' => 'foo',
        'provider' => 'cosmo spacely',
        'title' => 'third widget',
        'comments' => 'omg, that thing is a bomb! widgets are bombs! get out!',
      ],
      8 => [
        'id' => 8,
        'widget_type' => 'bax',
        'provider' => 'cosmo spacely',
        'title' => 'fourth widget',
        'comments' => 'todo: rebuild garage',
      ],
    ];

    $recs['sprocket'] = [
      1 => [
        'id' => 1,
        'sprocket_type' => 'whiz',
        'provider' => 'cosmo spacely',
        'title' => 'first sprocket',
        'comment' => 'this sprocket is so good i could eat it up',
        'widget_id' => 2,
      ],
      5 => [
        'id' => 5,
        'sprocket_type' => 'bang',
        'provider' => 'george jetson',
        'title' => 'second sprocket',
        'comment' => 'this green sprocket was made by soylent',
        'widget_id' => 2,
      ],
      7 => [
        'id' => 7,
        'sprocket_type' => 'quux',
        'provider' => 'cosmo spacely',
        'title' => 'third sprocket',
        'comment' => 'sprocket green is people! sprocket green is people!',
        'widget_id' => 3,
      ],
      8 => [
        'id' => 8,
        'sprocket_type' => 'baz',
        'provider' => 'george jetson',
        'title' => 'fourth sprocket',
        'comment' => 'see also: cooking.com/hannibal/1981420-sprocket-fava',
        'widget_id' => 3,
      ],
    ];

    return $recs;
  }

  public function restrictionCases() {
    $calls = $rules = [];
    $recs = $this->getFixtures();

    $calls['Widget.get-all'] = [
      'entity' => 'Widget',
      'action' => 'get',
      'params' => ['version' => 3],
      'expectedResults' => $recs['widget'],
    ];
    $calls['Widget.get-foo'] = [
      'entity' => 'Widget',
      'action' => 'get',
      'params' => ['version' => 3, 'widget_type' => 'foo'],
      'expectedResults' => [1 => $recs['widget'][1], 3 => $recs['widget'][3]],
    ];
    $calls['Widget.get-spacely'] = [
      'entity' => 'Widget',
      'action' => 'get',
      'params' => ['version' => 3, 'provider' => 'cosmo spacely'],
      'expectedResults' => [3 => $recs['widget'][3], 8 => $recs['widget'][8]],
    ];
    $calls['Widget.get-spacely=>title'] = [
      'entity' => 'Widget',
      'action' => 'get',
      'params' => ['version' => 3, 'provider' => 'cosmo spacely', 'return' => ['title']],
      'expectedResults' => [
        3 => ['id' => 3, 'title' => 'third widget'],
        8 => ['id' => 8, 'title' => 'fourth widget'],
      ],
    ];
    $calls['Widget.get-spacely-foo'] = [
      'entity' => 'Widget',
      'action' => 'get',
      'params' => ['version' => 3, 'provider' => 'cosmo spacely', 'widget_type' => 'foo'],
      'expectedResults' => [3 => $recs['widget'][3]],
    ];
    $calls['Sprocket.get-all'] = [
      'entity' => 'Sprocket',
      'action' => 'get',
      'params' => ['version' => 3],
      'expectedResults' => $recs['sprocket'],
    ];
    $calls['Widget.get-bar=>title + Sprocket.get=>provider'] = [
      'entity' => 'Widget',
      'action' => 'get',
      'params' => [
        'version' => 3,
        'widget_type' => 'bar',
        'return' => ['title'],
        'api.Sprocket.get' => [
          'widget_id' => '$value.id',
          'return' => ['provider'],
        ],
      ],
      'expectedResults' => [
        2 => [
          'id' => 2,
          'title' => 'second widget',
          'api.Sprocket.get' => [
            'is_error' => 0,
            'count' => 2,
            'version' => 3,
            'values' => [
              0 => ['id' => 1, 'provider' => 'cosmo spacely'],
              1 => ['id' => 5, 'provider' => 'george jetson'],
            ],
            // This is silly:
            'undefined_fields' => ['entity_id', 'entity_table', 'widget_id', 'api.has_parent'],
          ],
        ],
      ],
    ];

    $rules['*.*'] = [
      'version' => 3,
      'entity' => '*',
      'actions' => '*',
      'required' => [],
      'fields' => '*',
    ];
    $rules['Widget.*'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => '*',
      'required' => [],
      'fields' => '*',
    ];
    $rules['Sprocket.*'] = [
      'version' => 3,
      'entity' => 'Sprocket',
      'actions' => '*',
      'required' => [],
      'fields' => '*',
    ];
    $rules['Widget.get'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => [],
      'fields' => '*',
    ];
    $rules['Sprocket.get'] = [
      'version' => 3,
      'entity' => 'Sprocket',
      'actions' => 'get',
      'required' => [],
      'fields' => '*',
    ];
    $rules['Sprocket.get=>title,misc'] = [
      'version' => 3,
      'entity' => 'Sprocket',
      'actions' => 'get',
      'required' => [],
      // To call api.Sprocket.get via chaining, you must accept superfluous fields.
      // It would be a mistake for the whitelist mechanism to approve these
      // automatically, so instead we have to enumerate them. Ideally, ChainSubscriber
      // wouldn't generate superfluous fields.
      'fields' => ['id', 'title', 'widget_id', 'entity_id', 'entity_table'],
    ];
    $rules['Sprocket.get=>provider,misc'] = [
      'version' => 3,
      'entity' => 'Sprocket',
      'actions' => 'get',
      'required' => [],
      // To call api.Sprocket.get via chaining, you must accept superfluous fields.
      // It would be a mistake for the whitelist mechanism to approve these
      // automatically, so instead we have to enumerate them. Ideally, ChainSubscriber
      // wouldn't generate superfluous fields.
      'fields' => ['id', 'provider', 'widget_id', 'entity_id', 'entity_table'],
    ];
    $rules['Widget.get-foo'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => ['widget_type' => 'foo'],
      'fields' => '*',
    ];
    $rules['Widget.get-spacely'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => ['provider' => 'cosmo spacely'],
      'fields' => '*',
    ];
    $rules['Widget.get-bar=>title'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => ['widget_type' => 'bar'],
      'fields' => ['id', 'title'],
    ];
    $rules['Widget.get-spacely=>title'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => ['provider' => 'cosmo spacely'],
      'fields' => ['id', 'title'],
    ];
    $rules['Widget.get-spacely=>widget_type'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => ['provider' => 'cosmo spacely'],
      'fields' => ['id', 'widget_type'],
    ];
    $rules['Widget.getcreate'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => ['get', 'create'],
      'required' => [],
      'fields' => '*',
    ];
    $rules['Widget.create'] = [
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'create',
      'required' => [],
      'fields' => '*',
    ];

    $c = [];

    $c[] = [$calls['Widget.get-all'], [$rules['*.*']], TRUE];
    $c[] = [$calls['Widget.get-all'], [$rules['Widget.*']], TRUE];
    $c[] = [$calls['Widget.get-all'], [$rules['Widget.get']], TRUE];
    $c[] = [$calls['Widget.get-all'], [$rules['Widget.create']], FALSE];
    $c[] = [$calls['Widget.get-all'], [$rules['Widget.getcreate']], TRUE];
    $c[] = [$calls['Widget.get-all'], [$rules['Sprocket.*']], FALSE];

    $c[] = [$calls['Sprocket.get-all'], [$rules['*.*']], TRUE];
    $c[] = [$calls['Sprocket.get-all'], [$rules['Sprocket.*']], TRUE];
    $c[] = [$calls['Sprocket.get-all'], [$rules['Widget.*']], FALSE];
    $c[] = [$calls['Sprocket.get-all'], [$rules['Widget.get']], FALSE];

    $c[] = [$calls['Widget.get-spacely'], [$rules['Widget.*']], TRUE];
    $c[] = [$calls['Widget.get-spacely'], [$rules['Widget.get-spacely']], TRUE];
    $c[] = [$calls['Widget.get-spacely'], [$rules['Widget.get-foo']], FALSE];
    $c[] = [$calls['Widget.get-spacely'], [$rules['Widget.get-foo'], $rules['Sprocket.*']], FALSE];
    $c[] = [
      // we do a broad get, but 'fields' filtering kicks in and restricts the results
      array_merge($calls['Widget.get-spacely'], [
        'expectedResults' => $calls['Widget.get-spacely=>title']['expectedResults'],
      ]),
      [$rules['Widget.get-spacely=>title']],
      TRUE,
    ];

    $c[] = [$calls['Widget.get-foo'], [$rules['Widget.*']], TRUE];
    $c[] = [$calls['Widget.get-foo'], [$rules['Widget.get-foo']], TRUE];
    $c[] = [$calls['Widget.get-foo'], [$rules['Widget.get-spacely']], FALSE];

    $c[] = [$calls['Widget.get-spacely=>title'], [$rules['*.*']], TRUE];
    $c[] = [$calls['Widget.get-spacely=>title'], [$rules['Widget.*']], TRUE];
    $c[] = [$calls['Widget.get-spacely=>title'], [$rules['Widget.get-spacely']], TRUE];
    $c[] = [$calls['Widget.get-spacely=>title'], [$rules['Widget.get-spacely=>title']], TRUE];

    // We request returning title field, but the rule doesn't allow title to be returned.
    // Need it to fail so that control could pass to another rule which does allow it.
    $c[] = [$calls['Widget.get-spacely=>title'], [$rules['Widget.get-spacely=>widget_type']], FALSE];

    // One rule would allow, one would be irrelevant. The order of the two rules shouldn't matter.
    $c[] = [
      $calls['Widget.get-spacely=>title'],
      [$rules['Widget.get-spacely=>widget_type'], $rules['Widget.get-spacely=>title']],
      TRUE,
    ];
    $c[] = [
      $calls['Widget.get-spacely=>title'],
      [$rules['Widget.get-spacely=>title'], $rules['Widget.get-spacely=>widget_type']],
      TRUE,
    ];

    $c[] = [$calls['Widget.get-bar=>title + Sprocket.get=>provider'], [$rules['*.*']], TRUE];
    $c[] = [$calls['Widget.get-bar=>title + Sprocket.get=>provider'], [$rules['Widget.get-bar=>title'], $rules['Sprocket.get']], TRUE];
    $c[] = [$calls['Widget.get-bar=>title + Sprocket.get=>provider'], [$rules['Widget.get'], $rules['Sprocket.get=>title,misc']], FALSE];
    $c[] = [$calls['Widget.get-bar=>title + Sprocket.get=>provider'], [$rules['Widget.get'], $rules['Sprocket.get=>provider,misc']], TRUE];
    $c[] = [$calls['Widget.get-bar=>title + Sprocket.get=>provider'], [$rules['Widget.get-foo'], $rules['Sprocket.get']], FALSE];
    $c[] = [$calls['Widget.get-bar=>title + Sprocket.get=>provider'], [$rules['Widget.get']], FALSE];

    return $c;
  }

  protected function setUp() {
    parent::setUp();
  }

  /**
   * @param array $apiRequest
   *   Array(entity=>$,action=>$,params=>$,expectedResults=>$).
   * @param array $rules
   *   Whitelist - list of allowed API calls/patterns.
   * @param bool $expectSuccess
   *   TRUE if the call should succeed.
   *   Success implies that the 'expectedResults' are returned.
   *   Failure implies that the standard error message is returned.
   * @dataProvider restrictionCases
   */
  public function testEach($apiRequest, $rules, $expectSuccess) {
    \CRM_Core_DAO_AllCoreTables::init(TRUE);

    $recs = $this->getFixtures();

    \CRM_Core_DAO_AllCoreTables::registerEntityType('Widget', 'CRM_Fake_DAO_Widget', 'fake_widget');
    $widgetProvider = new \Civi\API\Provider\StaticProvider(3, 'Widget',
      ['id', 'widget_type', 'provider', 'title'],
      [],
      $recs['widget']
    );

    \CRM_Core_DAO_AllCoreTables::registerEntityType('Sprocket', 'CRM_Fake_DAO_Sprocket', 'fake_sprocket');
    $sprocketProvider = new \Civi\API\Provider\StaticProvider(
      3,
      'Sprocket',
      ['id', 'sprocket_type', 'widget_id', 'provider', 'title', 'comment'],
      [],
      $recs['sprocket']
    );

    $whitelist = WhitelistRule::createAll($rules);

    $dispatcher = new EventDispatcher();
    $kernel = new Kernel($dispatcher);
    $kernel->registerApiProvider($sprocketProvider);
    $kernel->registerApiProvider($widgetProvider);
    $dispatcher->addSubscriber(new WhitelistSubscriber($whitelist));
    $dispatcher->addSubscriber(new ChainSubscriber());

    $apiRequest['params']['debug'] = 1;
    $apiRequest['params']['check_permissions'] = 'whitelist';
    $result = $kernel->run($apiRequest['entity'], $apiRequest['action'], $apiRequest['params']);

    if ($expectSuccess) {
      $this->assertAPISuccess($result);
      $this->assertTrue(is_array($apiRequest['expectedResults']));
      $this->assertTreeEquals($apiRequest['expectedResults'], $result['values']);
    }
    else {
      $this->assertAPIFailure($result);
      $this->assertRegExp('/The request does not match any active API authorizations./', $result['error_message']);
    }
  }

}
