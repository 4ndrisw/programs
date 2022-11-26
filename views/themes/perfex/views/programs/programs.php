<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s section-heading section-programs">
    <div class="panel-body">
        <div class="col-md-2">
            <h4 class="no-margin section-text"><?php echo _l('clients_my_programs'); ?></h4>    
        </div>
        <div class="col-md-2">
             <a href="<?php echo site_url('programs/client/create'); ?>" class="btn btn-info pull-left new new-program-btn"><?php echo _l('create_new_program'); ?></a>
        </div>
        
    </div>
</div>
<div class="panel_s">
    <div class="panel-body">
        <?php get_template_part('programs_stats'); ?>
        <hr />
        <?php get_template_part('programs_table'); ?>
    </div>
</div>
