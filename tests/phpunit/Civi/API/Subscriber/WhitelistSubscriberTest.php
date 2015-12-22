<?php
namespace Civi\API\Subscriber;

use Civi\API\Kernel;
use Civi\API\WhitelistRule;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once 'CiviTest/CiviUnitTestCase.php';

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
    $recs = array();

    $recs['widget'] = array(
      1 => array(
        'id' => 1,
        'widget_type' => 'foo',
        'provider' => 'george jetson',
        'title' => 'first widget',
        'comments' => 'this widget is the bomb',
      ),
      2 => array(
        'id' => 2,
        'widget_type' => 'bar',
        'provider' => 'george jetson',
        'title' => 'second widget',
        'comments' => 'this widget is a bomb',
      ),
      3 => array(
        'id' => 3,
        'widget_type' => 'foo',
        'provider' => 'cosmo spacely',
        'title' => 'third widget',
        'comments' => 'omg, that thing is a bomb! widgets are bombs! get out!',
      ),
      8 => array(
        'id' => 8,
        'widget_type' => 'bax',
        'provider' => 'cosmo spacely',
        'title' => 'fourth widget',
        'comments' => 'todo: rebuild garage',
      ),
    );

    $recs['sprocket'] = array(
      1 => array(
        'id' => 1,
        'sprocket_type' => 'whiz',
        'provider' => 'cosmo spacely',
        'title' => 'first sprocket',
        'comment' => 'this sprocket is so good i could eat it up',
        'widget_id' => 2,
      ),
      5 => array(
        'id' => 5,
        'sprocket_type' => 'bang',
        'provider' => 'george jetson',
        'title' => 'second sprocket',
        'comment' => 'this green sprocket was made by soylent',
        'widget_id' => 2,
      ),
      7 => array(
        'id' => 7,
        'sprocket_type' => 'quux',
        'provider' => 'cosmo spacely',
        'title' => 'third sprocket',
        'comment' => 'sprocket green is people! sprocket green is people!',
        'widget_id' => 3,
      ),
      8 => array(
        'id' => 8,
        'sprocket_type' => 'baz',
        'provider' => 'george jetson',
        'title' => 'fourth sprocket',
        'comment' => 'see also: cooking.com/hannibal/1981420-sprocket-fava',
        'widget_id' => 3,
      ),
    );

    return $recs;
  }

  public function restrictionCases() {
    $calls = $rules = array();
    $recs = $this->getFixtures();

    $calls['Widget.get-all'] = array(
      'entity' => 'Widget',
      'action' => 'get',
      'params' => array('version' => 3),
      'expectedResults' => $recs['widget'],
    );
    $calls['Widget.get-foo'] = array(
      'entity' => 'Widget',
      'action' => 'get',
      'params' => array('version' => 3, 'widget_type' => 'foo'),
      'expectedResults' => array(1 => $recs['widget'][1], 3 => $recs['widget'][3]),
    );
    $calls['Widget.get-spacely'] = array(
      'entity' => 'Widget',
      'action' => 'get',
      'params' => array('version' => 3, 'provider' => 'cosmo spacely'),
      'expectedResults' => array(3 => $recs['widget'][3], 8 => $recs['widget'][8]),
    );
    $calls['Widget.get-spacely=>title'] = array(
      'entity' => 'Widget',
      'action' => 'get',
      'params' => array('version' => 3, 'provider' => 'cosmo spacely', 'return' => array('title')),
      'expectedResults' => array(
        3 => array('id' => 3, 'title' => 'third widget'),
        8 => array('id' => 8, 'title' => 'fourth widget'),
      ),
    );
    $calls['Widget.get-spacely-foo'] = array(
      'entity' => 'Widget',
      'action' => 'get',
      'params' => array('version' => 3, 'provider' => 'cosmo spacely', 'widget_type' => 'foo'),
      'expectedResults' => array(3 => $recs['widget'][3]),
    );
    $calls['Sprocket.get-all'] = array(
      'entity' => 'Sprocket',
      'action' => 'get',
      'params' => array('version' => 3),
      'expectedResults' => $recs['sprocket'],
    );
    $calls['Widget.get-bar=>title + Sprocket.get=>provider'] = array(
      'entity' => 'Widget',
      'action' => 'get',
      'params' => array(
        'version' => 3,
        'widget_type' => 'bar',
        'return' => array('title'),
        'api.Sprocket.get' => array(
          'widget_id' => '$value.id',
          'return' => array('provider'),
        ),
      ),
      'expectedResults' => array(
        2 => array(
          'id' => 2,
          'title' => 'second widget',
          'api.Sprocket.get' => array(
            'count' => 2,
            'version' => 3,
            'values' => array(
              0 => array('id' => 1, 'provider' => 'cosmo spacely'),
              1 => array('id' => 5, 'provider' => 'george jetson'),
            ),
            // This is silly:
            'undefined_fields' => array('entity_id', 'entity_table', 'widget_id', 'api.has_parent'),
          ),
        ),
      ),
    );

    $rules['*.*'] = array(
      'version' => 3,
      'entity' => '*',
      'actions' => '*',
      'required' => array(),
      'fields' => '*',
    );
    $rules['Widget.*'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => '*',
      'required' => array(),
      'fields' => '*',
    );
    $rules['Sprocket.*'] = array(
      'version' => 3,
      'entity' => 'Sprocket',
      'actions' => '*',
      'required' => array(),
      'fields' => '*',
    );
    $rules['Widget.get'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => array(),
      'fields' => '*',
    );
    $rules['Sprocket.get'] = array(
      'version' => 3,
      'entity' => 'Sprocket',
      'actions' => 'get',
      'required' => array(),
      'fields' => '*',
    );
    $rules['Sprocket.get=>title,misc'] = array(
      'version' => 3,
      'entity' => 'Sprocket',
      'actions' => 'get',
      'required' => array(),
      // To call api.Sprocket.get via chaining, you must accept superfluous fields.
      // It would be a mistake for the whitelist mechanism to approve these
      // automatically, so instead we have to enumerate them. Ideally, ChainSubscriber
      // wouldn't generate superfluous fields.
      'fields' => array('id', 'title', 'widget_id', 'entity_id', 'entity_table'),
    );
    $rules['Sprocket.get=>provider,misc'] = array(
      'version' => 3,
      'entity' => 'Sprocket',
      'actions' => 'get',
      'required' => array(),
      // To call api.Sprocket.get via chaining, you must accept superfluous fields.
      // It would be a mistake for the whitelist mechanism to approve these
      // automatically, so instead we have to enumerate them. Ideally, ChainSubscriber
      // wouldn't generate superfluous fields.
      'fields' => array('id', 'provider', 'widget_id', 'entity_id', 'entity_table'),
    );
    $rules['Widget.get-foo'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => array('widget_type' => 'foo'),
      'fields' => '*',
    );
    $rules['Widget.get-spacely'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => array('provider' => 'cosmo spacely'),
      'fields' => '*',
    );
    $rules['Widget.get-bar=>title'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => array('widget_type' => 'bar'),
      'fields' => array('id', 'title'),
    );
    $rules['Widget.get-spacely=>title'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => array('provider' => 'cosmo spacely'),
      'fields' => array('id', 'title'),
    );
    $rules['Widget.get-spacely=>widget_type'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'get',
      'required' => array('provider' => 'cosmo spacely'),
      'fields' => array('id', 'widget_type'),
    );
    $rules['Widget.getcreate'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => array('get', 'create'),
      'required' => array(),
      'fields' => '*',
    );
    $rules['Widget.create'] = array(
      'version' => 3,
      'entity' => 'Widget',
      'actions' => 'create',
      'required' => array(),
      'fields' => '*',
    );

    $c = array();

    $c[] = array($calls['Widget.get-all'], array($rules['*.*']), TRUE);
    $c[] = array($calls['Widget.get-all'], array($rules['Widget.*']), TRUE);
    $c[] = array($calls['Widget.get-all'], array($rules['Widget.get']), TRUE);
    $c[] = array($calls['Widget.get-all'], array($rules['Widget.create']), FALSE);
    $c[] = array($calls['Widget.get-all'], array($rules['Widget.getcreate']), TRUE);
    $c[] = array($calls['Widget.get-all'], array($rules['Sprocket.*']), FALSE);

    $c[] = array($calls['Sprocket.get-all'], array($rules['*.*']), TRUE);
    $c[] = array($calls['Sprocket.get-all'], array($rules['Sprocket.*']), TRUE);
    $c[] = array($calls['Sprocket.get-all'], array($rules['Widget.*']), FALSE);
    $c[] = array($calls['Sprocket.get-all'], array($rules['Widget.get']), FALSE);

    $c[] = array($calls['Widget.get-spacely'], array($rules['Widget.*']), TRUE);
    $c[] = array($calls['Widget.get-spacely'], array($rules['Widget.get-spacely']), TRUE);
    $c[] = array($calls['Widget.get-spacely'], array($rules['Widget.get-foo']), FALSE);
    $c[] = array($calls['Widget.get-spacely'], array($rules['Widget.get-foo'], $rules['Sprocket.*']), FALSE);
    $c[] = array(
      // we do a broad get, but 'fields' filtering kicks in and restricts the results
      array_merge($calls['Widget.get-spacely'], array(
        'expectedResults' => $calls['Widget.get-spacely=>title']['expectedResults'],
      )),
      array($rules['Widget.get-spacely=>title']),
      TRUE,
    );

    $c[] = array($calls['Widget.get-foo'], array($rules['Widget.*']), TRUE);
    $c[] = array($calls['Widget.get-foo'], array($rules['Widget.get-foo']), TRUE);
    $c[] = array($calls['Widget.get-foo'], array($rules['Widget.get-spacely']), FALSE);

    $c[] = array($calls['Widget.get-spacely=>title'], array($rules['*.*']), TRUE);
    $c[] = array($calls['Widget.get-spacely=>title'], array($rules['Widget.*']), TRUE);
    $c[] = array($calls['Widget.get-spacely=>title'], array($rules['Widget.get-spacely']), TRUE);
    $c[] = array($calls['Widget.get-spacely=>title'], array($rules['Widget.get-spacely=>title']), TRUE);

    // We request returning title field, but the rule doesn't allow title to be returned.
    // Need it to fail so that control could pass to another rule which does allow it.
    $c[] = array($calls['Widget.get-spacely=>title'], array($rules['Widget.get-spacely=>widget_type']), FALSE);

    // One rule would allow, one would be irrelevant. The order of the two rules shouldn't matter.
    $c[] = array(
      $calls['Widget.get-spacely=>title'],
      array($rules['Widget.get-spacely=>widget_type'], $rules['Widget.get-spacely=>title']),
      TRUE,
    );
    $c[] = array(
      $calls['Widget.get-spacely=>title'],
      array($rules['Widget.get-spacely=>title'], $rules['Widget.get-spacely=>widget_type']),
      TRUE,
    );

    $c[] = array($calls['Widget.get-bar=>title + Sprocket.get=>provider'], array($rules['*.*']), TRUE);
    $c[] = array($calls['Widget.get-bar=>title + Sprocket.get=>provider'], array($rules['Widget.get-bar=>title'], $rules['Sprocket.get']), TRUE);
    $c[] = array($calls['Widget.get-bar=>title + Sprocket.get=>provider'], array($rules['Widget.get'], $rules['Sprocket.get=>title,misc']), FALSE);
    $c[] = array($calls['Widget.get-bar=>title + Sprocket.get=>provider'], array($rules['Widget.get'], $rules['Sprocket.get=>provider,misc']), TRUE);
    $c[] = array($calls['Widget.get-bar=>title + Sprocket.get=>provider'], array($rules['Widget.get-foo'], $rules['Sprocket.get']), FALSE);
    $c[] = array($calls['Widget.get-bar=>title + Sprocket.get=>provider'], array($rules['Widget.get']), FALSE);

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
      array('id', 'widget_type', 'provider', 'title'),
      array(),
      $recs['widget']
    );

    \CRM_Core_DAO_AllCoreTables::registerEntityType('Sprocket', 'CRM_Fake_DAO_Sprocket', 'fake_sprocket');
    $sprocketProvider = new \Civi\API\Provider\StaticProvider(
      3,
      'Sprocket',
      array('id', 'sprocket_type', 'widget_id', 'provider', 'title', 'comment'),
      array(),
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
