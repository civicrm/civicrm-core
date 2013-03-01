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

<script type="text/javascript">
  {* Initialize CRM.url *}
  CRM.url('init', '{crmURL p="civicrm/example" q="placeholder" h=0 }');

{*/*
 * Here we define the CRM object,
 * A single global variable to hold everything that needs to be accessed from the global scope
 * Translated strings are stored in the CRM.strings object, and can be accessed via ts() in javascript
 * Very common strings are included here for convenience. Others should be added dynamically per-page.
 *
 * To extend this object from php:
 * CRM_Core_Resources::singleton()->addSetting(array('myNamespace' => array('foo' => 'bar')));
 * It can then be accessed client-side via CRM.myNamespace.foo
 */
 *}
  {literal}
  var CRM = CRM || {};
  CRM = cj.extend(true, {
    strings: {{/literal}
      '- select -': '{ts escape="js"}- select -{/ts}',
      Ok: '{ts escape="js"}Ok{/ts}',
      Cancel: '{ts escape="js"}Cancel{/ts}',
      Yes: '{ts escape="js"}Yes{/ts}',
      No: '{ts escape="js"}No{/ts}',
      Saved: '{ts escape="js"}Saved{/ts}',
      Error: '{ts escape="js"}Error{/ts}',
      Removed: '{ts escape="js"}Removed{/ts}'
    {literal}},
    config: {{/literal}
      urlIsPublic: {if $urlIsPublic}true{else}false{/if},
      userFramework: '{$config->userFramework}',
      resourceBase: '{$config->resourceBase}',
      search_autocomplete_count: {crmSetting name="search_autocomplete_count" group="Search Preferences"}
    {literal}},
  }, CRM);
  {/literal}
  {* Dynamically add server-side variables to the CRM object *}
  {crmRegion name='settings'}
  {/crmRegion}
</script>
