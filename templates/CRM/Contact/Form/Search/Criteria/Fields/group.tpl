<div id='groupselect'>
  <label>{ts}Group(s){/ts}
    <span class="description">
      (<a href="#" id='searchbygrouptype'>{ts}search by group type{/ts}</a>)
    </span>
  </label>
  <br/>
  {$form.group.html}
</div>
<div id='grouptypeselect'>
  <label>
    {ts}Group Type(s){/ts}
    <span class="description">
      (<a href="#" id='searchbygroup'>{ts}search by group{/ts}</a>)
    </span>
  </label>
  <br/>
  {$form.group_type.html}
  {literal}
    <script type="text/javascript">
      CRM.$(function ($) {
        function showGroupSearch() {
          $('#grouptypeselect').hide();
          $('#groupselect').show();
          $('#group_type').select2('val', '');
          return false;
        }

        function showGroupTypeSearch() {
          $('#groupselect').hide();
          $('#grouptypeselect').show();
          $('#group').select2('val', '');
          return false;
        }

        $('#searchbygrouptype').click(showGroupTypeSearch);
        $('#searchbygroup').click(showGroupSearch);

        if ($('#group_type').val()) {
          showGroupTypeSearch();
        }
        else {
          showGroupSearch();
        }

      });
    </script>
  {/literal}
</div>
