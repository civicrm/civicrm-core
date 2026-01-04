<div id="sortnameselect">
  <label>{ts}Name{/ts} <span class="description">(<a href="#" id='searchbyindivflds'>{ts}search by individual name fields{/ts}</a>)</span></label><br />
  {$form.sort_name.html}
</div>
<div id="indivfldselect">
  <label>{ts}First/Last Name{/ts}<span class="description"> (<a href="#" id='searchbysortname'>{ts}search by full name{/ts}</a>)</span></label><br />
  {$form.first_name.html} {$form.last_name.html}
</div>

{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      function showIndivFldsSearch() {
        $('#sortnameselect').css('visibility', 'hidden').css('position','absolute');
        $('#indivfldselect').css('visibility', 'visible').css('position','static');
        $('#sort_name').val('');
        $('#first_name').removeClass('big').addClass('eight');
        $('#last_name').removeClass('big').addClass('eight');
        return false;
      }
      function showSortNameSearch() {
        $('#indivfldselect').css('visibility', 'hidden').css('position','absolute');
        $('#sortnameselect').css('visibility', 'visible').css('position','static');
        $('#first_name').val('');
        $('#last_name').val('');
        return false;
      }
      $('#searchbyindivflds').click(showIndivFldsSearch);
      $('#searchbysortname').click(showSortNameSearch);

      if ($('#first_name').val() || $('#last_name').val()) {
        showIndivFldsSearch();
      }
      else {
        showSortNameSearch();
      }
    });
  </script>
{/literal}
