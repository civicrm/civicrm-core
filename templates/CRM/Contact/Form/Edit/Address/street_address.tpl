{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !empty($form.address.$blockId.street_address)}
   <tr id="streetAddress_{$blockId}">
     <td colspan="2">
      {$form.address.$blockId.street_address.label} {help id="id-street-address" file="CRM/Contact/Form/Contact.hlp"}<br />
      {$form.address.$blockId.street_address.html}
      {if $parseStreetAddress eq 1 && ($action eq 1 || $action eq 2)}
          &nbsp;&nbsp;<a href="#" title="{ts}Edit Address Elements{/ts}" onClick="processAddressFields( 'addressElements' , '{$blockId}', 1 );return false;">{ts}Edit Address Elements{/ts}</a>
          {help id="id-edit-street-elements" file="CRM/Contact/Form/Contact.hlp"}
      {/if}
    </td>
  </tr>

  {if $parseStreetAddress eq 1 && ($action eq 1 || $action eq 2)}
    <tr id="addressElements_{$blockId}" class=hiddenElement>
      <td>
         {$form.address.$blockId.street_number.label}<br />
         {$form.address.$blockId.street_number.html}
       </td>

      <td>
         {$form.address.$blockId.street_name.label}<br />
         {$form.address.$blockId.street_name.html}<br />
      </td>

      <td colspan="2">
        {$form.address.$blockId.street_unit.label}<br />
        {$form.address.$blockId.street_unit.html}
        <a href="#" title="{ts}Edit Street Address{/ts}" onClick="processAddressFields( 'streetAddress', '{$blockId}', 1 );return false;">{ts}Edit Complete Street Address{/ts}</a>
        {help id="id-edit-complete-street" file="CRM/Contact/Form/Contact.hlp"}
      </td>
    </tr>
  {/if}

{if $parseStreetAddress eq 1}
{literal}
<script type="text/javascript">
function processAddressFields( name, blockId, loadData ) {

  if ( loadData ) {
            var allAddressValues = {/literal}{if $allAddressFieldValues}{$allAddressFieldValues}{else}''{/if}{literal};

      var streetName    = eval( "allAddressValues.street_name_"    + blockId );
      if (streetName === null) streetName = '';
      var streetUnit    = eval( "allAddressValues.street_unit_"    + blockId );
      if (streetUnit === null) streetUnit = '';
      var streetNumber  = eval( "allAddressValues.street_number_"  + blockId );
      if (streetNumber === null) streetNumber = '';
      var streetAddress = eval( "allAddressValues.street_address_" + blockId );
      if (streetAddress === null) streetAddress = '';
  }

        if ( name == 'addressElements' ) {
             if ( loadData ) {
            streetAddress = '';
       }

       cj('#addressElements_' + blockId).show();
       cj('#streetAddress_' + blockId).hide();
  } else {
             if ( loadData ) {
                  streetNumber = streetName = streetUnit = '';
             }

             cj('#streetAddress_' +  blockId).show();
             cj('#addressElements_'+ blockId).hide();
       }

       // set the values.
       if ( loadData ) {
          cj( '#address_' + blockId +'_street_name'    ).val( streetName    );
          cj( '#address_' + blockId +'_street_unit'    ).val( streetUnit    );
          cj( '#address_' + blockId +'_street_number'  ).val( streetNumber  );
          cj( '#address_' + blockId +'_street_address' ).val( streetAddress );
       }
}

</script>
{/literal}
{/if}
{/if}

