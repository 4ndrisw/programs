<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('_attachment_sale_id',$program->id); ?>
<?php echo form_hidden('_attachment_sale_type','program'); ?>
<div class="col-md-12 no-padding">
   <div class="panel_s">
      <div class="panel-body">
         <div class="horizontal-scrollable-tabs preview-tabs-top">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
               <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                  <li role="presentation" class="active">
                     <a href="#tab_program" aria-controls="tab_program" role="tab" data-toggle="tab">
                     <?php echo _l('program'); ?>
                     </a>
                  </li>

                  <li role="presentation">
                     <a href="#tab_program_items" onclick="initDataTable('.table-program_items', admin_url + 'programs/get_program_items_table/'
                                                                                       + <?php echo $program->clientid ;?> + '/'
                                                                                       + <?php echo $program->institution_id ;?> + '/'
                                                                                       + <?php echo $program->inspector_id ;?> + '/'
                                                                                       + <?php echo $program->inspector_staff_id ;?> + '/'
                                                                                       + <?php echo $program->surveyor_id ?> + '/'
                                                                                       + <?php echo $program->id ;?>, undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_program_items" role="tab" data-toggle="tab">
                     <?php echo _l('program_items'); ?>
                     <?php
                        $total_program_items = total_rows(db_prefix().'program_items',
                          array(
                           'program_id'=>$program->id,
                           )
                          );
                        if($total_program_items > 0){
                          echo '<span class="badge">'.$total_program_items.'</span>';
                        }
                        ?>
                     </a>
                  </li>


                  <!--
                  <li role="presentation">
                     <a href="#tab_tasks" onclick="init_rel_tasks_table(<?php //echo $program->id; ?>,'program'); return false;" aria-controls="tab_tasks" role="tab" data-toggle="tab">
                     <?php //echo _l('tasks'); ?>
                     </a>
                  </li>
                  -->

                  <li role="presentation">
                     <a href="#tab_peralatan" onclick="initDataTable('.table-peralatan', admin_url + 'programs/get_peralatan_table/'
                                                                                       + <?php echo $program->clientid ;?> + '/'
                                                                                       + <?php echo $program->institution_id ;?> + '/'
                                                                                       + <?php echo $program->inspector_id ;?> + '/'
                                                                                       + <?php echo $program->inspector_staff_id ;?> + '/'
                                                                                       + <?php echo $program->surveyor_id ?> + '/'
                                                                                       + <?php echo $program->id ;?>, undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_peralatan" role="tab" data-toggle="tab">
                     <?php echo _l('equipment'); ?>
                     <?php
                        $total_peralatan = total_rows(db_prefix().'peralatan',
                          array(
                           'clientid'=> $program->clientid,
                           'peralatan_id'=> NULL,
                           )
                          );
                        if($total_peralatan > 0){
                          echo '<span class="badge">'.$total_peralatan.'</span>';
                        }
                        ?>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#tab_activity" aria-controls="tab_activity" role="tab" data-toggle="tab">
                     <?php echo _l('program_view_activity_tooltip'); ?>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#tab_reminders" onclick="initDataTable('.table-reminders', admin_url + 'misc/get_reminders/' + <?php echo $program->id ;?> + '/' + 'program', undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_reminders" role="tab" data-toggle="tab">
                     <?php echo _l('program_reminders'); ?>
                     <?php
                        $total_reminders = total_rows(db_prefix().'reminders',
                          array(
                           'isnotified'=>0,
                           'staff'=>get_staff_user_id(),
                           'rel_type'=>'program',
                           'rel_id'=>$program->id
                           )
                          );
                        if($total_reminders > 0){
                          echo '<span class="badge">'.$total_reminders.'</span>';
                        }
                        ?>
                     </a>
                  </li>
                  <li role="presentation" class="tab-separator">
                     <a href="#tab_notes" onclick="get_sales_notes(<?php echo $program->id; ?>,'programs'); return false" aria-controls="tab_notes" role="tab" data-toggle="tab">
                     <?php echo _l('program_notes'); ?>
                     <span class="notes-total">
                        <?php if($totalNotes > 0){ ?>
                           <span class="badge"><?php echo $totalNotes; ?></span>
                        <?php } ?>
                     </span>
                     </a>
                  </li>
                  <li role="presentation" data-toggle="tooltip" title="<?php echo _l('emails_tracking'); ?>" class="tab-separator">
                     <a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab">
                     <?php if(!is_mobile()){ ?>
                     <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
                     <?php } else { ?>
                     <?php echo _l('emails_tracking'); ?>
                     <?php } ?>
                     </a>
                  </li>
                  <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('view_tracking'); ?>" class="tab-separator">
                     <a href="#tab_views" aria-controls="tab_views" role="tab" data-toggle="tab">
                     <?php if(!is_mobile()){ ?>
                     <i class="fa fa-eye"></i>
                     <?php } else { ?>
                     <?php echo _l('view_tracking'); ?>
                     <?php } ?>
                     </a>
                  </li>
                  <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>" class="tab-separator toggle_view">
                     <a href="#" onclick="small_table_full_view(); return false;">
                     <i class="fa fa-expand"></i></a>
                  </li>
               </ul>
            </div>
         </div>
         <div class="row mtop10">
            <div class="col-md-3">
               <?php echo format_program_state($program->state,'mtop5');  ?>
            </div>
            <div class="col-md-9">
               <div class="visible-xs">
                  <div class="mtop10"></div>
               </div>
               <div class="pull-right _buttons">
                  <?php if(staff_can('edit', 'programs')){ ?>
                  <a href="<?php echo admin_url('programs/program/'.$program->id); ?>" class="btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo _l('edit_program_tooltip'); ?>" data-placement="bottom"><i class="fa-solid fa-pen-to-square"></i></a>
                  <?php } ?>
                  <div class="btn-group">
                     <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa-solid fa-file-pdf"></i><?php if(is_mobile()){echo ' PDF';} ?> <span class="caret"></span></a>
                     <ul class="dropdown-menu dropdown-menu-right">
                        <li class="hidden-xs"><a href="<?php echo admin_url('programs/pdf/'.$program->id.'?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
                        <li class="hidden-xs"><a href="<?php echo admin_url('programs/pdf/'.$program->id.'?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                        <li><a href="<?php echo admin_url('programs/pdf/'.$program->id); ?>"><?php echo _l('download'); ?></a></li>
                        <li>
                           <a href="<?php echo admin_url('programs/pdf/'.$program->id.'?print=true'); ?>" target="_blank">
                           <?php echo _l('print'); ?>
                           </a>
                        </li>
                     </ul>
                  </div>
                  <?php
                     $_tooltip = _l('program_sent_to_email_tooltip');
                     $_tooltip_already_send = '';
                     if($program->sent == 1){
                        $_tooltip_already_send = _l('program_already_send_to_client_tooltip', time_ago($program->datesend));
                     }
                     ?>
                  <?php if(!empty($program->clientid)){ ?>
                  <a href="#" class="program-send-to-client btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo $_tooltip; ?>" data-placement="bottom"><span data-toggle="tooltip" data-title="<?php echo $_tooltip_already_send; ?>"><i class="fa fa-envelope"></i></span></a>
                  <?php } ?>
                  <div class="btn-group">
                     <button type="button" class="btn btn-default pull-left dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <?php echo _l('more'); ?> <span class="caret"></span>
                     </button>
                     <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                           <a href="<?php echo site_url('program/' . $program->id . '/' .  $program->hash) ?>" target="_blank">
                           <?php echo _l('view_program_as_client'); ?>
                           </a>
                        </li>
                        <?php hooks()->do_action('after_program_view_as_client_link', $program); ?>
                        <?php if((!empty($program->duedate) && date('Y-m-d') < $program->duedate && ($program->state == 2 || $program->state == 5)) && is_programs_expiry_reminders_enabled()){ ?>
                        <li>
                           <a href="<?php echo admin_url('programs/send_expiry_reminder/'.$program->id); ?>">
                           <?php echo _l('send_expiry_reminder'); ?>
                           </a>
                        </li>
                        <?php } ?>
                        <li>
                           <a href="#" data-toggle="modal" data-target="#sales_attach_file"><?php echo _l('invoice_attach_file'); ?></a>
                        </li>
                        <?php if (staff_can('create', 'projects') && $program->project_id == 0) { ?>
                           <li>
                              <a href="<?php echo admin_url("projects/project?via_program_id={$program->id}&customer_id={$program->clientid}") ?>">
                                 <?php echo _l('program_convert_to_inspection'); ?>
                              </a>
                           </li>
                        <?php } ?>
                        <?php if($program->inspection_id == NULL){
                           if(staff_can('edit', 'programs')){
                             foreach($program_states as $state){
                               if($program->state != $state){ ?>
                        <li>
                           <a href="<?php echo admin_url() . 'programs/mark_action_state/'.$state.'/'.$program->id; ?>">
                           <?php echo _l('program_mark_as',format_program_state($state,'',false)); ?></a>
                        </li>
                        <?php }
                           }
                           ?>
                        <?php } ?>
                        <?php } ?>
                        <?php if(staff_can('create', 'programs')){ ?>
                        <li>
                           <a href="<?php echo admin_url('programs/copy/'.$program->id); ?>">
                           <?php echo _l('copy_program'); ?>
                           </a>
                        </li>
                        <?php } ?>
                        <?php if(!empty($program->signature) && staff_can('delete', 'programs')){ ?>
                        <li>
                           <a href="<?php echo admin_url('programs/clear_signature/'.$program->id); ?>" class="_delete">
                           <?php echo _l('clear_signature'); ?>
                           </a>
                        </li>
                        <?php } ?>
                        <?php if(staff_can('delete', 'programs')){ ?>
                        <?php
                           if((get_option('delete_only_on_last_program') == 1 && is_last_program($program->id)) || (get_option('delete_only_on_last_program') == 0)){ ?>
                        <li>
                           <a href="<?php echo admin_url('programs/delete/'.$program->id); ?>" class="text-danger delete-text _delete"><?php echo _l('delete_program_tooltip'); ?></a>
                        </li>
                        <?php
                           }
                           }
                           ?>
                     </ul>
                  </div>
                     <?php if($program->inspection_id == NULL){ ?>
                        <?php if(staff_can('create', 'inspections') && !empty($program->clientid)){ ?>
                        <div class="btn-group pull-right mleft5">
                           <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                           <?php echo _l('program_convert_to_inspection'); ?> <span class="caret"></span>
                           </button>
                           <ul class="dropdown-menu">
                              <li><a href="<?php echo admin_url('programs/convert_to_inspection/'.$program->id.'?save_as_draft=true'); ?>"><?php echo _l('convert_and_save_as_draft'); ?></a></li>
                              <li class="divider">
                              <li><a href="<?php echo admin_url('programs/convert_to_inspection/'.$program->id); ?>"><?php echo _l('convert'); ?></a></li>
                              </li>
                           </ul>
                        </div>
                        <?php } ?>
                     <?php } else { ?>                     
                  <a href="<?php echo admin_url('inspections/list_inspections/'.$program->inspection->id); ?>" data-placement="bottom" data-toggle="tooltip" title="<?php echo _l('program_inspection_date',_dt($program->inspection_date)); ?>"class="btn mleft10 btn-info"><?php echo format_inspection_number($program->inspection->id); ?></a>
                  <?php } ?>
               </div>
            </div>
         </div>
         <div class="clearfix"></div>
         <hr class="hr-panel-heading" />
         <div class="tab-content">
            <div role="tabpanel" class="tab-pane ptop10 active" id="tab_program">
               <?php if(isset($program->programd_email) && $program->programd_email) { ?>
                     <div class="alert alert-warning">
                        <?php echo _l('invoice_will_be_sent_at', _dt($program->programd_email->programd_at)); ?>
                        <?php if(staff_can('edit', 'programs') || $program->addedfrom == get_staff_user_id()) { ?>
                           <a href="#"
                           onclick="edit_program_programd_email(<?php echo $program->programd_email->id; ?>); return false;">
                           <?php echo _l('edit'); ?>
                        </a>
                     <?php } ?>
                  </div>
               <?php } ?>
               <div id="program-preview">
                  <div class="row">
                     <?php if($program->state == 4 && !empty($program->acceptance_firstname) && !empty($program->acceptance_lastname) && !empty($program->acceptance_email)){ ?>
                     <div class="col-md-12">
                        <div class="alert alert-info mbot15">
                           <?php echo _l('accepted_identity_info',array(
                              _l('program_lowercase'),
                              '<b>'.$program->acceptance_firstname . ' ' . $program->acceptance_lastname . '</b> (<a href="mailto:'.$program->acceptance_email.'">'.$program->acceptance_email.'</a>)',
                              '<b>'. _dt($program->acceptance_date).'</b>',
                              '<b>'.$program->acceptance_ip.'</b>'.(is_admin() ? '&nbsp;<a href="'.admin_url('programs/clear_acceptance_info/'.$program->id).'" class="_delete text-muted" data-toggle="tooltip" data-title="'._l('clear_this_information').'"><i class="fa fa-remove"></i></a>' : '')
                              )); ?>
                        </div>
                     </div>
                     <?php } ?>
                     <?php if($program->project_id != 0){ ?>
                     <div class="col-md-12">
                        <h4 class="font-medium mbot15"><?php echo _l('related_to_project',array(
                           _l('program_lowercase'),
                           _l('project_lowercase'),
                           '<a href="'.admin_url('projects/view/'.$program->project_id).'" target="_blank">' . $program->project_data->name . '</a>',
                           )); ?></h4>
                     </div>
                     <?php } ?>
                     <div class="col-md-6 col-sm-6">
                        <h4 class="bold">
                           <?php
                              $tags = get_tags_in($program->id,'program');
                              if(count($tags) > 0){
                                echo '<i class="fa fa-tag" aria-hidden="true" data-toggle="tooltip" data-title="'.html_escape(implode(', ',$tags)).'"></i>';
                              }
                              ?>
                           <a href="<?php echo admin_url('programs/program/'.$program->id); ?>">
                           <span id="program-number">
                           <?php echo format_program_number($program->id); ?>
                           </span>
                           </a>
                        </h4>
                        <address>
                           <?php echo format_organization_info(); ?>
                        </address>
                     </div>
                     <div class="col-sm-6 text-right">
                        <span class="bold"><?php echo _l('program_to'); ?>:</span>
                        <address>
                           <?php echo format_customer_info($program, 'program', 'billing', true); ?>
                        </address>
                        <?php if($program->include_shipping == 1 && $program->show_shipping_on_program == 1){ ?>
                        <span class="bold"><?php echo _l('ship_to'); ?>:</span>
                        <address>
                           <?php echo format_customer_info($program, 'program', 'shipping'); ?>
                        </address>
                        <?php } ?>
                        <p class="no-mbot">
                           <span class="bold">
                           <?php echo _l('program_data_date'); ?>:
                           </span>
                           <?php echo $program->date; ?>
                        </p>
                        <?php if(!empty($program->duedate)){ ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo _l('program_data_expiry_date'); ?>:</span>
                           <?php echo $program->duedate; ?>
                        </p>
                        <?php } ?>
                        <?php if(!empty($program->reference_no)){ ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                           <?php echo $program->reference_no; ?>
                        </p>
                        <?php } ?>
                        <?php if($program->inspector_staff_id != 0 && get_option('show_inspector_staff_id_on_programs') == 1){ ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo _l('inspector_staff_id_string'); ?>:</span>
                           <?php echo get_staff_full_name($program->inspector_staff_id); ?>
                        </p>
                        <?php } ?>
                        <?php if($program->project_id != 0 && get_option('show_project_on_program') == 1){ ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo _l('project'); ?>:</span>
                           <?php echo get_project_name_by_id($program->project_id); ?>
                        </p>
                        <?php } ?>
                        <?php $pdf_custom_fields = get_custom_fields('program',array('show_on_pdf'=>1));
                           foreach($pdf_custom_fields as $field){
                           $value = get_custom_field_value($program->id,$field['id'],'program');
                           if($value == ''){continue;} ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo $field['name']; ?>: </span>
                           <?php echo $value; ?>
                        </p>
                        <?php } ?>
                     </div>
                  </div>
                  <div class="row">
                     <div class="col-md-12">
                        <div class="table-responsive">
                              <?php
                                 $items = get_preview_table_data($program, 'program', 'html', true);
                                 echo $items->table();
                              ?>
                        </div>
                     </div>

                     <?php if(count($program->attachments) > 0){ ?>
                     <div class="clearfix"></div>
                     <hr />
                     <div class="col-md-12">
                        <p class="bold text-muted"><?php echo _l('program_files'); ?></p>
                     </div>
                     <?php foreach($program->attachments as $attachment){
                        $attachment_url = site_url('download/file/sales_attachment/'.$attachment['attachment_key']);
                        if(!empty($attachment['external'])){
                          $attachment_url = $attachment['external_link'];
                        }
                        ?>
                     <div class="mbot15 row col-md-12" data-attachment-id="<?php echo $attachment['id']; ?>">
                        <div class="col-md-8">
                           <div class="pull-left"><i class="<?php echo get_mime_class($attachment['filetype']); ?>"></i></div>
                           <a href="<?php echo $attachment_url; ?>" target="_blank"><?php echo $attachment['file_name']; ?></a>
                           <br />
                           <small class="text-muted"> <?php echo $attachment['filetype']; ?></small>
                        </div>
                        <div class="col-md-4 text-right">
                           <?php if($attachment['visible_to_customer'] == 0){
                              $icon = 'fa fa-toggle-off';
                              $tooltip = _l('show_to_customer');
                              } else {
                              $icon = 'fa fa-toggle-on';
                              $tooltip = _l('hide_from_customer');
                              }
                              ?>
                           <a href="#" data-toggle="tooltip" onclick="toggle_file_visibility(<?php echo $attachment['id']; ?>,<?php echo $program->id; ?>,this); return false;" data-title="<?php echo $tooltip; ?>"><i class="<?php echo $icon; ?>" aria-hidden="true"></i></a>
                           <?php if($attachment['staffid'] == get_staff_user_id() || is_admin()){ ?>
                           <a href="#" class="text-danger" onclick="delete_program_attachment(<?php echo $attachment['id']; ?>); return false;"><i class="fa fa-times"></i></a>
                           <?php } ?>
                        </div>
                     </div>
                     <?php } ?>
                     <?php } ?>

                  </div>
               </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_program_items">
               
               <span class="label label-success mbot5 mtop5"><?php echo _l('program_item_proposed'); ?> </span>
               <hr />
               <?php render_datatable(array( _l( 'program_items'), _l( 'serial_number'), _l( 'unit_number'), _l( 'kelompok_alat'), _l( 'process')), 'program_items'); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_peralatan">
               <a href="#" class="btn btn-info btn-disable" data-target=".reminder-modal-program-<?php echo $program->id; ?>"><i class="fa fa-bell-o"></i> <?php echo _l('equipments_available'); ?></a>
               <hr />
               <?php render_datatable(array( _l( 'peralatan'), _l( 'serial_number'), _l( 'unit_number'), _l( 'location'), _l( 'program')), 'peralatan'); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_tasks">
               <?php init_relation_tasks_table(array('data-new-rel-id'=>$program->id,'data-new-rel-type'=>'program')); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_reminders">
               <a href="#" data-toggle="modal" class="btn btn-info" data-target=".reminder-modal-program-<?php echo $program->id; ?>"><i class="fa fa-bell-o"></i> <?php echo _l('program_set_reminder_title'); ?></a>
               <hr />
               <?php render_datatable(array( _l( 'reminder_description'), _l( 'reminder_date'), _l( 'reminder_staff'), _l( 'reminder_is_notified')), 'reminders'); ?>
               <?php $this->load->view('admin/includes/modals/reminder',array('id'=>$program->id,'name'=>'program','members'=>$members,'reminder_title'=>_l('program_set_reminder_title'))); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_emails_tracking">
               <?php
                  $this->load->view('admin/includes/emails_tracking',array(
                     'tracked_emails'=>
                     get_tracked_emails($program->id, 'program'))
                  );
                  ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_notes">
               <?php echo form_open(admin_url('programs/add_note/'.$program->id),array('id'=>'sales-notes','class'=>'program-notes-form')); ?>
               <?php echo render_textarea('description'); ?>
               <div class="text-right">
                  <button type="submit" class="btn btn-info mtop15 mbot15"><?php echo _l('program_add_note'); ?></button>
               </div>
               <?php echo form_close(); ?>
               <hr />
               <div class="panel_s mtop20 no-shadow" id="sales_notes_area">
               </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_activity">
               <div class="row">
                  <div class="col-md-12">
                     <div class="activity-feed">
                        <?php foreach($activity as $activity){
                           $_custom_data = false;
                           ?>
                        <div class="feed-item" data-sale-activity-id="<?php echo $activity['id']; ?>">
                           <div class="date">
                              <span class="text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($activity['date']); ?>">
                              <?php echo time_ago($activity['date']); ?>
                              </span>
                           </div>
                           <div class="text">
                              <?php if(is_numeric($activity['staffid']) && $activity['staffid'] != 0){ ?>
                              <a href="<?php echo admin_url('profile/'.$activity["staffid"]); ?>">
                              <?php echo staff_profile_image($activity['staffid'],array('staff-profile-xs-image pull-left mright5'));
                                 ?>
                              </a>
                              <?php } ?>
                              <?php
                                 $additional_data = '';
                                 if(!empty($activity['additional_data'])){
                                  $additional_data = unserialize($activity['additional_data']);
                                  $i = 0;
                                  foreach($additional_data as $data){
                                    if(strpos($data,'<original_state>') !== false){
                                      $original_state = get_string_between($data, '<original_state>', '</original_state>');
                                      $additional_data[$i] = format_program_state($original_state,'',false);
                                    } else if(strpos($data,'<new_state>') !== false){
                                      $new_state = get_string_between($data, '<new_state>', '</new_state>');
                                      $additional_data[$i] = format_program_state($new_state,'',false);
                                    } else if(strpos($data,'<state>') !== false){
                                      $state = get_string_between($data, '<state>', '</state>');
                                      $additional_data[$i] = format_program_state($state,'',false);
                                    } else if(strpos($data,'<custom_data>') !== false){
                                      $_custom_data = get_string_between($data, '<custom_data>', '</custom_data>');
                                      unset($additional_data[$i]);
                                    }
                                    $i++;
                                  }
                                 }
                                 $_formatted_activity = _l($activity['description'],$additional_data);
                                 if($_custom_data !== false){
                                 $_formatted_activity .= ' - ' .$_custom_data;
                                 }
                                 if(!empty($activity['full_name'])){
                                 $_formatted_activity = $activity['full_name'] . ' - ' . $_formatted_activity;
                                 }
                                 echo $_formatted_activity;
                                 if(is_admin()){
                                 echo '<a href="#" class="pull-right text-danger" onclick="delete_sale_activity('.$activity['id'].'); return false;"><i class="fa fa-remove"></i></a>';
                                 }
                                 ?>
                           </div>
                        </div>
                        <?php } ?>
                     </div>
                  </div>
               </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_views">
               <?php
                  $views_activity = get_views_tracking('program',$program->id);
                  if(count($views_activity) === 0) {
                     echo '<h4 class="no-mbot">'._l('not_viewed_yet',_l('program_lowercase')).'</h4>';
                  }
                  foreach($views_activity as $activity){ ?>
               <p class="text-success no-margin">
                  <?php echo _l('view_date') . ': ' . _dt($activity['date']); ?>
               </p>
               <p class="text-muted">
                  <?php echo _l('view_ip') . ': ' . $activity['view_ip']; ?>
               </p>
               <hr />
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</div>
<script>
   init_items_sortable(true);
   init_btn_with_tooltips();
   init_datepicker();
   init_selectpicker();
   init_form_reminder();
   init_tabs_scrollable();
   <?php if($send_later) { ?>
      program_program_send(<?php echo $program->id; ?>);
   <?php } ?>
</script>
<?php $this->load->view('admin/programs/program_send_to_client'); ?>