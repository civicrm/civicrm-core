/**
 * Function to change button text and disable one it is clicked
 * @deprecated
 * @param obj object - the button clicked
 * @param formID string - the id of the form being submitted
 * @param string procText - button text after user clicks it
 * @return bool
 */
/* Changes button label on submit, and disables button after submit for newer browsers.
 Puts up alert for older browsers. */
function submitOnce(obj, formId, procText) {
  CRM.$(obj).closest('form').attr('data-warn-changes', 'false');
  CRM.$('.crm-button input').attr('disabled', true);
  CRM.$('button.crm-button').attr('disabled', true);
  // Needed because calling .submit() below will always send the default button in the POST data. @see also CRM_Core_Controller::getButtonName().
  CRM.$('#_qf_button_override').val(obj.name);
  document.getElementById(formId).submit();
  return true;
}
