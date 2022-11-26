<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Program_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $program;

    protected $contact;

    public $slug = 'program-send-to-client';

    public $rel_type = 'program';

    public function __construct($program, $contact, $cc = '')
    {
        parent::__construct();

        $this->program = $program;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->programs_model->get_attachments($this->program->id, $attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('program') . $this->program->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->program->id)
        ->set_merge_fields('client_merge_fields', $this->program->clientid, $this->contact->id)
        ->set_merge_fields('program_merge_fields', $this->program->id);
    }
}
