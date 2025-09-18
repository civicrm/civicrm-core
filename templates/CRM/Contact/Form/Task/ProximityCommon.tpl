{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<fieldset>
    <legend>{ts}By Distance from a Location{/ts}</legend>
    <table class="form-layout-compressed">
       <tr><td class="label">{$form.prox_distance.label nofilter}</td><td>{$form.prox_distance.html|crmAddClass:four nofilter} {$form.prox_distance_unit.html nofilter}</td></tr>
       <tr><td class="label">{ts}FROM...{/ts}</td><td></td></tr>
       <tr><td class="label">{$form.prox_street_address.label nofilter}</td><td>{$form.prox_street_address.html nofilter}</td></tr>
       <tr><td class="label">{$form.prox_city.label nofilter}</td><td>{$form.prox_city.html nofilter}</td></tr>
       <tr><td class="label">{$form.prox_postal_code.label nofilter}</td><td>{$form.prox_postal_code.html nofilter}</td></tr>
       <tr><td class="label">{$form.prox_country_id.label nofilter}</td><td>{$form.prox_country_id.html nofilter}</td></tr>
       <tr><td class="label" style="white-space: nowrap;">{$form.prox_state_province_id.label nofilter}</td><td>{$form.prox_state_province_id.html nofilter}</td></tr>
    </table>
</fieldset>
