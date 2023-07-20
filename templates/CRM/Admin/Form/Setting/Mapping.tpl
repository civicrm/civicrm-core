{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
    {ts}CiviCRM includes plugins for several mapping and geocoding web services. When your users save a contact or event location address, a geocoding service will convert the address into geographical coordinates, which are required for mapping. Mapping services allow your users to display addresses on a map.{/ts} {help id='map-intro-id'}
</div>
<div class="crm-block crm-form-block crm-map-form-block">
    <table class="form-layout-compressed">
         <tr class="crm-map-form-block-mapProvider">
             <td>{$form.mapProvider.label}</td>
             <td>{$form.mapProvider.html}<br />
             <span class="description">{ts}Choose the mapping provider that has the best coverage for the majority of your contact addresses.{/ts}</span></td>
         </tr>
         <tr class="crm-map-form-block-mapAPIKey">
             <td>{$form.mapAPIKey.label}</td>
             <td>{$form.mapAPIKey.html|crmAddClass:huge}<br />
             <span class="description">{ts}Enter your API Key or Application ID. An API Key is required for the Google Maps API. Refer to developers.google.com for the latest information.{/ts}</span></td>
         </tr>
         <tr class="crm-map-form-block-geoProvider">
             <td>{$form.geoProvider.label}</td>
             <td>{$form.geoProvider.html}<br />
             <span class="description">{ts}This can be the same or different from the mapping provider selected.{/ts}</span></td>
         </tr>
         <tr class="crm-map-form-block-geoAPIKey">
             <td>{$form.geoAPIKey.label}</td>
             <td>{$form.geoAPIKey.html|crmAddClass:huge}<br />
             <span class="description">{ts}Enter the API key or Application ID associated with your geocoding provider.{/ts}</span></td>
         </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">
CRM.$(function($) {
  var $form = $('form.{/literal}{$form.formClass}{literal}');
  function showHideMapAPIkey() {
    var mapProvider = $(this).val();
    if ( !mapProvider || ( mapProvider === 'OpenStreetMaps' ) ) {
      $('tr.crm-map-form-block-mapAPIKey', $form).hide( );
    } else {
      $('tr.crm-map-form-block-mapAPIKey', $form).show( );
    }
  }
  $('#mapProvider').each(showHideMapAPIkey).change(showHideMapAPIkey);
});
</script>
{/literal}
