{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
<div id="location" class="form-item">
    <table class="form-layout">
  <tr>
        <td>
           {$form.location_type.label}<br />
           {$form.location_type.html}
           <div class="description" >
             {ts}Location search uses the PRIMARY location for each contact by default.{/ts}<br />
             {ts}To search by specific location types (e.g. Home, Work...), check one or more boxes above.{/ts}
           </div>
        </td>
        <td colspan="2">
          <div id="streetAddress">
            {$form.street_address.label}<br />
            {$form.street_address.html|crmAddClass:big}
{if $parseStreetAddress}
            <br /><a href="#" title="{ts}Use Address Elements{/ts}" onClick="processAddressFields( 'addressElements' , 1 );return false;">{ts}Use Address Elements{/ts}</a>
          </div>
          <div id="addressElements" class=hiddenElement>
            <table class="crm-block crm-form-block advanced-search-address-elements">
          <tr><td>{$form.street_number.label}<br />{$form.street_number.html}<br /><span class="description nowrap">{ts}or ODD / EVEN{/ts}</td>
              <td>{$form.street_name.label}<br />{$form.street_name.html}</td>
              <td>{$form.street_unit.label}<br />{$form.street_unit.html|crmAddClass:four}</td>
          </tr>
          <tr>
                <td colspan="3"><a href="#" title="{ts}Use Complete Address{/ts}" onClick="processAddressFields( 'streetAddress', 1 );return false;">{ts}Use Street Address{/ts}</a></td>
            </tr>
            </table>
          </div>
{/if}
            <br />
            {$form.city.label}<br />
            {$form.city.html}
    </td>
    </tr>

    <tr>
        <td>
        {if $form.postal_code.html}
    <table class="inner-table">
       <tr>
      <td>
           {$form.postal_code.label}<br />
                             {$form.postal_code.html}
      </td>
      <td>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <label>{ts}OR{/ts}</label>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      </td>
      <td><label>{ts}Postal Code{/ts}</label>
        {$form.postal_code_low.label|replace:'-':'<br />'}
                    &nbsp;&nbsp;{$form.postal_code_low.html|crmAddClass:six}
                                {$form.postal_code_high.label}
                    &nbsp;&nbsp;{$form.postal_code_high.html|crmAddClass:six}
      </td>
        </tr>
        <tr>
                            <td colspan="2">&nbsp;</td>
                            <td>{$form.prox_distance.label}<br />{$form.prox_distance.html}&nbsp;{$form.prox_distance_unit.html}</td>
                    </tr>
              <tr>
      <td colspan="2">{$form.address_name.label}<br />
        {$form.address_name.html|crmAddClass:medium}
      </td>
      <td>{$form.world_region.label}<br />
        {$form.world_region.html}&nbsp;
      </td>
        </tr>
        <tr>
      <td colspan="2">{$form.county.label}<br />
        {$form.county.html|crmAddClass:bigSelect}&nbsp;
      </td>
      <td>{$form.country.label}<br />
        {$form.country.html|crmAddClass:big}&nbsp;
      </td>
        </tr>
    </table>
        {/if}&nbsp;
        </td>
        <td>{$form.state_province.label}<br />
            {$form.state_province.html|crmAddClass:bigSelect}
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

{if $parseStreetAddress eq 1}
{literal}
<script type="text/javascript">
function processAddressFields( name, loadData ) {
    if ( name == 'addressElements' ) {
        if ( loadData ) {
      cj( '#street_address' ).val( '' );
      }

      cj('#addressElements').show();
      cj('#streetAddress').hide();
  } else {
        if ( loadData ) {
             cj( '#street_name'   ).val( '' );
             cj( '#street_unit'   ).val( '' );
             cj( '#street_number' ).val( '' );
        }

        cj('#streetAddress').show();
        cj('#addressElements').hide();
       }

}

cj(function( ) {
  if (  cj('#street_name').val( ).length > 0 ||
        cj('#street_unit').val( ).length > 0 ||
        cj('#street_number').val( ).length > 0 ) {
    processAddressFields( 'addressElements', 1 );
  }
}
);

</script>
{/literal}
{/if}


