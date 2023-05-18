<?php
return CRM_Core_CodeGen_OptionGroup::create('paper_size', 'a/0057')
  ->addMetadata([
    'title' => ts('Paper Size'),
  ])
  ->addValues([
    [
      'label' => ts('Letter'),
      'value' => '{"metric":"in","width":8.5,"height":11}',
      'name' => 'letter',
      'is_default' => 1,
    ],
    [
      'label' => ts('Legal'),
      'value' => '{"metric":"in","width":8.5,"height":14}',
      'name' => 'legal',
    ],
    [
      'label' => ts('Ledger'),
      'value' => '{"metric":"in","width":17,"height":11}',
      'name' => 'ledger',
    ],
    [
      'label' => ts('Tabloid'),
      'value' => '{"metric":"in","width":11,"height":17}',
      'name' => 'tabloid',
    ],
    [
      'label' => ts('Executive'),
      'value' => '{"metric":"in","width":7.25,"height":10.5}',
      'name' => 'executive',
    ],
    [
      'label' => ts('Folio'),
      'value' => '{"metric":"in","width":8.5,"height":13}',
      'name' => 'folio',
    ],
    [
      'label' => ts('Envelope #9'),
      'value' => '{"metric":"pt","width":638.93,"height":278.93}',
      'name' => 'envelope-9',
    ],
    [
      'label' => ts('Envelope #10'),
      'value' => '{"metric":"pt","width":684,"height":297}',
      'name' => 'envelope-10',
    ],
    [
      'label' => ts('Envelope #11'),
      'value' => '{"metric":"pt","width":747,"height":324}',
      'name' => 'envelope-11',
    ],
    [
      'label' => ts('Envelope #12'),
      'value' => '{"metric":"pt","width":792,"height":342}',
      'name' => 'envelope-12',
    ],
    [
      'label' => ts('Envelope #14'),
      'value' => '{"metric":"pt","width":828,"height":360}',
      'name' => 'envelope-14',
    ],
    [
      'label' => ts('Envelope ISO B4'),
      'value' => '{"metric":"pt","width":1000.63,"height":708.66}',
      'name' => 'envelope-b4',
    ],
    [
      'label' => ts('Envelope ISO B5'),
      'value' => '{"metric":"pt","width":708.66,"height":498.9}',
      'name' => 'envelope-b5',
    ],
    [
      'label' => ts('Envelope ISO B6'),
      'value' => '{"metric":"pt","width":498.9,"height":354.33}',
      'name' => 'envelope-b6',
    ],
    [
      'label' => ts('Envelope ISO C3'),
      'value' => '{"metric":"pt","width":1298.27,"height":918.42}',
      'name' => 'envelope-c3',
    ],
    [
      'label' => ts('Envelope ISO C4'),
      'value' => '{"metric":"pt","width":918.42,"height":649.13}',
      'name' => 'envelope-c4',
    ],
    [
      'label' => ts('Envelope ISO C5'),
      'value' => '{"metric":"pt","width":649.13,"height":459.21}',
      'name' => 'envelope-c5',
    ],
    [
      'label' => ts('Envelope ISO C6'),
      'value' => '{"metric":"pt","width":459.21,"height":323.15}',
      'name' => 'envelope-c6',
    ],
    [
      'label' => ts('Envelope ISO DL'),
      'value' => '{"metric":"pt","width":623.622,"height":311.811}',
      'name' => 'envelope-dl',
    ],
    [
      'label' => ts('ISO A0'),
      'value' => '{"metric":"pt","width":2383.94,"height":3370.39}',
      'name' => 'a0',
    ],
    [
      'label' => ts('ISO A1'),
      'value' => '{"metric":"pt","width":1683.78,"height":2383.94}',
      'name' => 'a1',
    ],
    [
      'label' => ts('ISO A2'),
      'value' => '{"metric":"pt","width":1190.55,"height":1683.78}',
      'name' => 'a2',
    ],
    [
      'label' => ts('ISO A3'),
      'value' => '{"metric":"pt","width":841.89,"height":1190.55}',
      'name' => 'a3',
    ],
    [
      'label' => ts('ISO A4'),
      'value' => '{"metric":"pt","width":595.28,"height":841.89}',
      'name' => 'a4',
    ],
    [
      'label' => ts('ISO A5'),
      'value' => '{"metric":"pt","width":419.53,"height":595.28}',
      'name' => 'a5',
    ],
    [
      'label' => ts('ISO A6'),
      'value' => '{"metric":"pt","width":297.64,"height":419.53}',
      'name' => 'a6',
    ],
    [
      'label' => ts('ISO A7'),
      'value' => '{"metric":"pt","width":209.76,"height":297.64}',
      'name' => 'a7',
    ],
    [
      'label' => ts('ISO A8'),
      'value' => '{"metric":"pt","width":147.4,"height":209.76}',
      'name' => 'a8',
    ],
    [
      'label' => ts('ISO A9'),
      'value' => '{"metric":"pt","width":104.88,"height":147.4}',
      'name' => 'a9',
    ],
    [
      'label' => ts('ISO A10'),
      'value' => '{"metric":"pt","width":73.7,"height":104.88}',
      'name' => 'a10',
    ],
    [
      'label' => ts('ISO B0'),
      'value' => '{"metric":"pt","width":2834.65,"height":4008.19}',
      'name' => 'b0',
    ],
    [
      'label' => ts('ISO B1'),
      'value' => '{"metric":"pt","width":2004.09,"height":2834.65}',
      'name' => 'b1',
    ],
    [
      'label' => ts('ISO B2'),
      'value' => '{"metric":"pt","width":1417.32,"height":2004.09}',
      'name' => 'b2',
    ],
    [
      'label' => ts('ISO B3'),
      'value' => '{"metric":"pt","width":1000.63,"height":1417.32}',
      'name' => 'b3',
    ],
    [
      'label' => ts('ISO B4'),
      'value' => '{"metric":"pt","width":708.66,"height":1000.63}',
      'name' => 'b4',
    ],
    [
      'label' => ts('ISO B5'),
      'value' => '{"metric":"pt","width":498.9,"height":708.66}',
      'name' => 'b5',
    ],
    [
      'label' => ts('ISO B6'),
      'value' => '{"metric":"pt","width":354.33,"height":498.9}',
      'name' => 'b6',
    ],
    [
      'label' => ts('ISO B7'),
      'value' => '{"metric":"pt","width":249.45,"height":354.33}',
      'name' => 'b7',
    ],
    [
      'label' => ts('ISO B8'),
      'value' => '{"metric":"pt","width":175.75,"height":249.45}',
      'name' => 'b8',
    ],
    [
      'label' => ts('ISO B9'),
      'value' => '{"metric":"pt","width":124.72,"height":175.75}',
      'name' => 'b9',
    ],
    [
      'label' => ts('ISO B10'),
      'value' => '{"metric":"pt","width":87.87,"height":124.72}',
      'name' => 'b10',
    ],
    [
      'label' => ts('ISO C0'),
      'value' => '{"metric":"pt","width":2599.37,"height":3676.54}',
      'name' => 'c0',
    ],
    [
      'label' => ts('ISO C1'),
      'value' => '{"metric":"pt","width":1836.85,"height":2599.37}',
      'name' => 'c1',
    ],
    [
      'label' => ts('ISO C2'),
      'value' => '{"metric":"pt","width":1298.27,"height":1836.85}',
      'name' => 'c2',
    ],
    [
      'label' => ts('ISO C3'),
      'value' => '{"metric":"pt","width":918.43,"height":1298.27}',
      'name' => 'c3',
    ],
    [
      'label' => ts('ISO C4'),
      'value' => '{"metric":"pt","width":649.13,"height":918.43}',
      'name' => 'c4',
    ],
    [
      'label' => ts('ISO C5'),
      'value' => '{"metric":"pt","width":459.21,"height":649.13}',
      'name' => 'c5',
    ],
    [
      'label' => ts('ISO C6'),
      'value' => '{"metric":"pt","width":323.15,"height":459.21}',
      'name' => 'c6',
    ],
    [
      'label' => ts('ISO C7'),
      'value' => '{"metric":"pt","width":229.61,"height":323.15}',
      'name' => 'c7',
    ],
    [
      'label' => ts('ISO C8'),
      'value' => '{"metric":"pt","width":161.57,"height":229.61}',
      'name' => 'c8',
    ],
    [
      'label' => ts('ISO C9'),
      'value' => '{"metric":"pt","width":113.39,"height":161.57}',
      'name' => 'c9',
    ],
    [
      'label' => ts('ISO C10'),
      'value' => '{"metric":"pt","width":79.37,"height":113.39}',
      'name' => 'c10',
    ],
    [
      'label' => ts('ISO RA0'),
      'value' => '{"metric":"pt","width":2437.8,"height":3458.27}',
      'name' => 'ra0',
    ],
    [
      'label' => ts('ISO RA1'),
      'value' => '{"metric":"pt","width":1729.13,"height":2437.8}',
      'name' => 'ra1',
    ],
    [
      'label' => ts('ISO RA2'),
      'value' => '{"metric":"pt","width":1218.9,"height":1729.13}',
      'name' => 'ra2',
    ],
    [
      'label' => ts('ISO RA3'),
      'value' => '{"metric":"pt","width":864.57,"height":1218.9}',
      'name' => 'ra3',
    ],
    [
      'label' => ts('ISO RA4'),
      'value' => '{"metric":"pt","width":609.45,"height":864.57}',
      'name' => 'ra4',
    ],
    [
      'label' => ts('ISO SRA0'),
      'value' => '{"metric":"pt","width":2551.18,"height":3628.35}',
      'name' => 'sra0',
    ],
    [
      'label' => ts('ISO SRA1'),
      'value' => '{"metric":"pt","width":1814.17,"height":2551.18}',
      'name' => 'sra1',
    ],
    [
      'label' => ts('ISO SRA2'),
      'value' => '{"metric":"pt","width":1275.59,"height":1814.17}',
      'name' => 'sra2',
    ],
    [
      'label' => ts('ISO SRA3'),
      'value' => '{"metric":"pt","width":907.09,"height":1275.59}',
      'name' => 'sra3',
    ],
    [
      'label' => ts('ISO SRA4'),
      'value' => '{"metric":"pt","width":637.8,"height":907.09}',
      'name' => 'sra4',
    ],
  ])
  ->addDefaults([
    'filter' => NULL,
  ]);
