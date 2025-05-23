{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Highlight the required field during import (included within a <script>)*}
{literal}
CRM.$(function($) {
  var highlightedFields = {/literal}{$highlightedFields}{literal};
  $.each(highlightedFields, function() {
    // This addition of color by css appears to have stopped working along the way although the append works.
    $('select[id^="mapper"][id$="_0"] option[value='+ this + ']').append(' *').css({"color":"#FF0000"});
  });
  {/literal}{if $highlightedRelFields}{literal}
  var highlightedRelFields = {/literal}{$highlightedRelFields}{literal};
  function highlight() {
    var select, fields = highlightedRelFields[$(this).val()];
    if (fields) {
      select = $(this).next();
      $.each(fields, function() {
        $('option[value='+ this + ']', select).append(' *').css({"color":"#FF0000"});
      });
    }
  }
  $('select[id^="mapper"][id$="_0"]').each(highlight).click(highlight);
  {/literal}{/if}{literal}
});
{/literal}
