{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Deprecation notice - this file is slated for remove and has not been used from
  core for some years - replaced by datepicker. eg at the php layer ....
  $this->add('datepicker', 'start_date', ts('Campaign Start Date'), [], FALSE, ['time' => FALSE]);
*}
{if $batchUpdate}
    {assign var='elementId'   value=$form.field.$elementIndex.$elementName.id}
    {assign var="tElement" value=$elementName|cat:"_time"}
    {assign var="timeElement" value="field_`$elementIndex`_`$elementName`_time"}
    {$form.field.$elementIndex.$elementName.html}
{elseif $elementIndex}
    {assign var='elementId'   value=$form.$elementName.$elementIndex.id}
    {assign var="timeElement" value=$elementName|cat:"_time.$elementIndex"}
    {$form.$elementName.$elementIndex.html}
{elseif $blockId and $blockSection}
    {assign var='elementId'   value=$form.$blockSection.$blockId.$elementName.id}
    {assign var="tElement" value="`$elementName`_time"}
    {$form.$blockSection.$blockId.$elementName.html}
    {assign var="timeElement" value="`$blockSection`_`$blockId`_`$elementName`_time"}
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

{* CRM-15804 - CiviEvent Date Picker broken in modal dialog *}
{assign var='displayDate' value=$elementId|cat:"_display"|cat:"_$string"|uniqid}

<input type="text" name="{$displayDate}" id="{$displayDate}" class="dateplugin" autocomplete="off"/>

{if $batchUpdate AND $timeElement AND $tElement}
    &nbsp;&nbsp;{$form.field.$elementIndex.$tElement.label}&nbsp;&nbsp;{$form.field.$elementIndex.$tElement.html|crmAddClass:six}
{elseif $timeElement AND !$tElement}
    {if $form.$timeElement.label}
      &nbsp;&nbsp;{$form.$timeElement.label}&nbsp;&nbsp;
    {/if}
    {$form.$timeElement.html|crmAddClass:six}
{/if}


 <a href="#" class="crm-hover-button crm-clear-link" title="{ts escape='htmlattribute'}Clear{/ts}"><i class="crm-i fa-times" aria-hidden="true"></i></a>

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
