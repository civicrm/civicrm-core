{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Js needed to initialize custom field of type ContactReference *}
{if empty($form.$element_name.frozen)}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    // dev/core#362 if in an onbehalf profile reformat the id
    var $field = $("{/literal}#{if $prefix}{$prefix}_{/if}{$element_name|replace:']':''|replace:'[':'_'}{literal}");

    $field.crmSelect2({
      placeholder: {/literal}'{ts escape="js"}- select contact -{/ts}'{literal},
      minimumInputLength: 1,
      multiple: !!$field.attr('multiple'),
      ajax: {
        url: {/literal}"{$customUrls.$element_name}"{literal},
        quietMillis: 300,
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
