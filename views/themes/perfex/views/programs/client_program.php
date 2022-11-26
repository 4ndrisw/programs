<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>


<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            echo form_open($this->uri->uri_string(),array('id'=>'program-form','class'=>'_transaction_form'));
            if(isset($program)){
                echo form_hidden('isedit');
            }
            ?>
            <div class="col-md-12">
                <?php 
                    $this->view('themes/'. active_clients_theme() .'/views/programs/client_program_template');
                ?>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
</div>
<?php init_tail(); ?>
<script>
    $(function(){
        validate_program_form();
        //apps_ajax_projects_search();
        apps_ajax_inspector_staffs_search();
        init_ajax_projects_search();
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script>
        $('.select2bs4_daftar').select2({
            placeholder: "Pilih Bahasa Pemrograman",
            theme: 'bootstrap3',
            ajax: {
                dataType: 'json',
                delay: 250,
                url: '<?php echo site_url('programs/list_inspector_staffs'); ?>',
                data: function(params) {
                    return {
                        searchTerm: params.term
                    }
                },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(obj) {
                            return {
                                id: obj.staffid,
                                text: obj.name
                            };
                        })
                    };
                }
            }
        });
    </script>