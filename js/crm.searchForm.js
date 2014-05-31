// http://civicrm.org/licensing

function countSelectedCheckboxes(fldPrefix, form) {
  fieldCount = 0;
  for (i = 0; i < form.elements.length; i++) {
    fpLen = fldPrefix.length;
    if (form.elements[i].type == 'checkbox' && form.elements[i].name.slice(0, fpLen) == fldPrefix && form.elements[i].checked == true) {
      fieldCount++;
    }
  }
  return fieldCount;
}

/**
 * Function to enable task action select
 */
function toggleTaskAction(status) {
  var radio_ts = document.getElementsByName('radio_ts');
  if (!radio_ts[1]) {
    radio_ts[0].checked = true;
  }
  if (radio_ts[0].checked || radio_ts[1].checked) {
    status = true;
  }

  var formElements = ['task', 'Go', 'Print'];
  for (var i = 0; i < formElements.length; i++) {
    var element = document.getElementById(formElements[i]);
    if (element) {
      if (status) {
        element.disabled = false;
      }
      else {
        element.disabled = true;
      }
    }
  }
}
