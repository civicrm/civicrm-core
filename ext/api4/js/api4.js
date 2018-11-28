(function($, _) {

  // result is an array, but in js, an array is also an object
  // Assign all the metadata properties to it, mirroring the results arrayObject in php
  function arrayObject(data) {
    var result = data.values || [];
    delete(data.values);
    _.assign(result, data);
    return result;
  }

  CRM.api4 = function(entity, action, params) {
    var deferred = $.Deferred();
    if (typeof entity === 'string') {
      $.post(CRM.url('civicrm/ajax/api4/' + entity + '/' + action), {
          params: JSON.stringify(params)
        })
        .done(function (data) {
          deferred.resolve(arrayObject(data));
        })
        .fail(function (data) {
          deferred.reject(data.responseJSON);
        });
    } else {
      $.post(CRM.url('civicrm/ajax/api4'), {
          calls: JSON.stringify(entity)
        })
        .done(function(data) {
          _.each(data, function(item, index) {
            data[index] = arrayObject(item);
          });
          deferred.resolve(data);
        })
        .fail(function (data) {
          deferred.reject(data.responseJSON);
        });
    }

    return deferred;
  };
})(CRM.$, CRM._);