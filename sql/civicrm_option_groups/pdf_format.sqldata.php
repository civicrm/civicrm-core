<?php
return CRM_Core_CodeGen_OptionGroup::create('pdf_format', 'a/0058')
  ->addMetadata([
    'title' => ts('PDF Page Format'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Invoice PDF Format'), 'default_invoice_pdf_format', '{"metric":"px","margin_top":10,"margin_bottom":0,"margin_left":65,"margin_right":0}', 'is_reserved' => 1],
  ]);
