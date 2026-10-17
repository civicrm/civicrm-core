<?php

return [
  [
    'name' => 'SavedSearch_Manage_Tags_Table',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Manage_Tags_Table',
        'label' => ts('Manage Tags (Table)'),
        'api_entity' => 'Tag',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'label',
            'name',
            'description',
            'used_for:label',
            'used_for',
            'parent_id',
            'parent_id.label',
            'is_selectable',
            'is_reserved',
            'is_tagset',
            'color',
            'created_date',
            'created_id.display_name',
            'COUNT(Tag_EntityTag_tag_id_01.id) AS COUNT_Tag_EntityTag_tag_id_01_id',
          ],
          'orderBy' => [
            'label' => 'ASC',
          ],
          'where' => [
            ['is_tagset', '=', FALSE],
            [
              'OR',
              [
                ['parent_id.is_tagset', 'IS NULL'],
                ['parent_id.is_tagset', '=', FALSE],
              ],
            ],
          ],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'EntityTag AS Tag_EntityTag_tag_id_01',
              'LEFT',
              [
                'id',
                '=',
                'Tag_EntityTag_tag_id_01.tag_id',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
