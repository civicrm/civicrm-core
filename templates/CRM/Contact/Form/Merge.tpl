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
<div class="crm-block crm-form-block crm-contact-merge-form-block">
<div id="help">
{ts}Click <strong>Merge</strong> to move data from the Duplicate Contact on the left into the Main Contact. In addition to the contact data (address, phone, email...), you may choose to move all or some of the related activity records (groups, contributions, memberships, etc.).{/ts} {help id="intro"}
</div>

<div class="crm-submit-buttons">{if $prev}<a href="{$prev}" class="button"><span>{ts}<< Prev{/ts}</span></a>{/if}{include file="CRM/common/formButtons.tpl" location="top"}{if $next}<a href="{$next}" class="button"><span>{ts}Next >>{/ts}</span></a>{/if}</div>

<div class="action-link">
      <a href="{$flip}">&raquo; {ts}Flip between original and duplicate contacts.{/ts}</a>
</div>

<div class="action-link">
       <a id='notDuplicate' href="#" title={ts}Mark this pair as not a duplicate.{/ts} onClick="processDupes( {$main_cid}, {$other_cid}, 'dupe-nondupe', 'merge-contact', '{crmURL p="civicrm/contact/deduperules" q="reset=1&context=nonDupe"}' );return false;">&raquo; {ts}Mark this pair as not a duplicate.{/ts}</a>
</div>

<table>
  <tr class="columnheader">
    <th>&nbsp;</th>
    <th><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$other_cid"}">{$other_name}&nbsp;<em>{$other_contact_subtype}</em></a> ({ts}duplicate{/ts})</th>
    <th>{ts}Mark All{/ts}<br />=={$form.toggleSelect.html} ==&gt;</th>
    <th><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$main_cid"}">{$main_name}&nbsp;<em>{$main_contact_subtype}</em></a></th>
  </tr>
  {foreach from=$rows item=row key=field}
     <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.title}</td>
        <td>
           {if !is_array($row.other)}
               {$row.other}
           {else}
               {$row.other.fileName}
           {/if}
        </td>
        <td style='white-space: nowrap'>{if $form.$field}=={$form.$field.html}==&gt;{/if}</td>
        <td>
            {if $row.title|substr:0:5 == "Email"   OR
                $row.title|substr:0:7 == "Address" OR
                $row.title|substr:0:2 == "IM"      OR
                $row.title|substr:0:6 == "OpenID"  OR
                $row.title|substr:0:5 == "Phone"}

          {assign var=position  value=$field|strrpos:'_'}
                {assign var=blockId   value=$field|substr:$position+1}
                {assign var=blockName value=$field|substr:14:$position-14}

                {$form.location.$blockName.$blockId.locTypeId.html}&nbsp;
                {if $blockName eq 'email' || $blockName eq 'phone' }
     <span id="main_{$blockName}_{$blockId}_overwrite">{if $row.main}(overwrite){$form.location.$blockName.$blockId.operation.html}&nbsp;<br />{else}(add){/if}</span>
    {literal}
    <script type="text/javascript">
    function mergeBlock(blockname, element, blockId) {
        var allBlock = {/literal}{$mainLocBlock}{literal};
        var block    = eval( "allBlock." + 'main_'+ blockname + element.value);
        if(blockname == 'email' || blockname == 'phone'){
           var label = '(overwrite)'+ '<span id="main_blockname_blockId_overwrite">{/literal}{$form.location.$blockName.$blockId.operation.html}{literal}<br /></span>';
        }
        else {
          label = '(overwrite)<br />';
        }

        if ( !block ) {
                  block = '';
           label   = '(add)';
           }
         cj( "#main_"+ blockname +"_" + blockId ).html( block );
         cj( "#main_"+ blockname +"_" + blockId +"_overwrite" ).html( label );
    }
    </script>
    {/literal}
    {else}
    <span id="main_{$blockName}_{$blockId}_overwrite">{if $row.main}(overwrite)<br />{else}(add){/if}</span>
                {/if}

            {/if}
            {*NYSS 5546*}
            <span id="main_{$blockName}_{$blockId}">
              {if !is_array($row.main)}
                {$row.main}
              {else}
                {$row.main.fileName}
              {/if}
            </span>
        </td>
     </tr>
  {/foreach}

  {foreach from=$rel_tables item=params key=paramName}
    {if $paramName eq 'move_rel_table_users'}
      <tr class="{cycle values="even-row,odd-row"}">
      <th>{ts}Move related...{/ts}</th><td>{if $otherUfId}<a target="_blank" href="{$params.other_url}">{$otherUfName}</a></td><td style='white-space: nowrap'>=={$form.$paramName.html}==&gt;{else}<td style='white-space: nowrap'></td>{/if}</td><td>{if $mainUfId}<a target="_blank" href="{$params.main_url}">{$mainUfName}</a>{/if}</td>
    </tr>
    {else}
    <tr class="{cycle values="even-row,odd-row"}">
      <th>{ts}Move related...{/ts}</th><td><a href="{$params.other_url}">{$params.title}</a></td><td style='white-space: nowrap'>=={$form.$paramName.html}==&gt;</td><td><a href="{$params.main_url}">{$params.title}</a>{if $form.operation.$paramName.add.html}&nbsp;{$form.operation.$paramName.add.html}{/if}</td>
    </tr>
    {/if}
  {/foreach}
