<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
    $CI = &get_instance();
    $CI->load->model('programs/programs_model');
    $staffid = get_staff_user_id();
    //$programs = get_client_type($staffid);

    $current_user = get_client_type($staffid);
    switch ($current_user->client_type) {
        case 'institution':
            $current_user->institution_id = $current_user->client_id;
            $current_user->inspector_id = FALSE;
            $current_user->inspector_staff_id = FALSE;
            $current_user->surveyor_id = FALSE;
            $current_user->clientid = FALSE;
            break;
        case 'inspector':
            $current_user->institution_id = FALSE;
            $current_user->inspector_id = $current_user->client_id;
            $current_user->inspector_staff_id = $staffid;
            $current_user->surveyor_id = FALSE;
            $current_user->clientid = FALSE;
            break;
        case 'surveyor':
            $current_user->institution_id = FALSE;
            $current_user->inspector_id = FALSE;
            $current_user->inspector_staff_id = FALSE;
            $current_user->surveyor_id = $current_user->client_id;
            $current_user->clientid = FALSE;
            break;
        case 'company':
            $current_user->institution_id = FALSE;
            $current_user->inspector_id = FALSE;
            $current_user->inspector_staff_id = FALSE;
            $current_user->surveyorid = FALSE;
            $current_user->clientid = $current_user->client_id;
            break;
        
        default:
            // code...
            break;
    }
?>

<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('program_this_week'); ?>" onload="initDataTable('.table-program_items', admin_url + 'programs/get_programs_this_week/'+ <?php echo $current_user->clientid ;?> + '/'+ <?php echo $current_user->institution_id ;?> + '/'+ <?php echo $current_user->inspector_id ;?> + '/'+ <?php echo $current_user->inspector_staff_id ;?> + '/'+ <?php echo $current_user->surveyor_id ;?> + '/', undefined, undefined, undefined,[1,'asc'])">
    <?php if(staff_can('view', 'programs') || staff_can('view_own', 'programs')) { ?>
    <div class="panel_s programs-expiring">
        <div class="panel-body padding-10">
            <p class="padding-5"><?php echo _l('program_this_week'); ?></p>
            <hr class="hr-panel-heading-dashboard">
            <?php if (!empty($current_user)) { ?>
                <div class="table-vertical-scroll">
                    <a href="<?php echo admin_url('programs'); ?>" class="mbot20 inline-block full-width"><?php echo _l('home_widget_view_all'); ?></a>
                </div>

                    <?php

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


                    $table_data = hooks()->apply_filters('programs_table_columns', $table_data);

                    render_datatable($table_data, isset($class) ? $class : 'programs');
                    } else { ?>
                        <div class="text-center padding-5">
                            <i class="fa fa-check fa-5x" aria-hidden="true"></i>
                            <h4><?php echo _l('no_program_this_week',["7"]) ; ?> </h4>
                        </div>
            <?php } ?>
        </div>
    </div>     
    <?php } ?>
</div>
