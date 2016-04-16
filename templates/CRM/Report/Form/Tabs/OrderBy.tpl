{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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


  <div id="report-tab-order-by-elements" class="civireport-criteria">
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
              <a onclick="hideRow({$index}); return false;" name="orderBy_{$index}" href="#" class="form-link"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}hide field or section{/ts}"/></a>
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
      <a onclick="showHideRow(); return false;" name="optionFieldLink" href="#" class="form-link"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}show field or section{/ts}"/>{ts}another column{/ts}</a>
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
        cj('select#order_by_column_'+ i).val('');
        cj('select#order_by_order_'+ i).val('ASC');
        cj('input#order_by_section_'+ i).prop('checked', false);
        cj('input#order_by_pagebreak_'+ i).prop('checked', false);
      }

      {/literal}
    </script>
  </div>
