{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

{* add single row *}
{if $soInstance}
<tr id="string_override_row_{$soInstance}">
  <td class="even-row checkbox">{$form.enabled.$soInstance.html}</td>
  <td class="even-row">{$form.old.$soInstance.html}</td>
  <td class="even-row">{$form.new.$soInstance.html}</td>
  <td class="even-row checkbox">{$form.cb.$soInstance.html}</td>
</tr>

{else}
{* this template is used for adding/editing string overrides  *}
<div class="crm-form crm-form-block crm-string_override-form-block">
<div id="help">
    {ts}Use <strong>Word Replacements</strong> to change all occurrences of a word or phrase in CiviCRM screens (e.g. replace all occurences of 'Contribution' with 'Donation').{/ts} {help id="id-word_replace"}
</div>
<table class="form-layout-compressed">
  <tr>
      <td>
            <table>
        <tr class="columnheader">
            <td>{ts}Enabled{/ts}</td>
            <td>{ts}Original{/ts}</td>
            <td>{ts}Replacement{/ts}</td>
            <td>{ts}Exact Match?{/ts}</td>
        </tr>

         {section name="numStrings" start=1 step=1 loop=$numStrings+1}
        {assign var='soInstance' value=$smarty.section.numStrings.index}

        <tr id="string_override_row_{$soInstance}">
            <td class="even-row checkbox">{$form.enabled.$soInstance.html}</td>
              <td class="even-row">{$form.old.$soInstance.html}</td>
              <td class="even-row">{$form.new.$soInstance.html}</td>
            <td class="even-row checkbox">{$form.cb.$soInstance.html}</td>
        </tr>

          {/section}
          </table>
         </td>
  </tr>
</table>
 <div class="crm-submit-buttons" ><a class="button" onClick="Javascript:buildStringOverrideRow( false );return false;"><span><div class="icon add-icon"></div>{ts}Add row{/ts}</span></a>{include file="CRM/common/formButtons.tpl"} </div>

</div>
{/if}

{literal}
<script type="text/javascript">
function buildStringOverrideRow( curInstance )
{
   var rowId = 'string_override_row_';

   if ( curInstance ) {
      if ( curInstance <= 10 ) return;
      currentInstance  = curInstance;
      previousInstance = currentInstance - 1;
   } else {
      var previousInstance = cj( '[id^="'+ rowId +'"]:last' ).attr('id').slice( rowId.length );
      var currentInstance = parseInt( previousInstance ) + 1;
   }

   var dataUrl  = {/literal}"{crmURL q='snippet=4' h=0}"{literal} ;
   dataUrl     += "&instance="+currentInstance;

   var prevInstRowId = '#string_override_row_' + previousInstance;

   cj.ajax({ url     : dataUrl,
             async   : false,
             success : function( html ) {
       cj( prevInstRowId ).after( html );
       cj('#old_'+currentInstance).TextAreaResizer();
       cj('#new_'+currentInstance).TextAreaResizer();
       }
   });
}

cj( function( ) {
  {/literal}
  {if $stringOverrideInstances}
     {foreach from=$stringOverrideInstances key="index" item="instance"}
        buildStringOverrideRow( {$instance} );
     {/foreach}
  {/if}
  {literal}
});
</script>
{/literal}
