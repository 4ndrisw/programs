<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once('install/programs.php');
require_once('install/program_activity.php');
require_once('install/program_items.php');
require_once('install/program_members.php');



$CI->db->query("
INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('program', 'program-send-to-client', 'english', 'Send program to Customer', 'program # {program_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached program <strong># {program_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>program state:</strong> {program_state}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-already-send', 'english', 'program Already Sent to Customer', 'program # {program_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your program request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-declined-to-staff', 'english', 'program Declined (Sent to Staff)', 'Customer Declined program', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined program with number <strong># {program_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-accepted-to-staff', 'english', 'program Accepted (Sent to Staff)', 'Customer Accepted program', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted program with number <strong># {program_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-thank-you-to-customer', 'english', 'Thank You Email (Sent to Customer After Accept)', 'Thank for you accepting program', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank for for accepting the program.</span><br /> <br /><span style=\"font-size: 12pt;\">We look forward to doing business with you.</span><br /> <br /><span style=\"font-size: 12pt;\">We will contact you as soon as possible.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-expiry-reminder', 'english', 'program Expiration Reminder', 'program Expiration Reminder', '<p><span style=\"font-size: 12pt;\">Hello {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">The program with <strong># {program_number}</strong> will expire on <strong>{program_duedate}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-send-to-client', 'english', 'Send program to Customer', 'program # {program_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached program <strong># {program_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>program state:</strong> {program_state}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-already-send', 'english', 'program Already Sent to Customer', 'program # {program_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your program request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-declined-to-staff', 'english', 'program Declined (Sent to Staff)', 'Customer Declined program', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined program with number <strong># {program_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-accepted-to-staff', 'english', 'program Accepted (Sent to Staff)', 'Customer Accepted program', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted program with number <strong># {program_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'staff-added-as-project-member', 'english', 'Staff Added as Project Member', 'New project assigned to you', '<p>Hi <br /><br />New program has been assigned to you.<br /><br />You can view the program on the following link <a href=\"{program_link}\">program__number</a><br /><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
('program', 'program-accepted-to-staff', 'english', 'program Accepted (Sent to Staff)', 'Customer Accepted program', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted program with number <strong># {program_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the program on the following link: <a href=\"{program_link}\">{program_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0);
");
/*
 *
 */

// Add options for programs
add_option('delete_only_on_last_program', 1);
add_option('program_prefix', 'PRG-');
add_option('next_program_number', 1);
add_option('default_program_assigned', 9);
add_option('program_number_decrement_on_delete', 0);
add_option('program_number_format', 4);
add_option('program_year', date('Y'));
add_option('exclude_program_from_client_area_with_draft_state', 1);
add_option('predefined_clientnote_program', '- Staf diatas untuk melakukan riksa uji pada peralatan tersebut.
- Staf diatas untuk membuat dokumentasi riksa uji sesuai kebutuhan.');
add_option('predefined_terms_program', '- Pelaksanaan riksa uji harus mengikuti prosedur yang ditetapkan perusahaan pemilik alat.
- Dilarang membuat dokumentasi tanpa seizin perusahaan pemilik alat.
- Dokumen ini diterbitkan dari sistem CRM, tidak memerlukan tanda tangan dari PT. Cipta Mas Jaya');
add_option('program_due_after', 1);
add_option('allow_staff_view_programs_assigned', 1);
add_option('show_assigned_on_programs', 1);
add_option('require_client_logged_in_to_view_program', 0);

add_option('show_project_on_program', 1);
add_option('programs_pipeline_limit', 1);
add_option('default_programs_pipeline_sort', 1);
add_option('program_accept_identity_confirmation', 1);
add_option('program_qrcode_size', '160');
add_option('program_send_telegram_message', 0);


/*

DROP TABLE `tblprograms`;
DROP TABLE `tblprogram_activity`, `tblprogram_items`, `tblprogram_members`;
delete FROM `tbloptions` WHERE `name` LIKE '%program%';
DELETE FROM `tblemailtemplates` WHERE `type` LIKE 'program';



*/