{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    {if !($field.operatorType & 4) && !$field.no_display && $form.$fieldOp.html}
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
    {/literal}{if $displayToggleGroupByFields}{literal}
      $('.crm-report-criteria-field input:checkbox').click(function() {
        $('#group_bys_' + this.id.substr(7)).prop('checked', this.checked);
      });
      {/literal}{/if}{literal}
    });
  </script>
{/literal}
