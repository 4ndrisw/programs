<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s accounting-template program">
   <div class="panel-body">
      <?php if(isset($program)){ ?>
      <?php echo format_program_state($program->state); ?>
      <hr class="hr-panel-heading" />
      <?php } ?>
      <div class="row">
          <?php if (isset($program_request_id) && $program_request_id != '') {
              echo form_hidden('program_request_id',$program_request_id);
          }
          ?>
         <div class="col-md-6 border-right">
            <?php if(!$is_company){ ?>
               <div class="f_client_id">
                <div class="form-group select-placeholder">
                   <label for="clientid" class="control-label"><?php echo _l('program_select_customer'); ?></label>
                   <select id="clientid" name="clientid" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($program) && empty($program->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                  <?php $selected = (isset($program) ? $program->clientid : '');
                    if($selected == ''){
                      $selected = (isset($customer_id) ? $customer_id: '');
                    }
                    if($selected != ''){
                       $rel_data = apps_get_relation_data('company',$selected);
                       $rel_val = apps_get_relation_values($rel_data,'company');
                       echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                    } ?>
                   </select>
                 </div>
               </div>
               <div class="form-group select-placeholder projects-wrapper<?php if((!isset($program)) || (isset($program) && !customer_has_projects($program->clientid))){ echo ' hide';} ?>">
                <label for="project_id"><?php echo _l('project'); ?></label>
                <div id="project_ajax_search_wrapper">
                  <select name="project_id" id="project_id" class="projects ajax-search" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                   <?php
                     if(isset($program) && $program->project_id != 0){
                       echo '<option value="'.$program->project_id.'" selected>'.get_project_name_by_id($program->project_id).'</option>';
                     }
                   ?>
                 </select>
               </div>
              </div>
            <?php } ?>

            <div class="row">
            <?php if(!$is_company){ ?>
               <div class="col-md-12">
                  <a href="#" class="edit_shipping_billing_info" data-toggle="modal" data-target="#billing_and_shipping_details"><i class="fa fa-pencil-square-o"></i></a>
                  <?php include_once(module_views_path('programs','admin/programs/billing_and_shipping_template.php')); ?>
               </div>

               <div class="col-md-6">
                  <p class="bold"><?php echo _l('invoice_bill_to'); ?></p>
                  <address>
                     <span class="billing_street">
                     <?php $billing_street = (isset($program) ? $program->billing_street : '--'); ?>
                     <?php $billing_street = ($billing_street == '' ? '--' :$billing_street); ?>
                     <?php echo $billing_street; ?></span><br>
                     <span class="billing_city">
                     <?php $billing_city = (isset($program) ? $program->billing_city : '--'); ?>
                     <?php $billing_city = ($billing_city == '' ? '--' :$billing_city); ?>
                     <?php echo $billing_city; ?></span>,
                     <span class="billing_state">
                     <?php $billing_state = (isset($program) ? $program->billing_state : '--'); ?>
                     <?php $billing_state = ($billing_state == '' ? '--' :$billing_state); ?>
                     <?php echo $billing_state; ?></span>
                     <br/>
                     <span class="billing_country">
                     <?php $billing_country = (isset($program) ? get_country_short_name($program->billing_country) : '--'); ?>
                     <?php $billing_country = ($billing_country == '' ? '--' :$billing_country); ?>
                     <?php echo $billing_country; ?></span>,
                     <span class="billing_zip">
                     <?php $billing_zip = (isset($program) ? $program->billing_zip : '--'); ?>
                     <?php $billing_zip = ($billing_zip == '' ? '--' :$billing_zip); ?>
                     <?php echo $billing_zip; ?></span>
                  </address>
               </div>
               <div class="col-md-6">
                  <p class="bold"><?php echo _l('ship_to'); ?></p>
                  <address>
                     <span class="shipping_street">
                     <?php $shipping_street = (isset($program) ? $program->shipping_street : '--'); ?>
                     <?php $shipping_street = ($shipping_street == '' ? '--' :$shipping_street); ?>
                     <?php echo $shipping_street; ?></span><br>
                     <span class="shipping_city">
                     <?php $shipping_city = (isset($program) ? $program->shipping_city : '--'); ?>
                     <?php $shipping_city = ($shipping_city == '' ? '--' :$shipping_city); ?>
                     <?php echo $shipping_city; ?></span>,
                     <span class="shipping_state">
                     <?php $shipping_state = (isset($program) ? $program->shipping_state : '--'); ?>
                     <?php $shipping_state = ($shipping_state == '' ? '--' :$shipping_state); ?>
                     <?php echo $shipping_state; ?></span>
                     <br/>
                     <span class="shipping_country">
                     <?php $shipping_country = (isset($program) ? get_country_short_name($program->shipping_country) : '--'); ?>
                     <?php $shipping_country = ($shipping_country == '' ? '--' :$shipping_country); ?>
                     <?php echo $shipping_country; ?></span>,
                     <span class="shipping_zip">
                     <?php $shipping_zip = (isset($program) ? $program->shipping_zip : '--'); ?>
                     <?php $shipping_zip = ($shipping_zip == '' ? '--' :$shipping_zip); ?>
                     <?php echo $shipping_zip; ?></span>
                  </address>
               </div>

               <?php } ?>
            </div>

            <?php
               $next_program_number = get_option('next_program_number');
               $format = get_option('program_number_format');

                if(isset($program)){
                  $format = $program->number_format;
                }

               $prefix = get_option('program_prefix');

               if ($format == 1) {
                 $__number = $next_program_number;
                 if(isset($program)){
                   $__number = $program->number;
                   $prefix = '<span id="prefix">' . $program->prefix . '</span>';
                 }
               } else if($format == 2) {
                 if(isset($program)){
                   $__number = $program->number;
                   $prefix = $program->prefix;
                   $prefix = '<span id="prefix">'. $prefix . '</span><span id="prefix_year">' . date('Y',strtotime($program->date)).'</span>/';
                 } else {
                   $__number = $next_program_number;
                   $prefix = $prefix.'<span id="prefix_year">'.date('Y').'</span>/';
                 }
               } else if($format == 3) {
                  if(isset($program)){
                   $yy = date('y',strtotime($program->date));
                   $__number = $program->number;
                   $prefix = '<span id="prefix">'. $program->prefix . '</span>';
                 } else {
                  $yy = date('y');
                  $__number = $next_program_number;
                }
               } else if($format == 4) {
                  if(isset($program)){
                   $yyyy = date('Y',strtotime($program->date));
                   $mm = date('m',strtotime($program->date));
                   $__number = $program->number;
                   $prefix = '<span id="prefix">'. $program->prefix . '</span>';
                 } else {
                  $yyyy = date('Y');
                  $mm = date('m');
                  $__number = $next_program_number;
                }
               }

               $_program_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
               $isedit = isset($program) ? 'true' : 'false';
               $data_original_number = isset($program) ? $program->number : 'false';
               ?>
            <div class="form-group">
               <label for="number"><?php echo _l('program_add_edit_number'); ?></label>
               <div class="input-group">
                  <span class="input-group-addon">
                  <?php if(isset($program)){ ?>
                  <a href="#" onclick="return false;" data-toggle="popover" data-container='._transaction_form' data-html="true" data-content="<label class='control-label'><?php echo _l('settings_sales_program_prefix'); ?></label><div class='input-group'><input name='s_prefix' type='text' class='form-control' value='<?php echo $program->prefix; ?>'></div><button type='button' onclick='save_sales_number_settings(this); return false;' data-url='<?php echo admin_url('programs/update_number_settings/'.$program->id); ?>' class='btn btn-info btn-block mtop15'><?php echo _l('submit'); ?></button>"><i class="fa fa-cog"></i></a>
                   <?php }
                    echo $prefix;
                  ?>
                 </span>
                  <input type="text" name="number" class="form-control" value="<?php echo $_program_number; ?>" data-isedit="<?php echo $isedit; ?>" data-original-number="<?php echo $data_original_number; ?>">
                  <?php if($format == 3) { ?>
                  <span class="input-group-addon">
                     <span id="prefix_year" class="format-n-yy"><?php echo $yy; ?></span>
                  </span>
                  <?php } else if($format == 4) { ?>
                   <span class="input-group-addon">
                     <span id="prefix_month" class="format-mm-yyyy"><?php echo $mm; ?></span>
                     /
                     <span id="prefix_year" class="format-mm-yyyy"><?php echo $yyyy; ?></span>
                  </span>
                  <?php } ?>
               </div>
            </div>

            <div class="row">
               <div class="col-md-6">
                  <?php $value = (isset($program) ? _d($program->date) : _d(date('Y-m-d'))); ?>
                  <?php echo render_date_input('date','program_add_edit_date',$value); ?>
               </div>
               <div class="col-md-6">
                  <?php
                  $value = '';
                  if(isset($program)){
                    $value = _d($program->duedate);
                  } else {
                      if(get_option('program_due_after') != 0){
                          $value = _d(date('Y-m-d', strtotime('+' . get_option('program_due_after') . ' DAY', strtotime(date('Y-m-d')))));
                      }
                  }
                  echo render_date_input('duedate','program_add_edit_duedate',$value); ?>
               </div>
            </div>
            <div class="clearfix mbot15"></div>
            <?php $rel_id = (isset($program) ? $program->id : false); ?>
            <?php
                  if(isset($custom_fields_rel_transfer)) {
                      $rel_id = $custom_fields_rel_transfer;
                  }
             ?>
            <?php //echo render_custom_fields('program',$rel_id); ?>
         </div>
         <div class="col-md-6">
            <div class="no-shadow">
            <?php if(!$is_company){ ?>

               <div class="row">
                  <div class="col-md-12">
                     <div class="f_client_id">
                      <div class="form-group select-placeholder">
                         <label for="inspectorid" class="control-label"><?php echo _l('inspector'); ?></label>
                         <select id="inspectorid" name="inspector_id" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($program) && empty($program->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                        <?php $selected = (isset($program) ? $program->inspector_id : '');
                          if($selected == ''){
                            $selected = (isset($customer_id) ? $customer_id: '');
                          }
                          if($selected != ''){
                             $rel_data = apps_get_relation_data('inspector',$selected);
                             $rel_val = get_relation_values($rel_data,'inspector');
                             echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                          } ?>
                         </select>
                       </div>
                     </div>
                  </div>
               </div>
                  <?php } ?>
               <div class="row">
                  <div class="col-md-12">
                     <div class="f_client_id">
                      <div class="form-group select-placeholder">
                         <label for="surveyorid" class="control-label"><?php echo _l('surveyor'); ?></label>
                         <select id="surveyorid" name="surveyor_id" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($program) && empty($program->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                        <?php $selected = (isset($program) ? $program->surveyor_id : '');
                          if($selected == ''){
                            $selected = (isset($customer_id) ? $customer_id: '');
                          }
                          if($selected != ''){
                             $rel_data = apps_get_relation_data('surveyor',$selected);
                             $rel_val = get_relation_values($rel_data,'surveyor');
                             echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                          } ?>
                         </select>
                       </div>
                     </div>
                  </div>
               </div>

               <div class="row">
                  <?php if(!$is_company){ ?>
                  <div class="col-md-6">
                         <?php
                        $selected = '';
                        foreach($staff as $member){
                         if(isset($program)){
                           if($program->inspector_staff_id == $member['staffid']) {
                             $selected = $member['staffid'];
                           }
                         }
                        }
                        echo render_select('inspector_staff_id',$staff,array('staffid',array('firstname','lastname')),'inspector_staff_id_string',$selected);
                        ?>
                  </div>

                  <?php } ?>
                   <div class="col-md-6">
                     <div class="form-group select-placeholder">
                        <label class="control-label"><?php echo _l('program_state'); ?></label>
                        <select class="selectpicker display-block mbot15" name="state" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                           <?php foreach($program_states as $state){ ?>
                           <option value="<?php echo $state; ?>" <?php if(isset($program) && $program->state == $state){echo 'selected';} ?>><?php echo format_program_state($state,'',false); ?></option>
                           <?php } ?>
                        </select>
                     </div>
                  </div>
                  <div class="col-md-6">
                    <?php $value = (isset($program) ? $program->reference_no : ''); ?>
                    <?php echo render_input('reference_no','reference_no',$value); ?>
                  </div>

               </div>
               <?php //$value = (isset($program) ? $program->adminnote : ''); ?>
               <?php //echo render_textarea('adminnote','program_add_edit_admin_note',$value); ?>

            </div>
         </div>
      </div>
   </div>

   <div class="row">
    <div class="col-md-12 mtop15">
      <div class="panel-body bottom-transaction">

        <div class="clearfix"></div>
        <div class="btn-bottom-toolbar text-right">
          <a class="btn btn-default" href="../"><?php echo _l('back') ;?><a>
          <div class="btn-group dropup">
            <button type="button" class="btn-tr btn btn-info program-form-submit transaction-submit">
              <?php echo _l('submit'); ?>
            </button>
                <button type="button"
                  class="btn btn-info dropdown-toggle"
                  data-toggle="dropdown"
                  aria-haspopup="true"
                  aria-expanded="false">
                  <span class="caret"></span>
                </button>
             <ul class="dropdown-menu dropdown-menu-right width200">
               <li>
                 <a href="#" class="program-form-submit save-and-send transaction-submit">
                   <?php echo _l('save_and_send'); ?>
                 </a>
               </li>
               <?php if(!isset($program)) { ?>
                 <li>
                   <a href="#" class="program-form-submit save-and-send-later transaction-submit">
                     <?php echo _l('save_and_send_later'); ?>
                   </a>
                 </li>
               <?php } ?>
             </ul>
        </div>
      </div>
    </div>
    <div class="btn-bottom-pusher"></div>
  </div>
</div>
</div>
