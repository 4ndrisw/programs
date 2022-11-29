// Init single program
function init_program(id) {
    load_small_table_item(id, '#program', 'programid', 'programs/get_program_data_ajax', '.table-programs');
}

// Init single company
function init_company(userid) {
    load_small_table_item(userid, '#company', 'companyid', 'companies/get_company_data_ajax', '.table-companies');
}


// Validates program add/edit form
function validate_program_form(selector) {

    selector = typeof (selector) == 'undefined' ? '#program-form' : selector;

    appValidateForm($(selector), {
        clientid: {
            required: {
                depends: function () {
                    var customerRemoved = $('select#clientid').hasClass('customer-removed');
                    return !customerRemoved;
                }
            }
        },
        date: 'required',
        surveyor_id: 'required',
        inspector_id: 'required',
        inspector_staff_id: 'required',
        number: {
            required: true
        }
    });

    $("body").find('input[name="number"]').rules('add', {
        remote: {
            url: admin_url + "programs/validate_program_number",
            type: 'post',
            data: {
                number: function () {
                    return $('input[name="number"]').val();
                },
                isedit: function () {
                    return $('input[name="number"]').data('isedit');
                },
                original_number: function () {
                    return $('input[name="number"]').data('original-number');
                },
                date: function () {
                    return $('body').find('.program input[name="date"]').val();
                },
            }
        },
        messages: {
            remote: app.lang.program_number_exists,
        }
    });

}


// Get the preview main values
function get_program_item_preview_values() {
    var response = {};
    response.description = $('.main textarea[name="description"]').val();
    response.long_description = $('.main textarea[name="long_description"]').val();
    response.qty = $('.main input[name="quantity"]').val();
    return response;
}

// From program table mark as
function program_mark_as(state_id, program_id) {
    var data = {};
    data.state = state_id;
    data.programid = program_id;
    $.post(admin_url + 'programs/update_program_state', data).done(function (response) {
        //table_programs.DataTable().ajax.reload(null, false);
        reload_programs_tables();
    });
}
function programs_add_program_item(clientid, institution_id, inspector_id, inspector_staff_id, surveyor_id, program_id, jenis_pesawat_id, id) {
    var data = {};
    data.clientid = clientid;
    data.institution_id = institution_id;
    data.inspector_id = inspector_id;
    data.inspector_staff_id = inspector_staff_id;
    data.surveyor_id = surveyor_id;
    data.program_id = program_id;
    data.jenis_pesawat_id = jenis_pesawat_id;
    data.peralatan_id = id;
    //data.peralatan_id = peralatan_id;
    console.log(data);
    $.post(admin_url + 'programs/add_program_item', data).done(function (response) {
        reload_programs_tables();
    });
}


function programs_remove_program_item(id) {
    var data = {};
    data.id = id;
    console.log(data);
    $.post(admin_url + 'programs/remove_program_item', data).done(function (response) {
        reload_programs_tables();
    });
}


function reload_programs_tables() {
    var av_programs_tables = ['.table-programs', '.table-peralatan', '.table-program_items'];
    //var av_programs_tables = ['.program-items-proposed'];
    $.each(av_programs_tables, function (i, selector) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().ajax.reload(null, false);
        }
    });
}
