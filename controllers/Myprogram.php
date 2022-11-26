<?php defined('BASEPATH') or exit('No direct script access allowed');

class Myprogram extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('programs_model');
        $this->load->model('clients_model');
    }

    /* Get all programs in case user go on index page */
    public function list($id = '')
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('programs', 'admin/tables/table'));
        }
        $contact_id = get_contact_user_id();
        $user_id = get_user_id_by_contact_id($contact_id);
        $client = $this->clients_model->get($user_id);
        $data['programs'] = $this->programs_model->get_client_programs($client);
        $data['programid']            = $id;
        $data['title']                 = _l('programs_tracking');

        $data['bodyclass'] = 'programs';
        $this->data($data);
        $this->view('themes/'. active_clients_theme() .'/views/programs/programs');
        $this->layout();
    }

    public function show($id, $hash)
    {
        check_program_restrictions($id, $hash);
        $program = $this->programs_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($program->clientid);
        }

        $identity_confirmation_enabled = get_option('program_accept_identity_confirmation');

        if ($this->input->post('program_action')) {
            $action = $this->input->post('program_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->programs_model->mark_action_state($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_program_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_program_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_program_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), PRGEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'programs', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Program PDF generator

        $program_number = format_program_number($program->id);
        /*
        if ($this->input->post('programpdf')) {
            try {
                $pdf = program_pdf($program);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$program_number = format_program_number($program->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $program_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_program_filename', mb_strtoupper(slug_it($program_number), 'UTF-8') . '.pdf', $program);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $program_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['program_number']              = $program_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['program']                     = hooks()->apply_filters('program_html_pdf_data', $program);
        $data['bodyclass']                     = 'viewprogram';
        $data['client_company']                = $this->clients_model->get($program->clientid)->company;
        $setSize = get_option('program_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        //$data['program_members']  = $this->programs_model->get_program_members($program->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('program_number') . ' : ' . $program_number ."\r\n";
        $qrcode_data .= _l('program_date') . ' : ' . $program->date ."\r\n";
        $qrcode_data .= _l('program_datesend') . ' : ' . $program->datesend ."\r\n";
        //$qrcode_data .= _l('program_assigned_string') . ' : ' . get_staff_full_name($program->assigned) ."\r\n";
        //$qrcode_data .= _l('program_url') . ' : ' . site_url('programs/show/'. $program->id .'/'.$program->hash) ."\r\n";


        $program_path = get_upload_path_by_type('programs') . $program->id . '/';
        _maybe_create_upload_path('uploads/programs');
        _maybe_create_upload_path('uploads/programs/'.$program_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $program_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/programs/'.$program_path .'assigned-'.$program_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/programs/programhtml');
        add_views_tracking('program', $id);
        hooks()->do_action('program_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }


    public function office($id, $hash)
    {
        check_program_restrictions($id, $hash);
        $program = $this->programs_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($program->clientid);
        }

        $identity_confirmation_enabled = get_option('program_accept_identity_confirmation');

        if ($this->input->post('program_action')) {
            $action = $this->input->post('program_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->programs_model->mark_action_state($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_program_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_program_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_program_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), PRGEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'programs', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Program PDF generator

        $program_number = format_program_number($program->id);
        /*
        if ($this->input->post('programpdf')) {
            try {
                $pdf = program_pdf($program);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$program_number = format_program_number($program->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $program_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_program_filename', mb_strtoupper(slug_it($program_number), 'UTF-8') . '.pdf', $program);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $program_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['program_number']              = $program_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['program']                     = hooks()->apply_filters('program_html_pdf_data', $program);
        $data['bodyclass']                     = 'viewprogram';
        $data['client_company']                = $this->clients_model->get($program->clientid)->company;
        $setSize = get_option('program_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['program_members']  = $this->programs_model->get_program_members($program->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('program_number') . ' : ' . $program_number ."\r\n";
        $qrcode_data .= _l('program_date') . ' : ' . $program->date ."\r\n";
        $qrcode_data .= _l('program_datesend') . ' : ' . $program->datesend ."\r\n";
        //$qrcode_data .= _l('program_assigned_string') . ' : ' . get_staff_full_name($program->assigned) ."\r\n";
        //$qrcode_data .= _l('program_url') . ' : ' . site_url('programs/show/'. $program->id .'/'.$program->hash) ."\r\n";


        $program_path = get_upload_path_by_type('programs') . $program->id . '/';
        _maybe_create_upload_path('uploads/programs');
        _maybe_create_upload_path('uploads/programs/'.$program_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $program_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/programs/'.$program_path .'assigned-'.$program_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/programs/program_office_html');
        add_views_tracking('program', $id);
        hooks()->do_action('program_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }
    
    /* Generates program PDF and senting to email  */
    public function pdf($id)
    {
        $canView = user_can_view_program($id);
        if (!$canView) {
            access_denied('Programs');
        } else {
            if (!has_contact_permission('programs', '', 'view') && !has_contact_permission('programs', '', 'view_own') && $canView == false) {
                access_denied('Programs');
            }
        }
        if (!$id) {
            redirect(admin_url('programs'));
        }
        $program        = $this->programs_model->get($id);
        $program_number = format_program_number($program->id);
        
        $program->assigned_path = FCPATH . get_program_upload_path('program').$program->id.'/assigned-'.$program_number.'.png';
        $program->acceptance_path = FCPATH . get_program_upload_path('program').$program->id .'/'.$program->signature;
        
        $program->client_company = $this->clients_model->get($program->clientid)->company;
        $program->acceptance_date_string = _dt($program->acceptance_date);


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

    /* Generates program PDF and senting to email  */
    public function office_pdf($id)
    {
        $canView = user_can_view_program($id);
        if (!$canView) {
            access_denied('Programs');
        } else {
            if (!has_contact_permission('programs', '', 'view') && !has_contact_permission('programs', '', 'view_own') && $canView == false) {
                access_denied('Programs');
            }
        }
        if (!$id) {
            redirect(admin_url('programs'));
        }
        $program        = $this->programs_model->get($id);
        $program_number = format_program_number($program->id);
        
        $program->assigned_path = FCPATH . get_program_upload_path('program').$program->id.'/assigned-'.$program_number.'.png';
        $program->acceptance_path = FCPATH . get_program_upload_path('program').$program->id .'/'.$program->signature;
        
        $program->client_company = $this->clients_model->get($program->clientid)->company;
        $program->acceptance_date_string = _dt($program->acceptance_date);


        try {
            $pdf = program_office_pdf($program);
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
                            'file_name' => str_replace("PRG", "PRG-UPT", mb_strtoupper(slug_it($program_number)) . '.pdf'),
                            'program'  => $program,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }

    /* Add new program or update existing */
    public function create_program(){
        
        if ($this->input->post()) {
            $program_data = $this->input->post();

            $save_and_send_later = false;
            if (isset($program_data['save_and_send_later'])) {
                unset($program_data['save_and_send_later']);
                $save_and_send_later = true;
            }

                if (!has_contact_permission('programs')) {
                    access_denied('programs');
                }
                $id = $this->programs_model->add($program_data);

                if ($id) {
                    set_alert('success', _l('added_successfully', _l('program')));

                    $redUrl = admin_url('programs/program/' . $id);

                    if ($save_and_send_later) {
                        $this->session->set_userdata('send_later', true);
                        // die(redirect($redUrl));
                    }

                    redirect(
                        !$this->set_program_pipeline_autoload($id) ? $redUrl : admin_url('programs/list/')
                    );
                }
            
        }
        
            $title = _l('create_new_program');

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        if ($this->input->get('program_request_id')) {
            $data['program_request_id'] = $this->input->get('program_request_id');
        }

        $data['staff']             = $this->staff_model->get('', ['active' => 1]);
        $data['program_states'] = $this->programs_model->get_states();
        $data['title']             = $title;

        $this->view('themes/'. active_clients_theme() .'/views/programs/client_program');
        $this->data($data);
        $this->layout();

    }

    public function inspector_staff(){
        if (!has_contact_permission('programs')) {
            //access_denied('programs');
            die();
        }
        var_dump($this->input->get('searchTerm'));
        if ($this->input->get('searchTerm', TRUE)) {
            $data = get_inspector_staff_data_ajax(($this->input->get('searchTerm', TRUE)));
        } else {
            $data = get_inspector_staff_data_ajax();
        }

        $this->output->set_state_header(200)->set_content_type('application/json')->set_output(json_encode($data));
    

    }

}
