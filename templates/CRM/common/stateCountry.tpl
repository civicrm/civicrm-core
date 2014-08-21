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
{if $config->stateCountryMap}
<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}');
    function chainSelect(e) {
      var info = $(this).data('chainSelect');
      var val = info.target.val();
      var multiple = info.target.attr('multiple');
      var placeholder = $(this).val() ? "{/literal}{ts escape='js'}Loading{/ts}{literal}..." : info.placeholder;
      if (multiple) {
        info.target.html('').prop('disabled', true).crmSelect2({placeholder: placeholder});
      }
      else {
        info.target.html('<option value="">' + placeholder + '</option>').prop('disabled', true).crmSelect2();
      }
      if ($(this).val()) {
        $.getJSON(info.callback, {_value: $(this).val()}, function(data) {
          var options = '';
          function buildOptions(data) {
            $.each(data, function() {
              if (this.children) {
                options += '<optgroup label="' + this.name + '">';
                buildOptions(this.children);
                options += '</optgroup>';
              }
              else if (this.value || !multiple) {
                options += '<option value="' + this.value + '">' + this.name + '</option>';
              }
              else {
                info.target.crmSelect2({placeholder: this.name});
              }
            });
          }
          buildOptions(data);
          info.target.html(options).val(val).prop('disabled', false).trigger('change');
        });
      }
      else {
        info.target.trigger('change');
      }
    }
    function initField(selector) {
      return $(selector, $form).css('min-width', '20em').crmSelect2();
    }
    {/literal}
    {foreach from=$config->stateCountryMap item=stateCountryMap}
      {if $stateCountryMap.state_province && $stateCountryMap.county}
        $('select[name="{$stateCountryMap.state_province}"], select#{$stateCountryMap.state_province}', $form).data('chainSelect', {ldelim}
          callback: CRM.url('civicrm/ajax/jqCounty'),
          target: initField('select[name="{$stateCountryMap.county}"], #{$stateCountryMap.county}'),
          placeholder: "{ts escape='js'}(choose state first){/ts}"
        {rdelim}).on('change',  chainSelect);
      {/if}
      {if $stateCountryMap.country && $stateCountryMap.state_province}
        initField('select[name="{$stateCountryMap.country}"], select#{$stateCountryMap.country}').data('chainSelect', {ldelim}
          callback: CRM.url('civicrm/ajax/jqState'),
          target: initField('select[name="{$stateCountryMap.state_province}"], #{$stateCountryMap.state_province}'),
          placeholder: "{ts escape='js'}(choose country first){/ts}"
        {rdelim}).on('change', chainSelect).change();
      {/if}
    {/foreach}
    {literal}
  });
  {/literal}
</script>
{/if}
