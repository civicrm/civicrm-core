module('dates');

test("format date", function() {
  var value = CRM.utils.formatDate('2021-05-10', 'mm/dd/yy');
  equal(value, "05/10/2021", "Expect formatted date");
});

test("format datetime 12", function() {
  var value = CRM.utils.formatDate('2021-05-10 12:35:00', 'mm/dd/yy', 12);
  equal(value, "05/10/2021 12:35 PM", "Expect formatted date time");

  value = CRM.utils.formatDate('2021-05-10 00:35:00', 'mm/dd/yy', 12);
  equal(value, "05/10/2021 12:35 AM", "Expect formatted date time");
});


test("format datetime 24", function() {
  var value = CRM.utils.formatDate('2020-05-20 04:25:40', 'mm/dd/yy', 24);
  equal(value, "05/20/2020 04:25", "Expect formatted date time");

  value = CRM.utils.formatDate('2020-05-20 14:25:40', 'mm/dd/yy', 24);
  equal(value, "05/20/2020 14:25", "Expect formatted date time");
});
