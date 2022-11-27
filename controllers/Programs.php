<?php

use app\services\programs\ProgramsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Programs extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('programs_model');
    }

    /* Get all programs in case user go on index page */
    public function index($id = '')
    {
        $this->list_programs($id);
    }

    /* List all programs datatables */
    public function list_programs($id = '')
    {
        if (!has_permission('programs', '', 'view') && !has_permission('programs', '', 'view_own') && get_option('allow_staff_view_programs_assigned') == '0') {
            access_denied('programs');
        }

        $isPipeline = $this->session->userdata('program_pipeline') == 'true';

        $data['program_states'] = $this->programs_model->get_states();
        if ($isPipeline && !$this->input->get('state') && !$this->input->get('filter')) {
            $data['title']           = _l('programs_pipeline');
            $data['bodyclass']       = 'programs-pipeline programs-total-manual';
            $data['switch_pipeline'] = false;

            if (is_numeric($id)) {
                $data['programid'] = $id;
            } else {
                $data['programid'] = $this->session->flashdata('programid');
            }

            $this->load->view('admin/programs/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('state') || $this->input->get('filter') && $isPipeline) {
                $this->pipeline(0, true);
            }

            $data['programid']            = $id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('programs');
            $data['bodyclass']             = 'programs-total-manual';
            $data['programs_years']       = $this->programs_model->get_programs_years();
            $data['programs_inspector_staff_ids'] = $this->programs_model->get_inspector_staff_ids();
            if($id){
                $this->load->view('admin/programs/manage_small_table', $data);

            }else{
                $this->load->view('admin/programs/manage_table', $data);

            }

        }
    }

    public function table($clientid = '')
    {
        if (!has_permission('programs', '', 'view') && !has_permission('programs', '', 'view_own') && get_option('allow_staff_view_programs_assigned') == '0') {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('programs', 'admin/tables/table',[
            'clientid' => $clientid,
        ]));
    }

    public function get_peralatan_table($clientid, $institution_id, $inspector_id, $inspector_staff_id, $surveyor_id, $id)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('programs', 'admin/tables/peralatan_table'), [
                'clientid' => $clientid,
                'institution_id' => $institution_id,
                'inspector_id' => $inspector_id,
                'inspector_staff_id' => $inspector_staff_id,
                'surveyor_id' => $surveyor_id,
                'program_id' => $id,
            ]);
        }
    }

    public function get_program_items_table($clientid, $institution_id, $inspector_id, $inspector_staff_id, $surveyor_id, $id)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('programs', 'admin/tables/program_items_table'), [
                'clientid' => $clientid,
                'institution_id' => $institution_id,
                'inspector_id' => $inspector_id,
                'inspector_staff_id' => $inspector_staff_id,
                'surveyor_id' => $surveyor_id,
                'program_id' => $id,
            ]);
        }
    }

    /* Add new program or update existing */
    public function program($id = '')
    {
        if ($this->input->post()) {
            $program_data = $this->input->post();

            $save_and_send_later = false;
            if (isset($program_data['save_and_send_later'])) {
                unset($program_data['save_and_send_later']);
                $save_and_send_later = true;
            }

            if ($id == '') {
                if (!has_permission('programs', '', 'create')) {
                    access_denied('programs');
                }
                $program_data['institution_id'] = get_institution_id_by_inspector_id($program_data['inspector_id']);
                $id = $this->programs_model->add($program_data);

                if ($id) {
                    set_alert('success', _l('added_successfully', _l('program')));

                    $redUrl = admin_url('programs/list_programs/#' . $id);

                    if ($save_and_send_later) {
                        $this->session->set_userdata('send_later', true);
                        // die(redirect($redUrl));
                    }

                    redirect(
                        !$this->set_program_pipeline_autoload($id) ? $redUrl : admin_url('programs/list_programs/')
                    );
                }
            } else {
                if (!has_permission('programs', '', 'edit')) {
                    access_denied('programs');
                }
                
                $success = $this->programs_model->update($program_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('program')));
                }
                if ($this->set_program_pipeline_autoload($id)) {
                    redirect(admin_url('programs/list_programs/'));
                } else {
                    redirect(admin_url('programs/list_programs/' . $id));
                }
            }
        }
        if ($id == '') {
            $title = _l('create_new_program');
        } else {
            $program = $this->programs_model->get($id);

            if (!$program || !user_can_view_program($id)) {
                blank_page(_l('program_not_found'));
            }

            $data['program'] = $program;
            $data['edit']     = true;
            $title            = _l('edit', _l('program_lowercase'));
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        if ($this->input->get('program_request_id')) {
            $data['program_request_id'] = $this->input->get('program_request_id');
        }


        $data['staff']             = $this->staff_model->get('', ['active' => 1]);
        $data['program_states'] = $this->programs_model->get_states();
        $data['title']             = $title;
        $this->load->view('admin/programs/program', $data);
    }
    
    public function clear_signature($id)
    {
        if (has_permission('programs', '', 'delete')) {
            $this->programs_model->clear_signature($id);
        }

        redirect(admin_url('programs/list_programs/' . $id));
    }

    public function update_number_settings($id)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];
        if (has_permission('programs', '', 'edit')) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'programs', [
                'prefix' => $this->input->post('prefix'),
            ]);
            if ($this->db->affected_rows() > 0) {
                $response['success'] = true;
                $response['message'] = _l('updated_successfully', _l('program'));
            }
        }

        echo json_encode($response);
        die;
    }

    public function validate_program_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        if (total_rows(db_prefix() . 'programs', [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    public function delete_attachment($id)
    {
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->programs_model->delete_attachment($id);
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /* Get all program data used when user click on program number in a datatable left side*/
    public function get_program_data_ajax($id, $to_return = false)
    {
        if (!has_permission('programs', '', 'view') && !has_permission('programs', '', 'view_own') && get_option('allow_staff_view_programs_assigned') == '0') {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die('No program found');
        }
        
        if(is_inspector_staff(get_staff_user_id()) && get_option('inspector_staff_only_view_programs_assigned') && !inspector_staff_has_program($id,get_staff_user_id())){
            echo _l('access_denied');
            die;
        }

        $program = $this->programs_model->get($id);

        if (!$program || !user_can_view_program($id)) {
            echo _l('program_not_found');
            die;
        }

        $program->date       = _d($program->date);
        $program->duedate = _d($program->duedate);
        if (isset($program->inspection_id) && $program->inspection_id !== null) {
            $this->load->model('inspections_model');
            $program->inspection = $this->inspections_model->get($program->inspection_id);
        }

        if ($program->sent == 0) {
            $template_name = 'program_send_to_customer';
        } else {
            $template_name = 'program_send_to_customer_already_sent';
        }

        $data = prepare_mail_preview_data($template_name, $program->clientid);

        $data['activity']          = $this->programs_model->get_program_activity($id);
        $data['program']          = $program;
        
        $data['members']           = $this->staff_model->get('', ['active' => 1]);
        $data['program_states'] = $this->programs_model->get_states();
        $data['totalNotes']        = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'program']);

        $data['send_later'] = false;
        if ($this->session->has_userdata('send_later')) {
            $data['send_later'] = true;
            $this->session->unset_userdata('send_later');
        }

        if ($to_return == false) {
            $this->load->view('admin/programs/program_preview_template', $data);
        } else {
            return $this->load->view('admin/programs/program_preview_template', $data, true);
        }
    }

    public function get_programs_total()
    {
        if ($this->input->post()) {
            $data['totals'] = $this->programs_model->get_programs_total($this->input->post());

            $this->load->model('currencies_model');

            if (!$this->input->post('customer_id')) {
                $multiple_currencies = call_user_func('is_using_multiple_currencies', db_prefix() . 'programs');
            } else {
                $multiple_currencies = call_user_func('is_client_using_multiple_currencies', $this->input->post('customer_id'), db_prefix() . 'programs');
            }

            if ($multiple_currencies) {
                $data['currencies'] = $this->currencies_model->get();
            }

            $data['programs_years'] = $this->programs_model->get_programs_years();

            if (
                count($data['programs_years']) >= 1
                && !\app\services\utilities\Arr::inMultidimensional($data['programs_years'], 'year', date('Y'))
            ) {
                array_unshift($data['programs_years'], ['year' => date('Y')]);
            }

            $data['_currency'] = $data['totals']['currencyid'];
            unset($data['totals']['currencyid']);
            $this->load->view('admin/programs/programs_total_template', $data);
        }
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && user_can_view_program($rel_id)) {
            $this->misc_model->add_note($this->input->post(), 'program', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if (user_can_view_program($id)) {
            $data['notes'] = $this->misc_model->get_notes($id, 'program');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function mark_action_state($state, $id)
    {
        if (!has_permission('programs', '', 'edit')) {
            access_denied('programs');
        }
        $success = $this->programs_model->mark_action_state($state, $id);
        if ($success) {
            set_alert('success', _l('program_state_changed_success'));
        } else {
            set_alert('danger', _l('program_state_changed_fail'));
        }
        if ($this->set_program_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('programs/list_programs/' . $id));
        }
    }

    public function send_expiry_reminder($id)
    {
        $canView = user_can_view_program($id);
        if (!$canView) {
            access_denied('Programs');
        } else {
            if (!has_permission('programs', '', 'view') && !has_permission('programs', '', 'view_own') && $canView == false) {
                access_denied('Programs');
            }
        }

        $success = $this->programs_model->send_expiry_reminder($id);
        if ($success) {
            set_alert('success', _l('sent_expiry_reminder_success'));
        } else {
            set_alert('danger', _l('sent_expiry_reminder_fail'));
        }
        if ($this->set_program_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('programs/list_programs/' . $id));
        }
    }

    /* Send program to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_program($id);
        if (!$canView) {
            access_denied('programs');
        } else {
            if (!has_permission('programs', '', 'view') && !has_permission('programs', '', 'view_own') && $canView == false) {
                access_denied('programs');
            }
        }

        try {
            $success = $this->programs_model->send_program_to_client($id, '', $this->input->post('attach_pdf'), $this->input->post('cc'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        // In case client use another language
        load_admin_language();
        if ($success) {
            set_alert('success', _l('program_sent_to_client_success'));
        } else {
            set_alert('danger', _l('program_sent_to_client_fail'));
        }
        if ($this->set_program_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('programs/list_programs/' . $id));
        }
    }

    /* Convert program to invoice */
    public function convert_to_inspection($id)
    {
        if (!has_permission('inspections', '', 'create')) {
            access_denied('inspections');
        }
        if (!$id) {
            die('No program found');
        }
        $draft_invoice = false;
        if ($this->input->get('save_as_draft')) {
            $draft_invoice = true;
        }
        $inspection_id = $this->programs_model->convert_to_inspection($id, false, $draft_inspection);
        if ($inspection_id) {
            set_alert('success', _l('program_convert_to_inspection_successfully'));
            redirect(admin_url('inspections/list_inspections/' . $inspection_id));
        } else {
            if ($this->session->has_userdata('program_pipeline') && $this->session->userdata('program_pipeline') == 'true') {
                $this->session->set_flashdata('programid', $id);
            }
            if ($this->set_program_pipeline_autoload($id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('programs/list_programs/' . $id));
            }
        }
    }

    public function copy($id)
    {
        if (!has_permission('programs', '', 'create')) {
            access_denied('programs');
        }
        if (!$id) {
            die('No program found');
        }
        $new_id = $this->programs_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('program_copied_successfully'));
            if ($this->set_program_pipeline_autoload($new_id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('programs/program/' . $new_id));
            }
        }
        set_alert('danger', _l('program_copied_fail'));
        if ($this->set_program_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('programs/program/' . $id));
        }
    }

    /* Delete program */
    public function delete($id)
    {
        if (!has_permission('programs', '', 'delete')) {
            access_denied('programs');
        }
        if (!$id) {
            redirect(admin_url('programs/list_programs'));
        }
        $success = $this->programs_model->delete($id);
        if (is_array($success)) {
            set_alert('warning', _l('is_invoiced_program_delete_error'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('program')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('program_lowercase')));
        }
        redirect(admin_url('programs/list_programs'));
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'programs', get_acceptance_info_array(true));
        }

        redirect(admin_url('programs/list_programs/' . $id));
    }

    /* Generates program PDF and senting to email  */
    public function pdf($id)
    {
        $canView = user_can_view_program($id);
        if (!$canView) {
            access_denied('Programs');
        } else {
            if (!has_permission('programs', '', 'view') && !has_permission('programs', '', 'view_own') && $canView == false) {
                access_denied('Programs');
            }
        }
        if (!$id) {
            redirect(admin_url('programs/list_programs'));
        }
        $program        = $this->programs_model->get($id);
        $program_number = format_program_number($program->id);

        try {
            $pdf = program_pdf($program);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $fileNameHookData = hooks()->apply_filters('program_file_name_admin_area', [
                            'file_name' => mb_strtoupper(slug_it($program_number)) . '.pdf',
                            'program'  => $program,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }

    // Pipeline
    public function get_pipeline()
    {
        if (has_permission('programs', '', 'view') || has_permission('programs', '', 'view_own') || get_option('allow_staff_view_programs_assigned') == '1') {
            $data['program_states'] = $this->programs_model->get_states();
            $this->load->view('admin/programs/pipeline/pipeline', $data);
        }
    }

    public function pipeline_open($id)
    {
        $canView = user_can_view_program($id);
        if (!$canView) {
            access_denied('Programs');
        } else {
            if (!has_permission('programs', '', 'view') && !has_permission('programs', '', 'view_own') && $canView == false) {
                access_denied('Programs');
            }
        }

        $data['id']       = $id;
        $data['program'] = $this->get_program_data_ajax($id, true);
        $this->load->view('admin/programs/pipeline/program', $data);
    }

    public function update_pipeline()
    {
        if (has_permission('programs', '', 'edit')) {
            $this->programs_model->update_pipeline($this->input->post());
        }
    }

    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'program_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('programs/list_programs'));
        }
    }

    public function pipeline_load_more()
    {
        $state = $this->input->get('state');
        $page   = $this->input->get('page');

        $programs = (new ProgramsPipeline($state))
            ->search($this->input->get('search'))
            ->sortBy(
                $this->input->get('sort_by'),
                $this->input->get('sort')
            )
            ->page($page)->get();

        foreach ($programs as $program) {
            $this->load->view('admin/programs/pipeline/_kanban_card', [
                'program' => $program,
                'state'   => $state,
            ]);
        }
    }

    public function set_program_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if ($this->session->has_userdata('program_pipeline')
                && $this->session->userdata('program_pipeline') == 'true') {
            $this->session->set_flashdata('programid', $id);

            return true;
        }

        return false;
    }

    public function get_due_date()
    {
        if ($this->input->post()) {
            $date    = $this->input->post('date');
            $duedate = '';
            if (get_option('program_due_after') != 0) {
                $date    = to_sql_date($date);
                $d       = date('Y-m-d', strtotime('+' . get_option('program_due_after') . ' DAY', strtotime($date)));
                $duedate = _d($d);
                echo $duedate;
            }
        }
    }


    public function add_program_item()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $this->programs_model->programs_add_program_item($this->input->post());
        }
    }

    public function remove_program_item()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $this->programs_model->programs_remove_program_item($this->input->post());
        }
    }


}
