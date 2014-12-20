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
{if $batchUpdate}
    {assign var='elementId'   value=$form.field.$elementIndex.$elementName.id}
    {assign var="tElement" value=$elementName|cat:"_time"}
    {assign var="timeElement" value=field_`$elementIndex`_`$elementName`_time}
    {$form.field.$elementIndex.$elementName.html}
{elseif $elementIndex}
    {assign var='elementId'   value=$form.$elementName.$elementIndex.id}
    {assign var="timeElement" value=$elementName|cat:"_time.$elementIndex"}
    {$form.$elementName.$elementIndex.html}
{elseif $blockId and $blockSection}
    {assign var='elementId'   value=$form.$blockSection.$blockId.$elementName.id}
    {assign var="tElement" value=`$elementName`_time}
    {$form.$blockSection.$blockId.$elementName.html}
    {assign var="timeElement" value=`$blockSection`_`$blockId`_`$elementName`_time}
    {if $tElement}
      &nbsp;&nbsp;{$form.$blockSection.$blockId.$tElement.label}
      &nbsp;&nbsp;{$form.$blockSection.$blockId.$tElement.html|crmAddClass:six}
    {/if}
{else}
    {if !$elementId}
      {assign var='elementId'   value=$form.$elementName.id}
    {/if}
    {assign var="timeElement" value=$elementName|cat:'_time'}
    {$form.$elementName.html}
{/if}

{assign var='displayDate' value=$elementId|cat:"_display"}

{if $action neq 1028}
    <input type="text" name="{$displayDate}" id="{$displayDate}" class="dateplugin" autocomplete="off"/>
{/if}

{if $batchUpdate AND $timeElement AND $tElement}
    &nbsp;&nbsp;{$form.field.$elementIndex.$tElement.label}&nbsp;&nbsp;{$form.field.$elementIndex.$tElement.html|crmAddClass:six}
{elseif $timeElement AND !$tElement}
    {if $form.$timeElement.label}
      &nbsp;&nbsp;{$form.$timeElement.label}&nbsp;&nbsp;
    {/if}
    {$form.$timeElement.html|crmAddClass:six}
{/if}

{if $action neq 1028}
    <a href="#" class="crm-hover-button crm-clear-link" title="{ts}Clear{/ts}"><span class="icon close-icon"></span></a>
{/if}

<script type="text/javascript">
    {literal}
    CRM.$(function($) {
      {/literal}
      // Workaround for possible duplicate ids in the dom - select by name instead of id and exclude already initialized widgets
      var $dateElement = $('input[name={$displayDate}].dateplugin:not(.hasDatepicker)');
      {literal}
      if (!$dateElement.length) {
        return;
      }
      {/literal}
      {if $timeElement}
        var $timeElement = $dateElement.siblings("#{$timeElement}");
        var time_format = $timeElement.attr('timeFormat');
          {literal}
            $timeElement.timeEntry({ show24Hours : time_format, spinnerImage: '' });
          {/literal}
      {else}
        var $timeElement = $();
      {/if}
      var currentYear = new Date().getFullYear(),
        $originalElement = $dateElement.siblings('#{$elementId}').hide(),
        date_format = $originalElement.attr('format'),
        altDateFormat = 'mm/dd/yy';
      {literal}

      if ( !( ( date_format == 'M yy' ) || ( date_format == 'yy' ) || ( date_format == 'yy-mm' ) ) ) {
          $dateElement.addClass( 'dpDate' );
      }

      var yearRange = (currentYear - parseInt($originalElement.attr('startOffset'))) +
        ':' + currentYear + parseInt($originalElement.attr('endOffset')),
        startRangeYr = currentYear - parseInt($originalElement.attr('startOffset')),
        endRangeYr = currentYear + parseInt($originalElement.attr('endOffset'));

      $dateElement.datepicker({
        closeAtTop: true,
        dateFormat: date_format,
        changeMonth: (date_format.indexOf('m') > -1),
        changeYear: (date_format.indexOf('y') > -1),
        altField: $originalElement,
        altFormat: altDateFormat,
        yearRange: yearRange,
        minDate: new Date(startRangeYr, 1 - 1, 1),
        maxDate: new Date(endRangeYr, 12 - 1, 31)
      });

      // format display date
      var displayDateValue = $.datepicker.formatDate(date_format, $.datepicker.parseDate(altDateFormat, $originalElement.val()));
      //support unsaved-changes warning: CRM-14353
      $dateElement.val(displayDateValue).data('crm-initial-value', displayDateValue);

      // Add clear button
      $($timeElement).add($originalElement).add($dateElement).on('blur change', function() {
        var vis = $dateElement.val() || $timeElement.val() ? '' : 'hidden';
        $dateElement.siblings('.crm-clear-link').css('visibility', vis);
      });
      $originalElement.change();
    });

    {/literal}
</script>

