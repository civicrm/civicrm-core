{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
              <a href="#" title="{ts escape='htmlattribute'}Use Address Elements{/ts}" rel="addressElements" class="address-elements-toggle">{ts}Use Address Elements{/ts}</a>
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
              <td colspan="3"><a href="#" title="{ts escape='htmlattribute'}Use Complete Address{/ts}" rel="streetAddress" class="address-elements-toggle">{ts}Use Street Address{/ts}</a></td>
            </tr>
          </table>
        </div>
        {/if}
        <div class="crm-field-wrapper">
          {$form.supplemental_address_1.label}<br />
          {$form.supplemental_address_1.html}
        </div>
        <div class="crm-field-wrapper">
          {$form.supplemental_address_2.label}<br />
          {$form.supplemental_address_2.html}
        </div>
        <div class="crm-field-wrapper">
          {$form.supplemental_address_3.label}<br />
          {$form.supplemental_address_3.html}
        </div>
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
        {if !empty($form.address_name.html)}
          <div class="crm-field-wrapper">
            {$form.address_name.label}<br />
            {$form.address_name.html}
          </div>
        {/if}
        {if !empty($form.postal_code.html)}
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
        {if !empty($form.prox_distance.html)}
          <div class="crm-field-wrapper">
            {$form.prox_distance.label}<br />
            {$form.prox_distance.html}&nbsp;{$form.prox_distance_unit.html}
          </div>
        {/if}
      </td>
    </tr>

    {if !empty($addressGroupTree)}
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
