(function($, _) {

  // result is an array, but in js, an array is also an object
  // Assign all the metadata properties to it, mirroring the results arrayObject in php
  function arrayObject(data) {
    var result = data.values || [];
    if (_.isArray(result)) {
      delete(data.values);
      _.assign(result, data);
    }
    return result;
  }

  CRM.api4 = function(entity, action, params, index) {
    return new Promise(function(resolve, reject) {
      if (typeof entity === 'string') {
        $.post(CRM.url('civicrm/ajax/api4/' + entity + '/' + action), {
            params: JSON.stringify(params),
            index: index
          })
          .done(function (data) {
            resolve(arrayObject(data));
          })
          .fail(function (data) {
            reject(data.responseJSON);
          });
      } else {
        $.post(CRM.url('civicrm/ajax/api4'), {
            calls: JSON.stringify(entity)
          })
          .done(function(data) {
            _.each(data, function(item, key) {
              data[key] = arrayObject(item);
            });
            resolve(data);
          })
          .fail(function (data) {
            reject(data.responseJSON);
          });
      }
    });
  };
})(CRM.$, CRM._);