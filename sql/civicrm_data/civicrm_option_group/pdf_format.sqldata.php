<?php
return CRM_Core_CodeGen_OptionGroup::create('pdf_format', 'a/0058')
  ->addMetadata([
    'title' => ts('PDF Page Format'),
  ])
  ->addValues([
    [
      'label' => ts('Invoice PDF Format'),
      'value' => '{"metric":"px","margin_top":10,"margin_bottom":0,"margin_left":65,"margin_right":0}',
      'name' => 'default_invoice_pdf_format',
      'is_reserved' => 1,
    ],
  ]);
