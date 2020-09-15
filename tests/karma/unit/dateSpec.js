'use strict';

describe('CRM.utils.formatDate', function() {

  it('should format date input', function() {
    var value = CRM.utils.formatDate('2021-05-10', 'mm/dd/yy');
    expect(value).toBe("05/10/2021");
  });

  it("should format 12-hr time", function() {
    var value = CRM.utils.formatDate('2021-05-10 12:35:00', 'mm/dd/yy', 12);
    expect(value).toBe("05/10/2021 12:35 PM");

    value = CRM.utils.formatDate('2021-05-10 00:35:00', 'mm/dd/yy', 12);
    expect(value).toBe("05/10/2021 12:35 AM");
  });

  it("should format 24-hr time", function() {
    var value = CRM.utils.formatDate('2020-05-20 04:25:40', 'mm/dd/yy', 24);
    expect(value).toBe("05/20/2020 04:25");

    value = CRM.utils.formatDate('2020-05-20 14:25:40', 'mm/dd/yy', 24);
    expect(value).toBe("05/20/2020 14:25");
  });

});
