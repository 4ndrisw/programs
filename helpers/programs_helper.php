<?php
defined('BASEPATH') or exit('No direct script access allowed');


function programs_notification()
{
    $CI = &get_instance();
    $CI->load->model('programs/programs_model');
    $programs = $CI->programs_model->get('', true);
    /*
    foreach ($programs as $goal) {
        $achievement = $CI->programs_model->calculate_goal_achievement($goal['id']);

        if ($achievement['percent'] >= 100) {
            if (date('Y-m-d') >= $goal['end_date']) {
                if ($goal['notify_when_achieve'] == 1) {
                    $CI->programs_model->notify_staff_members($goal['id'], 'success', $achievement);
                } else {
                    $CI->programs_model->mark_as_notified($goal['id']);
                }
            }
        } else {
            // not yet achieved, check for end date
            if (date('Y-m-d') > $goal['end_date']) {
                if ($goal['notify_when_fail'] == 1) {
                    $CI->programs_model->notify_staff_members($goal['id'], 'failed', $achievement);
                } else {
                    $CI->programs_model->mark_as_notified($goal['id']);
                }
            }
        }
    }
    */
}


/**
 * Function that return program item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_program_item_taxes($itemid)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'program');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}

/**
 * Get Program short_url
 * @since  Version 2.7.3
 * @param  object $program
 * @return string Url
 */
function get_program_shortlink($program)
{
    $long_url = site_url("program/{$program->id}/{$program->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if program has short link, if yes return short link
    if (!empty($program->short_link)) {
        return $program->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url'  => $long_url,
        'title'     => format_program_number($program->id)
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $program->id);
        $CI->db->update(db_prefix() . 'programs', [
            'short_link' => $short_link
        ]);
        return $short_link;
    }
    return $long_url;
}

/**
 * Check program restrictions - hash, clientid
 * @param  mixed $id   program id
 * @param  string $hash program hash
 */
function check_program_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('programs_model');
    if (!$hash || !$id) {
        show_404();
    }
    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_program_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }
    $program = $CI->programs_model->get($id);
    if (!$program || ($program->hash != $hash)) {
        show_404();
    }
    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_program_only_logged_in') == 1) {
            if ($program->clientid != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Check if program email template for expiry reminders is enabled
 * @return boolean
 */
function is_programs_email_expiry_reminder_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'program-expiry-reminder', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending program expiry reminders
 * Will be either email or SMS
 * @return boolean
 */
function is_programs_expiry_reminders_enabled()
{
    return is_programs_email_expiry_reminder_enabled() || is_sms_trigger_active(SMS_TRIGGER_PRGEDULE_EXP_REMINDER);
}

/**
 * Return RGBa program state color for PDF documents
 * @param  mixed $state_id current program state
 * @return string
 */
function program_state_color_pdf($state_id)
{
    if ($state_id == 1) {
        $stateColor = '119, 119, 119';
    } elseif ($state_id == 2) {
        // Sent
        $stateColor = '3, 169, 244';
    } elseif ($state_id == 3) {
        //Declines
        $stateColor = '252, 45, 66';
    } elseif ($state_id == 4) {
        //Accepted
        $stateColor = '0, 191, 54';
    } else {
        // Expired
        $stateColor = '255, 111, 0';
    }

    return hooks()->apply_filters('program_state_pdf_color', $stateColor, $state_id);
}

/**
 * Format program state
 * @param  integer  $state
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_program_state($state, $classes = '', $label = true)
{
    $id          = $state;
    $label_class = program_state_color_class($state);
    $state      = program_state_by_id($state);
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-state program-state-' . $id . ' program-state-' . $label_class . '">' . $state . '</span>';
    }

    return $state;
}

/**
 * Return program state translated by passed state id
 * @param  mixed $id program state id
 * @return string
 */
function program_state_by_id($id)
{
    $state = '';
    if ($id == 1) {
        $state = _l('program_state_draft');
    } elseif ($id == 2) {
        $state = _l('program_state_sent');
    } elseif ($id == 3) {
        $state = _l('program_state_declined');
    } elseif ($id == 4) {
        $state = _l('program_state_accepted');
    } elseif ($id == 5) {
        // state 5
        $state = _l('program_state_expired');
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $state = _l('not_sent_indicator');
            }
        }
    }

    return hooks()->apply_filters('program_state_label', $state, $id);
}

