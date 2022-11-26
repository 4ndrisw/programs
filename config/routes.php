<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['programs/program/(:num)/(:any)'] = 'program/index/$1/$2';

/**
 * @since 2.0.0
 */
$route['programs/list'] = 'myprogram/list';
$route['programs/list_inspector_staffs'] = 'myprogram/inspector_staff';
$route['programs/client/create'] = 'myprogram/create_program';

$route['programs/show/(:num)/(:any)'] = 'myprogram/show/$1/$2';
$route['programs/office/(:num)/(:any)'] = 'myprogram/office/$1/$2';
$route['programs/pdf/(:num)'] = 'myprogram/pdf/$1';
$route['programs/office_pdf/(:num)'] = 'myprogram/office_pdf/$1';
