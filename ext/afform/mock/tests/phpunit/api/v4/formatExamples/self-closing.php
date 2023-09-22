<?php

return [
  'html' => '<span class="one"></span><img class="two" /><div><br class="three" /><br /></div>',
  'pretty' => '<span class="one"></span>
<img class="two" />
<div>
  <br class="three" /><br />
</div>
',
  'shallow' => [
    ['#tag' => 'span', 'class' => 'one'],
    ['#tag' => 'img', 'class' => 'two'],
    ['#tag' => 'div', '#markup' => '<br class="three" /><br />'],
  ],
  'deep' => [
    ['#tag' => 'span', 'class' => 'one'],
    ['#tag' => 'img', 'class' => 'two'],
    [
      '#tag' => 'div',
      '#children' => [
        ['#tag' => 'br', 'class' => 'three'],
        ['#tag' => 'br'],
      ],
    ],
  ],
];
