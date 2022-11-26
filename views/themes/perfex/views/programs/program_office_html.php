<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop15 preview-top-wrapper">
   <div class="row">
      <div class="col-md-3">
         <div class="mbot30">
            <div class="program-html-logo">
               <?php echo get_dark_company_logo(); ?>
            </div>
         </div>
      </div>
      <div class="clearfix"></div>
   </div>
   <div class="top" data-sticky data-sticky-class="preview-sticky-header">
      <div class="container preview-sticky-container">
         <div class="row">
            <div class="col-md-12">
               <div class="col-md-3">
                  <h3 class="bold no-mtop program-html-number no-mbot">
                     <span class="sticky-visible hide">
                     <?php echo format_program_number($program->id); ?>
                     </span>
                  </h3>
                  <h4 class="program-html-state mtop7">
                     <?php echo format_program_state($program->state,'',true); ?>
                  </h4>
               </div>
               <div class="col-md-9">
                  <?php echo form_open(site_url('programs/office_pdf/'.$program->id), array('class'=>'pull-right action-button')); ?>
                  <button type="submit" name="programpdf" class="btn btn-default action-button download mright5 mtop7" value="programpdf">
                  <i class="fa fa-file-pdf-o"></i>
                  <?php echo _l('clients_invoice_html_btn_download'); ?>
                  </button>
                  <?php echo form_close(); ?>
                  <?php if(is_client_logged_in() || is_staff_member()){ ?>
                  <a href="<?php echo site_url('clients/programs/'); ?>" class="btn btn-default pull-right mright5 mtop7 action-button go-to-portal">
                  <?php echo _l('client_go_to_dashboard'); ?>
                  </a>
                  <?php } ?>
               </div>
            </div>
            <div class="clearfix"></div>
         </div>
      </div>
   </div>
</div>
<div class="clearfix"></div>
<div class="panel_s mtop20">
   <div class="panel-body">
      <div class="col-md-10 col-md-offset-1">
         <div class="row mtop20">
            <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
               <h4 class="bold program-html-number"><?php echo format_program_number($program->id); ?></h4>
               <address class="program-html-company-info">
                  <?php echo format_organization_info(); ?>
               </address>
            </div>
            <div class="col-sm-6 text-right transaction-html-info-col-right">
               <span class="bold program_to"><?php echo _l('program_office_to'); ?>:</span>
               <address class="program-html-customer-billing-info">
                  <?php echo format_office_info($program->office, 'office', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($program->include_shipping == 1 && $program->show_shipping_on_program == 1){ ?>
               <span class="bold program_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="program-html-customer-shipping-info">
                  <?php echo format_office_info($program->office, 'office', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>
         </div>
         <div class="row">

            <div class="col-sm-12 text-left transaction-html-info-col-left">
               <p class="program_to"><?php echo _l('program_opening'); ?>:</p>
               <span class="program_to"><?php echo _l('program_client'); ?>:</span>
               <address class="program-html-customer-billing-info">
                  <?php echo format_customer_info($program, 'program', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($program->include_shipping == 1 && $program->show_shipping_on_program == 1){ ?>
               <span class="bold program_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="program-html-customer-shipping-info">
                  <?php echo format_customer_info($program, 'program', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>



            <div class="col-md-6">
               <div class="container-fluid">
                  <?php if(!empty($program_members)){ ?>
                     <strong><?= _l('program_members') ?></strong>
                     <ul class="program_members">
                     <?php 
                        foreach($program_members as $member){
                          echo ('<li style="list-style:auto" class="member">' . $member['firstname'] .' '. $member['lastname'] .'</li>');
                         }
                     ?>
                     </ul>
                  <?php } ?>
               </div>
            </div>
            <div class="col-md-6 text-right">
               <p class="no-mbot program-html-date">
                  <span class="bold">
                  <?php echo _l('program_data_date'); ?>:
                  </span>
                  <?php echo _d($program->date); ?>
               </p>
               <?php if(!empty($program->duedate)){ ?>
               <p class="no-mbot program-html-expiry-date">
                  <span class="bold"><?php echo _l('program_data_expiry_date'); ?></span>:
                  <?php echo _d($program->duedate); ?>
               </p>
               <?php } ?>
               <?php if(!empty($program->reference_no)){ ?>
               <p class="no-mbot program-html-reference-no">
                  <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                  <?php echo $program->reference_no; ?>
               </p>
               <?php } ?>
               <?php if($program->project_id != 0 && get_option('show_project_on_program') == 1){ ?>
               <p class="no-mbot program-html-project">
                  <span class="bold"><?php echo _l('project'); ?>:</span>
                  <?php echo get_project_name_by_id($program->project_id); ?>
               </p>
               <?php } ?>
               <?php $pdf_custom_fields = get_custom_fields('program',array('show_on_pdf'=>1,'show_on_client_portal'=>1));
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
                     $items = get_program_items_table_data($program, 'program');
                     echo $items->table();
                  ?>
               </div>
            </div>


            <div class="row mtop25">
               <div class="col-md-12">
                  <div class="col-md-6 text-center">
                     <div class="bold"><?php echo get_option('invoice_company_name'); ?></div>
                     <div class="qrcode text-center">
                        <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_program_upload_path('program').$program->id.'/assigned-'.$program_number.'.png')); ?>" class="img-responsive center-block program-assigned" alt="program-<?= $program->id ?>">
                     </div>
                     <div class="assigned">
                     <?php if($program->assigned != 0 && get_option('show_assigned_on_programs') == 1){ ?>
                        <?php echo get_staff_full_name($program->assigned); ?>
                     <?php } ?>

                     </div>
                  </div>
                     <div class="col-md-6 text-center">
                       <div class="bold"><?php echo $client_company; ?></div>
                       <?php if(!empty($program->signature)) { ?>
                           <div class="bold">
                              <p class="no-mbot"><?php echo _l('program_signed_by') . ": {$program->acceptance_firstname} {$program->acceptance_lastname}"?></p>
                              <p class="no-mbot"><?php echo _l('program_signed_date') . ': ' . _dt($program->acceptance_date) ?></p>
                              <p class="no-mbot"><?php echo _l('program_signed_ip') . ": {$program->acceptance_ip}"?></p>
                           </div>
                           <p class="bold"><?php echo _l('document_customer_signature_text'); ?>
                           <?php if($program->signed == 1 && has_permission('programs','','delete')){ ?>
                              <a href="<?php echo admin_url('programs/clear_signature/'.$program->id); ?>" data-toggle="tooltip" title="<?php echo _l('clear_signature'); ?>" class="_delete text-danger">
                                 <i class="fa fa-remove"></i>
                              </a>
                           <?php } ?>
                           </p>
                           <div class="customer_signature text-center">
                              <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_program_upload_path('program').$program->id.'/'.$program->signature)); ?>" class="img-responsive center-block program-signature" alt="program-<?= $program->id ?>">
                           </div>
                       <?php } ?>
                     </div>
               </div>
            </div>

         </div>
      </div>
   </div>
</div>

