{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<fieldset><legend>{if $useForMember}{ts}Membership Options{/ts}{else}{ts}Price Field Options{/ts}{/if}</legend>
    <div class="description">
        {if $useForMember}
            {capture assign='helpTitle'}{ts}Price Options{/ts}{/capture}
            {ts}Fill in a row for each membership type you want to offer as an option (click 'another choice' for each additional choice). Click the help icon for more info on membership price sets.{/ts}
            {help id="member-price-options" title=$helpTitle}
        {else}
            {ts}Enter up to fifteen (15) multiple choice options in this table (click 'another choice' for each additional choice). If you need more than ten options, you can create an unlimited number of additional choices using the Edit Price Options link after saving this new field. Enter a description of the option in the 'Label' column, and the associated price in the 'Amount' column. Click the 'Default' radio button to the left of an option if you want that to be selected by default.{/ts}
        {/if}
    </div>
  {strip}
  <table id='optionField'>
  <tr>
      <th>&nbsp;</th>
      <th>{ts}Default{/ts}</th>
  {if $useForMember}
      <th>
        {capture assign='colTitle'}{ts}Membership Type{/ts}{/capture}{$colTitle}
        {help id="membership-type" title=$colTitle}
      </th>
      <th>{ts}Number of Terms{/ts}</th>
  {/if}
      <th>{ts}Label{/ts}</th>
      <th>
        {capture assign='colTitle'}{ts}Amount{/ts}{/capture}{$colTitle}
        {if $useForEvent}{help id="price" title=$colTitle}{/if}
      </th>
      <th>{ts}Financial Type{/ts}</th>
    {if $useForEvent}
      <th>
        {capture assign='colTitle'}{ts}Participant Count{/ts}{/capture}{$colTitle}
        {help id="count" title=$colTitle}
      </th>
      <th>
        {capture assign='colTitle'}{ts}Max Participant{/ts}{/capture}{$colTitle}
        {help id="max_value" title=$colTitle}
      </th>
  {/if}
        <th>{ts}Order{/ts}</th>
        <th>
          {capture assign='colTitle'}{ts}Visibility{/ts}{/capture}{$colTitle}
          {help id="visibility-options" title=$colTitle}
        </th>
        <th>{ts}Active?{/ts}</th>
    </tr>

  {section name=rowLoop start=1 loop=16}
  {assign var=index value=$smarty.section.rowLoop.index}
  <tr id="optionField_{$index}" class="form-item {cycle values="odd-row,even-row"}">
        <td>
        {if $index GT 1}
            <a onclick="showHideRow({$index}); return false;" name="optionField_{$index}" href="#" class="form-link"><i class="crm-i fa-trash" title="{ts escape='htmlattribute'}hide field or section{/ts}" role="img" aria-hidden="true"></i></a>
        {/if}
        </td>
      <td>
    <div id="radio{$index}" style="display:none">
         {$form.default_option[$index].html}
    </div>
    <div id="checkbox{$index}" style="display:none">
         {$form.default_checkbox_option.$index.html}
    </div>
      </td>
      {if $useForMember}
          <td>{$form.membership_type_id.$index.html}</td>
          <td>{$form.membership_num_terms.$index.html}</td>
      {/if}
      <td> {$form.option_label.$index.html}</td>

      <td> {$form.option_amount.$index.html}</td>
      <td>{$form.option_financial_type_id.$index.html}</td>
      {if $useForEvent}
          <td>{$form.option_count.$index.html}</td>
          <td>{$form.option_max_value.$index.html}</td>
      {/if}
      <td> {$form.option_weight.$index.html}</td>
      <td> {$form.option_visibility_id.$index.html}</td>
      <td> {$form.option_status.$index.html}</td>
  </tr>
    {/section}
    </table>
  <div id="optionFieldLink" class="add-remove-link">
        <a onclick="showHideRow(); return false;" name="optionFieldLink" href="#" class="form-link"><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}add another choice{/ts}</a>
    </div>
  <div id="additionalOption" class="description">
    {ts}If you need additional options - you can add them after you Save your current entries.{/ts}
  </div>
    {/strip}

</fieldset>
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

    cj('#optionField input').blur( function(){
      var currentId = cj(this).attr('id');
      var arrayID = currentId.split('_');
      if ((arrayID[1] == 'label' || arrayID[1] == 'amount') && arrayID[2] > 1) {
        var value = cj("#"+currentId).val();
  if (value.length != 0  && cj("#option_financial_type_id_"+arrayID[2]).val() =='') {
    var currentFtid = "#option_financial_type_id_"+arrayID[2];
    var previousFtid = "#option_financial_type_id_"+ (arrayID[2]-1);
    var financial_type = cj(previousFtid).val();
    cj(currentFtid).val(financial_type);
  }
  if (cj("#option_label_"+arrayID[2]).val().length == 0 && cj("#option_amount_"+arrayID[2]).val().length == 0) {
          cj("#option_financial_type_id_"+arrayID[2]).val('');
  }
      }

    });

    {/literal}
    {* hide and display the appropriate blocks as directed by the php code *}
    on_load_init_blocks( showRows, hideBlocks, '' );
</script>
