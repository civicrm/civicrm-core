{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Included in Custom/Form/Field.tpl - used for fields with multiple choice options. *}
<tr>
  <td class="label">{$form.option_type.label} {help id="option_type" file="CRM/Custom/Form/Field"}</td>
  <td class="html-adjust">{$form.option_type.html}</td>
</tr>

<tr id="option_group" {if empty($form.option_group_id)}class="hiddenElement"{/if}>
  <td class="label">{$form.option_group_id.label}</td>
  <td class="html-adjust">{$form.option_group_id.html}</td>
</tr>

<tr id="multiple">
<td colspan="2" class="html-adjust">
    <fieldset><legend>{ts}Multiple Choice Options{/ts}</legend>
    <span class="description">
        {ts}Enter up to ten (10) multiple choice options in this table (click 'another choice' for each additional choice). If you need more than ten options, you can create an unlimited number of additional choices using the Edit Multiple Choice Options link after saving this new field. If desired, you can mark one of the choices as the default choice. The option 'label' is displayed on the form, while the option 'value' is stored in the contact record. The label and value may be the same or different. Inactive options are hidden when the field is presented.{/ts}
    </span>
  {strip}
  <table id="optionField">
  <tr>
        <th>&nbsp;</th>
        <th> {ts}Default{/ts}</th>
        <th> {ts}Label{/ts}</th>
        <th> {ts}Value{/ts}</th>
        <th> {ts}Order{/ts}</th>
        <th> {ts}Active?{/ts}</th>
    </tr>

  {section name=rowLoop start=1 loop=12}
  {assign var=index value=$smarty.section.rowLoop.index}
  <tr id="optionField_{$index}" class="form-item {cycle values="odd-row,even-row"}">
        <td>
        {if $index GT 1}
            <a onclick="showHideRow({$index}); return false;" name="optionField_{$index}" href="#" class="form-link"><i class="crm-i fa-trash" title="{ts escape='htmlattribute'}hide field or section{/ts}" role="img" aria-hidden="true"></i></a>
        {/if}
        </td>
      <td>
    <div id="radio{$index}" style="display:none">
         {$form.default_option[$index].html}
    </div>
    <div id="checkbox{$index}" style="display:none">
         {$form.default_checkbox_option.$index.html}
    </div>
      </td>
      <td> {$form.option_label.$index.html}</td>
      <td> {$form.option_value.$index.html}</td>
      <td> {$form.option_weight.$index.html}</td>
       <td> {$form.option_status.$index.html}</td>
  </tr>
    {/section}
    </table>
  <div id="optionFieldLink" class="add-remove-link">
        <a onclick="showHideRow(); return false;" name="optionFieldLink" href="#" class="form-link"><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}add another choice{/ts}</a>
    </div>
  <span id="additionalOption" class="description">
    {ts}If you need additional options - you can add them after you Save your current entries.{/ts}
  </span>
    {/strip}

</fieldset>
</td>
</tr>
<script type="text/javascript">
    var showRows   = new Array({$showBlocks});
    var hideBlocks = new Array({$hideBlocks});
    var rowcounter = 0;
    {* hide and display the appropriate blocks as directed by the php code *}
    on_load_init_blocks( showRows, hideBlocks, '' );

{if !empty($form.option_group_id)}
{literal}
function showOptionSelect( ) {
   if ( document.getElementsByName("option_type")[0].checked ) {
      cj('#multiple').show();
      cj('#option_group').hide();
   } else {
      cj('#multiple').hide();
      cj('#option_group').show();
   }
}
showOptionSelect( );
{/literal}
{/if}
</script>


