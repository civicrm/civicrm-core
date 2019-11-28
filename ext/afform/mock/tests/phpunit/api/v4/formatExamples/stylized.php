<?php

return [
  'html' => '<p><strong>Stylized</strong> text is <em>wonky</em> text!</p>',
  'pretty' => "<p><strong>Stylized</strong> text is <em>wonky</em> text!</p>\n",
  'shallow' => [
    ['#tag' => 'p', '#children' => [
      ['#tag' => 'strong', '#children' => [['#text' => 'Stylized']]],
      ['#text' => ' text is '],
      ['#tag' => 'em', '#children' => [['#text' => 'wonky']]],
      ['#text' => ' text!'],
    ]],
  ],
  'deep' => [
    ['#tag' => 'p', '#children' => [
      ['#tag' => 'strong', '#children' => [['#text' => 'Stylized']]],
      ['#text' => ' text is '],
      ['#tag' => 'em', '#children' => [['#text' => 'wonky']]],
      ['#text' => ' text!'],
    ]],
  ],
];
