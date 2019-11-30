<?php

return [
  'html' => '<div>One<!-- uno --> Two <!--dos & so on --> Three</div><!--tres-a--b---c-->',
  'shallow' => [
    [
      '#tag' => 'div',
      '#markup' => 'One<!-- uno --> Two <!--dos & so on --> Three',
    ],
    ['#comment' => 'tres-a--b---c'],
  ],
  'deep' => [
    [
      '#tag' => 'div',
      '#children' => [
        ['#text' => 'One'],
        ['#comment' => ' uno '],
        ['#text' => ' Two '],
        ['#comment' => 'dos & so on '],
        ['#text' => ' Three'],
      ],
    ],
    ['#comment' => 'tres-a--b---c'],
  ],
];
