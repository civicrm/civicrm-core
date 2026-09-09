<?php

return [
  [
    'name' => 'SavedSearch_Manage_Tags_Tagset_Members',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Manage_Tags_Tagset_Members',
        'label' => ts('Manage Tags (Tagset Members)'),
        'api_entity' => 'Tag',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id', 'label', 'name', 'description', 'used_for:label', 'used_for',
            'is_selectable', 'is_reserved', 'color', 'created_date',
            'created_id.display_name',
            'COUNT(Tag_EntityTag_tag_id_01.id) AS COUNT_Tag_EntityTag_tag_id_01_id',
          ],
          'orderBy' => ['label' => 'ASC'],
          'where' => [
            ['is_tagset', '=', FALSE],
          ],
          'groupBy' => ['id'],
          'join' => [['EntityTag AS Tag_EntityTag_tag_id_01', 'LEFT', ['id', '=', 'Tag_EntityTag_tag_id_01.tag_id']]],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
];
