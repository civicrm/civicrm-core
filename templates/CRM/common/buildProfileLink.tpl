{literal}
<script type="text/javascript">
    function buildLinks( element, profileId ) {
      if ( profileId >= 1 ) {
        var ufFieldUrl = {/literal}"{crmURL p='civicrm/admin/uf/group/field' q='reset=1&action=browse&gid=' h=0}"{literal};
        ufFieldUrl = ufFieldUrl + profileId;
        var editTitle = {/literal}"{ts escape='js'}edit profile{/ts}"{literal};
        element.parent().find('span.profile-links').html('<a href="' + ufFieldUrl +'" target="_blank" title="'+ editTitle+'">'+ editTitle+'</a>');
      } else {
        element.parent().find('span.profile-links').html('');
      }
    }
</script>
{/literal}