/**
 * Return program state color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function program_state_color_class($id, $replace_default_by_muted = false)
{
    $class = '';
    if ($id == 1) {
        $class = 'default';
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'danger';
    } elseif ($id == 4) {
        $class = 'success';
    } elseif ($id == 5) {
        // state 5
        $class = 'warning';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $class = 'default';
                if ($replace_default_by_muted == true) {
                    $class = 'muted';
                }
            }
        }
    }

    return hooks()->apply_filters('program_state_color_class', $class, $id);
}

/**
 * Check if the program id is last invoice
 * @param  mixed  $id programid
 * @return boolean
 */
function is_last_program($id)
{
    $CI = &get_instance();
    $CI->db->select('id')->from(db_prefix() . 'programs')->order_by('id', 'desc')->limit(1);
    $query            = $CI->db->get();
    $last_program_id = $query->row()->id;
    if ($last_program_id == $id) {
        return true;
    }

    return false;
}

/**
 * Format program number based on description
 * @param  mixed $id
 * @return string
 */
function format_program_number($id)
{
    $CI = &get_instance();
    $CI->db->select('date,number,prefix,number_format')->from(db_prefix() . 'programs')->where('id', $id);
    $program = $CI->db->get()->row();

    if (!$program) {
        return '';
    }

    $number = program_number_format($program->number, $program->number_format, $program->prefix, $program->date);

    return hooks()->apply_filters('format_program_number', $number, [
        'id'       => $id,
        'program' => $program,
    ]);
}


function program_number_format($number, $format, $applied_prefix, $date)
{
    $originalNumber = $number;
    $prefixPadding  = get_option('number_padding_prefixes');

    if ($format == 1) {
        // Number based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 2) {
        // Year based
        $number = $applied_prefix . date('Y', strtotime($date)) . '.' . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 3) {
        // Number-yy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '-' . date('y', strtotime($date));
    } elseif ($format == 4) {
        // Number-mm-yyyy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '.' . date('m', strtotime($date)) . '.' . date('Y', strtotime($date));
    }

    return hooks()->apply_filters('program_number_format', $number, [
        'format'         => $format,
        'date'           => $date,
        'number'         => $originalNumber,
        'prefix_padding' => $prefixPadding,
    ]);
}

/**
 * Calculate programs percent by state
 * @param  mixed $state          program state
 * @return array
 */
function get_programs_percent_by_state($state, $program_id = null)
{
    $has_permission_view = has_permission('programs', '', 'view');
    $where               = '';

    if (isset($program_id)) {
        $where .= 'program_id=' . get_instance()->db->escape_str($program_id) . ' AND ';
    }
    if (!$has_permission_view) {
        $where .= get_programs_where_sql_for_staff(get_staff_user_id());
    }

    $where = trim($where);

    if (endsWith($where, ' AND')) {
        $where = substr_replace($where, '', -3);
    }

    $total_programs = total_rows(db_prefix() . 'programs', $where);

    $data            = [];
    $total_by_state = 0;

    if (!is_numeric($state)) {
        if ($state == 'not_sent') {
            $total_by_state = total_rows(db_prefix() . 'programs', 'sent=0 AND state NOT IN(2,3,4)' . ($where != '' ? ' AND (' . $where . ')' : ''));
        }
    } else {
        $whereByStatus = 'state=' . $state;
        if ($where != '') {
            $whereByStatus .= ' AND (' . $where . ')';
        }
        $total_by_state = total_rows(db_prefix() . 'programs', $whereByStatus);
    }

    $percent                 = ($total_programs > 0 ? number_format(($total_by_state * 100) / $total_programs, 2) : 0);
    $data['total_by_state'] = $total_by_state;
    $data['percent']         = $percent;
    $data['total']           = $total_programs;

    return $data;
}

