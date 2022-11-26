<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
    $CI = &get_instance();
    $CI->load->model('programs/programs_model');
    $programs = $CI->programs_model->get_project_not_programd(get_staff_user_id());
?>

<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('project_not_programd'); ?>">
    <?php if(staff_can('view', 'programs') || staff_can('view_own', 'programs')) { ?>
    <div class="panel_s programs-expiring">
        <div class="panel-body padding-10">
            <p class="padding-5"><?php echo _l('project_not_programd'); ?></p>
            <hr class="hr-panel-heading-dashboard">
            <?php if (!empty($programs)) { ?>
                <div class="table-vertical-scroll">
                    <a href="<?php echo admin_url('programs'); ?>" class="mbot20 inline-block full-width"><?php echo _l('home_widget_view_all'); ?></a>
                    <table id="widget-<?php echo create_widget_id(); ?>" class="table dt-table" data-order-col="2" data-order-type="desc">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th class="<?php echo (isset($client) ? 'not_visible' : ''); ?>"><?php echo _l('program_list_project'); ?></th>
                                <th><?php echo _l('program_list_client'); ?></th>
                                <th><?php echo _l('program_list_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($programs as $program) { ?>
                                <tr>
                                    <td> <?php echo $i; ?>
                                    </td>
                                    <td>
                                        <?php //echo $program['name']; ?>
                                        <?php echo '<a href="' . admin_url("projects/view/" . $program["id"]) . '">' . $program['name'] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo '<a href="' . admin_url("clients/client/" . $program["userid"]) . '">' . $program["company"] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo _d($program['start_date']); ?>
                                    </td>
                                </tr>
                            <?php $i++; ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="text-center padding-5">
                    <i class="fa fa-check fa-5x" aria-hidden="true"></i>
                    <h4><?php echo _l('no_project_not_programd',["7"]) ; ?> </h4>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
