<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'subject',
    db_prefix().'peralatan.nomor_seri',
    db_prefix().'peralatan.nomor_unit',
    'open_till',
    '1',
    ];

$sIndexColumn = 'id';
$sTable       = db_prefix().'peralatan';

$where        = [
    'AND '.db_prefix().'peralatan.clientid=' . $clientid,
    ];

array_push($where, 'AND '.db_prefix().'program_items.peralatan_id IS NULL');

$join = [
    'LEFT JOIN '.db_prefix().'program_items ON '.db_prefix().'peralatan.id = '.db_prefix().'program_items.peralatan_id',
//    'JOIN '.db_prefix().'staff ON '.db_prefix().'staff.staffid = '.db_prefix().'reminders.staff',
    ];
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix().'peralatan.id',
    'lokasi',
    db_prefix().'peralatan.clientid',
    db_prefix().'peralatan.jenis_pesawat_id',
    ]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'staff') {
            $_data = '<a href="' . admin_url('staff/profile/' . $aRow['staff']) . '">' . staff_profile_image($aRow['staff'], [
                'staff-profile-image-small',
                ]) . ' ' . $aRow['firstname'] . ' ' . $aRow['lastname'] . '</a>';
        } elseif ($aColumns[$i] == 'description') {
            if ($aRow['creator'] == get_staff_user_id() || is_admin()) {
                $_data .= '<div class="row-options">';
                if ($aRow['isnotified'] == 0) {
                    $_data .= '<a href="#" onclick="edit_reminder(' . $aRow['id'] . ',this); return false;" class="edit-reminder">' . _l('edit') . '</a> | ';
                }
                $_data .= '<a href="' . admin_url('misc/delete_reminder/' . $id . '/' . $aRow['id'] . '/' . $aRow['rel_type']) . '" class="text-danger delete-reminder">' . _l('delete') . '</a>';
                $_data .= '</div>';
            }
        } elseif ($aColumns[$i] == 'isnotified') {
            if ($_data == 1) {
                $_data = _l('reminder_is_notified_boolean_yes');
            } else {
                $_data = _l('reminder_is_notified_boolean_no');
            }
        } elseif ($aColumns[$i] == 'date') {
            $_data = _dt($_data);
        }
        elseif ($aColumns[$i] == '1') {
            $_data = '<a class="btn btn-success" title = "'._l('propose_this_item').'" href="#" onclick="programs_add_program_item(' . $clientid . ','. $institution_id . ',' . $inspector_id . ','. $inspector_staff_id  . ','. $surveyor_id . ','. $program_id . ',' . $aRow['jenis_pesawat_id'] .','.$aRow['id'] .'); return false;">+</a>';
        }
        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
