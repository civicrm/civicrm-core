<?php
if (!\CRM_Core_I18n::isMultilingual()) {
  return [];
}
$items = [];

$languages = \CRM_Core_I18n::languages();

foreach (\CRM_Core_I18n::getMultilingual() as $index => $langCode) {
  $items[] = [
    'name' => "SavedSearch_Translation_$langCode",
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => "Translations_$langCode",
        'label' => ts('Translations for %1', [1 => $languages[$langCode]]),
        'form_values' => [
          'join' => [
            'TranslationSource_Translation_source_key_01' => 'Translated Strings',
          ],
        ],
        'api_entity' => 'TranslationSource',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'source',
            'source_key',
            'TranslationSource_Translation_source_key_01.id',
            'TranslationSource_Translation_source_key_01.string',
            'TranslationSource_Translation_source_key_01.language:label',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'Translation AS TranslationSource_Translation_source_key_01',
              'LEFT',
              [
                'source_key',
                '=',
                'TranslationSource_Translation_source_key_01.source_key',
              ],
              [
                'TranslationSource_Translation_source_key_01.language:name',
                '=',
                "\"$langCode\"",
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
  ];
  $items[] = [
    'name' => "SavedSearch_Translation_Display_Table_$langCode",
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => "Translations_Table_$langCode",
        'label' => ts('Translations for %1', [1 => $languages[$langCode]]),
        'saved_search_id.name' => "Translations_$langCode",
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'TranslationSource_Translation_source_key_01.string',
              'ASC',
            ],
            [
              'source',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'source',
              'dataType' => 'Text',
              'label' => ts('Source Text'),
              'sortable' => TRUE,
              'editable' => FALSE,
            ],
            [
              'type' => 'field',
              'key' => 'TranslationSource_Translation_source_key_01.string',
              'dataType' => 'Text',
              'label' => ts('Translation'),
              'sortable' => TRUE,
              'editable' => TRUE,
              'icons' => [
                [
                  'icon' => 'fa-square-plus',
                  'side' => 'left',
                  'if' => [
                    'TranslationSource_Translation_source_key_01.string',
                    'IS EMPTY',
                  ],
                ],
              ],
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ];
  // Insert navigation menu item
  $items[] = [
    'name' => 'navigation_afsearchTranslation' . $langCode,
    'cleanup' => 'always',
    'update' => 'unmodified',
    'entity' => 'Navigation',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afsearchTranslation' . $langCode,
        'parent_id.name' => 'Localization',
        'label' => ts('Translations to %1', [1 => $languages[$langCode]]),
        'permission' => ['translate CiviCRM'],
        'permission_operator' => 'AND',
        'weight' => 5,
        'url' => "civicrm/admin/translation-$langCode",
      ],
      'match' => ['name'],
    ],
  ];
}
return $items;
