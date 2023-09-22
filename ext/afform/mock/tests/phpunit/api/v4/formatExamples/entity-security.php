<?php

return [
  'html' => '<af-form ctrl="afform"><af-entity security="FBAC" type="Contact" name="me" data="{contact_type: \'Individual\', source: \'Hello\'}" actions="{create: 1, update: 0}" /></af-form>',
  'pretty' => '<af-form ctrl="afform">
  <af-entity security="FBAC" type="Contact" name="me" data="{contact_type: \'Individual\', source: \'Hello\'}" actions="{create: 1, update: 0}" />
</af-form>
',
  'stripped' => [
    [
      '#tag' => 'af-form',
      'ctrl' => 'afform',
      '#children' => [
        [
          '#tag' => 'af-entity',
          'security' => 'FBAC',
          'type' => 'Contact',
          'name' => 'me',
          'data' => '{contact_type: \'Individual\', source: \'Hello\'}',
          'actions' => '{create: 1, update: 0}',
        ],
      ],
    ],
  ],
  'shallow' => [
    [
      '#tag' => 'af-form',
      'ctrl' => 'afform',
      '#children' => [
        [
          '#tag' => 'af-entity',
          'security' => 'FBAC',
          'type' => 'Contact',
          'name' => 'me',
          'data' => '{contact_type: \'Individual\', source: \'Hello\'}',
          'actions' => '{create: 1, update: 0}',
        ],
      ],
    ],
  ],
  'deep' => [
    [
      '#tag' => 'af-form',
      'ctrl' => 'afform',
      '#children' => [
        [
          '#tag' => 'af-entity',
          'security' => 'FBAC',
          'type' => 'Contact',
          'name' => 'me',
          'data' => ['contact_type' => 'Individual', 'source' => 'Hello'],
          'actions' => ['create' => 1, 'update' => 0],
        ],
      ],
    ],
  ],
];
