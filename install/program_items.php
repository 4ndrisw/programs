<?php defined('BASEPATH') or exit('No direct script access allowed');


if (!$CI->db->table_exists(db_prefix() . 'program_items')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "program_items` (
        `id` int(11) NOT NULL,
        `government_id` int(11) NOT NULL DEFAULT 0,
        `institution_id` int(11) NOT NULL DEFAULT 0,
        `inspector_id` int(11) NOT NULL DEFAULT 0,
        `inspector_staff_id` int(11) NOT NULL DEFAULT 0,
        `surveyor_id` int(11) NOT NULL DEFAULT 0,
        `clientid` int(11) NOT NULL DEFAULT 0,
        `peralatan_id` int(11) NOT NULL DEFAULT 0,
        `program_id` int(11) NOT NULL DEFAULT 0,
        `inspection_id` int(11) DEFAULT NULL,
        `inspection_date` datetime DEFAULT NULL,
        `licence_id` int(11) DEFAULT NULL,
        `licence_date` datetime DEFAULT NULL,
        `suket_addfrom` int(1) DEFAULT NULL,
        `tanggal_penerbitan` date DEFAULT NULL,
        `tanggal_kadaluarsa` date DEFAULT NULL,
        `nomor_suket` varchar(30) DEFAULT NULL,
        `institution_head_id` int(11) DEFAULT NULL,
        `kepala_dinas_nip` varchar(30) DEFAULT NULL,
        `kepala_dinas_nama` varchar(100) DEFAULT NULL,
        `kelompok_alat` varchar(10) DEFAULT NULL,
        `jenis_pesawat_id` int(11) DEFAULT NULL,
        `jenis_pesawat` varchar(60) DEFAULT NULL,
        `nama_pesawat` varchar(100) DEFAULT NULL,
        `nomor_seri` varchar(60) DEFAULT NULL,
        `nomor_unit` varchar(30) DEFAULT NULL,
        `task_id` int(11) NOT NULL,
        `equipment_name` varchar(60) DEFAULT NULL,
        `expired` date DEFAULT NULL,
        `project_id` int(11) NOT NULL,
        `datecreated` datetime NOT NULL DEFAULT current_timestamp(),
        `addedfrom` int(11) NOT NULL DEFAULT 0,
        `inspectionedfrom` int(11) DEFAULT NULL,
        `licence_addfrom` int(11) DEFAULT NULL,
        `tanggal_suket` date DEFAULT NULL,
        `flag` tinyint(1) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'program_items`
          ADD PRIMARY KEY (`id`),
          ADD KEY `clientid` (`clientid`),
          ADD KEY `inspector_id` (`inspector_id`),
          ADD KEY `inspector_staff_id` (`inspector_staff_id`),
          ADD KEY `institution_id` (`institution_id`),
          ADD KEY `peralatan_id` (`peralatan_id`),
          ADD KEY `surveyor_id` (`surveyor_id`),
          ADD KEY `addedfrom` (`addedfrom`),
          ADD KEY `datecreated` (`datecreated`),
          ADD KEY `goverment_id` (`government_id`),
          ADD KEY `inspection_id` (`inspection_id`),
          ADD KEY `inspectionedfrom` (`inspectionedfrom`),
          ADD KEY `licence_id` (`licence_id`),
          ADD KEY `licence_date` (`licence_date`),
          ADD KEY `inspection_date` (`inspection_date`),
          ADD KEY `licence_addfrom` (`licence_addfrom`),
          ADD KEY `tanggal_penerbitan` (`tanggal_penerbitan`),
          ADD KEY `tanggal_kadaluarsa` (`tanggal_kadaluarsa`),
          ADD KEY `suket_addfrom` (`suket_addfrom`)
    ;
  ');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'program_items`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
