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
{* This file provides the plugin for the Address block *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller*}
{* @var $blockId Contains the current address block id, and assigned in the  CRM/Contact/Form/Location.php file *}

{if $title and $className eq 'CRM_Contact_Form_Contact'}
<div id="addressBlockId" class="crm-accordion-wrapper crm-address-accordion collapsed">
 <div class="crm-accordion-header">
    {$title}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body" id="addressBlock">
{/if}

 <div id="Address_Block_{$blockId}" {if $className eq 'CRM_Contact_Form_Contact'} class="boxBlock crm-edit-address-block crm-address_{$blockId}"{/if}>
  {if $blockId gt 1}<fieldset><legend>{ts}Supplemental Address{/ts}</legend>{/if}
  <table class="form-layout-compressed crm-edit-address-form">
     {if $masterAddress.$blockId gt 0 }
        <tr><td><div class="message status"><div class="icon inform-icon"></div>&nbsp; {ts 1=$masterAddress.$blockId}This address is shared with %1 contact record(s). Modifying this address will automatically update the shared address for these contacts.{/ts}</div></td></tr>
     {/if}

   {if $className eq 'CRM_Contact_Form_Contact'}
     <tr>
        <td id='Address-Primary-html' colspan="2">
           <span class="crm-address-element location_type_id-address-element">{$form.address.$blockId.location_type_id.label}
           {$form.address.$blockId.location_type_id.html}</span>
           <span class="crm-address-element is_primary-address-element">{$form.address.$blockId.is_primary.html}</span>
           <span class="crm-address-element is_billing-address-element">{$form.address.$blockId.is_billing.html}</span>
        </td>
     {if $blockId gt 0}
         <td>
             <a href="#" title="{ts}Delete Address Block{/ts}" onClick="removeBlock( 'Address', '{$blockId}' ); return false;">{ts}Delete this address{/ts}</a>
         </td>
     {/if}
     </tr>

    {* include shared address template *}
    {include file="CRM/Contact/Form/ShareAddress.tpl"}

    {/if}
     <tr>
        <td>
     <table id="address_table_{$blockId}" class="form-layout-compressed">
         {* build address block w/ address sequence. *}
         {foreach item=addressElement from=$addressSequence}
              {include file=CRM/Contact/Form/Edit/Address/$addressElement.tpl}
         {/foreach}
         {include file=CRM/Contact/Form/Edit/Address/geo_code.tpl}
     </table>
        </td>
        <td colspan="2">
           <div class="crm-edit-address-custom_data crm-address-custom-set-block-{$blockId}">
            {include file="CRM/Contact/Form/Edit/Address/CustomData.tpl"}
            </div>
        </td>
     </tr>
  </table>

  {if $className eq 'CRM_Contact_Form_Contact'}
      <div id="addMoreAddress{$blockId}" class="crm-add-address-wrapper">
          <a href="#" class="button" onclick="buildAdditionalBlocks( 'Address', '{$className}' );return false;"><span><i class="crm-i fa-plus-circle"></i> {ts}Another Address{/ts}</span></a>
      </div>
  {/if}

{if $title and $className eq 'CRM_Contact_Form_Contact'}
</div>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{/if}
{literal}
<script type="text/javascript">
//to check if same location type is already selected.
function checkLocation( object, noAlert ) {
  var ele = cj('#' + object);
  var selectedText = cj(':selected', ele).text();
  cj('td#Address-Primary-html select').each( function() {
    element = cj(this).attr('id');
    if ( cj(this).val() && element != object && selectedText == cj(':selected', this).text() ) {
      if ( !noAlert ) {
          var alertText = selectedText + {/literal}" {ts escape='js'}has already been assigned to another address. Please select another location for this address.{/ts}"{literal};
          ele.crmError(alertText);
      }
      cj( '#' + object ).val('');
    }
  });
}
</script>
{/literal}
