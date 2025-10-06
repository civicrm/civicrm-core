{literal}
<script type="text/javascript">
    function buildLinks( element, profileId ) {
      if ( profileId >= 1 ) {
        let path = 'civicrm/admin/uf/group/field';
        // Sorta hackish solution to support both with and without the AdminUI extension
        path += '#/?gid=' + profileId;
        const ufFieldUrl = CRM.url(path, {reset: 1, action: 'browse', gid: profileId});
        var editTitle = {/literal}"{ts escape='js'}edit profile{/ts}"{literal};
        element.parent().find('span.profile-links').html('<a href="' + ufFieldUrl +'" target="_blank" title="'+ editTitle+'">'+ editTitle+'</a>');
      } else {
        element.parent().find('span.profile-links').html('');
      }
    }
</script>
{/literal}
