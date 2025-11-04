{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}


  <div id="report-tab-order-by-elements" role="tabpanel" class="civireport-criteria">
    <table id="optionField">
      <tr>
        <th>&nbsp;</th>
        <th> {ts}Column{/ts}</th>
        <th> {ts}Order{/ts}</th>
        <th> {ts}Section Header / Group By{/ts}</th>
        <th> {ts}Page Break{/ts}</th>
      </tr>

      {section name=rowLoop start=1 loop=6}
        {assign var=index value=$smarty.section.rowLoop.index}
        <tr id="optionField_{$index}" class="form-item {cycle values="odd-row,even-row"}">
          <td>
            {if $index GT 1}
              <a onclick="hideRow({$index}); return false;" name="orderBy_{$index}" href="#" class="form-link">{icon icon="fa-trash"}{ts}remove sort by column{/ts}{/icon}</a>
            {/if}
          </td>
          <td> {$form.order_bys.$index.column.html}</td>
          <td> {$form.order_bys.$index.order.html}</td>
          <td> {$form.order_bys.$index.section.html}</td>
          <td> {$form.order_bys.$index.pageBreak.html}</td>
        </tr>
      {/section}
    </table>
    <div id="optionFieldLink" class="add-remove-link">
      <a onclick="showHideRow(); return false;" name="optionFieldLink" href="#" class="form-link"><i class="crm-i fa-plus action-icon" role="img" aria-hidden="true"></i> {ts}another column{/ts}</a>
    </div>
    <script type="text/javascript">
      var showRows   = new Array({$showBlocks});
      var hideBlocks = new Array({$hideBlocks});
      var rowcounter = 0;
      {literal}
      if (navigator.appName == "Microsoft Internet Explorer") {
        for ( var count = 0; count < hideBlocks.length; count++ ) {
          var r = document.getElementById(hideBlocks[count]);
          r.style.display = 'none';
        }
      }

      // hide and display the appropriate blocks as directed by the php code
      on_load_init_blocks( showRows, hideBlocks, '');

      cj('input[id^="order_by_section_"]').click(disPageBreak).each(disPageBreak);

      function disPageBreak() {
        if (!cj(this).prop('checked')) {
          cj(this).parent('td').next('td').children('input[id^="order_by_pagebreak_"]').prop({checked: false, disabled: true});
        }
        else {
          cj(this).parent('td').next('td').children('input[id^="order_by_pagebreak_"]').prop({disabled: false});
        }
      }

      function hideRow(i) {
        showHideRow(i);
        // clear values on hidden field, so they're not saved
        cj('select#order_bys_'+ i +'_column').val('-');
        cj('select#order_bys_'+ i +'_order').val('ASC');
        cj('input#order_by_section_'+ i).prop('checked', false);
        cj('input#order_by_pagebreak_'+ i).prop('checked', false);
      }

      {/literal}
    </script>
  </div>
