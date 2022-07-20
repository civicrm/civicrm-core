{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{include file="CRM/Import/Form/MapTableCommon.tpl"}

{if $wizard.currentStepName != 'Preview'}
  {* // Set default location type *}
  {literal}
    <script type="text/javascript">
    CRM.$(function($) {
      var defaultLocationType = "{/literal}{$defaultLocationType}{literal}";
      if (defaultLocationType.length) {
        $('#map-field').on('change', 'select[id^="mapper"][id$="_0"]', function() {
          var select = $(this).next();
          $('option', select).each(function() {
            if ($(this).attr('value') == defaultLocationType  && $(this).text() == {/literal}{
              $defaultLocationTypeLabel|@json_encode}{literal}) {
              select.val(defaultLocationType);
            }
          });
        });
      }
    });
    </script>
  {/literal}
{/if}
