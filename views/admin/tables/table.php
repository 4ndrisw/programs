<?php

defined('BASEPATH') or exit('No direct script access allowed');

$project_id = $this->ci->input->post('project_id');
$staff_id = get_staff_user_id();
$current_user = get_client_type($staff_id);
$company_id = $current_user->client_id;

$aColumns = [
    db_prefix() . 'programs.number',
    get_sql_select_client_company(),
    'surveyor_id',
    'YEAR(date) as year',
    db_prefix() . 'programs.inspector_id',
    db_prefix() . 'programs.inspector_staff_id as inspector_staff',
    'date',
    'duedate',
    'reference_no',
    db_prefix() . 'programs.state',
    ];

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'programs.clientid',
//    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'programs.currency',
//    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'programs.project_id',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'programs';

/*
$custom_fields = get_table_custom_fields('program');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'programs.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}
*/

$where  = [];
$filter = [];

if ($this->ci->input->post('not_sent')) {
    array_push($filter, 'OR (sent= 0 AND ' . db_prefix() . 'programs.state NOT IN (2,3,4))');
}
if ($this->ci->input->post('invoiced')) {
    array_push($filter, 'OR inspection_id IS NOT NULL');
}

if ($this->ci->input->post('not_inspected')) {
    array_push($filter, 'OR inspection_id IS NULL');
}
$states  = $this->ci->programs_model->get_states();
$stateIds = [];
foreach ($states as $state) {
    if ($this->ci->input->post('programs_' . $state)) {
        array_push($stateIds, $state);
    }
}
if (count($stateIds) > 0) {
    array_push($filter, 'AND ' . db_prefix() . 'programs.state IN (' . implode(', ', $stateIds) . ')');
}

$agents    = $this->ci->programs_model->get_inspector_staff_ids();
$agentsIds = [];
/*
foreach ($agents as $agent) {
    if ($this->ci->input->post('inspector_staff_id_' . $agent['inspector_staff_id'])) {
        array_push($agentsIds, $agent['inspector_staff_id']);
    }
}

if (count($agentsIds) > 0) {
    array_push($filter, 'AND inspector_staff_id IN (' . implode(', ', $agentsIds) . ')');
}
*/

$years      = $this->ci->programs_model->get_programs_years();
$yearsArray = [];
foreach ($years as $year) {
    if ($this->ci->input->post('year_' . $year['year'])) {
        array_push($yearsArray, $year['year']);
    }
}
if (count($yearsArray) > 0) {
    array_push($filter, 'AND YEAR(date) IN (' . implode(', ', $yearsArray) . ')');
}

if (count($filter) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
}
/*
if (isset($company_id) && $company_id != '') {
   if($current_user->client_type == 'Company'){
     array_push($where, 'AND ' . db_prefix() . 'programs.clientid=' . $this->ci->db->escape_str($company_id));
   } 
}
*/
/*
if ($project_id) {
    array_push($where, 'AND project_id=' . $this->ci->db->escape_str($project_id));
}
*/

if (!has_permission('programs', '', 'view')) {
    $userWhere = 'AND ' . get_programs_where_sql_for_staff($staff_id);
    array_push($where, $userWhere);
}


if (!is_admin() && has_permission('programs', '', 'view_programs_in_inpectors')){
    $inspector_id = get_inspector_id_by_staff_id($staff_id);
    array_push($where, 'AND ' . db_prefix() . 'programs.inspector_id =' . $this->ci->db->escape_str($inspector_id));
}

if(is_surveyor_staff($staff_id)){
    $surveyor_id = get_surveyor_id_by_staff_id($staff_id);
    $userWhere = 'AND surveyor_id = ' . $this->ci->db->escape_str($surveyor_id);
    array_push($where, $userWhere);

}

if (!is_admin() && has_permission('programs', '', 'view_programs_in_institutions')){
    $institution_id = get_institution_id_by_staff_id($staff_id);
    array_push($where, 'AND ' . db_prefix() . 'programs.institution_id =' . $this->ci->db->escape_str($institution_id));
}

if(!is_admin() && is_staff_related_to_company($company_id)){
    array_push($where, 'AND ('.db_prefix().'programs.clientid = '. $company_id . ') ');    
}

$aColumns = hooks()->apply_filters('programs_table_sql_columns', $aColumns);

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'programs.id',
    db_prefix() . 'programs.clientid',
    db_prefix() . 'programs.institution_id',
    db_prefix() . 'programs.inspector_id',
    db_prefix() . 'programs.inspector_staff_id',
    db_prefix() . 'programs.surveyor_id',
    db_prefix() . 'programs.inspection_id',
    //'project_id',
    'deleted_customer_name',
    db_prefix() . 'programs.hash',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $numberOutput = '';
    // If is from client area table or projects area request
    if ((isset($clientid) && is_numeric($clientid)) || $project_id) {
        $numberOutput = '<a href="' . admin_url('programs/list_programs/' . $aRow['id']) . '">' . format_program_number($aRow['id']) . '</a>';
    } else {
        $numberOutput = '<a href="' . admin_url('programs/list_programs/#' . $aRow['id'] .'/'.$aRow['id']) . '" onclick="init_program(' . $aRow['id'] . '); return false;">' . format_program_number($aRow['id']) . '</a>';
    }

    $numberOutput .= '<div class="row-options">';

    //$numberOutput .= '<a href="' . site_url('program/' . $aRow['id'] . '/' . $aRow['hash']) . '" target="_blank">' . _l('view') . '</a>';
    if (has_permission('programs', '', 'edit')) {
        $numberOutput .= ' | <a href="' . admin_url('programs/program/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }
    $numberOutput .= '</div>';

    $row[] = $numberOutput;

//    $inspector = get_inspector_name_by_id($aRow['inspector_id']);
    if (empty($aRow['deleted_customer_name'])) {
        //$row[] = '<a href="' . admin_url('companies/list_companies/'. $aRow['clientid'].'/'. $aRow['clientid']) . '" onclick="init_company(' . $aRow['clientid'] . '); return false;">' . $aRow['company'] . '</a>';
        $row[] = '<a href="' . admin_url('companies/list_companies/'. $aRow['clientid']).'">' . $aRow['company'] . '</a>';
        //$row[] = '<a href="' . admin_url('companies/list_companies/'. $aRow['clientid'].'/#'. $aRow['clientid']) . '" onclick="init_company(' . $aRow['clientid'] . '); return false;">' . $aRow['company'] . '</a>';
        //$row[] = '<a href="' . admin_url('companies/#' . $aRow['clientid']) . '" onclick="init_company(' . $aRow['clientid'] . '); return false;">' . $aRow['company'] . '</a>';
    } else {
        $row[] = $aRow['deleted_customer_name'];
    }

    $row[] = get_surveyor_name_by_id($aRow['surveyor_id']);

    $row[] = $aRow['year'];

    $inspector = get_inspector_name_by_id($aRow['inspector_id']);

    if ($aRow['inspection_id']) {
        $inspector .= '<br /><span class="hide"> - </span><span class="text-success">' . _l('program_invoiced') . '</span>';
    }

    $row[] = $inspector;

    $row[] = get_staff_full_name($aRow['inspector_staff']);

    $row[] = html_date($aRow['date']);

    $row[] = html_date($aRow['duedate']);

    $row[] = $aRow['reference_no'];

    $row[] = format_program_state($aRow[db_prefix() . 'programs.state']);

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('programs_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}

echo json_encode($output);
die();