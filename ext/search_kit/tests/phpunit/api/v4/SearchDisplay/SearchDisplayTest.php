<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchDisplayTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {
  use \Civi\Test\Api4TestTrait;

  private ?\Civi\Core\Locale $previousLocale = NULL;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function tearDown(): void {
    $this->previousLocale?->apply();
    parent::tearDown();
  }

  public function testGetOptions(): void {
    $displayTypeOptions = \Civi\Api4\SearchDisplay::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label', 'description', 'icon', 'grouping'])
      ->addWhere('name', '=', 'type')
      ->execute()
      ->first()['options'];

    $displayTypeOptions = array_column($displayTypeOptions, NULL, 'id');
    $this->assertEquals('non-viewable', $displayTypeOptions['autocomplete']['grouping']);
  }

  public function testGetDefault() {
    $params = [
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['first_name', 'display_name', 'contact_sub_type:label', 'gender_id'],
        'where' => [],
      ],
    ];
    $display = SearchDisplay::getDefault(FALSE)
      ->setSavedSearch($params)
      ->addSelect('*', 'saved_search_id.api_entity', 'saved_search_id.api_entity:label', 'type:name', 'type:icon')
      ->execute()->single();

    $this->assertCount(5, $display['settings']['columns']);
    $this->assertEquals('Contacts', $display['label']);
    $this->assertEquals('crm-search-display-table', $display['type:name']);
    $this->assertEquals('fa-table', $display['type:icon']);
    $this->assertEquals('Contact', $display['saved_search_id.api_entity']);
    $this->assertEquals('Contacts', $display['saved_search_id.api_entity:label']);

    // Default sort order should have been added
    $this->assertEquals([['sort_name', 'ASC']], $display['settings']['sort']);

    // `display_name` column should have a link
    $this->assertEquals('Contact', $display['settings']['columns'][1]['link']['entity']);
    $this->assertEquals('view', $display['settings']['columns'][1]['link']['action']);
    $this->assertEmpty($display['settings']['columns'][1]['link']['join']);

    // `first_name` column should not have a link
    $this->assertArrayNotHasKey('link', $display['settings']['columns'][0]);
  }

  public function testGetDefaultWithExplicitAndImplicitJoinColumnLink(): void {
    $params = [
      'api_entity' => 'LineItem',
      'api_params' => [
        'version' => 4,
        'join' => [
          ['Contribution AS con', 'INNER', ['contribution_id', '=', 'con.id']],
        ],
        'select' => ['id', 'con.contact_id.display_name'],
      ],
    ];
    $display = SearchDisplay::getDefault(FALSE)
      ->setSavedSearch($params)
      ->execute()->single();

    $this->assertEquals('Contact', $display['settings']['columns'][1]['link']['entity']);
    $this->assertEquals('con.contact_id', $display['settings']['columns'][1]['link']['join']);
  }

  public function testGetDefaultNoEntity() {
    $display = SearchDisplay::getDefault(FALSE)
      ->addSelect('*', 'saved_search_id', 'type:name', 'type:icon')
      ->execute()->single();

    $this->assertCount(0, $display['settings']['columns']);
    $this->assertEquals('', $display['label']);
    $this->assertEquals('crm-search-display-table', $display['type:name']);
    $this->assertEquals('fa-table', $display['type:icon']);
    $this->assertNull($display['saved_search_id']);
  }

  public function testGetSearchTasksIncludesRegisterParticipantsForContactSearch(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $tasks = SearchDisplay::getSearchTasks(FALSE)
      ->setSavedSearch([
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id'],
        ],
      ])
      ->setDisplay([
        'type' => 'table',
        'label' => __FUNCTION__,
        'settings' => [
          'actions' => TRUE,
        ],
      ])
      ->execute()->indexBy('name');

    $this->assertArrayHasKey('contact.' . \CRM_Contact_Task::ADD_EVENT, (array) $tasks);
    $task = $tasks['contact.' . \CRM_Contact_Task::ADD_EVENT];
    $this->assertSame('Register participants for event', $task['title']);
    $this->assertSame('fa-calendar-plus-o', $task['icon']);
    $this->assertSame("'civicrm/task/register-participants'", $task['crmPopup']['path']);
  }

  public function testAutoFormatName() {
    // Create 3 saved searches; they should all get unique names
    $savedSearch0 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    $savedSearch1 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    $savedSearch2 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    // Name will be created from munged label
    $this->assertEquals('My_test_search', $savedSearch0['name'], "SavedSearch 0");
    // Name will have _r appended to ensure it's unique, where r is a string of
    // random chars.
    $this->assertEquals('My_test_search_', substr($savedSearch1['name'], 0, 15), "SavedSearch 1");
    $this->assertEquals('My_test_search_', substr($savedSearch2['name'], 0, 15), "SavedSearch 2");
    $this->assertNotSame($savedSearch1['name'], $savedSearch2['name'], "SavedSearch 1,2");

    $display0 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch0['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    $display1 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch0['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    $display2 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch1['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    // Name will be created from munged label
    $this->assertEquals('My_test_display', $display0['name'], "SearchDisplay 0");
    // Name will have _r appended (r is random string) to ensure it's unique to
    // savedSearch0.
    $this->assertEquals('My_test_display_', substr($display1['name'], 0, 16), "SearchDisplay 1");
    // This is for a different saved search so doesn't need a suffix appended
    $this->assertEquals('My_test_display', $display2['name'], "SearchDisplay 2");
  }

  public function testGetMarkup(): void {
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => 'testGetMarkup',
      'name' => 'testGetMarkup',
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
      ],
    ]);
    $searchDisplay = $this->createTestRecord('SearchDisplay', [
      'label' => 'testGetMarkupDisplay',
      'name' => 'testGetMarkupDisplay',
      'saved_search_id' => $savedSearch['id'],
      'type' => 'list',
      'settings' => [
        'columns' => [
          [
            'key' => 'id',
            'rewrite' => '"[test] & <escape>"',
          ],
        ],
      ],
    ]);

    $result = SearchDisplay::getMarkup(FALSE)
      ->addWhere('id', '=', $searchDisplay['id'])
      ->addFilter('first_name', 'Fil')
      ->execute()->first();

    $this->assertEquals('crmSearchDisplayList', $result['module']);

    $expected = <<<MARKUP
    <crm-search-display-list search="'testGetMarkup'" display="'testGetMarkupDisplay'" api-entity="Individual" settings="{columns: [{key: 'id', rewrite: '&quot;[test] &amp; &lt;escape&gt;&quot;'}]}" filters="{first_name: 'Fil'}"></crm-search-display-list>
    MARKUP;
    $this->assertSame($expected, $result['markup']);
  }

  /**
   * The Angular client only renders a toolbar when settings.toolbar is set,
   * so the legacy `addButton` setting must be normalized into `toolbar`
   * before being passed to the client via the markup's `settings` attribute.
   */
  public function testGetMarkupNormalizesLegacyAddButtonToToolbar(): void {
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => 'testGetMarkupAddButton',
      'name' => 'testGetMarkupAddButton',
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
      ],
    ]);
    $searchDisplay = $this->createTestRecord('SearchDisplay', [
      'label' => 'testGetMarkupAddButtonDisplay',
      'name' => 'testGetMarkupAddButtonDisplay',
      'saved_search_id' => $savedSearch['id'],
      'type' => 'list',
      'settings' => [
        'addButton' => [
          'path' => 'civicrm/test/url',
          'text' => 'Test',
          'icon' => 'fa-plus',
        ],
      ],
    ]);

    $result = SearchDisplay::getMarkup(FALSE)
      ->addWhere('id', '=', $searchDisplay['id'])
      ->execute()->first();

    $expected = <<<MARKUP
    <crm-search-display-list search="'testGetMarkupAddButton'" display="'testGetMarkupAddButtonDisplay'" api-entity="Individual" settings="{addButton: {path: 'civicrm/test/url', text: 'Test', icon: 'fa-plus'}, toolbar: [{path: 'civicrm/test/url', text: 'Test', icon: 'fa-plus', style: 'primary', target: 'crm-popup'}]}" filters="{}"></crm-search-display-list>
    MARKUP;
    $this->assertSame($expected, $result['markup']);
  }

  /**
   * Every user-facing text of a display is translated on get, not only labels and link texts.
   */
  public function testGetTranslatesDisplayTexts(): void {
    $texts = [
      'Display label', 'Column label', 'Column tooltip', 'Nothing here', 'Link text', 'Link tooltip',
      'Menu text', 'Toolbar text', 'Toolbar tooltip', 'Legacy add', 'Intro text', 'No results text',
      'Search button', 'Totals', 'Column total', 'Add row', 'Rewrite text', 'Image alt', 'Html label',
      'Html rewrite', 'Markup <b>label</b>', 'Smarty {$label}',
    ];
    \Civi\Api4\WordReplacement::save(FALSE)
      ->setRecords(array_map(fn($text) => ['find_word' => $text, 'replace_word' => "DE $text"], $texts))
      ->setDefaults(['language' => 'de_DE', 'match_type' => 'exactMatch'])
      ->execute();

    $savedSearch = $this->createTestRecord('SavedSearch', [
      'api_entity' => 'Contact',
      'api_params' => ['version' => 4, 'select' => ['id', 'display_name']],
    ]);
    $display = $this->createTestRecord('SearchDisplay', [
      'saved_search_id' => $savedSearch['id'],
      'type' => 'table',
      'label' => 'Display label',
      'settings' => [
        'description' => 'Intro text',
        'noResultsText' => 'No results text',
        'button' => 'Search button',
        'tally' => ['label' => 'Totals'],
        'editableRow' => ['create' => TRUE, 'createLabel' => 'Add row'],
        'addButton' => ['path' => 'civicrm/test', 'text' => 'Legacy add'],
        'toolbar' => [['path' => 'civicrm/test', 'text' => 'Toolbar text', 'title' => 'Toolbar tooltip']],
        'columns' => [
          [
            'type' => 'field',
            'key' => 'display_name',
            'label' => 'Column label',
            'title' => 'Column tooltip',
            'rewrite' => 'Rewrite text',
            'empty_value' => 'Nothing here',
            'tally' => ['label' => 'Column total'],
          ],
          [
            'type' => 'menu',
            'text' => 'Menu text',
            'links' => [['path' => 'civicrm/test', 'text' => 'Link text', 'title' => 'Link tooltip']],
          ],
          ['type' => 'image', 'image' => ['alt' => 'Image alt']],
          ['type' => 'html', 'key' => 'display_name', 'label' => 'Html label', 'rewrite' => 'Html rewrite'],
          ['type' => 'field', 'key' => 'id', 'label' => 'Markup <b>label</b>', 'rewrite' => 'Smarty {$label}'],
        ],
      ],
    ]);

    // Activate the locale directly: a language name is negotiated down to en_US without its .mo file
    $this->previousLocale = \Civi\Core\Locale::detect();
    (new \Civi\Core\Locale(['nominal' => 'de_DE', 'ts' => 'de_DE', 'moneyFormat' => 'de_DE']))->apply();
    $result = SearchDisplay::get(FALSE)
      ->addWhere('id', '=', $display['id'])
      ->execute()->single();
    $this->previousLocale->apply();
    $settings = $result['settings'];

    $this->assertSame([
      'label' => 'DE Display label',
      'description' => 'DE Intro text',
      'noResultsText' => 'DE No results text',
      'button' => 'DE Search button',
      'tally.label' => 'DE Totals',
      'editableRow.createLabel' => 'DE Add row',
      'addButton.text' => 'DE Legacy add',
      'toolbar.0.text' => 'DE Toolbar text',
      'toolbar.0.title' => 'DE Toolbar tooltip',
      'columns.0.label' => 'DE Column label',
      'columns.0.title' => 'DE Column tooltip',
      'columns.0.rewrite' => 'DE Rewrite text',
      'columns.0.empty_value' => 'DE Nothing here',
      'columns.0.tally.label' => 'DE Column total',
      'columns.1.text' => 'DE Menu text',
      'columns.1.links.0.text' => 'DE Link text',
      'columns.1.links.0.title' => 'DE Link tooltip',
      'columns.2.image.alt' => 'DE Image alt',
      'columns.3.label' => 'DE Html label',
    ], [
      'label' => $result['label'],
      'description' => $settings['description'],
      'noResultsText' => $settings['noResultsText'],
      'button' => $settings['button'],
      'tally.label' => $settings['tally']['label'],
      'editableRow.createLabel' => $settings['editableRow']['createLabel'],
      'addButton.text' => $settings['addButton']['text'],
      'toolbar.0.text' => $settings['toolbar'][0]['text'],
      'toolbar.0.title' => $settings['toolbar'][0]['title'],
      'columns.0.label' => $settings['columns'][0]['label'],
      'columns.0.title' => $settings['columns'][0]['title'],
      'columns.0.rewrite' => $settings['columns'][0]['rewrite'],
      'columns.0.empty_value' => $settings['columns'][0]['empty_value'],
      'columns.0.tally.label' => $settings['columns'][0]['tally']['label'],
      'columns.1.text' => $settings['columns'][1]['text'],
      'columns.1.links.0.text' => $settings['columns'][1]['links'][0]['text'],
      'columns.1.links.0.title' => $settings['columns'][1]['links'][0]['title'],
      'columns.2.image.alt' => $settings['columns'][2]['image']['alt'],
      'columns.3.label' => $settings['columns'][3]['label'],
    ]);
    // The rewrite of an html column and texts with markup or Smarty are templates and stay as stored
    $this->assertSame('Html rewrite', $settings['columns'][3]['rewrite']);
    $this->assertSame('Markup <b>label</b>', $settings['columns'][4]['label']);
    $this->assertSame('Smarty {$label}', $settings['columns'][4]['rewrite']);
    // Keys that are not texts stay as stored
    $this->assertSame('display_name', $settings['columns'][0]['key']);
    $this->assertSame('civicrm/test', $settings['toolbar'][0]['path']);

    // In English the stored texts come back unchanged
    $result = SearchDisplay::get(FALSE)
      ->addWhere('id', '=', $display['id'])
      ->execute()->single();
    $this->assertSame('No results text', $result['settings']['noResultsText']);
  }

}
