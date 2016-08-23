<?php

/**
 * @group headless
 */
class CRM_Contact_Page_AjaxTest extends CiviUnitTestCase {


  public function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
  }

  /**
   * Minimal test on the testGetDupes function to make sure it completes without error.
   */
  public function testGetDedupes() {
    $_REQUEST['gid'] = 1;
    $_REQUEST['rgid'] = 1;
    $_REQUEST['columns'] = array(
      array(
        'search' => array(
          'value' => array(
            'src' => 'first_name',
          ),
        ),
        'data' => 'src',
      ),
    );
    $_REQUEST['is_unit_test'] = TRUE;
    $result = CRM_Contact_Page_AJAX::getDedupes();
    $this->assertEquals(array('data' => array(), 'recordsTotal' => 0, 'recordsFiltered' => 0), $result);
  }

  public function testGetDedupesPostCode() {
    $_REQUEST['gid'] = 1;
    $_REQUEST['rgid'] = 1;
    $_REQUEST['snippet'] = 4;
    $_REQUEST['draw'] = 3;
    $_REQUEST['columns'] = array(
      0 => array(
        'data' => 'is_selected_input',
        'name' => '',
        'searchable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      1 => array(
        'data' => 'src_image',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      2 => array(
        'data' => 'src',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      3 => array(
        'data' => 'dst_image',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      4 => array(
        'data' => 'dst',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      5 => array(
        'data' => 'src_email',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      6 => array(
        'data' => 'dst_email',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      7 => array(
        'data' => 'src_street',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      8 => array(
        'data' => 'dst_street',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      9 => array(
        'data' => 'src_postcode',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => 123,
          'regex' => FALSE,
        ),
      ),

      10 => array(
        'data' => 'dst_postcode',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      11 => array(
        'data' => 'conflicts',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      12 => array(
        'data' => 'weight',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      13 => array(
        'data' => 'actions',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),
    );

    $_REQUEST['start'] = 0;
    $_REQUEST['length'] = 10;
    $_REQUEST['search'] = array(
      'value' => '',
      'regex' => FALSE,
    );

    $_REQUEST['_'] = 1466478641007;
    $_REQUEST['Drupal_toolbar_collapsed'] = 0;
    $_REQUEST['has_js'] = 1;
    $_REQUEST['SESSa06550b3043ecca303761d968e3c846a'] = 'qxSxw0F_UmBITMM0JaVwTRcHV1bQqBSHNmBMY9AA8Wk';

    $_REQUEST['is_unit_test'] = TRUE;

    $result = CRM_Contact_Page_AJAX::getDedupes();
    $this->assertEquals(array('data' => array(), 'recordsTotal' => 0, 'recordsFiltered' => 0), $result);
  }

}
