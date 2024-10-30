{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
<script type="text/javascript" >
CRM.$(function($) {
    {/literal}
    {if $generateAjaxRequest}
        {foreach from=$ajaxRequestBlocks key="blockName" item="instances"}
            {foreach from=$instances key="instance" item="active"}
                buildAdditionalBlocks( '{$blockName}', '{$className}' );
            {/foreach}
        {/foreach}
    {/if}

    {if !empty($loadShowHideAddressFields)}
        {foreach from=$showHideAddressFields key="blockId" item="fieldName"}
           processAddressFields( '{$fieldName}', '{$blockId}', 0 );
        {/foreach}
    {/if}
    {literal}
});

function buildAdditionalBlocks( blockName, className ) {
    var element = blockName + '_Block_';

    //get blockcount of last element of relevant blockName
    var previousInstance = cj( '[id^="'+ element +'"]:last' ).attr('id').slice( element.length );
    var currentInstance  = parseInt( previousInstance ) + 1;

    //show primary option if block count = 2
    if ( currentInstance == 2) {
        cj("#" + blockName + '-Primary').show( );
        cj("#" + blockName + '-Primary-html').show( );
    }

    var dataUrl = {/literal}"{crmURL h=0 q='snippet=4'}"{literal} + '&block=' + blockName + '&count=' + currentInstance;

    if ( className == 'CRM_Event_Form_ManageEvent_Location' ) {
        dataUrl = ( currentInstance <= 2 ) ? dataUrl + '&subPage=Location' : '';
    }

    {/literal}
    {if $qfKey}
        dataUrl += "&qfKey={$qfKey}";
    {/if}
    {literal}

    if ( !dataUrl ) {
        return;
    }

    var fname = '#' + blockName + '_Block_'+ previousInstance;

    cj('#addMore' + blockName + previousInstance ).hide( );
    cj.ajax({
        url     : dataUrl,
        async   : false,
        success : function(html){
            cj(fname).after(html);
            cj(fname).nextAll().trigger('crmLoad');
        }
    });

    if ( blockName == 'Address' ) {
        checkLocation('address_' + currentInstance + '_location_type_id', true );
        /* FIX: for IE, To get the focus after adding new address block on first element */
        cj('#address_' + currentInstance + '_location_type_id').focus();
    }
}

//select single for is_primary, although we are moving away from this - e.g on Email.
function singleSelect( object ) {
    var element = object.split( '_', 3 );

    var block = (element['0'] == 'Address') ? 'Primary' : element['2'].slice('2');
    var execBlock  = '#' + element['0'] + '-' + block + '-html Input[id*="' + element['2'] + '"]';

    //element to check for checkbox
    var elementChecked =  cj( '#' + object ).prop('checked');
    if ( elementChecked ) {
        cj( execBlock ).each( function() {
            if ( cj(this).attr('id') != object ) {
                cj(this).prop('checked', false );
            }
        });
    } else {
        cj( '#' + object ).prop('checked', false );
    }

  //check if non of elements is set Primary / Allowed to Login.
  if( cj.inArray( element['2'].slice('2'), [ 'Primary', 'Login' ] ) != -1 ) {
    primary = false;
    cj( execBlock ).each( function( ) {
      if ( cj(this).prop('checked' ) ) {
        primary = true;
      }
    });
    if( ! primary ) {
      cj('#' + object).prop('checked', true );
    }
  }
}

function removeBlock( blockName, blockId ) {
    var element = cj("#addressBlock > div").size();
    if ( ( blockName == 'Address' ) && element == 1 ) {
      return clearFirstBlock(blockName , blockId);
    }

    if ( cj( "#"+ blockName + "_" + blockId + "_IsPrimary").prop('checked') ) {
         var primaryBlockId = 1;
        // consider next block as a primary,
        // when user delete first block
        if ( blockId >= 1 ) {
           var blockIds = getAddressBlock('next');
           for ( var i = 0; i <= blockIds.length; i++) {
               var curBlockId = blockIds[i];
             if ( curBlockId != blockId ) {
                 primaryBlockId = curBlockId;
                 break;
             }
            }
        }

        // finally sets the primary address
        cj( '#'+ blockName + '_' + primaryBlockId + '_IsPrimary').prop('checked', true);
    }

    //remove the spacer for address block only.
    if ( blockName == 'Address' && cj( "#"+ blockName + "_Block_" + blockId ).prev().attr('class') == 'spacer' ){
        cj( "#"+ blockName + "_Block_" + blockId ).prev().remove();
    }

    //unset block from html
    cj( "#"+ blockName + "_Block_" + blockId ).empty().remove();

    //show the link 'add address' to last element of Address Block
    if ( blockName == 'Address' ) {
        var lastAddressBlock = cj('div[id^=Address_Block_]').last().attr('id');
        var lastBlockId = lastAddressBlock.split( '_' );
        if ( lastBlockId[2] ) {
            cj( '#addMoreAddress' + lastBlockId[2] ).show();
        }
    }
}

function clearFirstBlock( blockName , blockId ) {
    var element =  blockName + '_Block_' + blockId;
    cj("#" + element +" input, " + "#" + element + " select").each(function () {
        cj(this).val('');
    });
    cj("#addressBlockId[open]").prop('open', false);
    cj("#addressBlockId .active").removeClass('active');
}

function getAddressBlock( position ) {
   var addressBlockIds = [];
   var i = 0;
   switch ( position ) {
        case 'last':
              var lastBlockInfo = cj("#addressBlockId > div").children(':last').attr('id').split( '_', 3);
              addressBlockIds[i] = lastBlockInfo['2'];
              break;
        case 'next':
              cj("#addressBlockId > div").children().each( function() {
                  if ( cj(this).attr('id') ) {
                     var blockInfo = cj(this).attr('id').split( '_', 3);
                     addressBlockIds[i] = blockInfo['2'];
                     i++;
                  }
              });
              break;
   }
   return addressBlockIds;
}
</script>
{/literal}
