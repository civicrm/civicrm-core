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
{* template for handling share address functionality*}
<tr>
  <td>
    {$form.address.$blockId.use_shared_address.html}{$form.address.$blockId.use_shared_address.label}{help id="id-sharedAddress" file="CRM/Contact/Form/Contact.hlp"}<br />
    {if !empty($sharedAddresses.$blockId.shared_address_display)}
      <span class="shared-address-display" id="shared-address-display-name-{$blockId}">
        {$sharedAddresses.$blockId.shared_address_display.name}
      </span>

      <span class="shared-address-display" id="shared-address-display-{$blockId}" onclick="cj(this).hide( );cj('#shared-address-display-name-{$blockId}').hide( );cj('#shared-address-display-cancel-{$blockId}').show( );cj('#shared-address-{$blockId}').show( );">
              {$sharedAddresses.$blockId.shared_address_display.address} <a href='#' onclick='return false;'>( {ts}Change current shared address{/ts} )</a>
      </span>

      <span id="shared-address-display-cancel-{$blockId}" class="hiddenElement" onclick="cj(this).hide( );cj('#shared-address-display-name-{$blockId}').show( );cj('#shared-address-display-{$blockId}').show( );cj('#shared-address-{$blockId}').hide( );">
              <a href='#' onclick='return false;'>( {ts}Cancel{/ts} )</a>
      </span>
    {/if}

    <table id="shared-address-{$blockId}" class="form-layout-compressed hiddenElement">
      {include file="CRM/Contact/Form/NewContact.tpl" blockNo="$blockId"}
    </table>
  </td>
</tr>


{literal}
<script type="text/javascript">
  function showHideSharedAddress( blockNo, showSelect ) {
    // based on checkbox, show or hide
    if ( cj( '#address\\[' + blockNo + '\\]\\[use_shared_address\\]' ).attr( 'checked') ) {
      if ( showSelect && cj( '#shared-address-display-' + blockNo ).length == 0 ) {
        cj( '#shared-address-' + blockNo ).show( );
      }
      cj( 'table#address_table_' + blockNo ).hide( );
      cj( '#shared-address-display-' + blockNo ).show( );
      cj( '#shared-address-display-name-' + blockNo ).show( );
      cj( '#shared-address-display-cancel-' + blockNo ).hide( );
      cj( '.crm-address-custom-set-block-' + blockNo).hide( );
    } else {
      cj( '#shared-address-' + blockNo ).hide( );
      cj( 'table#address_table_' + blockNo ).show( );
      cj( '#shared-address-display-' + blockNo ).hide( );
      cj( '#shared-address-display-name-' + blockNo ).hide( );
      cj( '#shared-address-display-cancel-' + blockNo ).hide( );
      cj( '.crm-address-custom-set-block-' + blockNo).show( );
    }
  }

cj( function( ) {
    var blockNo = {/literal}{$blockId}{literal};

    // call this when form loads
    showHideSharedAddress( blockNo, true );

    // handle check / uncheck of checkbox
    cj( '#address\\[' + blockNo + '\\]\\[use_shared_address\\]' ).click( function( ) {
      showHideSharedAddress( blockNo, true );
    });

    // start of code to add onchange event for hidden element
    var contactHiddenElement = 'input[name="contact_select_id[' + blockNo +']"]';

    // store initial value
    var _default  = cj( contactHiddenElement ).val();

    // observe changes
    cj( contactHiddenElement ).change(function( ) {
      var sharedContactId = cj( this ).val( );
      if ( !sharedContactId || isNaN( sharedContactId ) ) {
        return;
      }

      var addressHTML = '';
      var postUrl = {/literal}"{crmURL p='civicrm/ajax/inline' h=0}"{literal};

      addCiviOverlay('div.crm-address_' + blockNo);

      cj.post( postUrl, {
        'contact_id': sharedContactId,
        'type': 'method',
        'class_name': 'CRM_Contact_Page_AJAX',
        'fn_name': 'getAddressDisplay'
        },
        function( response ) {
          if ( response ) {
            var selected = 'checked';
            var addressExists = false;

            cj.each( response, function( i, val ) {
              if ( i > 1 ) {
                selected = '';
              } else {
                cj( 'input[name="address[' + blockNo + '][master_id]"]' ).val( val.id );
              }

              addressHTML = addressHTML + '<input type="radio" name="selected_shared_address-'+ blockNo +'" value=' + val.id + ' ' + selected +'>' + val.display_text + '<br/>';

              addressExists = true;
            });

            if ( addressExists  ) {
              cj( '#shared-address-' + blockNo + ' .shared-address-list' ).remove( );
              cj( '#shared-address-' + blockNo ).append( '<tr class="shared-address-list"><td></td><td>' + addressHTML + '</td></tr>');
              cj( 'input[name^=selected_shared_address-]' ).click( function( ) {

              // get the block id
              var elemId = cj(this).attr( 'name' ).split('-');
              cj( 'input[name="address[' + elemId[1] + '][master_id]"]' ).val( cj(this).val( ) );
              });
            } else {
              var helpText = {/literal}"{ts escape='js'}Selected contact does not have an address. Please edit that contact to add an address, or select a different contact.{/ts}"{literal};
              cj( '#shared-address-' + blockNo + ' .shared-address-list' ).remove( );
              cj( '#shared-address-' + blockNo ).append( '<tr class="shared-address-list"><td></td><td>' + helpText + '</td></tr>');
            }

            removeCiviOverlay('div.crm-address_' + blockNo);
          }
        },'json');
    });


    // continuous check for changed value
    setInterval(function( ) {
        if ( cj( contactHiddenElement ).val( ) != _default ) {
        // trigger native
        cj( contactHiddenElement ).change( );

        // update stored value
        _default = cj( contactHiddenElement ).val( );
        }

    }, 500);
    // end of code to add onchange event for hidden element
});
</script>
{/literal}


