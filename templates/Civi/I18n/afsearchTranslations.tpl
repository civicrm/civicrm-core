{* Template used by Civi/I18n/TranslationAfformProvider.php *}
<div af-fieldset="">
  {literal}<af-field name="source,TranslationSource_Translation_entity_id_01.string" defn="{required: false, input_attrs: {placeholder: '\ud83d\udd0d  {/literal}{ts}Search{/ts}{literal}'}, input_type: 'Text', label: false}" />{/literal}
  <crm-search-display-table search-name="Translations_{$langCode}" display-name="Translations_Table_{$langCode}"></crm-search-display-table>
</div>
