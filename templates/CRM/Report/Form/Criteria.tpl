{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Report form criteria section *}

{foreach from=$tabs item=tab}
  {assign var = 'region' value = "report-tab"|cat:$tab.div_label}
  {assign var = 'fileName' value = "CRM/Report/Form/Tabs/"|cat:$tab.tpl|cat:".tpl"}
  {crmRegion name=$region}
    {include file=$fileName}
  {/crmRegion}
{/foreach}

{literal}
  <script type="text/javascript">
{/literal}
{foreach from=$filters item=table key=tableName}
  {foreach from=$table item=field key=fieldName}
    {literal}var val = "dnc";{/literal}
    {assign var=fieldOp     value=$fieldName|cat:"_op"}
    {if !(!empty($field.operatorType) && $field.operatorType & 4) && empty($field.no_display) && !empty($form.$fieldOp.html)}
      {literal}var val = document.getElementById("{/literal}{$fieldOp}{literal}").value;{/literal}
    {/if}
    {literal}showHideMaxMinVal( "{/literal}{$fieldName}{literal}", val );{/literal}
  {/foreach}
{/foreach}

{literal}
  function showHideMaxMinVal( field, val ) {
    var fldVal    = field + "_value_cell";
    var fldMinMax = field + "_min_max_cell";
    if ( val == "bw" || val == "nbw" ) {
      cj('#' + fldVal ).hide();
      cj('#' + fldMinMax ).show();
    } else if (val =="nll" || val == "nnll") {
      cj('#' + fldVal).hide() ;
      cj('#' + field + '_value').val('');
      cj('#' + fldMinMax ).hide();
    } else {
      cj('#' + fldVal ).show();
      cj('#' + fldMinMax ).hide();
    }
  }

  CRM.$(function($) {
    $('.crm-report-criteria-groupby input:checkbox').click(function() {
      $('#fields_' + this.id.substr(10)).prop('checked', this.checked);
    });
    {/literal}{if !empty($displayToggleGroupByFields)}{literal}
      $('.crm-report-criteria-field input:checkbox').click(function() {
        $('#group_bys_' + this.id.substr(7)).prop('checked', this.checked);
      });
      {/literal}{/if}{literal}
    });
  </script>
{/literal}
