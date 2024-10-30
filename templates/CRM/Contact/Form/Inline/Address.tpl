{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file builds html for address block inline edit *}
  <table class="form-layout crm-edit-address-form crm-inline-edit-form">
    <tr>
      <td>
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl" location=''}
          {if $addressId}
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a class="button delete-button" href="#" style="display:inline-block;float:none;"><div class="icon delete-icon"></div> {ts}Delete{/ts}</a>
          {/if}
        </div>
      </td>
    </tr>
     <tr>
        <td>
           <span class="crm-address-element location_type_id-address-element">
            {$form.address.$blockId.location_type_id.label}&nbsp;{$form.address.$blockId.location_type_id.html}
            </span>
        </td>
     </tr>
     <tr>
        <td>
           <span class="crm-address-element is_primary-address-element">{$form.address.$blockId.is_primary.html}</span>
           <span class="crm-address-element is_billing-address-element">{$form.address.$blockId.is_billing.html}</span>
        </td>
     </tr>

     {* include shared address template *}
     {include file="CRM/Contact/Form/ShareAddress.tpl"}

     <tr>
      <td>
        <table id="address_table_{$blockId}" class="form-layout-compressed">
           {* build address block w/ address sequence. *}
           {foreach item=addressElement from=$addressSequence}
            {include file="CRM/Contact/Form/Edit/Address/`$addressElement`.tpl"}
           {/foreach}
           {include file="CRM/Contact/Form/Edit/Address/geo_code.tpl"}
       </table>
      </td>
     </tr>
  </table>

  <div class="crm-edit-address-custom_data crm-inline-edit-form crm-address-custom-set-block-{$blockId}">
    {include file="CRM/Contact/Form/Edit/Address/CustomData.tpl"}
  </div>
{literal}
<script type="text/javascript">
  {/literal}{* // Enforce unique location_type_id fields *}{literal}
  cj('#address_{/literal}{$blockId}{literal}_location_type_id').change(function() {
    var ele = cj(this);
    var lt = ele.val();
    var container = ele.closest('div.crm-inline-edit.address');
    container.data('location-type-id', '');
    var ok = true;
    if (lt != '') {
      cj('.crm-inline-edit.address').each(function() {
        if (ok && cj(this).data('location-type-id') == lt) {
          var label = cj('option:selected', ele).text();
          ele.select2('val', '');
          ele.crmError(label + "{/literal} {ts escape='js'}has already been assigned to another address. Please select another location for this address.{/ts}"{literal});
          ok = false;
        }
      });
      if (ok) {
        container.data('location-type-id', lt);
      }
    }
  });
  {/literal}{* // Enforce unique is_primary fields *}{literal}
  cj(':checkbox[id*="[is_primary"]', 'form[name=Address_{/literal}{$blockId}{literal}]').change(function() {
    if (this.defaultChecked) {
      cj(this).crmError("{/literal} {ts escape='js'}Please choose another address to be primary before changing this one.{/ts}{literal}");
      cj(this).prop('checked', true);
    }
  });
  {/literal}{* // Reset location_type_id when cancel button pressed *}{literal}
  cj(':submit[name$=cancel]', 'form[name=Address_{/literal}{$blockId}{literal}]').click(function() {
    var container = cj(this).closest('div.crm-inline-edit.address');
    var origValue = container.attr('data-location-type-id') || '';
    container.data('location-type-id', origValue);
  });
  {/literal}
  {if $masterAddress.$blockId}
    CRM.alert('{ts escape="js" 1=$masterAddress.$blockId}This address is shared with %1 contact record(s). Modifying this address will automatically update the shared address for these contacts.{/ts}', '{ts escape="js"}Editing Master Address{/ts}', 'info', {ldelim}expires: 0{rdelim});
  {/if}
</script>
