{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
<div class="crm-block crm-form-block crm-search-setting-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
        <tr class="crm-search-setting-form-block-includeWildCardInName">
            <td class="label">{$form.includeWildCardInName.label}</td>
            <td>{$form.includeWildCardInName.html}<br />
                <span class="description">{ts}If enabled, wildcards are automatically added to the beginning AND end of the search term when users search for contacts by Name. EXAMPLE: Searching for 'ada' will return any contact whose name includes those letters - e.g. 'Adams, Janet', 'Nadal, Jorge', etc. If disabled, a wildcard is added to the end of the search term only. EXAMPLE: Searching for 'ada' will return any contact whose last name begins with those letters - e.g. 'Adams, Janet' but NOT 'Nadal, Jorge'. Disabling this feature will speed up search significantly for larger databases, but users must manually enter wildcards ('%' or '_') to the beginning of the search term if they want to find all records which contain those letters. EXAMPLE: '%ada' will return 'Nadal, Jorge'.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-search-setting-form-block-includeEmailInName">
            <td class="label">{$form.includeEmailInName.label}</td>
            <td>{$form.includeEmailInName.html}<br />
                <span class="description">{ts}If enabled, email addresses are automatically included when users search by Name. Disabling this feature will speed up search significantly for larger databases, but users will need to use the Email search fields (from Advanced Search, Search Builder, or Profiles) to find contacts by email address.{/ts}</span></td>
        </tr>
        <tr class="crm-search-setting-form-block-searchPrimaryDetailsOnly">
            <td class="label">{$form.searchPrimaryDetailsOnly.label}</td>
            <td>{$form.searchPrimaryDetailsOnly.html}<br />
                <span class="description">{ts}If enabled, only primary details (eg contact's primary email, phone, etc) will be included in Basic and Advanced Search results. Disabling this feature will allow users to match contacts using any email, phone etc detail.{/ts}</span>
            </td>
        </tr>
        <tr  class="crm-search-setting-form-block-includeNickNameInName">
            <td class="label">{$form.includeNickNameInName.label}</td>
            <td>{$form.includeNickNameInName.html}<br />
                <span class="description">{ts}If enabled, nicknames are automatically included when users search by Name.{/ts}</span></td>
        </tr>
        <tr class="crm-search-setting-form-block-includeAlphabeticalPager">
            <td class="label">{$form.includeAlphabeticalPager.label}</td>
            <td>{$form.includeAlphabeticalPager.html}<br />
                <span class="description">{ts}If disabled, the alphabetical pager will not be displayed on the search screens. This will improve response time on search results on large datasets.{/ts}</span></td>
        </tr>
        <tr class="crm-search-setting-form-block-includeOrderByClause">
            <td class="label">{$form.includeOrderByClause.label}</td>
            <td>{$form.includeOrderByClause.html}<br />
                <span class="description">{ts}If disabled, the search results will not be ordered. This will improve response time on search results on large datasets significantly.{/ts}</span></td>
        </tr>
        <tr class="crm-search-setting-form-block-defaultSearchProfileID">
            <td class="label">{$form.defaultSearchProfileID.label}</td>
            <td>{$form.defaultSearchProfileID.html}<br />
                <span class="description">{ts}If set, this will be the default profile used for contact search. This is experimental functionality.{/ts}</span></td>
        </tr>
        <tr class="crm-search-setting-form-block-smartGroupCacheTimeout">
            <td class="label">{$form.smartGroupCacheTimeout.label}</td>
            <td>{$form.smartGroupCacheTimeout.html}<br />
                <span class="description">{ts}The number of minutes to cache smart group contacts. We strongly recommend that this value be greater than zero, since a value of zero means no caching at all. If your contact data changes frequently, you should set this value to at least 5 minutes.{/ts}</span></td>
        </tr>
        <tr class="crm-search-setting-form-block-autocompleteContactSearch">
            <td class="label">{$form.contact_autocomplete_options.label}</td>
            <td>{$form.contact_autocomplete_options.html}<br/>
            <span class="description">{ts}Selected fields will be displayed in back-office autocomplete dropdown search results (Quick Search, etc.). Contact Name is always included.{/ts}</span></td>
        </tr>
        <tr class="crm-search-setting-form-block-autocompleteContactReference">
            <td class="label">{$form.contact_reference_options.label}</td>
            <td>{$form.contact_reference_options.html}<br/>
            <span class="description">{ts}Selected fields will be displayed in autocomplete dropdown search results for 'Contact Reference' custom fields. Contact Name is always included. NOTE: You must assign 'access contact reference fields' permission to the anonymous role if you want to use custom contact reference fields in profiles on public pages. For most situations, you should use the 'Limit List to Group' setting when configuring a contact reference field which will be used in public forms to prevent exposing your entire contact list.{/ts}</span></td>
        </tr>
        <tr class="crm-search-setting-form-block-search_autocomplete_count">
            <td class="label">{$form.search_autocomplete_count.label}</td>
            <td>{$form.search_autocomplete_count.html}<br />
            <span class="description">{ts}The maximum number of contacts to show at a time when typing in an autocomplete field.{/ts}</span></td>
        </tr>
        <tr class="crm-miscellaneous-form-block-enable_innodb_fts">
            <td class="label">{$form.enable_innodb_fts.label}</td>
            <td>{$form.enable_innodb_fts.html}<br />
                <p class="description">{$enable_innodb_fts_description}</p>
            </td>
        </tr>


       </table>
            <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

</div>
