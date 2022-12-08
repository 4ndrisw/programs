<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
    $CI = &get_instance();
    $CI->load->model('programs/programs_model');
    $staffid = get_staff_user_id();
    $programs = $CI->programs_model->get_client_programs($staffid);

?>

<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('program_this_week'); ?>">
    <?php if(staff_can('view', 'programs') || staff_can('view_own', 'programs')) { ?>
    <div class="panel_s programs-expiring">
        <div class="panel-body padding-10">
            <p class="padding-5"><?php echo _l('program_this_week'); ?></p>
            <hr class="hr-panel-heading-dashboard">
            <?php if (!empty($programs)) { ?>
                <div class="table-vertical-scroll">
                    <a href="<?php echo admin_url('programs'); ?>" class="mbot20 inline-block full-width"><?php echo _l('home_widget_view_all'); ?></a>
                    <table id="widget-<?php echo create_widget_id(); ?>" class="table dt-table dt-inline dataTable no-footer" data-order-col="3" data-order-type="desc">
                        <thead>
                            <tr>
                                <th><?php echo _l('program_number'); ?> #</th>
                                <th class="<?php echo (isset($client) ? 'not_visible' : ''); ?>"><?php echo _l('program_list_client'); ?></th>
                                <th><?php echo _l('program_list_project'); ?></th>
                                <th><?php echo _l('program_list_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program) { ?>
                                <tr class="<?= 'program_state_' . $program['state']?>">
                                    <td>
                                        <?php echo '<a href="' . admin_url("programs/list_programs/" . $program["id"]) . '">' . format_program_number($program["id"]) . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo '<a href="' . admin_url("companies/list_companies/" . $program["clientid"]) . '">' . $program["company"] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo $program['reference_no']; ?>
                                    </td>
                                    <td>
                                        <?php echo _d($program['date']); ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="text-center padding-5">
                    <i class="fa fa-check fa-5x" aria-hidden="true"></i>
                    <h4><?php echo _l('no_program_this_week',["7"]) ; ?> </h4>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