function allow_inspector_staff_view_programs_in_institution($staff_user_id){
    $CI = &get_instance();
    $staff_inspector_id = get_inspector_id_by_staff_id($staff_user_id);
    $whereUser .= 'inspector_id =' . $CI->db->escape_str($staff_inspector_id);
    return $whereUser;
}

function inspector_staff_only_view_programs_assigned($staff_user_id){
    $CI = &get_instance();
    $whereUser .= 'inspector_staff_id =' . $CI->db->escape_str($staff_inspector_id);
    return $whereUser;
}

function get_programs_where_sql_for_staff($staff_id)
{
    $CI = &get_instance();
    $has_permission_view_own             = has_permission('programs', '', 'view_own');
    $allow_staff_view_programs_assigned = get_option('allow_staff_view_programs_assigned');
    $whereUser                           = '';
    if ($has_permission_view_own) {
        $whereUser = '((' . db_prefix() . 'programs.addedfrom=' . $CI->db->escape_str($staff_id) . ' AND ' . db_prefix() . 'programs.addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature = "programs" AND capability="view_own"))';
        if ($allow_staff_view_programs_assigned == 1) {
            $whereUser .= ' OR '. db_prefix() . 'programs.inspector_staff_id=' . $CI->db->escape_str($staff_id);
        }
        $whereUser .= ')';
    } else {
        $whereUser .= db_prefix() . 'programs.inspector_staff_id=' . $CI->db->escape_str($staff_id);
    }

    return $whereUser;
}
/**
 * Check if staff member have assigned programs / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_programs($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-programs-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'programs', ['inspector_staff_id' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-programs-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}
/**
 * Check if staff member can view program
 * @param  mixed $id program id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_program($id, $staff_id = false)
{
    $CI = &get_instance();

    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    if (has_permission('programs', $staff_id, 'view')) {
        return true;
    }

    if(is_client_logged_in()){

        $CI = &get_instance();
        $CI->load->model('programs_model');
       
        $program = $CI->programs_model->get($id);
        if (!$program) {
            show_404();
        }
        // Do one more check
        if (get_option('view_programs_only_logged_in') == 1) {
            if ($program->clientid != get_client_user_id()) {
                show_404();
            }
        }
    
        return true;
    }
    
    $CI->db->select('id, addedfrom, number, inspector_staff_id');
    $CI->db->from(db_prefix() . 'programs');
    $CI->db->where('id', $id);
    $program = $CI->db->get()->row();
    
    if ((has_permission('programs', $staff_id, 'view_own') && $program->addedfrom == $staff_id)
        || ($program->inspector_staff_id == $staff_id && get_option('allow_staff_view_programs_assigned') == '1')
    ) {
        return true;
    }

    return false;
}


/**
 * Prepare general program pdf
 * @since  Version 1.0.2
 * @param  object $program program as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function program_pdf($program, $tag = '')
{
    return app_pdf('program',  module_libs_path(PROGRAMS_MODULE_NAME) . 'pdf/Program_pdf', $program, $tag);
}


/**
 * Prepare general program pdf
 * @since  Version 1.0.2
 * @param  object $program program as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function program_office_pdf($program, $tag = '')
{
    return app_pdf('program',  module_libs_path(PROGRAMS_MODULE_NAME) . 'pdf/Program_office_pdf', $program, $tag);
}



/**
 * Get items table for preview
 * @param  object  $transaction   e.q. invoice, program from database result row
 * @param  string  $type          type, e.q. invoice, program, proposal
 * @param  string  $for           where the items will be shown, html or pdf
 * @param  boolean $admin_preview is the preview for admin area
 * @return object
 */
function get_program_items_table_data($transaction, $type, $for = 'html', $admin_preview = false)
{
    include_once(module_libs_path(PROGRAMS_MODULE_NAME) . 'Program_items_table.php');

    $class = new Program_items_table($transaction, $type, $for, $admin_preview);

    $class = hooks()->apply_filters('items_table_class', $class, $transaction, $type, $for, $admin_preview);

    if (!$class instanceof App_items_table_template) {
        show_error(get_class($class) . ' must be instance of "Program_items_template"');
    }

    return $class;
}



