<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = array(
   _l('program_dt_table_heading_number'),
   _l('program_dt_table_heading_client'),
   _l('programs_surveyor'),
   array(
      'name'=>_l('invoice_program_year'),
      'th_attrs'=>array('class'=>'not_visible')
   ),
   array(
      'name'=>_l('program_dt_table_heading_inspector'),
      'th_attrs'=>array('class'=> (isset($client) ? 'not_visible' : ''))
   ),
   _l('staff'),
   _l('program_dt_table_heading_date'),
   _l('program_dt_table_heading_duedate'),
   _l('reference_no'),
   _l('program_dt_table_heading_state'));

$custom_fields = get_custom_fields('program',array('show_on_table'=>1));

foreach($custom_fields as $field){
   array_push($table_data,$field['name']);
}

$table_data = hooks()->apply_filters('programs_table_columns', $table_data);

render_datatable($table_data, isset($class) ? $class : 'programs');
