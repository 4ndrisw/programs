<?php

use app\services\AbstractKanban;
use app\services\programs\ProgramsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Programs_model extends App_Model
{
    private $states;

    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();

        $this->states = hooks()->apply_filters('before_set_program_states', [
            1,
            2,
            5,
            3,
            4,
        ]);
    }


    /**
     * Get unique sale agent for programs / Used for filters
     * @return array
     */
    public function get_inspector_staff_ids()
    {
        return $this->db->query("SELECT DISTINCT(inspector_staff_id) as inspector_staff_id, CONCAT(firstname, ' ', lastname) as full_name FROM "
                                 . db_prefix() . 'programs JOIN '
                                 . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'programs.inspector_staff_id WHERE inspector_staff_id != 0'
                                )->result_array();
    }

    /**
     * Get program/s
     * @param mixed $id program id
     * @param array $where perform where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'programs.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'programs');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'programs.currency', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'programs.id', $id);
            $program = $this->db->get()->row();
            if ($program) {
                $program->attachments                           = $this->get_attachments($id);
                $program->visible_attachments_to_customer_found = false;

                foreach ($program->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $program->visible_attachments_to_customer_found = true;

                        break;
                    }
                }

                $program->items = get_items_by_type('program', $id);

                if ($program->project_id != 0) {
                    $this->load->model('projects_model');
                    $program->project_data = $this->projects_model->get($program->project_id);
                }

                $program->client = $this->clients_model->get($program->clientid);

                if (!$program->client) {
                    $program->client          = new stdClass();
                    $program->client->company = $program->deleted_customer_name;
                }

                $this->load->model('email_schedule_model');
                $program->programd_email = $this->email_schedule_model->get($id, 'program');
            }

            return $program;
        }
        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Get program states
     * @return array
     */
    public function get_states()
    {
        return $this->states;
    }

    public function clear_signature($id)
    {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $program = $this->db->get(db_prefix() . 'programs')->row();

        if ($program) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'programs', ['signature' => null]);

            if (!empty($program->signature)) {
                unlink(get_upload_path_by_type('program') . $id . '/' . $program->signature);
            }

            return true;
        }

        return false;
    }

    /**
     * Convert program to invoice
     * @param mixed $id program id
     * @return mixed     New invoice ID
     */
    public function convert_to_inspection($id, $client = false, $draft_invoice = false)
    {
        // Recurring invoice date is okey lets convert it to new invoice
        $_program = $this->get($id);

        $new_invoice_data = [];
        if ($draft_invoice == true) {
            $new_invoice_data['save_as_draft'] = true;
        }
        $new_invoice_data['clientid']   = $_program->clientid;
        $new_invoice_data['project_id'] = $_program->project_id;
        $new_invoice_data['number']     = get_option('next_invoice_number');
        $new_invoice_data['date']       = _d(date('Y-m-d'));
        $new_invoice_data['duedate']    = _d(date('Y-m-d'));
        if (get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }
        $new_invoice_data['show_quantity_as'] = $_program->show_quantity_as;
        $new_invoice_data['currency']         = $_program->currency;
        $new_invoice_data['subtotal']         = $_program->subtotal;
        $new_invoice_data['total']            = $_program->total;
        $new_invoice_data['adjustment']       = $_program->adjustment;
        $new_invoice_data['discount_percent'] = $_program->discount_percent;
        $new_invoice_data['discount_total']   = $_program->discount_total;
        $new_invoice_data['discount_type']    = $_program->discount_type;
        $new_invoice_data['inspector_staff_id']       = $_program->inspector_staff_id;
        // Since version 1.0.6
        $new_invoice_data['billing_street']   = clear_textarea_breaks($_program->billing_street);
        $new_invoice_data['billing_city']     = $_program->billing_city;
        $new_invoice_data['billing_state']    = $_program->billing_state;
        $new_invoice_data['billing_zip']      = $_program->billing_zip;
        $new_invoice_data['billing_country']  = $_program->billing_country;
        $new_invoice_data['shipping_street']  = clear_textarea_breaks($_program->shipping_street);
        $new_invoice_data['shipping_city']    = $_program->shipping_city;
        $new_invoice_data['shipping_state']   = $_program->shipping_state;
        $new_invoice_data['shipping_zip']     = $_program->shipping_zip;
        $new_invoice_data['shipping_country'] = $_program->shipping_country;

        if ($_program->include_shipping == 1) {
            $new_invoice_data['include_shipping'] = 1;
        }

        $new_invoice_data['show_shipping_on_invoice'] = $_program->show_shipping_on_program;
        $new_invoice_data['terms']                    = get_option('predefined_terms_invoice');
        $new_invoice_data['clientnote']               = get_option('predefined_clientnote_invoice');
        // Set to unpaid state automatically
        $new_invoice_data['state']    = 1;
        $new_invoice_data['adminnote'] = '';

        $this->load->model('payment_modes_model');
        $modes = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);
        $temp_modes = [];
        foreach ($modes as $mode) {
            if ($mode['selected_by_default'] == 0) {
                continue;
            }
            $temp_modes[] = $mode['id'];
        }
        $new_invoice_data['allowed_payment_modes'] = $temp_modes;
        $new_invoice_data['newitems']              = [];
        $custom_fields_items                       = get_custom_fields('items');
        $key                                       = 1;
        foreach ($_program->items as $item) {
            $new_invoice_data['newitems'][$key]['description']      = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_invoice_data['newitems'][$key]['qty']              = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit']             = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname']          = [];
            $taxes                                                  = get_program_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_invoice_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_invoice_data['newitems'][$key]['rate']  = $item['rate'];
            $new_invoice_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_invoice_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $this->load->model('invoices_model');
        $id = $this->invoices_model->add($new_invoice_data);
        if ($id) {
            // Customer accepted the program and is auto converted to invoice
            if (!is_staff_logged_in()) {
                $this->db->where('rel_type', 'invoice');
                $this->db->where('rel_id', $id);
                $this->db->delete(db_prefix() . 'sales_activity');
                $this->invoices_model->log_invoice_activity($id, 'invoice_activity_auto_converted_from_program', true, serialize([
                    '<a href="' . admin_url('programs/list_programs/' . $_program->id) . '">' . format_program_number($_program->id) . '</a>',
                ]));
            }
            // For all cases update addefrom and sale agent from the invoice
            // May happen staff is not logged in and these values to be 0
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'invoices', [
                'addedfrom'  => $_program->addedfrom,
                'inspector_staff_id' => $_program->inspector_staff_id,
            ]);

            // Update program with the new invoice data and set to state accepted
            $this->db->where('id', $_program->id);
            $this->db->update(db_prefix() . 'programs', [
                'invoiced_date' => date('Y-m-d H:i:s'),
                'invoiceid'     => $id,
                'state'        => 4,
            ]);


            if (is_custom_fields_smart_transfer_enabled()) {
                $this->db->where('fieldto', 'program');
                $this->db->where('active', 1);
                $cfPrograms = $this->db->get(db_prefix() . 'customfields')->result_array();
                foreach ($cfPrograms as $field) {
                    $tmpSlug = explode('_', $field['slug'], 2);
                    if (isset($tmpSlug[1])) {
                        $this->db->where('fieldto', 'invoice');

                        $this->db->group_start();
                        $this->db->like('slug', 'invoice_' . $tmpSlug[1], 'after');
                        $this->db->where('type', $field['type']);
                        $this->db->where('options', $field['options']);
                        $this->db->where('active', 1);
                        $this->db->group_end();

                        // $this->db->where('slug LIKE "invoice_' . $tmpSlug[1] . '%" AND type="' . $field['type'] . '" AND options="' . $field['options'] . '" AND active=1');
                        $cfTransfer = $this->db->get(db_prefix() . 'customfields')->result_array();

                        // Don't make mistakes
                        // Only valid if 1 result returned
                        // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                        if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                            $value = get_custom_field_value($_program->id, $field['id'], 'program', false);

                            if ($value == '') {
                                continue;
                            }

                            $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                'relid'   => $id,
                                'fieldid' => $cfTransfer[0]['id'],
                                'fieldto' => 'invoice',
                                'value'   => $value,
                            ]);
                        }
                    }
                }
            }

            if ($client == false) {
                $this->log_program_activity($_program->id, 'program_activity_converted', false, serialize([
                    '<a href="' . admin_url('invoices/list_invoices/' . $id) . '">' . format_invoice_number($id) . '</a>',
                ]));
            }

            hooks()->do_action('program_converted_to_invoice', ['invoice_id' => $id, 'program_id' => $_program->id]);
        }

        return $id;
    }

    /**
     * Copy program
     * @param mixed $id program id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_program                       = $this->get($id);
        $new_program_data               = [];
        $new_program_data['clientid']   = $_program->clientid;
        $new_program_data['project_id'] = $_program->project_id;
        $new_program_data['number']     = get_option('next_program_number');
        $new_program_data['date']       = _d(date('Y-m-d'));
        $new_program_data['duedate'] = null;

        if ($_program->duedate && get_option('program_due_after') != 0) {
            $new_program_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('program_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_program_data['show_quantity_as'] = $_program->show_quantity_as;
        $new_program_data['currency']         = $_program->currency;
        $new_program_data['subtotal']         = $_program->subtotal;
        $new_program_data['total']            = $_program->total;
        $new_program_data['adminnote']        = $_program->adminnote;
        $new_program_data['adjustment']       = $_program->adjustment;
        $new_program_data['discount_percent'] = $_program->discount_percent;
        $new_program_data['discount_total']   = $_program->discount_total;
        $new_program_data['discount_type']    = $_program->discount_type;
        $new_program_data['terms']            = $_program->terms;
        $new_program_data['inspector_staff_id']       = $_program->inspector_staff_id;
        $new_program_data['reference_no']     = $_program->reference_no;
        // Since version 1.0.6
        $new_program_data['billing_street']   = clear_textarea_breaks($_program->billing_street);
        $new_program_data['billing_city']     = $_program->billing_city;
        $new_program_data['billing_state']    = $_program->billing_state;
        $new_program_data['billing_zip']      = $_program->billing_zip;
        $new_program_data['billing_country']  = $_program->billing_country;
        $new_program_data['shipping_street']  = clear_textarea_breaks($_program->shipping_street);
        $new_program_data['shipping_city']    = $_program->shipping_city;
        $new_program_data['shipping_state']   = $_program->shipping_state;
        $new_program_data['shipping_zip']     = $_program->shipping_zip;
        $new_program_data['shipping_country'] = $_program->shipping_country;
        if ($_program->include_shipping == 1) {
            $new_program_data['include_shipping'] = $_program->include_shipping;
        }
        $new_program_data['show_shipping_on_program'] = $_program->show_shipping_on_program;
        // Set to unpaid state automatically
        $new_program_data['state']     = 1;
        $new_program_data['clientnote'] = $_program->clientnote;
        $new_program_data['adminnote']  = '';
        $new_program_data['newitems']   = [];
        $custom_fields_items             = get_custom_fields('items');
        $key                             = 1;
        foreach ($_program->items as $item) {
            $new_program_data['newitems'][$key]['description']      = $item['description'];
            $new_program_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_program_data['newitems'][$key]['qty']              = $item['qty'];
            $new_program_data['newitems'][$key]['unit']             = $item['unit'];
            $new_program_data['newitems'][$key]['taxname']          = [];
            $taxes                                                   = get_program_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_program_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_program_data['newitems'][$key]['rate']  = $item['rate'];
            $new_program_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_program_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_program_data);
        if ($id) {
            $custom_fields = get_custom_fields('program');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_program->id, $field['id'], 'program', false);
                if ($value == '') {
                    continue;
                }

                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'program',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($_program->id, 'program');
            handle_tags_save($tags, $id, 'program');

            log_activity('Copied Program ' . format_program_number($_program->id));

            return $id;
        }

        return false;
    }

    /**
     * Performs programs totals state
     * @param array $data
     * @return array
     */
    public function get_programs_total($data)
    {
        $states            = $this->get_states();
        $has_permission_view = has_permission('programs', '', 'view');
        $this->load->model('currencies_model');
        if (isset($data['currency'])) {
            $currencyid = $data['currency'];
        } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
            $currencyid = $this->clients_model->get_customer_default_currency($data['customer_id']);
            if ($currencyid == 0) {
                $currencyid = $this->currencies_model->get_base_currency()->id;
            }
        } elseif (isset($data['project_id']) && $data['project_id'] != '') {
            $this->load->model('projects_model');
            $currencyid = $this->projects_model->get_currency($data['project_id'])->id;
        } else {
            $currencyid = $this->currencies_model->get_base_currency()->id;
        }

        $currency = get_currency($currencyid);
        $where    = '';
        if (isset($data['customer_id']) && $data['customer_id'] != '') {
            $where = ' AND clientid=' . $data['customer_id'];
        }

        if (isset($data['project_id']) && $data['project_id'] != '') {
            $where .= ' AND project_id=' . $data['project_id'];
        }

        if (!$has_permission_view) {
            $where .= ' AND ' . get_programs_where_sql_for_staff(get_staff_user_id());
        }

        $sql = 'SELECT';
        foreach ($states as $program_state) {
            $sql .= '(SELECT SUM(total) FROM ' . db_prefix() . 'programs WHERE state=' . $program_state;
            $sql .= ' AND currency =' . $this->db->escape_str($currencyid);
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', array_map(function ($year) {
                    return get_instance()->db->escape_str($year);
                }, $data['years'])) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $program_state . '",';
        }

        $sql     = substr($sql, 0, -1);
        $result  = $this->db->query($sql)->result_array();
        $_result = [];
        $i       = 1;
        foreach ($result as $key => $val) {
            foreach ($val as $state => $total) {
                $_result[$i]['total']         = $total;
                $_result[$i]['symbol']        = $currency->symbol;
                $_result[$i]['currency_name'] = $currency->name;
                $_result[$i]['state']        = $state;
                $i++;
            }
        }
        $_result['currencyid'] = $currencyid;

        return $_result;
    }

    /**
     * Insert new program to database
     * @param array $data invoiec data
     * @return mixed - false if not insert, program ID if succes
     */
    public function add($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');

        $data['addedfrom'] = get_staff_user_id();

        $data['prefix'] = get_option('program_prefix');

        $data['number_format'] = get_option('program_number_format');

        $save_and_send = isset($data['save_and_send']);

        $programRequestID = false;
        if (isset($data['program_request_id'])) {
            $programRequestID = $data['program_request_id'];
            unset($data['program_request_id']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['hash'] = app_generate_hash();
        $tags         = isset($data['tags']) ? $data['tags'] : '';

        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        $data = $this->map_shipping_columns($data);

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        if (isset($data['shipping_street'])) {
            $data['shipping_street'] = trim($data['shipping_street']);
            $data['shipping_street'] = nl2br($data['shipping_street']);
        }

        $hook = hooks()->apply_filters('before_program_added', [
            'data'  => $data,
            'items' => $items,
        ]);

        $data  = $hook['data'];
        $items = $hook['items'];

        $this->db->insert(db_prefix() . 'programs', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update next program number in settings
            $this->db->where('name', 'next_program_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            if ($programRequestID !== false && $programRequestID != '') {
                $this->load->model('program_request_model');
                $completedStatus = $this->program_request_model->get_state_by_flag('completed');
                $this->program_request_model->update_request_state([
                    'requestid' => $programRequestID,
                    'state'    => $completedStatus->id,
                ]);
            }

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            handle_tags_save($tags, $insert_id, 'program');

            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'program')) {
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'program');
                }
            }

            update_sales_total_tax_column($insert_id, 'program', db_prefix() . 'programs');
            $this->log_program_activity($insert_id, 'program_activity_created');

            hooks()->do_action('after_program_added', $insert_id);

            if ($save_and_send === true) {
                $this->send_program_to_client($insert_id, '', true, '', true);
            }

            return $insert_id;
        }

        return false;
    }

    /**
     * Get item by id
     * @param mixed $id item id
     * @return object
     */
    public function get_program_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'program_item')->row();
    }

    /**
     * Update program data
     * @param array $data program data
     * @param mixed $id programid
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows = 0;

        $data['number'] = trim($data['number']);

        $original_program = $this->get($id);

        $original_state = $original_program->state;

        $original_number = $original_program->number;

        $original_number_formatted = format_program_number($id);

        $save_and_send = isset($data['save_and_send']);

        $items = [];
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
        }

        $newitems = [];
        if (isset($data['newitems'])) {
            $newitems = $data['newitems'];
            unset($data['newitems']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'program')) {
                $affectedRows++;
            }
        }

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        $data['shipping_street'] = trim($data['shipping_street']);
        $data['shipping_street'] = nl2br($data['shipping_street']);

        $data = $this->map_shipping_columns($data);

        $hook = hooks()->apply_filters('before_program_updated', [
            'data'          => $data,
            'items'         => $items,
            'newitems'      => $newitems,
            'removed_items' => isset($data['removed_items']) ? $data['removed_items'] : [],
        ], $id);

        $data                  = $hook['data'];
        $items                 = $hook['items'];
        $newitems              = $hook['newitems'];
        $data['removed_items'] = $hook['removed_items'];

        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            $original_item = $this->get_program_item($remove_item_id);
            if (handle_removed_sales_item_post($remove_item_id, 'program')) {
                $affectedRows++;
                $this->log_program_activity($id, 'invoice_program_activity_removed_item', false, serialize([
                    $original_item->description,
                ]));
            }
        }

        unset($data['removed_items']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'programs', $data);

        if ($this->db->affected_rows() > 0) {
            // Check for state change
            if ($original_state != $data['state']) {
                $this->log_program_activity($original_program->id, 'not_program_state_updated', false, serialize([
                    '<original_state>' . $original_state . '</original_state>',
                    '<new_state>' . $data['state'] . '</new_state>',
                ]));
                if ($data['state'] == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'programs', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
            }
            if ($original_number != $data['number']) {
                $this->log_program_activity($original_program->id, 'program_activity_number_changed', false, serialize([
                    $original_number_formatted,
                    format_program_number($original_program->id),
                ]));
            }
            $affectedRows++;
        }

        foreach ($items as $key => $item) {
            $original_item = $this->get_program_item($item['itemid']);

            if (update_sales_item_post($item['itemid'], $item, 'item_order')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'unit')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'rate')) {
                $this->log_program_activity($id, 'invoice_program_activity_updated_item_rate', false, serialize([
                    $original_item->rate,
                    $item['rate'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'qty')) {
                $this->log_program_activity($id, 'invoice_program_activity_updated_qty_item', false, serialize([
                    $item['description'],
                    $original_item->qty,
                    $item['qty'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'description')) {
                $this->log_program_activity($id, 'invoice_program_activity_updated_item_short_description', false, serialize([
                    $original_item->description,
                    $item['description'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'long_description')) {
                $this->log_program_activity($id, 'invoice_program_activity_updated_item_long_description', false, serialize([
                    $original_item->long_description,
                    $item['long_description'],
                ]));
                $affectedRows++;
            }

            if (isset($item['custom_fields'])) {
                if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                    $affectedRows++;
                }
            }

            if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'program')) {
                    $affectedRows++;
                }
            } else {
                $item_taxes        = get_program_item_taxes($item['itemid']);
                $_item_taxes_names = [];
                foreach ($item_taxes as $_item_tax) {
                    array_push($_item_taxes_names, $_item_tax['taxname']);
                }

                $i = 0;
                foreach ($_item_taxes_names as $_item_tax) {
                    if (!in_array($_item_tax, $item['taxname'])) {
                        $this->db->where('id', $item_taxes[$i]['id'])
                            ->delete(db_prefix() . 'item_tax');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                    $i++;
                }
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'program')) {
                    $affectedRows++;
                }
            }
        }

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'program')) {
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'program');
                $this->log_program_activity($id, 'invoice_program_activity_added_item', false, serialize([
                    $item['description'],
                ]));
                $affectedRows++;
            }
        }

        if ($affectedRows > 0) {
            update_sales_total_tax_column($id, 'program', db_prefix() . 'programs');
        }

        if ($save_and_send === true) {
            $this->send_program_to_client($id, '', true, '', true);
        }

        if ($affectedRows > 0) {
            hooks()->do_action('after_program_updated', $id);

            return true;
        }

        return false;
    }

    public function mark_action_state($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'programs', [
            'state' => $action,
        ]);

        $notifiedUsers = [];

        if ($this->db->affected_rows() > 0) {
            $program = $this->get($id);
            if ($client == true) {
                $this->db->where('staffid', $program->addedfrom);
                $this->db->or_where('staffid', $program->inspector_staff_id);
                $staff_program = $this->db->get(db_prefix() . 'staff')->result_array();

                $invoiceid = false;
                $invoiced  = false;

                $contact_id = !is_client_logged_in()
                    ? get_primary_contact_user_id($program->clientid)
                    : get_contact_user_id();

                if ($action == 4) {
                    if (get_option('program_auto_convert_to_inspection_on_client_accept') == 1) {
                        $invoiceid = $this->convert_to_inspection($id, true);
                        $this->load->model('invoices_model');
                        if ($invoiceid) {
                            $invoiced = true;
                            $invoice  = $this->invoices_model->get($invoiceid);
                            $this->log_program_activity($id, 'program_activity_client_accepted_and_converted', true, serialize([
                                '<a href="' . admin_url('invoices/list_invoices/' . $invoiceid) . '">' . format_invoice_number($invoice->id) . '</a>',
                            ]));
                        }
                    } else {
                        $this->log_program_activity($id, 'program_activity_client_accepted', true);
                    }

                    // Send thank you email to all contacts with permission programs
                    $contacts = $this->clients_model->get_contacts($program->clientid, ['active' => 1, 'program_emails' => 1]);

                    foreach ($contacts as $contact) {
                        send_mail_template('program_accepted_to_customer', $program, $contact);
                    }

                    foreach ($staff_program as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_program_customer_accepted',
                            'link'            => 'programs/list_programs/' . $id,
                            'additional_data' => serialize([
                                format_program_number($program->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }

                        send_mail_template('program_accepted_to_staff', $program, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    hooks()->do_action('program_accepted', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                } elseif ($action == 3) {
                    foreach ($staff_program as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_program_customer_declined',
                            'link'            => 'programs/list_programs/' . $id,
                            'additional_data' => serialize([
                                format_program_number($program->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that customer declined program
                        send_mail_template('program_declined_to_staff', $program, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    $this->log_program_activity($id, 'program_activity_client_declined', true);
                    hooks()->do_action('program_declined', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                }
            } else {
                if ($action == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'programs', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
                // Admin marked program
                $this->log_program_activity($id, 'program_activity_marked', false, serialize([
                    '<state>' . $action . '</state>',
                ]));

                return true;
            }
        }

        return false;
    }

    /**
     * Get program attachments
     * @param mixed $program_id
     * @param string $id attachment id
     * @return mixed
     */
    public function get_attachments($program_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $program_id);
        }
        $this->db->where('rel_type', 'program');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete program attachment
     * @param mixed $id attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('program') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Program Attachment Deleted [ProgramID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('program') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('program') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('program') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete program items and all connections
     * @param mixed $id programid
     * @return boolean
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_program') == 1 && $simpleDelete == false) {
            if (!is_last_program($id)) {
                return false;
            }
        }
        $program = $this->get($id);
        if (!is_null($program->invoiceid) && $simpleDelete == false) {
            return [
                'is_invoiced_program_delete_error' => true,
            ];
        }
        hooks()->do_action('before_program_deleted', $id);

        $number = format_program_number($id);

        $this->clear_signature($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'programs');

        if ($this->db->affected_rows() > 0) {
            if (!is_null($program->short_link)) {
                app_archive_short_link($program->short_link);
            }

            if (get_option('program_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_program_number = get_option('next_program_number');
                if ($current_next_program_number > 1) {
                    // Decrement next program number to
                    $this->db->where('name', 'next_program_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }

            if (total_rows(db_prefix() . 'proposals', [
                    'program_id' => $id,
                ]) > 0) {
                $this->db->where('program_id', $id);
                $program = $this->db->get(db_prefix() . 'proposals')->row();
                $this->db->where('id', $program->id);
                $this->db->update(db_prefix() . 'proposals', [
                    'program_id'    => null,
                    'date_converted' => null,
                ]);
            }

            delete_tracked_emails($id, 'program');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="program" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'program');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'program');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('rel_type', 'program');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->where('rel_type', 'program');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'program');
            $this->db->delete(db_prefix() . 'itemable');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'program');
            $this->db->delete(db_prefix() . 'item_tax');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'program');
            $this->db->delete(db_prefix() . 'sales_activity');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'program');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'program');
            $this->db->delete('programd_emails');

            // Get related tasks
            $this->db->where('rel_type', 'program');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($simpleDelete == false) {
                log_activity('Programs Deleted [Number: ' . $number . ']');
            }

            return true;
        }

        return false;
    }

    /**
     * Set program to sent when email is successfuly sended to client
     * @param mixed $id programid
     */
    public function set_program_sent($id, $emails_sent = [])
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'programs', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);

        $this->log_program_activity($id, 'invoice_program_activity_sent_to_client', false, serialize([
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
        ]));

        // Update program state to sent
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'programs', [
            'state' => 2,
        ]);

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'program');
        $this->db->delete('programd_emails');
    }

    /**
     * Send expiration reminder to customer
     * @param mixed $id program id
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $program        = $this->get($id);
        $program_number = format_program_number($program->id);
        set_mailing_constant();
        $pdf              = program_pdf($program);
        $attach           = $pdf->Output($program_number . '.pdf', 'S');
        $emails_sent      = [];
        $sms_sent         = false;
        $sms_reminder_log = [];

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'programs', [
            'is_expiry_notified' => 1,
        ]);

        $contacts = $this->clients_model->get_contacts($program->clientid, ['active' => 1, 'program_emails' => 1]);

        foreach ($contacts as $contact) {
            $template = mail_template('program_expiration_reminder', $program, $contact);

            $merge_fields = $template->get_merge_fields();

            $template->add_attachment([
                'attachment' => $attach,
                'filename'   => str_replace('/', '-', $program_number . '.pdf'),
                'type'       => 'application/pdf',
            ]);

            if ($template->send()) {
                array_push($emails_sent, $contact['email']);
            }

            if (can_send_sms_based_on_creation_date($program->datecreated)
                && $this->app_sms->trigger(SMS_TRIGGER_ESTIMATE_EXP_REMINDER, $contact['phonenumber'], $merge_fields)) {
                $sms_sent = true;
                array_push($sms_reminder_log, $contact['firstname'] . ' (' . $contact['phonenumber'] . ')');
            }
        }

        if (count($emails_sent) > 0 || $sms_sent) {
            if (count($emails_sent) > 0) {
                $this->log_program_activity($id, 'not_expiry_reminder_sent', false, serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                ]));
            }

            if ($sms_sent) {
                $this->log_program_activity($id, 'sms_reminder_sent_to', false, serialize([
                    implode(', ', $sms_reminder_log),
                ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Send program to client
     * @param mixed $id programid
     * @param string $template email template to sent
     * @param boolean $attachpdf attach program pdf or not
     * @return boolean
     */
    public function send_program_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $program = $this->get($id);

        if ($template_name == '') {
            $template_name = $program->sent == 0 ?
                'program_send_to_customer' :
                'program_send_to_customer_already_sent';
        }

        $program_number = format_program_number($program->id);

        $emails_sent = [];
        $send_to     = [];

        // Manually is used when sending the program via add/edit area button Save & Send
        if (!DEFINED('CRON') && $manually === false) {
            $send_to = $this->input->post('sent_to');
        } elseif (isset($GLOBALS['programd_email_contacts'])) {
            $send_to = $GLOBALS['programd_email_contacts'];
        } else {
            $contacts = $this->clients_model->get_contacts(
                $program->clientid,
                ['active' => 1, 'program_emails' => 1]
            );

            foreach ($contacts as $contact) {
                array_push($send_to, $contact['id']);
            }
        }

        $state_auto_updated = false;
        $state_now          = $program->state;

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            // Auto update state to sent in case when user sends the program is with state draft
            if ($state_now == 1) {
                $this->db->where('id', $program->id);
                $this->db->update(db_prefix() . 'programs', [
                    'state' => 2,
                ]);
                $state_auto_updated = true;
            }

            if ($attachpdf) {
                $_pdf_program = $this->get($program->id);
                set_mailing_constant();
                $pdf = program_pdf($_pdf_program);

                $attach = $pdf->Output($program_number . '.pdf', 'S');
            }

            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    if (!$contact) {
                        continue;
                    }

                    $template = mail_template($template_name, $program, $contact, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_program_to_customer_file_name', [
                            'file_name' => str_replace('/', '-', $program_number . '.pdf'),
                            'program'  => $_pdf_program,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            $this->set_program_sent($id, $emails_sent);
            hooks()->do_action('program_sent', $id);

            return true;
        }

        if ($state_auto_updated) {
            // Program not send to customer but the state was previously updated to sent now we need to revert back to draft
            $this->db->where('id', $program->id);
            $this->db->update(db_prefix() . 'programs', [
                'state' => 1,
            ]);
        }

        return false;
    }

    /**
     * All program activity
     * @param mixed $id programid
     * @return array
     */
    public function get_program_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'program');
        $this->db->order_by('date', 'asc');

        return $this->db->get(db_prefix() . 'sales_activity')->result_array();
    }

    /**
     * Log program activity to database
     * @param mixed $id programid
     * @param string $description activity description
     */
    public function log_program_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'sales_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'program',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Updates pipeline order when drag and drop
     * @param mixe $data $_POST data
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_state($data['state'], $data['programid']);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', 'programs', $data['state']);
    }

    /**
     * Get program unique year for filtering
     * @return array
     */
    public function get_programs_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'programs ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_program'] = 1;
            $data['include_shipping']          = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_program']) && ($data['show_shipping_on_program'] == 1 || $data['show_shipping_on_program'] == 'on')) {
                $data['show_shipping_on_program'] = 1;
            } else {
                $data['show_shipping_on_program'] = 0;
            }
        }

        return $data;
    }

    public function do_kanban_query($state, $search = '', $page = 1, $sort = [], $count = false)
    {
        _deprecated_function('Programs_model::do_kanban_query', '2.9.2', 'ProgramsPipeline class');

        $kanBan = (new ProgramsPipeline($state))
            ->search($search)
            ->page($page)
            ->sortBy($sort['sort'] ?? null, $sort['sort_by'] ?? null);

        if ($count) {
            return $kanBan->countAll();
        }

        return $kanBan->get();
    }


    public function inspection_add_inspection_item($data){
        $category = get_option('tag_id_'.$data['tag_id']);
        $user_id = get_option('default_inspection_assigned_' . $category);
        $ahli_k3_nama = get_staff_full_name($user_id);
        $ahli_k3_skp = get_option('default_inspection_skp_' . $category);
        
        $this->db->insert(db_prefix() . 'inspection_items', [
                'inspection_id'      => $data['inspection_id'],
                'project_id' => $data['project_id'],
                'task_id'              => $data['task_id'],
                'category'              => $category,
                'tag_id'              => $data['tag_id'],
                'ahli_k3_nama'              => $ahli_k3_nama,
                'ahli_k3_skp'              => $ahli_k3_skp]);
    }

    public function program_add_program_item($data){

        log_activity(json_encode($data));
        /*
        $category = get_option('tag_id_'.$data['tag_id']);
        $user_id = get_option('default_inspection_assigned_' . $category);
        $ahli_k3_nama = get_staff_full_name($user_id);
        $ahli_k3_skp = get_option('default_inspection_skp_' . $category);
        
        $this->db->insert(db_prefix() . 'program_items', [
                'inspection_id'      => $data['inspection_id'],
                'project_id' => $data['project_id'],
                'task_id'              => $data['task_id'],
                'category'              => $category,
                'tag_id'              => $data['tag_id'],
                'ahli_k3_nama'              => $ahli_k3_nama,
                'ahli_k3_skp'              => $ahli_k3_skp]);
        */
        $peralatan = get_peralatan($data['peralatan_id']);
        $data['nama_pesawat'] = $peralatan->subject;
        $data['jenis_pesawat'] = $peralatan->jenis_pesawat;

        //$data['kelompok_alat'] = get_kelompok_alat($peralatan->jenis_pesawat_id);
        $kelompok_alat = get_kelompok_alat($peralatan->kelompok_alat_id);
        
        $data['kelompok_alat'] = $kelompok_alat[0]['name'];
        $data['nomor_seri'] = $peralatan->nomor_seri;
        $data['nomor_unit'] = $peralatan->nomor_unit;
        $data['addedfrom'] = get_staff_user_id();


         log_activity(json_encode($data));
         

        $this->db->insert(db_prefix() . 'program_items', $data);

    }


    /**
     * Get the programs for the client given
     *
     * @param  integer|null $staffId
     * @param  integer $days
     *
     * @return array
     */
    public function get_client_programs($client = null)
    {
        /*
        if ($staffId && ! staff_can('view', 'programs', $staffId)) {
            $this->db->where('addedfrom', $staffId);
        }
        */

        $this->db->select(db_prefix() . 'programs.id,' . db_prefix() . 'programs.number,' . db_prefix() . 'programs.state,' . db_prefix() . 'clients.userid,' . db_prefix() . 'programs.hash,' . db_prefix() . 'projects.name,' . db_prefix() . 'programs.date');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'programs.clientid', 'left');
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . db_prefix() . 'programs.project_id', 'left');
        $this->db->where('date IS NOT NULL');
        $this->db->where(db_prefix() . 'programs.state > ',1);
        $this->db->where(db_prefix() . 'programs.clientid =', $client->userid);
        
        return $this->db->get(db_prefix() . 'programs')->result_array();

    }


}
