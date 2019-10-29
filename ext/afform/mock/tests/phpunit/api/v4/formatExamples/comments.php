<?php

return [
  'html' => '<div>One<!-- uno --> Two <!--dos & so on --> Three</div><!--tres-a--b---c-->',
  'shallow' => [
    [
      '#tag' => 'div',
      '#children' => [
        'One',
        ['#comment' => ' uno '],
        ' Two ',
        ['#comment' => 'dos & so on '],
        ' Three',
      ],
    ],
    ['#comment' => 'tres-a--b---c'],
  ],
  'deep' => [
    [
      '#tag' => 'div',
      '#children' => [
        'One',
        ['#comment' => ' uno '],
        ' Two ',
        ['#comment' => 'dos & so on '],
        ' Three',
      ],
    ],
    ['#comment' => 'tres-a--b---c'],
  ],
];
