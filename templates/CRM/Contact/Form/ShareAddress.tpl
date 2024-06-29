{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for handling share address functionality*}
<tr>
  <td>
    {$form.address.$blockId.use_shared_address.html}{$form.address.$blockId.use_shared_address.label} {help id="id-sharedAddress" file="CRM/Contact/Form/Contact.hlp"}
    <div id="shared-address-{$blockId}" class="form-layout-compressed">
      {$form.address.$blockId.master_contact_id.label}
      {$form.address.$blockId.master_contact_id.html}
      <div class="shared-address-add-relationship" style="display: none;">
        {$form.address.$blockId.add_relationship.html}
        {$form.address.$blockId.add_relationship.label}
        <span class="employer">{help id="id-sharedAddress-updateRelationships" file="CRM/Contact/Form/Contact"}</span>
      </div>
      <div class="shared-address-list">
        {if !empty($sharedAddresses.$blockId.shared_address_display)}
          {foreach item='sa' from=$sharedAddresses.$blockId.shared_address_display.options}
            {assign var="sa_name" value="selected_shared_address-`$blockId`"}
            {assign var="sa_id" value="`$sa_name`-`$sa.id`"}
            <input type="radio" name="{$sa_name}" id="{$sa_id}" value="{$sa.id}" {if $sa.id eq $sharedAddresses.$blockId.shared_address_display.master_id}checked="checked"{/if}>
            <label for="{$sa_id}">{$sa.display_text}</label>{if $sa.location_type}({$sa.location_type}){/if}<br/>
          {/foreach}
        {/if}
      </div>
    </div>
  </td>
</tr>


{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var blockNo = {/literal}{$blockId}{literal},
      contactType = {/literal}{$contactType|@json_encode nofilter}{literal},
      $addRelationshipSection = $('#shared-address-' + blockNo + ' .shared-address-add-relationship'),
      $employerSection = $('#shared-address-' + blockNo + ' .shared-address-add-relationship .employer'),
      $employerLabel = $('#shared-address-' + blockNo + ' .shared-address-add-relationship label .addrel-employer'),
      $householdLabel = $('#shared-address-' + blockNo + ' .shared-address-add-relationship label .addrel-household'),
      $contentArea = $('#shared-address-' + blockNo + ' .shared-address-list'),
      $masterElement = $('input[name="address[' + blockNo + '][master_id]"]');

    function showHideSharedAddress() {
      // based on checkbox, show or hide
      var share = $(this).prop('checked');
      $('#shared-address-' + blockNo).toggle(!!share);
      $('table#address_table_' + blockNo +', .crm-address-custom-set-block-' + blockNo).toggle(!share);
    }

    // "Use another contact's address" checkbox
    $('#address\\[' + blockNo + '\\]\\[use_shared_address\\]').each(showHideSharedAddress).click(showHideSharedAddress);

    // When an address is selected
    $contentArea.off().on('click', 'input', function() {
      $masterElement.val($(this).val());
    });

    // When shared contact is selected/unselected
    $('input[name="address[' + blockNo +'][master_contact_id]"]').change(function() {
      var $el = $(this),
        sharedContactId = $el.val();

      $contentArea.html('');
      $masterElement.val('');

      if (!sharedContactId || isNaN(sharedContactId)) {
        $employerSection.hide();
        $addRelationshipSection.hide();
        $employerLabel.hide();
        $householdLabel.hide();
        return;
      }

      var otherContactType = $el.select2('data').extra.contact_type;
      $addRelationshipSection.toggle(contactType === 'Individual' && (otherContactType === 'Organization' || otherContactType === 'Household'));
      $employerSection.toggle(contactType === 'Individual' && otherContactType === 'Organization');

      // use the appropriate label
      $employerLabel.toggle(contactType === 'Individual' && otherContactType === 'Organization');
      $householdLabel.toggle(contactType === 'Individual' && otherContactType === 'Household');


      $.post(CRM.url('civicrm/ajax/inline'), {
          'contact_id': sharedContactId,
          'type': 'method',
          'class_name': 'CRM_Contact_Page_AJAX',
          'fn_name': 'getAddressDisplay'
        },
        function(response) {
          // Avoid race conditions - check that value hasn't been changed by the user while we were waiting for response
          if (response && $el.val() === sharedContactId) {
            var selected = ' checked="checked"',
              addressHTML = '';

            $.each(response, function(i, val) {
              if (addressHTML) {
                selected = '';
              } else {
                $('input[name="address[' + blockNo + '][master_id]"]').val(val.id);
              }
              var name = 'selected_shared_address-'+ blockNo,
                id = name + '-' + val.id;
              addressHTML += '<input type="radio" name="' + name + '" id="' + id + '" value="' + val.id + '"' + selected +'><label for="' + id + '">' + val.display_text + '</label>('+val.location_type+')<br/>';
            });

            if (!addressHTML) {
              addressHTML = {/literal}"{ts escape='js'}Selected contact does not have an address. Please click the following link to edit that contact to add an address, or select a different contact.{/ts}"{literal} + ' <a target="_blank" href="' + CRM.url('civicrm/contact/add', 'reset=1&action=update&cid=' + sharedContactId) + '">{/literal}{ts escape='js'}Edit Contact Details{/ts}{literal}</a>';
            }

            $contentArea.html(addressHTML);
          }
        },'json');
    });
  });
</script>
{/literal}
