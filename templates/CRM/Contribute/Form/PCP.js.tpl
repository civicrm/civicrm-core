{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $("form.{/literal}{$form.formClass}{literal}");

    // FIXME: This could be much simpler as an entityRef field but pcp doesn't have a searchable api :(
    var pcpURL = CRM.url('civicrm/ajax/rest', 'className=CRM_Contact_Page_AJAX&fnName=getPCPList&json=1&context=contact&reset=1');
    $('input[name=pcp_made_through_id]', $form).crmSelect2({
      minimumInputLength: 1,
      ajax: {
        url: pcpURL,
        data: function(term, page) {
          return {term: term, page_num: page};
        },
        results: function(response) {
          return response;
        }
      },
      initSelection: function(el, callback) {
        callback({id: $(el).val(), text: $('[name=pcp_made_through]', $form).val()});
      }
    })
      // This is just a cheap trick to store the name when the form reloads
      .on('change', function() {
        var fieldNameVal = $(this).select2('data');
        if (!fieldNameVal) {
          fieldNameVal = '';
        }
        $('[name=pcp_made_through]', $form).val(fieldNameVal.text);
      });
  });
</script>
{/literal}