/**
 * Add new item do database, used for proposals,programs,credit notes,invoices
 * This is repetitive action, that's why this function exists
 * @param array $item     item from $_POST
 * @param mixed $rel_id   relation id eq. invoice id
 * @param string $rel_type relation type eq invoice
 */
function add_new_program_item_post($item, $rel_id, $rel_type)
{

    $CI = &get_instance();

    $CI->db->insert(db_prefix() . 'itemable', [
                    'description'      => $item['description'],
                    'long_description' => nl2br($item['long_description']),
                    'qty'              => $item['qty'],
                    'rel_id'           => $rel_id,
                    'rel_type'         => $rel_type,
                    'item_order'       => $item['order'],
                    'unit'             => isset($item['unit']) ? $item['unit'] : 'unit',
                ]);

    $id = $CI->db->insert_id();

    return $id;
}

/**
 * Update program item from $_POST 
 * @param  mixed $item_id item id to update
 * @param  array $data    item $_POST data
 * @param  string $field   field is require to be passed for long_description,rate,item_order to do some additional checkings
 * @return boolean
 */
function update_program_item_post($item_id, $data, $field = '')
{
    $update = [];
    if ($field !== '') {
        if ($field == 'long_description') {
            $update[$field] = nl2br($data[$field]);
        } elseif ($field == 'rate') {
            $update[$field] = number_format($data[$field], get_decimal_places(), '.', '');
        } elseif ($field == 'item_order') {
            $update[$field] = $data['order'];
        } else {
            $update[$field] = $data[$field];
        }
    } else {
        $update = [
            'item_order'       => $data['order'],
            'description'      => $data['description'],
            'long_description' => nl2br($data['long_description']),
            'qty'              => $data['qty'],
            'unit'             => $data['unit'],
        ];
    }

    $CI = &get_instance();
    $CI->db->where('id', $item_id);
    $CI->db->update(db_prefix() . 'itemable', $update);

    return $CI->db->affected_rows() > 0 ? true : false;
}


/**
 * Prepares email template preview $data for the view
 * @param  string $template    template class name
 * @param  mixed $customer_id_or_email customer ID to fetch the primary contact email or email
 * @return array
 */
function program_mail_preview_data($template, $customer_id_or_email, $mailClassParams = [])
{
    $CI = &get_instance();

    if (is_numeric($customer_id_or_email)) {
        $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($customer_id_or_email));
        $email   = $contact ? $contact->email : '';
    } else {
        $email = $customer_id_or_email;
    }

    $CI->load->model('emails_model');

    $data['template'] = $CI->app_mail_template->prepare($email, $template);
    $slug             = $CI->app_mail_template->get_default_property_value('slug', $template, $mailClassParams);

    $data['template_name'] = $slug;

    $template_result = $CI->emails_model->get(['slug' => $slug, 'language' => 'english'], 'row');

    $data['template_system_name'] = $template_result->name;
    $data['template_id']          = $template_result->emailtemplateid;

    $data['template_disabled'] = $template_result->active == 0;

    return $data;
}


/**
 * Function that return full path for upload based on passed type
 * @param  string $type
 * @return string
 */
function get_program_upload_path($type=NULL)
{
   $type = 'program';
   $path = PRGEDULE_ATTACHMENTS_FOLDER;
   
    return hooks()->apply_filters('get_upload_path_by_type', $path, $type);
}

/**
 * Remove and format some common used data for the program feature eq invoice,programs etc..
 * @param  array $data $_POST data
 * @return array
 */
