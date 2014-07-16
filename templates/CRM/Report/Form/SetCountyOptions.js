//  http://civicrm.org/licensing
// jslint indent: 2 */
//
// This script populates the county options based on the state
// you have chosen.
cj(function () {
  cj('#state_province_id_value').click(function() {
    // empty existing counties
    cj('#county_id_value').empty();
    var state_province_ids = cj('#state_province_id_value').val();
    if(state_province_ids) {
      for(var spid=0; spid < state_province_ids.length; spid++){ 
        cj.get(
          '/civicrm/ajax/jqCounty',
          { '_value': state_province_ids[spid]},
          function( data ) {
            data = cj.parseJSON(data);
            for (i in data) {
              // Skip the "select" option since this is a multi-select field
              if(data[i].value == "") continue;
              // Strip the two letter state from the name variable
              name = data[i].name.replace(/[A-ZA-Z]{2}: /, '');
              var option = cj('<option></option>').attr("value", data[i].value).text(name);
              cj('#county_id_value').append(option);
            }
          }
        );
      }
    }
  });
});
