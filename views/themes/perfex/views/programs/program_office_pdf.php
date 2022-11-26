<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('program_office_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . str_replace("PRG","PRG-UPT",$program_number) . '</b>';

if (get_option('show_state_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="color:rgb(' . program_state_color_pdf($state) . ');text-transform:uppercase;">' . format_program_state($state, '', false) . '</span>';
}

// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(8);

$organization_info = '<div style="color:#424242;">';
    $organization_info .= format_organization_info();
$organization_info .= '</div>';

// Program to
$program_info = '<b>' . _l('program_office_to') . '</b>';
$program_info .= '<div style="color:#424242;">';
$program_info .= format_office_info($program->office, 'program', 'billing');
$program_info .= '</div>';

$left_info  = $swap == '1' ? $program_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $program_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// Program to
$left_info ='<p>' . _l('program_opening') . ',</p>';
$left_info .= _l('program_client');
$left_info .= '<div style="color:#424242;">';
$left_info .= format_customer_info($program, 'program', 'billing');
$left_info .= '</div>';

$right_info = '';

$pdf->ln(4);
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 1) - $dimensions['lm']);

$organization_info = '<strong>'. _l('program_members') . ': </strong><br />';

$CI = &get_instance();
$CI->load->model('programs_model');
$program_members = $CI->programs_model->get_program_members($program->id,true);
$i=1;
foreach($program_members as $member){
  $organization_info .=  $i.'. ' .$member['firstname'] .' '. $member['lastname']. '<br />';
  $i++;
}

$program_info = '<br />' . _l('program_data_date') . ': ' . _d($program->date) . '<br />';


if ($program->project_id != 0 && get_option('show_project_on_program') == 1) {
    $program_info .= _l('project') . ': ' . get_project_name_by_id($program->project_id) . '<br />';
}


$left_info  = $swap == '1' ? $program_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $program_info;

$pdf->ln(4);
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_program_items_table_data($program, 'program', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->SetFont($font_name, '', $font_size);

$assigned_path = <<<EOF
        <img width="150" height="150" src="$program->assigned_path">
    EOF;    
$assigned_info = '<div style="text-align:center;">';
    $assigned_info .= get_option('invoice_company_name') . '<br />';
    $assigned_info .= $assigned_path . '<br />';

if ($program->assigned != 0 && get_option('show_assigned_on_programs') == 1) {
    $assigned_info .= get_staff_full_name($program->assigned);
}
$assigned_info .= '</div>';

$acceptance_path = <<<EOF
    <img src="$program->acceptance_path">
EOF;
$client_info = '<div style="text-align:center;">';
    $client_info .= $program->client_company .'<br />';

if ($program->signed != 0) {
    $client_info .= _l('program_signed_by') . ": {$program->acceptance_firstname} {$program->acceptance_lastname}" . '<br />';
    $client_info .= _l('program_signed_date') . ': ' . _dt($program->acceptance_date_string) . '<br />';
    $client_info .= _l('program_signed_ip') . ": {$program->acceptance_ip}" . '<br />';

    $client_info .= $acceptance_path;
    $client_info .= '<br />';
}
$client_info .= '</div>';


$left_info  = $swap == '1' ? $client_info : $assigned_info;
$right_info = $swap == '1' ? $assigned_info : $client_info;
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(2);   
$companyname = get_option('companyname');
$pdf->writeHTMLCell('', '', '', '', _l('program_crm_info', $companyname), 0, 1, false, true, 'L', true);
