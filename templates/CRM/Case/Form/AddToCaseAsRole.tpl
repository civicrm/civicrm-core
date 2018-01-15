<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    $('#role_type').prop('disabled', 'disabled');

    $('#assign_to').change(function () {
      var apiCalls = {
        getCaseData: ['Case', 'getsingle', {
          'sequential': 1,
          'id': $('#assign_to').val(),
          'api.CaseType.getsingle': {'id':'$value.case_type_id'}
        }],
        getRelationshipTypes: ['RelationshipType', 'get', {
          sequential: 1,
        }],
      };

      CRM.api3(apiCalls).done(function(results) {
        $('#role_type').children('option:not(:first)').remove();
        $('#role_type').prop('disabled', false);

        var caseRoles = results.getCaseData['api.CaseType.getsingle'].definition.caseRoles;
        var relationshipTypes = results.getRelationshipTypes.values;

        for (var i = 0; i < relationshipTypes.length; i++) {
          for (var j = 0; j < caseRoles.length; j++) {
            if (relationshipTypes[i].label_b_a === caseRoles[j].name) {
              $('#role_type').append($('<option>', {value: relationshipTypes[i].id, text: relationshipTypes[i].label_b_a}));
            }
          }
        }
      });
    });
  });
  {/literal}
</script>

<div><label for="assign_to">{$form.assign_to.label}:</label></div>
<div>{$form.assign_to.html}</div>

<div>{$form.role_type.label}</div>
<div>{$form.role_type.html}</div><br />

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
