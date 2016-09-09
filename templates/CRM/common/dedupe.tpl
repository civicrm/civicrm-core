{* common dupe contacts processing *}
<div id='processDupes' style="display:none;"></div>
{literal}
<script type='text/javascript'>

cj( '#processDupes' ).hide( );

function processDupes(cid, oid, oper, context, reloadURL) {
        //currently we are doing in a single way.
        //later we might want two way operations.

        if ( !cid || !oid || !oper ) return;

  var title = {/literal}'{ts escape="js"}Mark as Dedupe Exception{/ts}'{literal};
  var msg = {/literal}'{ts escape="js"}Are you sure you want to mark this pair of contacts as NOT duplicates?{/ts}'{literal};
        if ( oper == 'nondupe-dupe' ) {
    var title = {/literal}'{ts escape="js"}Remove Dedupe Exception{/ts}'{literal};
          var msg = {/literal}'{ts escape="js"}Are you sure you want to remove this dedupe exception.{/ts}'{literal};
        }

  cj("#processDupes").show( );
  cj("#processDupes").dialog({
    title: title,
    modal: true,

    open:function() {
       cj( '#processDupes' ).show( ).html( msg );
    },

    buttons: {
      "Cancel": function() {
        cj(this).dialog("close");
      },
      "OK": function() {
              saveProcessDupes( cid, oid, oper, context );
              cj(this).dialog( 'close' );
        if ( context == 'merge-contact' && reloadURL ) {
                                     // redirect after a small delay
                                     setTimeout("window.location.href = '" + reloadURL + "'", 500);
        }
        else {
          //CRM-15113 this has the effect of causing the alert to display. Also, as they are already 'actioned' Civi sensibly returns the browser to the
          //search screen
          setTimeout(function(){
            window.location.reload();
          }, 500);
        }
      }
    }
  });
}


function saveProcessDupes( cid, oid, oper, context ) {
    //currently we are doing in a single way.
    //later we might want two way operations.

    if ( !cid || !oid || !oper ) return;

    var statusMsg = {/literal}'{ts escape="js"}Marked as non duplicates.{/ts}'{literal};
    if ( oper == 'nondupe-dupe' ) {
       var statusMsg = {/literal}'{ts escape="js"}Marked as duplicates.{/ts}'{literal};
    }

    var url = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=processDupes' h=0 }"{literal};
    //post the data to process dupes.
    cj.post( url,
            {cid: cid, oid: oid, op: oper},
             function( result ) {
     if ( result.status == oper ) {

        if ( oper == 'dupe-nondupe' &&
             context == 'dupe-listing' ) {
              oTable.fnDraw();
        } else if ( oper == 'nondupe-dupe' ) {
              cj( "#dupeRow_" + cid + '_' + oid ).hide( );
        }
                  }
       },
       'json' );
}
</script>
{/literal}
