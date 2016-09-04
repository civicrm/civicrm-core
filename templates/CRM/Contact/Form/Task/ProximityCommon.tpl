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
<fieldset>
    <legend>{ts}By Distance from a Location{/ts}</legend>
    <table class="form-layout-compressed">
       <tr><td class="label">{$form.prox_distance.label}</td><td>{$form.prox_distance.html|crmAddClass:four} {$form.prox_distance_unit.html}</td></tr>
       <tr><td class="label">FROM...</td><td></td></tr>
       <tr><td class="label">{$form.prox_street_address.label}</td><td>{$form.prox_street_address.html}</td></tr>
       <tr><td class="label">{$form.prox_city.label}</td><td>{$form.prox_city.html}</td></tr>
       <tr><td class="label">{$form.prox_postal_code.label}</td><td>{$form.prox_postal_code.html}</td></tr>
       <tr><td class="label">{$form.prox_country_id.label}</td><td>{$form.prox_country_id.html}</td></tr>
       <tr><td class="label" style="white-space: nowrap;">{$form.prox_state_province_id.label}</td><td>{$form.prox_state_province_id.html}</td></tr>
    </table>
</fieldset>
