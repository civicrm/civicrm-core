<script type="text/javascript">
{literal}
  function hideStatusAttachment( divName ) {
    cj( divName ).hide( );
  }

  function showDeleteAttachment( fileName, postURLData, fileID, divName, divFile ) {
    var confirmMsg = '<div>{/literal}{ts escape="js"}Are you sure you want to delete attachment: {/ts}{literal}' + fileName + '&nbsp; <a href="#" onclick="deleteAttachment( \'' + postURLData + '\',' + fileID + ',\'' + divName + '\', \'' + divFile + '\' ); return false;" style="text-decoration: underline;">{/literal}</div><div>{ts escape='js'}Yes{/ts}{literal}</a>&nbsp;&nbsp;&nbsp;<a href="#" onclick="hideStatusAttachment( \'' + divName + '\' ); return false;" style="text-decoration: underline;">{/literal}{ts escape='js'}No{/ts}{literal}</a></div>';
    cj( divName ).show( ).html( confirmMsg );
  }

  function deleteAttachment( postURLData, fileID, divName, divFile ) {
    var postUrl = {/literal}"{crmURL p='civicrm/file/delete' h=0 }"{literal};
    cj.ajax({
      type: "POST",
      data:  postURLData,
      url: postUrl,
      success: function(html){
        var resourceBase   = {/literal}"{$config->resourceBase}"{literal};
        var successMsg = '{/literal}{ts escape="js"}The selected attachment has been deleted.{/ts}{literal} &nbsp;&nbsp;<a href="#" onclick="hideStatusAttachment( \'' + divName + '\'); return false;"><img title="{/literal}{ts escape='js'}close{/ts}{literal}" src="' +resourceBase+'i/close.png"/></a>';
        cj( divFile ).hide( );
        cj( divName ).show( ).html( successMsg );
      }
    });
  }
{/literal}
</script>
