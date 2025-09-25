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
          {$form.street_address.label nofilter}<br />
          {$form.street_address.html|crmAddClass:big nofilter}
          {if $parseStreetAddress}
            <div>
              <a href="#" title="{ts escape='htmlattribute'}Use Address Elements{/ts}" rel="addressElements" class="address-elements-toggle">{ts}Use Address Elements{/ts}</a>
            </div>
          {/if}
        </div>
        {if $parseStreetAddress}
        <div id="addressElements" class="crm-field-wrapper" style="display: none;">
          <table class="crm-block crm-form-block advanced-search-address-elements">
            <tr><td>{$form.street_number.label nofilter}<br />{$form.street_number.html nofilter}<br /><span class="description nowrap">{ts}or ODD / EVEN{/ts}</td>
              <td>{$form.street_name.label nofilter}<br />{$form.street_name.html nofilter}</td>
              <td>{$form.street_unit.label nofilter}<br />{$form.street_unit.html|crmAddClass:four nofilter}</td>
            </tr>
            <tr>
              <td colspan="3"><a href="#" title="{ts escape='htmlattribute'}Use Complete Address{/ts}" rel="streetAddress" class="address-elements-toggle">{ts}Use Street Address{/ts}</a></td>
            </tr>
          </table>
        </div>
        {/if}
        <div class="crm-field-wrapper">
          {$form.supplemental_address_1.label nofilter}<br />
          {$form.supplemental_address_1.html nofilter}
        </div>
        <div class="crm-field-wrapper">
          {$form.supplemental_address_2.label nofilter}<br />
          {$form.supplemental_address_2.html nofilter}
        </div>
        <div class="crm-field-wrapper">
          {$form.supplemental_address_3.label nofilter}<br />
          {$form.supplemental_address_3.html nofilter}
        </div>
        <div class="crm-field-wrapper">
          {$form.city.label nofilter}<br />
          {$form.city.html nofilter}
        </div>
        <div class="crm-field-wrapper">
          {$form.country.label nofilter}<br />
          {$form.country.html nofilter}
        </div>
        <div class="crm-field-wrapper">
          {$form.state_province.label nofilter}<br />
          {$form.state_province.html nofilter}
        </div>
        <div class="crm-field-wrapper">
          {$form.county.label nofilter}<br />
          {$form.county.html nofilter}
        </div>
        <div class="crm-field-wrapper">
          {$form.world_region.label nofilter}<br />
          {$form.world_region.html nofilter}
        </div>
      </td>

      <td>
        <div class="crm-field-wrapper">
          <div>{$form.location_type.label nofilter} {help id="location_type"}</div>
          {$form.location_type.html nofilter}
        </div>
        {if !empty($form.address_name.html)}
          <div class="crm-field-wrapper">
            {$form.address_name.label nofilter}<br />
            {$form.address_name.html nofilter}
          </div>
        {/if}
        {if !empty($form.postal_code.html)}
          <div class="crm-field-wrapper">
            {$form.postal_code.label nofilter}
            <input type="checkbox" id="postal-code-range-toggle" value="1"/>
            <label for="postal-code-range-toggle">{ts}Range{/ts}</label><br />
            <div class="postal_code-wrapper">
              {$form.postal_code.html nofilter}
            </div>
            <div class="postal_code_range-wrapper" style="display: none;">
              {$form.postal_code_low.html nofilter}&nbsp;-&nbsp;{$form.postal_code_high.html nofilter}
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
            {$form.prox_distance.label nofilter}<br />
            {$form.prox_distance.html nofilter}&nbsp;{$form.prox_distance_unit.html nofilter}
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