</table>
<div class='form-item'>
  <!--<p>{$form.moveBelongings.html} {$form.moveBelongings.label}</p>-->
  <!--<p>{$form.deleteOther.html} {$form.deleteOther.label}</p>-->
</div>
<div class="message status">
    <p><strong>{ts}WARNING: The duplicate contact record WILL BE DELETED after the merge is complete.{/ts}</strong></p>
    {if $user}
      <p><strong>{ts 1=$config->userFramework}There are %1 user accounts associated with both the original and duplicate contacts. Ensure that the Drupal User you want to retain is on the right - if necessary use the 'Flip between original and duplicate contacts.' option at top to swap the positions of the two records before doing the merge.
The user record associated with the duplicate contact will not be deleted, but will be un-linked from the associated contact record (which will be deleted).
You will need to manually delete that user (click on the link to open Drupal User account in new screen). You may need to give thought to how you handle any content or contents associated with that user.{/ts}</strong></p>
    {/if}
    {if $other_contact_subtype}
      <p><strong>The duplicate contact (the one that will be deleted) is a <em>{$other_contact_subtype}</em>. Any data related to this will be lost forever (there is no undo) if you complete the merge.</strong></p>
    {/if}
</div>

<div class="crm-submit-buttons">{if $prev}<a href="{$prev}" class="button"><span>{ts}<< Prev{/ts}</span></a>{/if}{include file="CRM/common/formButtons.tpl" location="bottom"}{if $next}<a href="{$next}" class="button"><span>{ts}Next >>{/ts}</span></a>{/if}</div>
</div>

{literal}
<script type="text/javascript">

cj(document).ready(function(){
    cj('table td input.form-checkbox').each(function() {
       var ele = null;
       var element = cj(this).attr('id').split('_',3);

       switch ( element['1'] ) {
           case 'addressee':
                 var ele = '#' + element['0'] + '_' + element['1'];
                 break;

           case 'email':
           case 'postal':
                 var ele = '#' + element['0'] + '_' + element['1'] + '_' + element['2'];
                 break;
       }

       if( ele ) {
          cj(this).bind( 'click', function() {

              if( cj( this).attr( 'checked' ) ){
                  cj('input' + ele ).attr('checked', true );
                  cj('input' + ele + '_custom' ).attr('checked', true );
              } else {
                  cj('input' + ele ).attr('checked', false );
                  cj('input' + ele + '_custom' ).attr('checked', false );
              }
          });
       }
    });
});

</script>
{/literal}

{* process the dupe contacts *}
{include file="CRM/common/dedupe.tpl"}
