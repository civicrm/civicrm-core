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
    {include file='CRM/Admin/Form/Setting/SettingForm.tpl'}
</div>
{literal}
<script type="text/javascript">
CRM.$(function($) {
  var $form = $('form.{/literal}{$form.formClass}{literal}');
  function showHideMapAPIkey() {
    var mapProvider = $(this).val();
    if ( !mapProvider || ( mapProvider === 'OpenStreetMaps' ) ) {
      $('tr.crm-setting-form-block-mapAPIKey', $form).hide( );
    } else {
      $('tr.crm-setting-form-block-mapAPIKey', $form).show( );
    }
  }
  $('#mapProvider').each(showHideMapAPIkey).change(showHideMapAPIkey);
});
</script>
{/literal}
