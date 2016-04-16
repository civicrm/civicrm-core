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
<div id="location" class="form-item">
  <table class="form-layout">
    <tr>
      <td>
        <div id="streetAddress" class="crm-field-wrapper">
          {$form.street_address.label}<br />
          {$form.street_address.html|crmAddClass:big}
          {if $parseStreetAddress}
            <div>
              <a href="#" title="{ts}Use Address Elements{/ts}" rel="addressElements" class="address-elements-toggle">{ts}Use Address Elements{/ts}</a>
            </div>
          {/if}
        </div>
        {if $parseStreetAddress}
        <div id="addressElements" class="crm-field-wrapper" style="display: none;">
          <table class="crm-block crm-form-block advanced-search-address-elements">
            <tr><td>{$form.street_number.label}<br />{$form.street_number.html}<br /><span class="description nowrap">{ts}or ODD / EVEN{/ts}</td>
              <td>{$form.street_name.label}<br />{$form.street_name.html}</td>
              <td>{$form.street_unit.label}<br />{$form.street_unit.html|crmAddClass:four}</td>
            </tr>
            <tr>
              <td colspan="3"><a href="#" title="{ts}Use Complete Address{/ts}" rel="streetAddress" class="address-elements-toggle">{ts}Use Street Address{/ts}</a></td>
            </tr>
          </table>
        </div>
        {/if}
        <div class="crm-field-wrapper">
          {$form.city.label}<br />
          {$form.city.html}
        </div>
        <div class="crm-field-wrapper">
          {$form.country.label}<br />
          {$form.country.html}
        </div>
        <div class="crm-field-wrapper">
          {$form.state_province.label}<br />
          {$form.state_province.html}
        </div>
        <div class="crm-field-wrapper">
          {$form.county.label}<br />
          {$form.county.html}
        </div>
        <div class="crm-field-wrapper">
          {$form.world_region.label}<br />
          {$form.world_region.html}
        </div>
      </td>

      <td>
        <div class="crm-field-wrapper">
          <div>{$form.location_type.label} {help id="location_type" title=$form.location_type.label}</div>
          {$form.location_type.html}
        </div>
        {if $form.address_name.html}
          <div class="crm-field-wrapper">
            {$form.address_name.label}<br />
            {$form.address_name.html}
          </div>
        {/if}
        {if $form.postal_code.html}
          <div class="crm-field-wrapper">
            {$form.postal_code.label}
            <input type="checkbox" id="postal-code-range-toggle" value="1"/>
            <label for="postal-code-range-toggle">{ts}Range{/ts}</label><br />
            <div class="postal_code-wrapper">
              {$form.postal_code.html}
            </div>
            <div class="postal_code_range-wrapper" style="display: none;">
              {$form.postal_code_low.html}&nbsp;-&nbsp;{$form.postal_code_high.html}
            </div>
          </div>
          <script type="text/javascript">
            {literal}
            CRM.$(function($) {
              $('#postal-code-range-toggle').change(function() {
                if ($(this).is(':checked')) {
                  $('.postal_code_range-wrapper').show();
                  $('.postal_code-wrapper').hide().find('input').val('');
                } else {
                  $('.postal_code-wrapper').show();
                  $('.postal_code_range-wrapper').hide().find('input').val('');
                }
              });
              if ($('#postal_code_low').val() || $('#postal_code_high').val()) {
                $('#postal-code-range-toggle').prop('checked', true).change();
              }
            });
            {/literal}
          </script>
        {/if}
        {if $form.prox_distance.html}
          <div class="crm-field-wrapper">
            {$form.prox_distance.label}<br />
            {$form.prox_distance.html}&nbsp;{$form.prox_distance_unit.html}
          </div>
        {/if}
      </td>
    </tr>

    {if $addressGroupTree}
      <tr>
        <td colspan="2">
          {include file="CRM/Custom/Form/Search.tpl" groupTree=$addressGroupTree showHideLinks=false}
        </td>
      </tr>
    {/if}
  </table>
</div>

{if $parseStreetAddress}
  {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        function processAddressFields(name) {
          $('#' + name).show();
          if (name == 'addressElements') {
            $('#streetAddress').hide().find('input').val('');
          } else {
            $('#addressElements').hide().find('input').val('');
          }

        }
        $("a.address-elements-toggle").click(function(e) {
          e.preventDefault();
          processAddressFields(this.rel);
        });
        if ($('#street_name').val() || $('#street_unit').val() || $('#street_number').val()) {
          processAddressFields('addressElements');
        }
      }
    );

    </script>
  {/literal}
{/if}


