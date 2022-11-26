<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($client)){ ?>
	<h4 class="customer-profile-group-heading"><?php echo _l('programs'); ?></h4>
	<?php if(has_permission('programs','','create')){ ?>
		<a href="<?php echo admin_url('programs/program?customer_id='.$client->userid); ?>" class="btn btn-info mbot15<?php if($client->active == 0){echo ' disabled';} ?>"><?php echo _l('create_new_program'); ?></a>
	<?php } ?>
	<?php if(has_permission('programs','','view') || has_permission('programs','','view_own') || get_option('allow_staff_view_programs_assigned') == '1'){ ?>
		<a href="#" class="btn btn-info mbot15" data-toggle="modal" data-target="#client_zip_programs"><?php echo _l('zip_programs'); ?></a>
	<?php } ?>
	<div id="programs_total"></div>
	<?php
	$this->load->view('admin/programs/table_html', array('class'=>'programs-single-client'));
	//$this->load->view('admin/clients/modals/zip_programs');
	?>
<?php } ?>
