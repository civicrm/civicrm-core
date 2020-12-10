<?php

return [
  'html' => '<div class="af-container"><strong>  New text!</strong><strong class="af-text"> &nbsp; Get a trim! </strong><af-field name="do_not_sms" defn="{label: \'Do not do any of the emailing\'}" /></div>',
  'pretty' => '<div class="af-container">
  <strong>  New text!</strong>
  <strong class="af-text">&nbsp; Get a trim!</strong>
  <af-field name="do_not_sms" defn="{label: \'Do not do any of the emailing\'}" />
</div>
',
  'stripped' => [
    [
      '#tag' => 'div',
      'class' => 'af-container',
      '#children' => [
        ['#tag' => 'strong', '#markup' => '  New text!'],
        ['#tag' => 'strong', 'class' => 'af-text', '#children' => [['#text' => "&nbsp; Get a trim!"]]],
        ['#tag' => 'af-field', 'name' => 'do_not_sms', 'defn' => "{label: 'Do not do any of the emailing'}"],
      ],
    ],
  ],
  'shallow' => [
    [
      '#tag' => 'div',
      'class' => 'af-container',
      '#children' => [
        ['#tag' => 'strong', '#markup' => '  New text!'],
        ['#tag' => 'strong', 'class' => 'af-text', '#children' => [['#text' => " &nbsp; Get a trim! "]]],
        ['#tag' => 'af-field', 'name' => 'do_not_sms', 'defn' => "{label: 'Do not do any of the emailing'}"],
      ],
    ],
  ],
  'deep' => [
    [
      '#tag' => 'div',
      'class' => 'af-container',
      '#children' => [
        ['#tag' => 'strong', '#children' => [['#text' => '  New text!']]],
        ['#tag' => 'strong', 'class' => 'af-text', '#children' => [['#text' => " &nbsp; Get a trim! "]]],
        ['#tag' => 'af-field', 'name' => 'do_not_sms', 'defn' => ['label' => 'Do not do any of the emailing']],
      ],
    ],
  ],
];
