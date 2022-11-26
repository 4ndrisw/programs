<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="stats-top" class="hide">
    <div id="programs_total"></div>
    <div class="panel_s">
        <div class="panel-body">
         <div class="_filters _hidden_inputs hidden">
            <?php
            if(isset($programs_inspector_staff_ids)){
                foreach($programs_inspector_staff_ids as $agent){
                    echo form_hidden('inspector_staff_id_'.$agent['inspector_staff_id']);
                }
            }
            if(isset($program_states)){
                foreach($program_states as $_state){
                    $val = '';
                    if($_state == $this->input->get('state')){
                        $val = $_state;
                    }
                    echo form_hidden('programs_'.$_state,$val);
                }
            }
            if(isset($programs_years)){
                foreach($programs_years as $year){
                    echo form_hidden('year_'.$year['year'],$year['year']);
                }
            }
            echo form_hidden('not_sent',$this->input->get('filter'));
            echo form_hidden('project_id');
            echo form_hidden('invoiced');
            echo form_hidden('not_inspected');
            ?>
        </div>
        <div class="row text-left quick-top-stats">
            <?php foreach($program_states as $state){
              $percent_data = get_programs_percent_by_state($state, (isset($project) ? $project->id : null));
              ?>
              <div class="col-md-5ths col-xs-12">
                <div class="row">
                    <div class="col-md-7">
                        <a href="#" data-cview="programs_<?php echo $state; ?>" onclick="dt_custom_view('programs_<?php echo $state; ?>','.table-programs','programs_<?php echo $state; ?>',true); return false;">
                            <h5><?php echo format_program_state($state,'',false); ?></h5>
                        </a>
                    </div>
                    <div class="col-md-5 text-right">
                        <?php echo $percent_data['total_by_state']; ?> / <?php echo $percent_data['total']; ?>
                    </div>
                    <div class="col-md-12">
                        <div class="progress no-margin">
                            <div class="progress-bar progress-bar-<?php echo program_state_color_class($state); ?>" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-percent="<?php echo $percent_data['percent']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<hr />
</div>
