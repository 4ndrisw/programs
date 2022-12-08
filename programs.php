<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Programs
Description: Default module for defining programs
Version: 2.3.4
Requires at least: 2.3.*
*/

define('PROGRAMS_MODULE_NAME', 'programs');
define('PROGRAM_ATTACHMENTS_FOLDER', 'uploads/programs/');

hooks()->add_filter('before_program_updated', '_format_data_program_feature');
hooks()->add_filter('before_program_added', '_format_data_program_feature');

hooks()->add_action('after_cron_run', 'programs_notification');
hooks()->add_action('admin_init', 'programs_module_init_menu_items');
hooks()->add_action('admin_init', 'programs_permissions');
hooks()->add_action('admin_init', 'programs_settings_tab');
hooks()->add_action('clients_init', 'programs_clients_area_menu_items');
hooks()->add_filter('get_contact_permissions', 'programs_contact_permission',10,1);

hooks()->add_action('staff_member_deleted', 'programs_staff_member_deleted');

hooks()->add_filter('migration_tables_to_replace_old_links', 'programs_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'programs_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'programs_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'programs_add_dashboard_widget');
hooks()->add_filter('module_programs_action_links', 'module_programs_action_links');

//hooks()->add_action('after_user_data_widge_tabs_content', 'programs_after_user_data_widge_tabs_content');

function programs_add_dashboard_widget($widgets)
{
    $widgets[] = [
        'path'      => 'programs/widgets/program_this_week',
        'container' => 'left-8',
    ];

    return $widgets;
}


function programs_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->update(db_prefix() . 'programs', [
            'staff_id' => $data['transfer_data_to'],
        ]);
}

function programs_global_search_result_output($output, $data)
{
    if ($data['type'] == 'programs') {
        $output = '<a href="' . admin_url('programs/program/' . $data['result']['id']) . '">' . format_program_number($data['result']['id']) . '</a>';
    }

    return $output;
}

function programs_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('programs', '', 'view')) {

        // programs
        $CI->db->select()
           ->from(db_prefix() . 'programs')
           ->like(db_prefix() . 'programs.formatted_number', $q)->limit($limit);
        
        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'programs',
                'search_heading' => _l('programs'),
            ];
        
        if(isset($result[0]['result'][0]['id'])){
            return $result;
        }

        // programs
        $CI->db->select()->from(db_prefix() . 'programs')->like(db_prefix() . 'clients.company', $q)->or_like(db_prefix() . 'programs.formatted_number', $q)->limit($limit);
        $CI->db->join(db_prefix() . 'clients',db_prefix() . 'programs.clientid='.db_prefix() .'clients.userid', 'left');
        $CI->db->order_by(db_prefix() . 'clients.company', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'programs',
                'search_heading' => _l('programs'),
            ];
    }

    return $result;
}

function programs_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'programs',
                'field' => 'description',
            ];

    return $tables;
}

function programs_contact_permission($permissions){
        $item = array(
            'id'         => 10,
            'name'       => _l('programs'),
            'short_name' => 'programs',
        );
        $permissions[] = $item;
      return $permissions;
}

function programs_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'view_own'   => _l('permission_view_own'),
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('programs', $capabilities, _l('programs'));
}


/**
* Register activation module hook
*/
register_activation_hook(PROGRAMS_MODULE_NAME, 'programs_module_activation_hook');

function programs_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register deactivation module hook
*/
register_deactivation_hook(PROGRAMS_MODULE_NAME, 'programs_module_deactivation_hook');

function programs_module_deactivation_hook()
{

     log_activity( 'Hello, world! . programs_module_deactivation_hook ' );
}

//hooks()->add_action('deactivate_' . $module . '_module', $function);

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(PROGRAMS_MODULE_NAME, [PROGRAMS_MODULE_NAME]);

/**
 * Init programs module menu items in setup in admin_init hook
 * @return null
 */
function programs_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => _l('program'),
            'url'        => 'programs',
            'permission' => 'programs',
            'position'   => 57,
            ]);

    if (has_permission('programs', '', 'view') || has_permission('programs', '', 'view_own')) {
        $CI->app_menu->add_sidebar_menu_item('programs', [
                'slug'     => 'programs-tracking',
                'name'     => _l('programs'),
                'icon'     => 'fa-solid fa-bars-progress',
                'href'     => admin_url('programs'),
                'position' => 12,
        ]);
    }
}

function module_programs_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=programs') . '">' . _l('settings') . '</a>';

    return $actions;
}

function programs_clients_area_menu_items()
{   
    // Show menu item only if client is logged in
    if (is_client_logged_in() && has_contact_permission('programs')) {
        add_theme_menu_item('programs', [
                    'name'     => _l('programs'),
                    'href'     => site_url('programs/list'),
                    'position' => 15,
        ]);
    }
}

/**
 * [programs_settings_tab net menu item in setup->settings]
 * @return void
 */
function programs_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('programs', [
        'name'     => _l('settings_group_programs'),
        //'view'     => module_views_path(PROGRAMS_MODULE_NAME, 'admin/settings/includes/programs'),
        'view'     => 'programs/programs_settings',
        'position' => 51,
    ]);
}

$CI = &get_instance();

$CI->load->helper(PROGRAMS_MODULE_NAME . '/programs');
// Check if customers theme is enabled
if ($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='') {
    hooks()->add_action('app_customers_footer', 'program_dashboard__footer_js__component');
}

if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='programs') || $CI->uri->segment(1)=='programs'){
    $CI->app_css->add(PROGRAMS_MODULE_NAME.'-css', base_url('modules/'.PROGRAMS_MODULE_NAME.'/assets/css/'.PROGRAMS_MODULE_NAME.'.css'));
    $CI->app_scripts->add(PROGRAMS_MODULE_NAME.'-js', base_url('modules/'.PROGRAMS_MODULE_NAME.'/assets/js/'.PROGRAMS_MODULE_NAME.'.js'));
}

// Check if customers theme is enabled
if ($CI->uri->segment(1)=='programs') {
    hooks()->add_action('app_customers_head', 'program_client_head_includes');
    hooks()->add_action('app_customers_footer', 'program_client__footer_js__component');
}


/**
 * Theme clients footer includes
 * @return stylesheet
 */
function program_client_head_includes()
{
    echo '<link href="' . module_dir_url('programs', 'assets/css/clients.css') . '"  rel="stylesheet" type="text/css" >';
}

/**
 * Injects customers theme js components in footer
 * @return null
 */
function program_dashboard__footer_js__component()
{
    echo '<script src="' . module_dir_url('programs', 'assets/js/dashboards.js') . '"></script>';
}


/**
 * Injects customers theme js components in footer
 * @return null
 */
function program_client__footer_js__component()
{
    echo '<script src="' . module_dir_url('programs', 'assets/js/clients.js') . '"></script>';
}
