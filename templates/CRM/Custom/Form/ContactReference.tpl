{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Js needed to initialize custom field of type ContactReference *}
{if empty($form.$element_name.frozen)}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $field = $("{/literal}#{$element_name|replace:']':''|replace:'[':'_'}{literal}");

    $field.crmSelect2({
      placeholder: {/literal}'{ts escape="js"}- select contact -{/ts}'{literal},
      minimumInputLength: 1,
      ajax: {
        url: {/literal}"{$customUrls.$element_name}"{literal},
        data: function(term) {
          return {term: term};
        },
        results: function(response) {
          return {results: response};
        }
      },
      initSelection: function($el, callback) {
        callback($el.data('entity-value'));
      }
    });
});
</script>
{/literal}
{/if}
