{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<tr>
<td class="label">{$form.option_type.label}</td>
<td class="html-adjust">{$form.option_type.html}<br />
    <span class="description">{ts}You can create new result options for this survey, or select an existing survey result set which you've already created for another survey.{/ts}</span>
</td>
</tr>

<tr id="option_group" {if empty($form.option_group_id)}class="hiddenElement"{/if}>
  <td class="label">{$form.option_group_id.label}</td>
  <td class="html-adjust">{$form.option_group_id.html}</td>
</tr>

<tr id="multiple">
<td colspan="2" class="html-adjust">
    <fieldset><legend>{ts}Result Options{/ts}</legend>
    <span class="description">
        {ts}Enter up to ten (10) multiple choice options in this table (click 'another choice' for each additional choice).You can use existing result set options by selecting survey result set.{/ts}
    </span>
    <br />
  {strip}
  <table id="optionField">
  <tr>
        <th>&nbsp;</th>
        <th> {ts}Default{/ts}</th>
        <th> {ts}Label{/ts}</th>
        <th> {ts}Value{/ts}</th>
  <th> {ts}Recontact Interval{/ts}</th>
        <th> {ts}Order{/ts}</th>
    </tr>

  {section name=rowLoop start=1 loop=12}
  {assign var=index value=$smarty.section.rowLoop.index}
  <tr id="optionField_{$index}" class="form-item {cycle values="odd-row,even-row"}">
        <td>
        {if $index GT 1}
            <a onclick="showHideRow({$index}); return false;" name="optionField_{$index}" href="#" class="form-link"><i class="crm-i fa-trash" title="{ts escape='htmlattribute'}hide field or section{/ts}" role="img" aria-hidden="true"></i></a>
        {/if}
        </td>
      <td>
    <div id="radio{$index}">
         {$form.default_option[$index].html}
    </div>

      </td>
      <td> {$form.option_label.$index.html}</td>
      <td> {$form.option_value.$index.html}</td>
      <td> {$form.option_interval.$index.html}</td>
      <td> {$form.option_weight.$index.html}</td>
  </tr>
    {/section}
    </table>
  <div id="optionFieldLink" class="add-remove-link">
        <a onclick="showHideRow(); return false;" name="optionFieldLink" href="#" class="form-link"><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}add another choice{/ts}</a>
    </div>
  <span id="additionalOption" class="description">
    {ts}If you need additional options - you can add them after you Save your current entries.{/ts}
  </span>
    {/strip}

</fieldset>
</td>
</tr>

<script type="text/javascript">
    var showRows   = [{$showBlocks}];
    var hideBlocks = [{$hideBlocks}];
    var rowcounter = 0;
    var surveyId   = {if $surveyId}{$surveyId}{else}''{/if};

    {literal}

    function showOptionSelect( ) {
        if ( document.getElementsByName("option_type")[0].checked ) {
            cj('#option_group').hide();
            cj('#default_option').val('');
        } else {
            cj('#option_group').show();
            loadOptionGroup( );
        }
    }

    function resetResultSet( ) {
        for( i=1; i<=11; i++ ) {
            cj('#option_label_'+ i).val('');
            cj('#option_value_'+ i).val('');
            cj('#option_weight_'+ i).val('');
            cj('#option_interval_'+ i).val('');
            if ( i > 2 ) {
                showHideRow(i);
            }
        }
    }

    function loadOptionGroup( ) {
        var data = new Object;

        resetResultSet( );
        if ( cj('#option_group_id').val() ) {
            data['option_group_id'] = cj('#option_group_id').val();
            data['survey_id'] = surveyId;
        } else {
            return false;
        }

       var dataUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Campaign_Page_AJAX&fnName=loadOptionGroupDetails'}"{literal}

      // build new options
      cj.post( dataUrl, data, function( opGroup ) {
         if ( opGroup.status == 'success' ) {
           var result = opGroup.result;
             var countRows = 1;
            for( key in result ) {
                cj('#option_label_'+ countRows).val( result[key].label);
                cj('#option_value_'+ countRows).val( result[key].value);
                cj('#option_weight_'+ countRows).val( result[key].weight);

                if ( surveyId && result[key].interval ) {
                    cj('#option_interval_'+ countRows).val( result[key].interval);
                }

                if ( result[key].is_default == 1 ) {
                    cj('#radio'+countRows+' input').prop('checked', true);
                }

                if ( countRows > 1 ) {
                    showHideRow( );
                }
                countRows +=1;
        }
        }
      }, "json" );
  }

    CRM.$(function($) {
        showOptionSelect( );
    });

    {/literal}
    on_load_init_blocks( showRows, hideBlocks, '' );
</script>

