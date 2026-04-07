{* common dupe contacts processing *}
{literal}
<script type='text/javascript'>
  function processDupes( cid, oid, oper, context, reloadURL ) {
    if ( !cid || !oid || !oper ) return;

    statusMsg = ( oper == 'nondupe-dupe' ) ?
      {/literal}'{ts escape="js"}Dedupe exception removed.{/ts}'{literal} :
      {/literal}'{ts escape="js"}Marked as non duplicates.{/ts}'{literal};

    var url = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=processDupes' h=0}"{literal};
    CRM.$.post( url, {cid: cid, oid: oid, op: oper}, function( result ) {
      if ( result.status == oper ) {
        CRM.alert('', statusMsg, 'success');
        if ( context == 'merge-contact' && reloadURL ) {
          window.location.href = reloadURL;
        }
        else {
          window.location.reload();
        }
      }
    }, 'json' ).fail(function() {
      CRM.alert('{/literal}{ts escape="js"}Unable to complete the request. The server returned an error or could not be reached.{/ts}{literal}', '{/literal}{ts escape="js"}Request Failed{/ts}{literal}', 'error');
    });
  }
</script>
{/literal}
