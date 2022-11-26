<?php defined('BASEPATH') or exit('No direct script access allowed');
   if ($program['state'] == $state) { ?>
<li data-program-id="<?php echo $program['id']; ?>" class="<?php if($program['invoiceid'] != NULL){echo 'not-sortable';} ?>">
   <div class="panel-body">
      <div class="row">
         <div class="col-md-12">
            <h4 class="bold pipeline-heading"><a href="<?php echo admin_url('programs/list_programs/'.$program['id']); ?>" onclick="program_pipeline_open(<?php echo $program['id']; ?>); return false;"><?php echo format_program_number($program['id']); ?></a>
               <?php if(has_permission('programs','','edit')){ ?>
               <a href="<?php echo admin_url('programs/program/'.$program['id']); ?>" target="_blank" class="pull-right"><small><i class="fa fa-pencil-square-o" aria-hidden="true"></i></small></a>
               <?php } ?>
            </h4>
            <span class="inline-block full-width mbot10">
            <a href="<?php echo admin_url('clients/client/'.$program['clientid']); ?>" target="_blank">
            <?php echo $program['company']; ?>
            </a>
            </span>
         </div>
         <div class="col-md-12">
            <div class="row">
               <div class="col-md-8">
                  <span class="bold">
                  <?php echo _l('program_total') . ':' . app_format_money($program['total'], $program['currency_name']); ?>
                  </span>
                  <br />
                  <?php echo _l('program_data_date') . ': ' . _d($program['date']); ?>
                  <?php if(is_date($program['duedate']) || !empty($program['duedate'])){
                     echo '<br />';
                     echo _l('program_data_expiry_date') . ': ' . _d($program['duedate']);
                     } ?>
               </div>
               <div class="col-md-4 text-right">
                  <small><i class="fa fa-paperclip"></i> <?php echo _l('program_notes'); ?>: <?php echo total_rows(db_prefix().'notes', array(
                     'rel_id' => $program['id'],
                     'rel_type' => 'program',
                     )); ?></small>
               </div>
               <?php $tags = get_tags_in($program['id'],'program');
                  if(count($tags) > 0){ ?>
               <div class="col-md-12">
                  <div class="mtop5 kanban-tags">
                     <?php echo render_tags($tags); ?>
                  </div>
               </div>
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</li>
<?php } ?>
