{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
  cj(function($) {
    function chainSelect(e) {
      var info = $(this).data('chainSelect');
      var val = info.target.val();
      var multiple = info.target.attr('multiple');
      var placeholder = $(this).val() ? "{/literal}{ts escape='js'}Loading{/ts}{literal}..." : info.placeholder;
      !multiple && info.target.html('<option value="">' + placeholder + '</option>');
      if ($(this).val()) {
        $.getJSON(info.callback, {_value: $(this).val()}, function(data) {
          var options = '';
          $.each(data, function() {
            if (!multiple || this.value) {
              options += '<option value="' + this.value + '">' + this.name + '</option>';
            }
          });
          info.target.html(options).val(val).trigger('change');
        });
      } else {
        info.target.trigger('change');
      }
    }
    {/literal}
    {foreach from=$config->stateCountryMap item=stateCountryMap}
      {if $stateCountryMap.country && $stateCountryMap.state_province}
        $('select[name="{$stateCountryMap.country}"], #{$stateCountryMap.country}').data('chainSelect', {ldelim}
          callback: CRM.url('civicrm/ajax/jqState'),
          target: $('select[name="{$stateCountryMap.state_province}"], #{$stateCountryMap.state_province}'),
          placeholder: "{ts escape='js'}(choose country first){/ts}"
          {rdelim}).on('change', chainSelect);
      {/if}
      {if $stateCountryMap.state_province && $stateCountryMap.county}
        $('select[name="{$stateCountryMap.state_province}"], #{$stateCountryMap.state_province}').data('chainSelect', {ldelim}
          callback: CRM.url('civicrm/ajax/jqCounty'),
          target: $('select[name="{$stateCountryMap.county}"], #{$stateCountryMap.county}'),
          placeholder: "{ts escape='js'}(choose state first){/ts}"
        {rdelim}).on('change',  chainSelect);
      {/if}
    {/foreach}
    {literal}
  });
  {/literal}
</script>
{/if}
