<?php

return [
  [
    'name' => 'SavedSearch_Tagged_Entities',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Tagged_Entities',
        'label' => ts('Tagged Entities'),
        'api_entity' => 'TaggedEntity',
        'api_params' => [
          'version' => 4,
          'select' => [
            'entity_type',
            'label',
            'url',
          ],
          'orderBy' => [
            'entity_type' => 'ASC',
          ],
          'where' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Tagged_Entities_SearchDisplay_Grouped',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Grouped',
        'label' => ts('Grouped'),
        'saved_search_id.name' => 'Tagged_Entities',
        'type' => 'grouped',
        'settings' => [
          'limit' => 200,
          'classes' => [],
          'sort' => [
            ['entity_type', 'ASC'],
          ],
          'group_by' => 'entity_type',
          'noResultsText' => ts('No records are currently tagged with this tag.'),
          'columns' => [
            [
              'type' => 'html',
              'key' => 'label',
              'label' => ts('Record'),
              'rewrite' => '{if $url}<a href="[url]">[label]</a>{else}[label]{/if}',
            ],
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
