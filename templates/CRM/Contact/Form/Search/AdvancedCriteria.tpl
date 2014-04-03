{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Advanced Search Criteria Fieldset *}
{literal}
<script type="text/javascript">
CRM.$(function($) {
  // Bind first click of accordion header to load crm-accordion-body with snippet
  // everything else is taken care of by crmAccordions()
  $('.crm-search_criteria_basic-accordion .crm-accordion-header').addClass('active');
  $('.crm-ajax-accordion').on('click', '.crm-accordion-header:not(.active)', function() {
    loadPanes($(this).attr('id'));
  });
  $('.crm-ajax-accordion:not(.collapsed) .crm-accordion-header').each(function() {
    loadPanes($(this).attr('id'));
  });
  $('.crm-ajax-accordion').on('click', '.crm-close-accordion', function() {
    var header = $(this).parent();
    header.next().html('');
    header.removeClass('active');
    header.parent('.crm-ajax-accordion:not(.collapsed)').crmAccordionToggle();
    // Reset results-display mode if it depends on this pane
    var mode = modes[$('#component_mode').val()] || null;
    if (mode && header.attr('id') == mode) {
      var oldMode = $('#component_mode :selected').text();
      $('#component_mode').val('1');
      {/literal}
      var msg = '{ts escape="js"}Displaying results as "%1" is not available without search criteria from the pane you just closed.{/ts}';
      msg = msg.replace('%1', oldMode);
      CRM.alert(msg, '{ts escape="js"}Display Results have been Reset{/ts}');
      {literal}
    }
    $(this).remove();
    return false;
  });
  // TODO: Why are the modes numeric? If they used the string there would be no need for this map
  var modes = {
    '2': 'CiviContribute',
    '3': 'CiviEvent',
    '4': 'activity',
    '5': 'CiviMember',
    '6': 'CiviCase',
    '8': 'CiviMail'
  };
  // Handle change of results mode
  $('#component_mode').change(function() {
    // Reset task dropdown
    $('#task').val('');
    var mode = modes[$('#component_mode').val()] || null;
    if (mode) {
      $('.crm-' + mode + '-accordion.collapsed').crmAccordionToggle();
      loadPanes(mode);
    }
    if ($('#component_mode').val() == '7') {
      $('#crm-display_relationship_type').show();
    }
    else {
      $('#display_relationship_type').val('');
      $('#crm-display_relationship_type').hide();
    }
  }).change();
  /**
  * Loads snippet based on id of crm-accordion-header
  */
  function loadPanes(id) {
    var url = "{/literal}{crmURL p='civicrm/contact/search/advanced' q="qfKey=`$qfKey`&searchPane=" h=0}{literal}" + id;
    var header = $('#' + id);
    var body = $('.crm-accordion-body.' + id);
    if (header.length > 0 && body.length > 0 && !body.html()) {
      body.html('<div class="crm-loading-element"><span class="loading-text">{/literal}{ts escape='js'}Loading{/ts}{literal}...</span></div>');
      header.append('{/literal}<a href="#" class="crm-close-accordion crm-hover-button css_right" title="{ts escape='js'}Remove from search criteria{/ts}"><span class="icon close-icon"></span></a>{literal}');
      header.addClass('active');
      CRM.loadPage(url, {target: body, block: false});
    }
  }
});
</script>
{/literal}

    {if $context EQ 'smog' || $context EQ 'amtg' || $savedSearch}
          <h3>
          {if $context EQ 'smog'}{ts}Find Contacts within this Group{/ts}
          {elseif $context EQ 'amtg'}{ts}Find Contacts to Add to this Group{/ts}
          {elseif $savedSearch}{ts 1=$savedSearch.name}%1 Smart Group Criteria{/ts} &nbsp; {help id='id-advanced-smart'}
          {/if}
          </h3>
        {/if}

{strip}
<div class="crm-accordion-wrapper crm-search_criteria_basic-accordion ">
  <div class="crm-accordion-header">
    {ts}Basic Criteria{/ts}
  </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
      {include file="CRM/Contact/Form/Search/Criteria/Basic.tpl"}
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

    {foreach from=$allPanes key=paneName item=paneValue}
      <div class="crm-accordion-wrapper crm-ajax-accordion crm-{$paneValue.id}-accordion {if $paneValue.open eq 'true' and $openedPanes.$paneName} {else}collapsed{/if}">
       <div class="crm-accordion-header" id="{$paneValue.id}">
         {$paneName}
       </div>
       <div class="crm-accordion-body {$paneValue.id}"></div>
       </div>
    {/foreach}
    <div class="spacer"></div>

    <table class="form-layout">
        <tr>
            <td>{$form.buttons.html}</td>
        </tr>
    </table>
{/strip}
