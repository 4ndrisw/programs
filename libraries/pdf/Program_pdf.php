<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(LIBSPATH . 'pdf/App_pdf.php');

class Program_pdf extends App_pdf
{
    protected $program;

    private $program_number;

    public function __construct($program, $tag = '')
    {
        $this->load_language($program->clientid);

        $program                = hooks()->apply_filters('program_html_pdf_data', $program);
        $GLOBALS['program_pdf'] = $program;

        parent::__construct();

        $this->tag             = $tag;
        $this->program        = $program;
        $this->program_number = format_program_number($this->program->id);

        $this->SetTitle($this->program_number);
    }

    public function prepare()
    {

        $this->set_view_vars([
            'state'          => $this->program->state,
            'program_number' => $this->program_number,
            'program'        => $this->program,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'program';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_programpdf.php';
        $actualPath = module_views_path('programs','themes/' . active_clients_theme() . '/views/programs/programpdf.php');

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