function _format_data_program_feature($data)
{
    foreach (_get_program_feature_unused_names() as $u) {
        if (isset($data['data'][$u])) {
            unset($data['data'][$u]);
        }
    }

    if (isset($data['data']['date'])) {
        $data['data']['date'] = to_sql_date($data['data']['date']);
    }

    if (isset($data['data']['open_till'])) {
        $data['data']['open_till'] = to_sql_date($data['data']['open_till']);
    }

    if (isset($data['data']['duedate'])) {
        $data['data']['duedate'] = to_sql_date($data['data']['duedate']);
    }

    if (isset($data['data']['duedate'])) {
        $data['data']['duedate'] = to_sql_date($data['data']['duedate']);
    }

    if (isset($data['data']['clientnote'])) {
        $data['data']['clientnote'] = nl2br_save_html($data['data']['clientnote']);
    }

    if (isset($data['data']['terms'])) {
        $data['data']['terms'] = nl2br_save_html($data['data']['terms']);
    }

    if (isset($data['data']['adminnote'])) {
        $data['data']['adminnote'] = nl2br($data['data']['adminnote']);
    }

    foreach (['country', 'billing_country', 'shipping_country', 'program_id', 'assigned'] as $should_be_zero) {
        if (isset($data['data'][$should_be_zero]) && $data['data'][$should_be_zero] == '') {
            $data['data'][$should_be_zero] = 0;
        }
    }

    return $data;
}


/**
 * Unsed $_POST request names, mostly they are used as helper inputs in the form
 * The top function will check all of them and unset from the $data
 * @return array
 */
function _get_program_feature_unused_names()
{
    return [
        'taxname', 'description',
        'currency_symbol', 'price',
        'isedit', 'taxid',
        'long_description', 'unit',
        'rate', 'quantity',
        'item_select', 'tax',
        'billed_tasks', 'billed_expenses',
        'task_select', 'task_id',
        'expense_id', 'repeat_every_custom',
        'repeat_type_custom', 'bill_expenses',
        'save_and_send', 'merge_current_invoice',
        'cancel_merged_invoices', 'invoices_to_merge',
        'tags', 's_prefix', 'save_and_record_payment',
    ];
}

/**
 * When item is removed eq from invoice will be stored in removed_items in $_POST
 * With foreach loop this function will remove the item from database and it's taxes
 * @param  mixed $id       item id to remove
 * @param  string $rel_type item relation eq. invoice, program
 * @return boolena
 */
function handle_removed_program_item_post($id, $rel_type)
{
    $CI = &get_instance();

    $CI->db->where('id', $id);
    $CI->db->where('rel_type', $rel_type);
    $CI->db->delete(db_prefix() . 'itemable');
    if ($CI->db->affected_rows() > 0) {
        return true;
    }

    return false;
}

/**
 * Check if customer has program assigned
 * @param  mixed $customer_id customer id to check
 * @return boolean
 */
function program_has_programs($program_id)
{
    $totalProjectsProgramd = total_rows(db_prefix() . 'programs', 'program_id=' . get_instance()->db->escape_str($program_id));

    return ($totalProjectsProgramd > 0 ? true : false);
}


function inspector_staff_has_program($id, $staff_id){
    $CI = &get_instance();
    $CI->db->select('id');
    $CI->db->where('inspector_staff_id', $staff_id);
    return (bool)$CI->db->get(db_prefix() . 'programs')->result();
}


function get_program_states()
{
    $states = hooks()->apply_filters('before_get_program_states', [
        [
            'id'             => 1,
            'color'          => '#475569',
            'name'           => _l('program_state_1'),
            'order'          => 1,
            'filter_default' => true,
        ],
        [
            'id'             => 2,
            'color'          => '#2563eb',
            'name'           => _l('program_state_2'),
            'order'          => 2,
            'filter_default' => true,
        ],
        [
            'id'             => 3,
            'color'          => '#f97316',
            'name'           => _l('program_state_3'),
            'order'          => 3,
            'filter_default' => true,
        ],
        [
            'id'             => 4,
            'color'          => '#16a34a',
            'name'           => _l('program_state_4'),
            'order'          => 100,
            'filter_default' => false,
        ],
        [
            'id'             => 5,
            'color'          => '#94a3b8',
            'name'           => _l('program_state_5'),
            'order'          => 4,
            'filter_default' => false,
        ],
    ]);

    usort($states, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $states;
}

function programs_after_user_data_widge_tabs_content($widgets=''){

}