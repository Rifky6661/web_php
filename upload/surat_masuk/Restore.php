<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Carbon\Carbon;

class Restore extends MY_Controller {

	public $dataparsing = array();
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('wilayah_model','wilayah');
		$this->load->model('penerima_model','psg');
		$this->load->model('ukur_model','ukur');
		$this->load->model('pmt_model','ukur');
		$this->load->model('user_model','user');
		$this->load->library('pagination');
		if(!is_cli()){
            if($this->session->userdata('login')==false){
                redirect('login');
            }
        }
	}

	public function index() {
		$this->dataparsing['subtitle']= 'Restore Data';
		$this->dataparsing['title']= 'Restore Data';
		$this->dataparsing['active_link'] = 'restore';
		$this->renderView('Setting/restore');
	}

    public function do_restore() {
        $pkm = $this->session->userdata("login_pkm");

        $min = $this->db->query("SELECT value from config where name = 'min_restore'")->row()->value;
        //$lastUpdate = $this->db->query("SELECT id, date from backup_restore_limit where type ='restore' AND id_pkm = '$pkm'")->row();
        $lastUpdate = array();
        if(!empty($lastUpdate)) {
            $limit = Carbon::parse($lastUpdate->date)->diffInDays(Carbon::now());
            if($limit < $min) {
                $this->dataparsing['message'] = 'Restore Gagal! Restore Limit, bisa melakukan restore lagi tanggal ' . Carbon::parse($lastUpdate->date)->addDay($min)->format('j F Y, H:i');
                $this->dataparsing['subtitle']= 'Restore Data';
                $this->dataparsing['title']= 'Restore Data';
                $this->dataparsing['active_link'] = 'restore';
                $this->renderView('Setting/restore_finish');
            } else {
                $this->update($pkm, $lastUpdate);
            }
        } else {
            $this->update($pkm, $lastUpdate);
        }

    }

    public function restoreMaster($folder)
    {
        $this->restoreProvinces($folder);
        $this->restoreRegencies($folder);
        $this->restoreDistrict($folder);
        $this->restorePuskesmas($folder);
        $this->restorePuskesmasVillages($folder);
        $this->restoreVillages($folder);
        $this->restorePosyandu($folder);
    }

    /**
     * @param $folder
     * @param $pkm
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restoreIdentitas($folder, $pkm)
    {
        $this->db->db_debug = TRUE;
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/identitas.xlsx');
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:AP$highestRow");

        $insertParam = [];
        $meta = [
            'duplicate_identity' => 0,
            'duplicate_detail'   => [],
            'invalid'            => 0,
            'update'             => 0,
            'insert'             => 0,
            'total_data'         => count($data)
        ];
        foreach ($data as $row) {
            $i = 0;

            if ($row[6] == $pkm) {
                $sql = "select count(id_prim) as jml from identitas where ktp = '" . $row[1] . "' AND PKM != '$pkm'";
                $query = $this->db->query($sql);
                if ($query->row()->jml > 0) { //  JIka KTP sudah ada dan tidak pada PKM Tsb
                    if(count($meta['duplicate_detail']) <= 100){
                        $meta['duplicate_detail'][] = ['ktp' => $row[1], 'nama' => $row[4], 'tl' => $row[12], 'jk' => $row[16], 'jenis' => $row[17]];
                    }

                    $meta['duplicate_identity']++;
                } else {
                    $sql = "select count(id_prim) as jml from identitas where ktp = '" . $row[1] . "' AND PKM = '$pkm'";
                    $query = $this->db->query($sql);
                    if ($query->row()->jml > 0) { //  JIka KTP sudah ada dan pada PKM Tsb
                        // Update
                        $meta['update']++;
                        $tmp = [
                            'ID'            => $row[$i++],
                            'KTP'           => $row[$i++],
                            'nonik'         => $row[$i++],
                            'OP_VAL'        => $row[$i++],
                            'NAMA'          => $row[$i++],
                            'KEL'           => $row[$i++],
                            'PKM'           => $row[$i++],
                            'POSY'          => $row[$i++],
                            'ALAMAT'        => $row[$i++],
                            'rt'            => $row[$i++],
                            'rw'            => $row[$i++],
                            'telp_hp'       => $row[$i++],
                            'TL'            => $row[$i++],
                            'bb_lahir'      => $row[$i++],
                            'kms'           => $row[$i++],
                            'IMD'           => $row[$i++],
                            'JK'            => $row[$i++],
                            'JENIS'         => $row[$i++],
                            'ukur_bln'      => $row[$i++],
                            'ukur_thn'      => $row[$i++],
                            'ZS_BBU_N'      => $row[$i++],
                            'ZS_TBU_N'      => $row[$i++],
                            'ZS_BBTB_N'     => $row[$i++],
                            'ZS_IMTU_N'     => $row[$i++],
                            'KAT_BBU_N'     => $row[$i++],
                            'KAT_TBU_N'     => $row[$i++],
                            'KAT_BBTB_N'    => $row[$i++],
                            'KAT_IMTU_N'    => $row[$i++],
                            'KAT_LILA_N'    => $row[$i++],
                            'rujuk'         => $row[$i++],
                            'created_by'    => $row[$i++],
                            'created_date'  => $row[$i++],
                            'modified_by'   => $row[$i++],
                            'modified_date' => $row[$i++],
                            'created_app'   => $row[$i++],
                            'update_roll'   => $row[$i++],
                            'nm_ortu'       => $row[$i++],
                            'nik_ortu'      => $row[$i++],
                            'no_kk'         => $row[$i++],
                            'PROP'          => $row[$i++],
                            'KAB'           => $row[$i++],
                            'KEC'           => $row[$i]];

                        unset($tmp[1]);
                        $this->db->where('KTP', $row[1])->update('identitas', $tmp);
                    } else {
                        // Insert Batch
                        $tmp = [
                            'ID'            => $row[$i++],
                            'KTP'           => $row[$i++],
                            'nonik'         => $row[$i++],
                            'OP_VAL'        => $row[$i++],
                            'NAMA'          => $row[$i++],
                            'KEL'           => $row[$i++],
                            'PKM'           => $row[$i++],
                            'POSY'          => $row[$i++],
                            'ALAMAT'        => $row[$i++],
                            'rt'            => $row[$i++],
                            'rw'            => $row[$i++],
                            'telp_hp'       => $row[$i++],
                            'TL'            => $row[$i++],
                            'bb_lahir'      => $row[$i++],
                            'kms'           => $row[$i++],
                            'IMD'           => $row[$i++],
                            'JK'            => $row[$i++],
                            'JENIS'         => $row[$i++],
                            'ukur_bln'      => $row[$i++],
                            'ukur_thn'      => $row[$i++],
                            'ZS_BBU_N'      => $row[$i++],
                            'ZS_TBU_N'      => $row[$i++],
                            'ZS_BBTB_N'     => $row[$i++],
                            'ZS_IMTU_N'     => $row[$i++],
                            'KAT_BBU_N'     => $row[$i++],
                            'KAT_TBU_N'     => $row[$i++],
                            'KAT_BBTB_N'    => $row[$i++],
                            'KAT_IMTU_N'    => $row[$i++],
                            'KAT_LILA_N'    => $row[$i++],
                            'rujuk'         => $row[$i++],
                            'created_by'    => $row[$i++],
                            'created_date'  => $row[$i++],
                            'modified_by'   => $row[$i++],
                            'modified_date' => $row[$i++],
                            'created_app'   => $row[$i++],
                            'update_roll'   => $row[$i++],
                            'nm_ortu'       => $row[$i++],
                            'nik_ortu'      => $row[$i++],
                            'no_kk'         => $row[$i++],
                            'PROP'          => $row[$i++],
                            'KAB'           => $row[$i++],
                            'KEC'           => $row[$i]];
                        $insertParam[] = $tmp;

                        $meta['insert']++;
                    }
                }
            } else {
                $meta['invalid']++;
            }
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch('identitas', $insertParam);
        }
        return $meta;
    }

    /**
     * @param $folder
     * @param $pkm
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restoreDetasi($folder, $pkm)
    {
        $this->db->db_debug = TRUE;
        $table = 'det_asi';
        $filename = 'det_asi.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:K$highestRow");

        $meta = [
            'update'             => 0,
            'insert'             => 0,
            'total_data'         => count($data)
        ];

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            if ($row[10] == $pkm) {
                $sql = "select count(*) as jml from $table where ktp = '" . $row[3] . "'";
                $query = $this->db->query($sql);
                if ($query->row()->jml > 0) { //  JIka KTP sudah ada dan pada PKM Tsb
                    // Update
                    $tmp = [
                        'id_ukur' => $row[$i++],
                        'id_identitas' => $row[$i++],
                        'ID' => $row[$i++],
                        'KTP' => $row[$i++],
                        'asi1' => $row[$i++],
                        'asi2' => $row[$i++],
                        'asi3' => $row[$i++],
                        'asi4' => $row[$i++],
                        'asi5' => $row[$i++],
                        'asi6' => $row[$i]
                    ];
                    $meta['update']++;

                    unset($tmp[1]);
                    $this->db->where('KTP', $row[3])->update($table, $tmp);
                } else {
                    // Insert Batch
                    $tmp = [
                        'id_ukur' => $row[$i++],
                        'id_identitas' => $row[$i++],
                        'ID' => $row[$i++],
                        'KTP' => $row[$i++],
                        'asi1' => $row[$i++],
                        'asi2' => $row[$i++],
                        'asi3' => $row[$i++],
                        'asi4' => $row[$i++],
                        'asi5' => $row[$i++],
                        'asi6' => $row[$i]
                    ];
                    $insertParam[] = $tmp;
                    $meta['insert']++;
                }
            }
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @param $pkm
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restoreUkur($folder, $pkm)
    {
        $this->db->db_debug = TRUE;
        $table = 'ukur';
        $filename = 'ukur.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:AI$highestRow");

        $meta = [
            'update'             => 0,
            'insert'             => 0,
            'total_data'         => count($data)
        ];

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            if ($row[29] == $pkm) {
                $ktp = $row[2];
                $ukur_lama = "DELETE from $table where ktp = '$ktp' AND ukur_bln = '$row[4]' AND ukur_thn = '$row[5]'";
                $this->db->query($ukur_lama);                
                $ukur_lama = "DELETE from $table where ktp = '$ktp' AND TANGGALUKUR = '$row[3]'";
                $this->db->query($ukur_lama);
                $sql = "select count(*) as jml from $table where ktp = '$ktp' AND ukur_bln = '$row[4]' AND ukur_thn = '$row[5]'";
                $query = $this->db->query($sql);
                if ($query->row()->jml > 0) { //  JIka KTP sudah ada dan pada PKM Tsb
                    // Update
                    $tmp = [
                        'id_identitas' => $row[$i++],
                        'ID' => $row[$i++],
                        'KTP' => $row[$i++],
                        'TANGGALUKUR' => $row[$i++],
                        'ukur_bln' => $row[$i++],
                        'ukur_thn' => $row[$i++],
                        'UKURBERAT' => $row[$i++],
                        'BERAT' => $row[$i++],
                        'UKURTINGGI' => $row[$i++],
                        'CARAUKUR' => $row[$i++],
                        'TINGGI' => $row[$i++],
                        'UKURLILA' => $row[$i++],
                        'LILA' => $row[$i++],
                        'vita' => $row[$i++],
                        'vita_nilai' => $row[$i++],
                        'ttd' => $row[$i++],
                        'ttd_nilai1' => $row[$i++],
                        'ttd_nilai2' => $row[$i++],
                        'CATATAN' => $row[$i++],
                        'ZS_BBU' => $row[$i++],
                        'KAT_BBU' => $row[$i++],
                        'ZS_TBU' => $row[$i++],
                        'KAT_TBU' => $row[$i++],
                        'ZS_BBTB' => $row[$i++],
                        'KAT_BBTB' => $row[$i++],
                        'ZS_IMTU' => $row[$i++],
                        'KAT_IMTU' => $row[$i++],
                        'KAT_LILA' => $row[$i++],
                        'bantu' => $row[$i++],
                        'PKM'=>$row[$i++],
                        'prop'=>$row[$i++],
                        'kab'=>$row[$i++],
                        'kec'=>$row[$i++],
                        'kel'=>$row[$i++],
                        'posy'=>$row[$i]
                    ];
                    $meta['update']++;

                    unset($tmp[1]);
                    $this->db->where([
                        'KTP' => $ktp,
                        'ukur_bln' => $row[4],
                        'ukur_thn' => $row[5],
                        ])->update($table, $tmp);
                } else {
                    // Insert Batch
                    $tmp = [
                        'id_identitas' => $row[$i++],
                        'ID' => $row[$i++],
                        'KTP' => $row[$i++],
                        'TANGGALUKUR' => $row[$i++],
                        'ukur_bln' => $row[$i++],
                        'ukur_thn' => $row[$i++],
                        'UKURBERAT' => $row[$i++],
                        'BERAT' => $row[$i++],
                        'UKURTINGGI' => $row[$i++],
                        'CARAUKUR' => $row[$i++],
                        'TINGGI' => $row[$i++],
                        'UKURLILA' => $row[$i++],
                        'LILA' => $row[$i++],
                        'vita' => $row[$i++],
                        'vita_nilai' => $row[$i++],
                        'ttd' => $row[$i++],
                        'ttd_nilai1' => $row[$i++],
                        'ttd_nilai2' => $row[$i++],
                        'CATATAN' => $row[$i++],
                        'ZS_BBU' => $row[$i++],
                        'KAT_BBU' => $row[$i++],
                        'ZS_TBU' => $row[$i++],
                        'KAT_TBU' => $row[$i++],
                        'ZS_BBTB' => $row[$i++],
                        'KAT_BBTB' => $row[$i++],
                        'ZS_IMTU' => $row[$i++],
                        'KAT_IMTU' => $row[$i++],
                        'KAT_LILA' => $row[$i++],
                        'bantu' => $row[$i++],
                        'PKM'=>$row[$i++],
                        'prop'=>$row[$i++],
                        'kab'=>$row[$i++],
                        'kec'=>$row[$i++],
                        'kel'=>$row[$i++],
                        'posy'=>$row[$i]
                    ];
                    $insertParam[] = $tmp;
                    $this->db->insert($table, $tmp);
                    $meta['insert']++;
                }
            }
        }

        /*if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }*/
    }

    public function restoreKpsp($folder, $pkm)
    {
        $this->db->db_debug = TRUE;
        $table = 't_kpsp';
        $filename = 'kpsp.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:FX$highestRow");

        $meta = [
            'update'             => 0,
            'insert'             => 0,
            'total_data'         => count($data)
        ];

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

                $ktp = $row[0];
                $ukur_lama = "DELETE from $table where tkp_nik = '$ktp'";
                $this->db->query($ukur_lama);
                $sql = "select count(*) as jml from $table where tkp_nik = '$ktp'";
                $query = $this->db->query($sql);
                if ($query->row()->jml > 0) { //  JIka KTP sudah ada dan pada PKM Tsb
                    // Update
                    $tmp = [
                        'tkp_nik'=>$row[$i++],
                        'tkp_created_date'=>$row[$i++],
                        'tkp_3b_1_gk_terlentang'=>$row[$i++],
                        'tkp_3b_2_sdk_terlentang'=>$row[$i++],
                        'tkp_3b_3_bdb_ngoceh'=>$row[$i++],
                        'tkp_3b_4_sdk_waktu'=>$row[$i++],
                        'tkp_3b_5_bdb_tertawa'=>$row[$i++],
                        'tkp_3b_6_gh_wool'=>$row[$i++],
                        'tkp_3b_7_gh_wool_merah'=>$row[$i++],
                        'tkp_3b_8_gk_telungkup'=>$row[$i++],
                        'tkp_3b_9_gk_sudut'=>$row[$i++],
                        'tkp_3b_10_gk_gambar'=>$row[$i++],
                        'tkp_rekap_3b'=>$row[$i++],
                        'tkp_6b_1_gh_wajan'=>$row[$i++],
                        'tkp_6b_2_gk_pegang'=>$row[$i++],
                        'tkp_6b_3_gk_penyanggah'=>$row[$i++],
                        'tkp_6b_4_gk_posisi'=>$row[$i++],
                        'tkp_6b_5_gh_sentuh'=>$row[$i++],
                        'tkp_6b_6_gh_dapatkah'=>$row[$i++],
                        'tkp_6b_7_gh_meraih'=>$row[$i++],
                        'tkp_6b_8_bdb_suara'=>$row[$i++],
                        'tkp_6b_9_gk_berbalik'=>$row[$i++],
                        'tkp_6b_10_sdk_pernahkah'=>$row[$i++],
                        'tkp_rekap_6b'=>$row[$i++],
                        'tkp_9b_1'=>$row[$i++],
                        'tkp_9b_2'=>$row[$i++],
                        'tkp_9b_3'=>$row[$i++],
                        'tkp_9b_4'=>$row[$i++],
                        'tkp_9b_5'=>$row[$i++],
                        'tkp_9b_6'=>$row[$i++],
                        'tkp_9b_7'=>$row[$i++],
                        'tkp_9b_8'=>$row[$i++],
                        'tkp_9b_9'=>$row[$i++],
                        'tkp_9b_10'=>$row[$i++],
                        'tkp_rekap_9b'=>$row[$i++],
                        'tkp_12b_1'=>$row[$i++],
                        'tkp_12b_2'=>$row[$i++],
                        'tkp_12b_3'=>$row[$i++],
                        'tkp_12b_4'=>$row[$i++],
                        'tkp_12b_5'=>$row[$i++],
                        'tkp_12b_6'=>$row[$i++],
                        'tkp_12b_7'=>$row[$i++],
                        'tkp_12b_8'=>$row[$i++],
                        'tkp_12b_9'=>$row[$i++],
                        'tkp_12b_10'=>$row[$i++],
                        'tkp_rekap_12b'=>$row[$i++],
                        'tkp_15b_1'=>$row[$i++],
                        'tkp_15b_2'=>$row[$i++],
                        'tkp_15b_3'=>$row[$i++],
                        'tkp_15b_4'=>$row[$i++],
                        'tkp_15b_5'=>$row[$i++],
                        'tkp_15b_6'=>$row[$i++],
                        'tkp_15b_7'=>$row[$i++],
                        'tkp_15b_8'=>$row[$i++],
                        'tkp_15b_9'=>$row[$i++],
                        'tkp_15b_10'=>$row[$i++],
                        'tkp_rekap_15b'=>$row[$i++],
                        'tkp_18b_1'=>$row[$i++],
                        'tkp_18b_2'=>$row[$i++],
                        'tkp_18b_3'=>$row[$i++],
                        'tkp_18b_4'=>$row[$i++],
                        'tkp_18b_5'=>$row[$i++],
                        'tkp_18b_6'=>$row[$i++],
                        'tkp_18b_7'=>$row[$i++],
                        'tkp_18b_8'=>$row[$i++],
                        'tkp_18b_9'=>$row[$i++],
                        'tkp_18b_10'=>$row[$i++],
                        'tkp_rekap_18b'=>$row[$i++],
                        'tkp_21b_1'=>$row[$i++],
                        'tkp_21b_2'=>$row[$i++],
                        'tkp_21b_3'=>$row[$i++],
                        'tkp_21b_4'=>$row[$i++],
                        'tkp_21b_5'=>$row[$i++],
                        'tkp_21b_6'=>$row[$i++],
                        'tkp_21b_7'=>$row[$i++],
                        'tkp_21b_8'=>$row[$i++],
                        'tkp_21b_9'=>$row[$i++],
                        'tkp_21b_10'=>$row[$i++],
                        'tkp_rekap_21b'=>$row[$i++],
                        'tkp_24b_1'=>$row[$i++],
                        'tkp_24b_2'=>$row[$i++],
                        'tkp_24b_3'=>$row[$i++],
                        'tkp_24b_4'=>$row[$i++],
                        'tkp_24b_5'=>$row[$i++],
                        'tkp_24b_6'=>$row[$i++],
                        'tkp_24b_7'=>$row[$i++],
                        'tkp_24b_8'=>$row[$i++],
                        'tkp_24b_9'=>$row[$i++],
                        'tkp_24b_10'=>$row[$i++],
                        'tkp_rekap_24b'=>$row[$i++],
                        'tkp_30b_1'=>$row[$i++],
                        'tkp_30b_2'=>$row[$i++],
                        'tkp_30b_3'=>$row[$i++],
                        'tkp_30b_4'=>$row[$i++],
                        'tkp_30b_5'=>$row[$i++],
                        'tkp_30b_6'=>$row[$i++],
                        'tkp_30b_7'=>$row[$i++],
                        'tkp_30b_8'=>$row[$i++],
                        'tkp_30b_9'=>$row[$i++],
                        'tkp_30b_10'=>$row[$i++],
                        'tkp_rekap_30b'=>$row[$i++],
                        'tkp_36b_1'=>$row[$i++],
                        'tkp_36b_2'=>$row[$i++],
                        'tkp_36b_3'=>$row[$i++],
                        'tkp_36b_4'=>$row[$i++],
                        'tkp_36b_5'=>$row[$i++],
                        'tkp_36b_6'=>$row[$i++],
                        'tkp_36b_7'=>$row[$i++],
                        'tkp_36b_8'=>$row[$i++],
                        'tkp_36b_9'=>$row[$i++],
                        'tkp_36b_10'=>$row[$i++],
                        'tkp_rekap_36b'=>$row[$i++],
                        'tkp_42b_1'=>$row[$i++],
                        'tkp_42b_2'=>$row[$i++],
                        'tkp_42b_3'=>$row[$i++],
                        'tkp_42b_4'=>$row[$i++],
                        'tkp_42b_5'=>$row[$i++],
                        'tkp_42b_6'=>$row[$i++],
                        'tkp_42b_7'=>$row[$i++],
                        'tkp_42b_8'=>$row[$i++],
                        'tkp_42b_9'=>$row[$i++],
                        'tkp_42b_10'=>$row[$i++],
                        'tkp_rekap_42b'=>$row[$i++],
                        'tkp_48b_1'=>$row[$i++],
                        'tkp_48b_2'=>$row[$i++],
                        'tkp_48b_3'=>$row[$i++],
                        'tkp_48b_4'=>$row[$i++],
                        'tkp_48b_5'=>$row[$i++],
                        'tkp_48b_6'=>$row[$i++],
                        'tkp_48b_7'=>$row[$i++],
                        'tkp_48b_8'=>$row[$i++],
                        'tkp_48b_9'=>$row[$i++],
                        'tkp_48b_10'=>$row[$i++],
                        'tkp_rekap_48b'=>$row[$i++],
                        'tkp_54b_1'=>$row[$i++],
                        'tkp_54b_2'=>$row[$i++],
                        'tkp_54b_3'=>$row[$i++],
                        'tkp_54b_4'=>$row[$i++],
                        'tkp_54b_5'=>$row[$i++],
                        'tkp_54b_6'=>$row[$i++],
                        'tkp_54b_7'=>$row[$i++],
                        'tkp_54b_8'=>$row[$i++],
                        'tkp_54b_9'=>$row[$i++],
                        'tkp_54b_10'=>$row[$i++],
                        'tkp_rekap_54b'=>$row[$i++],
                        'tkp_60b_1'=>$row[$i++],
                        'tkp_60b_2'=>$row[$i++],
                        'tkp_60b_3'=>$row[$i++],
                        'tkp_60b_4'=>$row[$i++],
                        'tkp_60b_5'=>$row[$i++],
                        'tkp_60b_6'=>$row[$i++],
                        'tkp_60b_7'=>$row[$i++],
                        'tkp_60b_8'=>$row[$i++],
                        'tkp_60b_9'=>$row[$i++],
                        'tkp_60b_10'=>$row[$i++],
                        'tkp_rekap_60b'=>$row[$i++],
                        'tkp_66b_1'=>$row[$i++],
                        'tkp_66b_2'=>$row[$i++],
                        'tkp_66b_3'=>$row[$i++],
                        'tkp_66b_4'=>$row[$i++],
                        'tkp_66b_5'=>$row[$i++],
                        'tkp_66b_6'=>$row[$i++],
                        'tkp_66b_7'=>$row[$i++],
                        'tkp_66b_8'=>$row[$i++],
                        'tkp_66b_9'=>$row[$i++],
                        'tkp_66b_10'=>$row[$i++],
                        'tkp_rekap_66b'=>$row[$i++],
                        'tkp_72b_1'=>$row[$i++],
                        'tkp_72b_2'=>$row[$i++],
                        'tkp_72b_3'=>$row[$i++],
                        'tkp_72b_4'=>$row[$i++],
                        'tkp_72b_5'=>$row[$i++],
                        'tkp_72b_6'=>$row[$i++],
                        'tkp_72b_7'=>$row[$i++],
                        'tkp_72b_8'=>$row[$i++],
                        'tkp_72b_9'=>$row[$i++],
                        'tkp_72b_10'=>$row[$i++],
                        'tkp_rekap_72b'=>$row[$i++]

                    ];
                    $meta['update']++;
                } else {
                    // Insert Batch
                    $tmp = [
                        'tkp_nik'=>$row[$i++],
                        'tkp_created_date'=>$row[$i++],
                        'tkp_3b_1_gk_terlentang'=>$row[$i++],
                        'tkp_3b_2_sdk_terlentang'=>$row[$i++],
                        'tkp_3b_3_bdb_ngoceh'=>$row[$i++],
                        'tkp_3b_4_sdk_waktu'=>$row[$i++],
                        'tkp_3b_5_bdb_tertawa'=>$row[$i++],
                        'tkp_3b_6_gh_wool'=>$row[$i++],
                        'tkp_3b_7_gh_wool_merah'=>$row[$i++],
                        'tkp_3b_8_gk_telungkup'=>$row[$i++],
                        'tkp_3b_9_gk_sudut'=>$row[$i++],
                        'tkp_3b_10_gk_gambar'=>$row[$i++],
                        'tkp_rekap_3b'=>$row[$i++],
                        'tkp_6b_1_gh_wajan'=>$row[$i++],
                        'tkp_6b_2_gk_pegang'=>$row[$i++],
                        'tkp_6b_3_gk_penyanggah'=>$row[$i++],
                        'tkp_6b_4_gk_posisi'=>$row[$i++],
                        'tkp_6b_5_gh_sentuh'=>$row[$i++],
                        'tkp_6b_6_gh_dapatkah'=>$row[$i++],
                        'tkp_6b_7_gh_meraih'=>$row[$i++],
                        'tkp_6b_8_bdb_suara'=>$row[$i++],
                        'tkp_6b_9_gk_berbalik'=>$row[$i++],
                        'tkp_6b_10_sdk_pernahkah'=>$row[$i++],
                        'tkp_rekap_6b'=>$row[$i++],
                        'tkp_9b_1'=>$row[$i++],
                        'tkp_9b_2'=>$row[$i++],
                        'tkp_9b_3'=>$row[$i++],
                        'tkp_9b_4'=>$row[$i++],
                        'tkp_9b_5'=>$row[$i++],
                        'tkp_9b_6'=>$row[$i++],
                        'tkp_9b_7'=>$row[$i++],
                        'tkp_9b_8'=>$row[$i++],
                        'tkp_9b_9'=>$row[$i++],
                        'tkp_9b_10'=>$row[$i++],
                        'tkp_rekap_9b'=>$row[$i++],
                        'tkp_12b_1'=>$row[$i++],
                        'tkp_12b_2'=>$row[$i++],
                        'tkp_12b_3'=>$row[$i++],
                        'tkp_12b_4'=>$row[$i++],
                        'tkp_12b_5'=>$row[$i++],
                        'tkp_12b_6'=>$row[$i++],
                        'tkp_12b_7'=>$row[$i++],
                        'tkp_12b_8'=>$row[$i++],
                        'tkp_12b_9'=>$row[$i++],
                        'tkp_12b_10'=>$row[$i++],
                        'tkp_rekap_12b'=>$row[$i++],
                        'tkp_15b_1'=>$row[$i++],
                        'tkp_15b_2'=>$row[$i++],
                        'tkp_15b_3'=>$row[$i++],
                        'tkp_15b_4'=>$row[$i++],
                        'tkp_15b_5'=>$row[$i++],
                        'tkp_15b_6'=>$row[$i++],
                        'tkp_15b_7'=>$row[$i++],
                        'tkp_15b_8'=>$row[$i++],
                        'tkp_15b_9'=>$row[$i++],
                        'tkp_15b_10'=>$row[$i++],
                        'tkp_rekap_15b'=>$row[$i++],
                        'tkp_18b_1'=>$row[$i++],
                        'tkp_18b_2'=>$row[$i++],
                        'tkp_18b_3'=>$row[$i++],
                        'tkp_18b_4'=>$row[$i++],
                        'tkp_18b_5'=>$row[$i++],
                        'tkp_18b_6'=>$row[$i++],
                        'tkp_18b_7'=>$row[$i++],
                        'tkp_18b_8'=>$row[$i++],
                        'tkp_18b_9'=>$row[$i++],
                        'tkp_18b_10'=>$row[$i++],
                        'tkp_rekap_18b'=>$row[$i++],
                        'tkp_21b_1'=>$row[$i++],
                        'tkp_21b_2'=>$row[$i++],
                        'tkp_21b_3'=>$row[$i++],
                        'tkp_21b_4'=>$row[$i++],
                        'tkp_21b_5'=>$row[$i++],
                        'tkp_21b_6'=>$row[$i++],
                        'tkp_21b_7'=>$row[$i++],
                        'tkp_21b_8'=>$row[$i++],
                        'tkp_21b_9'=>$row[$i++],
                        'tkp_21b_10'=>$row[$i++],
                        'tkp_rekap_21b'=>$row[$i++],
                        'tkp_24b_1'=>$row[$i++],
                        'tkp_24b_2'=>$row[$i++],
                        'tkp_24b_3'=>$row[$i++],
                        'tkp_24b_4'=>$row[$i++],
                        'tkp_24b_5'=>$row[$i++],
                        'tkp_24b_6'=>$row[$i++],
                        'tkp_24b_7'=>$row[$i++],
                        'tkp_24b_8'=>$row[$i++],
                        'tkp_24b_9'=>$row[$i++],
                        'tkp_24b_10'=>$row[$i++],
                        'tkp_rekap_24b'=>$row[$i++],
                        'tkp_30b_1'=>$row[$i++],
                        'tkp_30b_2'=>$row[$i++],
                        'tkp_30b_3'=>$row[$i++],
                        'tkp_30b_4'=>$row[$i++],
                        'tkp_30b_5'=>$row[$i++],
                        'tkp_30b_6'=>$row[$i++],
                        'tkp_30b_7'=>$row[$i++],
                        'tkp_30b_8'=>$row[$i++],
                        'tkp_30b_9'=>$row[$i++],
                        'tkp_30b_10'=>$row[$i++],
                        'tkp_rekap_30b'=>$row[$i++],
                        'tkp_36b_1'=>$row[$i++],
                        'tkp_36b_2'=>$row[$i++],
                        'tkp_36b_3'=>$row[$i++],
                        'tkp_36b_4'=>$row[$i++],
                        'tkp_36b_5'=>$row[$i++],
                        'tkp_36b_6'=>$row[$i++],
                        'tkp_36b_7'=>$row[$i++],
                        'tkp_36b_8'=>$row[$i++],
                        'tkp_36b_9'=>$row[$i++],
                        'tkp_36b_10'=>$row[$i++],
                        'tkp_rekap_36b'=>$row[$i++],
                        'tkp_42b_1'=>$row[$i++],
                        'tkp_42b_2'=>$row[$i++],
                        'tkp_42b_3'=>$row[$i++],
                        'tkp_42b_4'=>$row[$i++],
                        'tkp_42b_5'=>$row[$i++],
                        'tkp_42b_6'=>$row[$i++],
                        'tkp_42b_7'=>$row[$i++],
                        'tkp_42b_8'=>$row[$i++],
                        'tkp_42b_9'=>$row[$i++],
                        'tkp_42b_10'=>$row[$i++],
                        'tkp_rekap_42b'=>$row[$i++],
                        'tkp_48b_1'=>$row[$i++],
                        'tkp_48b_2'=>$row[$i++],
                        'tkp_48b_3'=>$row[$i++],
                        'tkp_48b_4'=>$row[$i++],
                        'tkp_48b_5'=>$row[$i++],
                        'tkp_48b_6'=>$row[$i++],
                        'tkp_48b_7'=>$row[$i++],
                        'tkp_48b_8'=>$row[$i++],
                        'tkp_48b_9'=>$row[$i++],
                        'tkp_48b_10'=>$row[$i++],
                        'tkp_rekap_48b'=>$row[$i++],
                        'tkp_54b_1'=>$row[$i++],
                        'tkp_54b_2'=>$row[$i++],
                        'tkp_54b_3'=>$row[$i++],
                        'tkp_54b_4'=>$row[$i++],
                        'tkp_54b_5'=>$row[$i++],
                        'tkp_54b_6'=>$row[$i++],
                        'tkp_54b_7'=>$row[$i++],
                        'tkp_54b_8'=>$row[$i++],
                        'tkp_54b_9'=>$row[$i++],
                        'tkp_54b_10'=>$row[$i++],
                        'tkp_rekap_54b'=>$row[$i++],
                        'tkp_60b_1'=>$row[$i++],
                        'tkp_60b_2'=>$row[$i++],
                        'tkp_60b_3'=>$row[$i++],
                        'tkp_60b_4'=>$row[$i++],
                        'tkp_60b_5'=>$row[$i++],
                        'tkp_60b_6'=>$row[$i++],
                        'tkp_60b_7'=>$row[$i++],
                        'tkp_60b_8'=>$row[$i++],
                        'tkp_60b_9'=>$row[$i++],
                        'tkp_60b_10'=>$row[$i++],
                        'tkp_rekap_60b'=>$row[$i++],
                        'tkp_66b_1'=>$row[$i++],
                        'tkp_66b_2'=>$row[$i++],
                        'tkp_66b_3'=>$row[$i++],
                        'tkp_66b_4'=>$row[$i++],
                        'tkp_66b_5'=>$row[$i++],
                        'tkp_66b_6'=>$row[$i++],
                        'tkp_66b_7'=>$row[$i++],
                        'tkp_66b_8'=>$row[$i++],
                        'tkp_66b_9'=>$row[$i++],
                        'tkp_66b_10'=>$row[$i++],
                        'tkp_rekap_66b'=>$row[$i++],
                        'tkp_72b_1'=>$row[$i++],
                        'tkp_72b_2'=>$row[$i++],
                        'tkp_72b_3'=>$row[$i++],
                        'tkp_72b_4'=>$row[$i++],
                        'tkp_72b_5'=>$row[$i++],
                        'tkp_72b_6'=>$row[$i++],
                        'tkp_72b_7'=>$row[$i++],
                        'tkp_72b_8'=>$row[$i++],
                        'tkp_72b_9'=>$row[$i++],
                        'tkp_72b_10'=>$row[$i++],
                        'tkp_rekap_72b'=>$row[$i++]
                    ];
                    $insertParam[] = $tmp;
                    $meta['insert']++;
                }
            }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    public function restoreKia($folder, $pkm)
    {
        $this->db->db_debug = TRUE;
        $table = 't_kia';
        $filename = 'kia.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:CA$highestRow");

        $meta = [
            'update'             => 0,
            'insert'             => 0,
            'total_data'         => count($data)
        ];

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

                $ktp = $row[0];
                $ukur_lama = "DELETE from $table where tk_nik = '$ktp'";
                $this->db->query($ukur_lama);
                $sql = "select count(*) as jml from $table where tk_nik = '$ktp'";
                $query = $this->db->query($sql);
                if ($query->row()->jml > 0) { //  JIka KTP sudah ada dan pada PKM Tsb
                    // Update
                    $tmp = [
                        'tk_nik'=>$row[$i++],
                        'tk_created_date'=>$row[$i++],
                        'tk_1b_menatap'=>$row[$i++],
                        'tk_1b_mengeluarkan'=>$row[$i++],
                        'tk_1b_tersenyum'=>$row[$i++],
                        'tk_1b_menggerakan'=>$row[$i++],
                        'tk_rekap_1b'=>$row[$i++],
                        'tk_3b_mengangkat'=>$row[$i++],
                        'tk_3b_tertawa'=>$row[$i++],
                        'tk_3b_menggerakkan'=>$row[$i++],
                        'tk_3b_membalas'=>$row[$i++],
                        'tk_3b_mengoceh'=>$row[$i++],
                        'tk_rekap_3b'=>$row[$i++],
                        'tk_6b_berbalik'=>$row[$i++],
                        'tk_6b_mempertahankan'=>$row[$i++],
                        'tk_6b_meraih'=>$row[$i++],
                        'tk_6b_menirukan'=>$row[$i++],
                        'tk_6b_tersenyum'=>$row[$i++],
                        'tk_rekap_6b'=>$row[$i++],
                        'tk_9b_merambat'=>$row[$i++],
                        'tk_9b_mengucap'=>$row[$i++],
                        'tk_9b_meraih'=>$row[$i++],
                        'tk_9b_mencari'=>$row[$i++],
                        'tk_9b_bermain'=>$row[$i++],
                        'tk_9b_makan'=>$row[$i++],
                        'tk_9b_berdiri'=>$row[$i++],
                        'tk_9b_memegang'=>$row[$i++],
                        'tk_9b_meniru'=>$row[$i++],
                        'tk_9b_mengenal'=>$row[$i++],
                        'tk_9b_takut'=>$row[$i++],
                        'tk_9b_menunjuk'=>$row[$i++],
                        'tk_rekap_9b'=>$row[$i++],
                        'tk_12b_1'=>$row[$i++],
                        'tk_12b_2'=>$row[$i++],
                        'tk_12b_3'=>$row[$i++],
                        'tk_12b_4'=>$row[$i++],
                        'tk_12b_5'=>$row[$i++],
                        'tk_12b_6'=>$row[$i++],
                        'tk_rekap_12b'=>$row[$i++],
                        'tk_2t_naik'=>$row[$i++],
                        'tk_2t_mencoret'=>$row[$i++],
                        'tk_2t_dapat'=>$row[$i++],
                        'tk_2t_menyebut'=>$row[$i++],
                        'tk_2t_memegang'=>$row[$i++],
                        'tk_2t_belajar'=>$row[$i++],
                        'tk_rekap_2t'=>$row[$i++],
                        'tk_3t_mengayuh'=>$row[$i++],
                        'tk_3t_berdiri'=>$row[$i++],
                        'tk_3t_bicara'=>$row[$i++],
                        'tk_3t_mengenal'=>$row[$i++],
                        'tk_3t_menyebut'=>$row[$i++],
                        'tk_3t_menggambar'=>$row[$i++],
                        'tk_3t_bermain'=>$row[$i++],
                        'tk_3t_melepas'=>$row[$i++],
                        'tk_3t_mengenakan'=>$row[$i++],
                        'tk_rekap_3t'=>$row[$i++],
                        'tk_5t_melompat'=>$row[$i++],
                        'tk_5t_menggambar_orang'=>$row[$i++],
                        'tk_5t_menggambar_tanda'=>$row[$i++],
                        'tk_5t_menangkap'=>$row[$i++],
                        'tk_5t_menjawab'=>$row[$i++],
                        'tk_5t_menyebut'=>$row[$i++],
                        'tk_5t_bicaranya'=>$row[$i++],
                        'tk_5t_berpakaian'=>$row[$i++],
                        'tk_5t_mengancing'=>$row[$i++],
                        'tk_5t_menggosok'=>$row[$i++],
                        'tk_rekap_5t'=>$row[$i++],
                        'tk_6t_berjalan'=>$row[$i++],
                        'tk_6t_berdiri'=>$row[$i++],
                        'tk_6t_menggambar_6'=>$row[$i++],
                        'tk_6t_menangkap'=>$row[$i++],
                        'tk_6t_menggambar_segi'=>$row[$i++],
                        'tk_6t_mengerti'=>$row[$i++],
                        'tk_6t_mengenal_angka'=>$row[$i++],
                        'tk_6t_mengenal_warna'=>$row[$i++],
                        'tk_6t_mengikuti'=>$row[$i++],
                        'tk_6t_berpakaian'=>$row[$i++],
                        'tk_rekap_6t'=>$row[$i++]
                    ];
                    $meta['update']++;
                } else {
                    // Insert Batch
                    $tmp = [
                        'tk_nik'=>$row[$i++],
                        'tk_created_date'=>$row[$i++],
                        'tk_1b_menatap'=>$row[$i++],
                        'tk_1b_mengeluarkan'=>$row[$i++],
                        'tk_1b_tersenyum'=>$row[$i++],
                        'tk_1b_menggerakan'=>$row[$i++],
                        'tk_rekap_1b'=>$row[$i++],
                        'tk_3b_mengangkat'=>$row[$i++],
                        'tk_3b_tertawa'=>$row[$i++],
                        'tk_3b_menggerakkan'=>$row[$i++],
                        'tk_3b_membalas'=>$row[$i++],
                        'tk_3b_mengoceh'=>$row[$i++],
                        'tk_rekap_3b'=>$row[$i++],
                        'tk_6b_berbalik'=>$row[$i++],
                        'tk_6b_mempertahankan'=>$row[$i++],
                        'tk_6b_meraih'=>$row[$i++],
                        'tk_6b_menirukan'=>$row[$i++],
                        'tk_6b_tersenyum'=>$row[$i++],
                        'tk_rekap_6b'=>$row[$i++],
                        'tk_9b_merambat'=>$row[$i++],
                        'tk_9b_mengucap'=>$row[$i++],
                        'tk_9b_meraih'=>$row[$i++],
                        'tk_9b_mencari'=>$row[$i++],
                        'tk_9b_bermain'=>$row[$i++],
                        'tk_9b_makan'=>$row[$i++],
                        'tk_9b_berdiri'=>$row[$i++],
                        'tk_9b_memegang'=>$row[$i++],
                        'tk_9b_meniru'=>$row[$i++],
                        'tk_9b_mengenal'=>$row[$i++],
                        'tk_9b_takut'=>$row[$i++],
                        'tk_9b_menunjuk'=>$row[$i++],
                        'tk_rekap_9b'=>$row[$i++],
                        'tk_12b_1'=>$row[$i++],
                        'tk_12b_2'=>$row[$i++],
                        'tk_12b_3'=>$row[$i++],
                        'tk_12b_4'=>$row[$i++],
                        'tk_12b_5'=>$row[$i++],
                        'tk_12b_6'=>$row[$i++],
                        'tk_rekap_12b'=>$row[$i++],
                        'tk_2t_naik'=>$row[$i++],
                        'tk_2t_mencoret'=>$row[$i++],
                        'tk_2t_dapat'=>$row[$i++],
                        'tk_2t_menyebut'=>$row[$i++],
                        'tk_2t_memegang'=>$row[$i++],
                        'tk_2t_belajar'=>$row[$i++],
                        'tk_rekap_2t'=>$row[$i++],
                        'tk_3t_mengayuh'=>$row[$i++],
                        'tk_3t_berdiri'=>$row[$i++],
                        'tk_3t_bicara'=>$row[$i++],
                        'tk_3t_mengenal'=>$row[$i++],
                        'tk_3t_menyebut'=>$row[$i++],
                        'tk_3t_menggambar'=>$row[$i++],
                        'tk_3t_bermain'=>$row[$i++],
                        'tk_3t_melepas'=>$row[$i++],
                        'tk_3t_mengenakan'=>$row[$i++],
                        'tk_rekap_3t'=>$row[$i++],
                        'tk_5t_melompat'=>$row[$i++],
                        'tk_5t_menggambar_orang'=>$row[$i++],
                        'tk_5t_menggambar_tanda'=>$row[$i++],
                        'tk_5t_menangkap'=>$row[$i++],
                        'tk_5t_menjawab'=>$row[$i++],
                        'tk_5t_menyebut'=>$row[$i++],
                        'tk_5t_bicaranya'=>$row[$i++],
                        'tk_5t_berpakaian'=>$row[$i++],
                        'tk_5t_mengancing'=>$row[$i++],
                        'tk_5t_menggosok'=>$row[$i++],
                        'tk_rekap_5t'=>$row[$i++],
                        'tk_6t_berjalan'=>$row[$i++],
                        'tk_6t_berdiri'=>$row[$i++],
                        'tk_6t_menggambar_6'=>$row[$i++],
                        'tk_6t_menangkap'=>$row[$i++],
                        'tk_6t_menggambar_segi'=>$row[$i++],
                        'tk_6t_mengerti'=>$row[$i++],
                        'tk_6t_mengenal_angka'=>$row[$i++],
                        'tk_6t_mengenal_warna'=>$row[$i++],
                        'tk_6t_mengikuti'=>$row[$i++],
                        'tk_6t_berpakaian'=>$row[$i++],
                        'tk_rekap_6t'=>$row[$i++]
                    ];
                    $insertParam[] = $tmp;
                    $meta['insert']++;
                }
            }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }


    /**
     * @param $folder
     * @param $pkm
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restorePmtDetail($folder, $pkm)
    {
        $this->db->db_debug = TRUE;
        $table = 'pmt_detail';
        $filename = 'pmt_detail.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:Q$highestRow");

        $meta = [
            'update'             => 0,
            'insert'             => 0,
            'total_data'         => count($data)
        ];

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            if ($row[16] == $pkm) {
                $ktp = $row[3];
                $sql = "select count(*) as jml from $table where ktp = '$ktp' AND pemberian_ke = '$row[4]'";
                $query = $this->db->query($sql);
                if ($query->row()->jml > 0) { //  JIka KTP sudah ada dan pada PKM Tsb
                    // Update
                    $tmp = [
                        'id_identitas' => $row[$i++],
                        'jns_keluarga' => $row[$i++],
                        'ID' => $row[$i++],
                        'KTP' => $row[$i++],
                        'pemberian_ke' => $row[$i++],
                        'pemberian_tgl' => $row[$i++],
                        'pmt_bln' => $row[$i++],
                        'pmt_thn' => $row[$i++],
                        'pemberian_jml' => $row[$i++],
                        'pemberian_kg' => $row[$i++],
                        'created_by' => $row[$i++],
                        'created_date' => $row[$i++],
                        'modified_by' => $row[$i++],
                        'modified_date' => $row[$i++],
                        'sumber_pmt' => $row[$i++],
                        'pemberian_jml2' => $row[$i]
                    ];
                    $meta['update']++;

                    unset($tmp[1]);
                    $this->db->where([
                        'KTP' => $ktp,
                        'pemberian_ke' => $row[4]
                        ])->update($table, $tmp);
                } else {
                    // Insert Batch
                    $tmp = [
                        'id_identitas' => $row[$i++],
                        'jns_keluarga' => $row[$i++],
                        'ID' => $row[$i++],
                        'KTP' => $row[$i++],
                        'pemberian_ke' => $row[$i++],
                        'pemberian_tgl' => $row[$i++],
                        'pmt_bln' => $row[$i++],
                        'pmt_thn' => $row[$i++],
                        'pemberian_jml' => $row[$i++],
                        'pemberian_kg' => $row[$i++],
                        'created_by' => $row[$i++],
                        'created_date' => $row[$i++],
                        'modified_by' => $row[$i++],
                        'modified_date' => $row[$i++],
                        'sumber_pmt' => $row[$i++],
                        'pemberian_jml2' => $row[$i]
                    ];
                    $insertParam[] = $tmp;
                    $meta['insert']++;
                }
            }
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @param $pkm
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restoreTindakan($folder, $pkm)
    {
        $this->db->db_debug = TRUE;
        $table = 'tindakan';
        $filename = 'tindakan.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:U$highestRow");

        $meta = [
            'update'             => 0,
            'insert'             => 0,
            'total_data'         => count($data)
        ];

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            if ($row[20] == $pkm) {
                $ktp = $row[6];

                $sql = "select count(*) as jml from $table where ktp = '$ktp' AND ukur_bln = '$row[7]' AND ukur_thn='$row[8]'";
                $query = $this->db->query($sql);
                if ($query->row()->jml > 0) { //  JIka KTP sudah ada dan pada PKM Tsb
                    // Update
                    $tmp =  [
                        'id_identitas' => $row[$i++],
                        'tgl_tindakan' => $row[$i++],
                        'dirujuk_ke' => $row[$i++],
                        'catatan' => $row[$i++],
                        'created_by' => $row[$i++],
                        'created_date' => $row[$i++],
                        'KTP' => $row[$i++],
                        'ukur_bln' => $row[$i++],
                        'ukur_thn' => $row[$i++],
                        'jkn' => $row[$i++],
                        'air' => $row[$i++],
                        'jamban' => $row[$i++],
                        'imunisasi' => $row[$i++],
                        'merokok' => $row[$i++],
                        'faktor_6' => $row[$i++],
                        'rwyt_kehamilan' => $row[$i++],
                        'faktor_8' => $row[$i++],
                        'penyakit_1' => $row[$i++],
                        'penyakit_2' => $row[$i++],
                        'kecacingan' => $row[$i]
                    ];
                    $meta['update']++;

                    unset($tmp[1]);
                    $this->db->where([
                        'KTP' => $ktp,
                        'ukur_bln' => $row[7],
                        'ukur_thn' => $row[8]
                        ])->update($table, $tmp);
                } else {
                    // Insert Batch
                    $tmp = [
                        'id_identitas' => $row[$i++],
                        'tgl_tindakan' => $row[$i++],
                        'dirujuk_ke' => $row[$i++],
                        'catatan' => $row[$i++],
                        'created_by' => $row[$i++],
                        'created_date' => $row[$i++],
                        'KTP' => $row[$i++],
                        'ukur_bln' => $row[$i++],
                        'ukur_thn' => $row[$i++],
                        'jkn' => $row[$i++],
                        'air' => $row[$i++],
                        'jamban' => $row[$i++],
                        'imunisasi' => $row[$i++],
                        'merokok' => $row[$i++],
                        'faktor_6' => $row[$i++],
                        'rwyt_kehamilan' => $row[$i++],
                        'faktor_8' => $row[$i++],
                        'penyakit_1' => $row[$i++],
                        'penyakit_2' => $row[$i++],
                        'kecacingan' => $row[$i]
                    ];
                    $insertParam[] = $tmp;
                    $meta['insert']++;
                }
            }
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restoreProvinces($folder)
    {
        $this->db->db_debug = TRUE;
        $this->db->query("DELETE FROM provinces");

        $table = 'provinces';
        $filename = 'provinces.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:B$highestRow");

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            $tmp = [
                'id'   => $row[$i++],
                'name' => $row[$i]
            ];
            $insertParam[] = $tmp;
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restoreRegencies($folder)
    {
        $this->db->db_debug = TRUE;
        $this->db->query("DELETE FROM regencies");

        $table = 'regencies';
        $filename = 'regencies.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:D$highestRow");

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            $tmp = [
                'id'   => $row[$i++],
                'province_id' => $row[$i++],
                'name' => $row[$i++],
                'type' => $row[$i]
            ];
            $insertParam[] = $tmp;
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restoreDistrict($folder)
    {
        $this->db->db_debug = TRUE;
        $this->db->query("DELETE FROM districts");

        $table = 'districts';
        $filename = 'districts.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:C$highestRow");

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            $tmp = [
                'id'   => $row[$i++],
                'regency_id' => $row[$i++],
                'name' => $row[$i]
            ];
            $insertParam[] = $tmp;
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restorePuskesmas($folder)
    {
        $this->db->db_debug = TRUE;
        $this->db->query("DELETE FROM db_depkesmas.puskesmas");

        $table = 'db_depkesmas.puskesmas';
        $filename = 'puskesmas.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:F$highestRow");

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            $tmp = [
                'KODE_PUSK'   => $row[$i++],
                'NAMA_PUSK' => $row[$i++],
                'ALAMAT' => $row[$i++],
                'TELP' => $row[$i++],
                'ID' => $row[$i++],
                'ID_KECAMATAN' => $row[$i],
            ];
            $insertParam[] = $tmp;
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restorePuskesmasVillages($folder)
    {
        $this->db->db_debug = TRUE;
        $this->db->query("DELETE FROM db_depkesmas.puskesmas_villages");

        $table = 'db_depkesmas.puskesmas_villages';
        $filename = 'puskesmas_villages.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:B$highestRow");

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            $tmp = [
                'id_puskesmas'   => $row[$i++],
                'id_village' => $row[$i],
            ];
            $insertParam[] = $tmp;
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restoreVillages($folder)
    {
        $this->db->db_debug = TRUE;
        $this->db->query("DELETE FROM villages");

        $table = 'villages';
        $filename = 'villages.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:D$highestRow");

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            $tmp = [
                'id'   => $row[$i++],
                'district_id' => $row[$i++],
                'name' => $row[$i++],
                'jumlah_penduduk' => $row[$i],
            ];
            $insertParam[] = $tmp;
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $folder
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function restorePosyandu($folder)
    {
        $this->db->db_debug = TRUE;
        $this->db->query("DELETE FROM posyandu");

        $table = 'posyandu';
        $filename = 'posyandu.xlsx';
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($folder . '/' . $filename);
        $array = $objPHPExcel->getActiveSheet();
        $highestRow = $array->getHighestRow();
        $data = $array->rangeToArray("A2:G$highestRow");

        $insertParam = [];
        foreach ($data as $row) {
            $i = 0;

            $tmp = [
                'KD_POSYANDU' => $row[$i++],
                'PROV' => $row[$i++],
                'KAB' => $row[$i++],
                'KEC' => $row[$i++],
                'KEL' => $row[$i++],
                'PKM' => $row[$i++],
                'NM_POSYANDU' => $row[$i]
            ];
            $insertParam[] = $tmp;
        }

        if (!empty($insertParam)) {
            $this->db->insert_batch($table, $insertParam);
        }
    }

    /**
     * @param $pkm
     * @param $lastUpdate
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function update($pkm, $lastUpdate)
    {
        $this->db->db_debug = TRUE;
        $time_start = microtime(true);

        $config['upload_path'] = '_restoredb/';
        $config['allowed_types'] = '*';
        $this->load->library('upload', $config);
        $meta = [
            'total_data'         => 0,
            'insert'             => 0,
            'update'             => 0,
            'duplicate_identity' => 0,
            'duplicate_detail'   => [],
            'invalid'            => 0
        ];
        $valid = false;
        if (!$this->upload->do_upload('restore_file')) {
            $this->dataparsing['message'] = 'Restore Gagal ! ' . $this->upload->display_errors();
            $this->dataparsing['subtitle'] = 'Restore Data';
            $this->dataparsing['title'] = 'Restore Data';
            $this->dataparsing['active_link'] = 'restore';
            $this->renderView('Setting/restore_finish');
        } else {
            $upload_data = $this->upload->data();
            $zip = new ZipArchive();
            $res = $zip->open('_restoredb/' . $upload_data['file_name']);
            if ($res === TRUE) {
                $new_folder_name = str_replace(".zip", "", $upload_data['file_name']);
                $this->session->set_userdata('_newfoldername', $new_folder_name);
                $folder = '_restoredb/' . $new_folder_name;
                $zip->extractTo($folder);
                $zip->close();

                $this->db->trans_start();

                if (file_exists($folder . '/identitas.xlsx')) {
                    $valid = true;
                    $this->restoreTindakan($folder, $pkm);
                    $this->restorePmtDetail($folder, $pkm);
                    $this->restoreDetasi($folder, $pkm);
                    $this->restoreUkur($folder, $pkm);
                    $this->restoreKpsp($folder, $pkm);
                    $this->restoreKia($folder, $pkm);
                    $meta = $this->restoreIdentitas($folder, $pkm);
                }

                if (IS_OFFLINE && file_exists($folder . '/provinces.xlsx')) {
                    $valid = true;
//                    $this->restoreMaster($folder);
                }

                $this->db->trans_complete();
            } else {
                $this->dataparsing['message'] = 'Restore Gagal ! FIle Bukan Zip';
                $this->dataparsing['subtitle'] = 'Restore Data';
                $this->dataparsing['title'] = 'Restore Data';
                $this->dataparsing['active_link'] = 'restore';
                $this->renderView('Setting/restore_finish');
            }
            $time_end = microtime(true);

            if ($valid) {
                $time = $time_end - $time_start;

                if (IS_ONLINE) {
                    $this->db->insert("restore_history", [
                        'file'       => '_restoredb/' . $upload_data['file_name'],
                        'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                        'user_id'    => $this->session->userdata("login_id")
                    ]);
                }

                if (!empty($lastUpdate)) {
                    $this->db->where('id', $lastUpdate->id)->update('backup_restore_limit', ['date' => Carbon::now()->toDateTimeString()]);
                } else {
                    $this->db->insert('backup_restore_limit', ['id_pkm' => $pkm, 'type' => 'restore', 'date' => Carbon::now()->toDateTimeString()]);
                }

                $this->dataparsing['meta'] = $meta;
                $this->dataparsing['exec_time'] = $time;

                $this->dataparsing['message'] = 'Restore Sukses !';
                $this->dataparsing['subtitle'] = 'Restore Data';
                $this->dataparsing['title'] = 'Restore Data';
                $this->dataparsing['active_link'] = 'restore';
                $this->renderView('Setting/restore_finish3');
            } else {
                $this->dataparsing['message'] = 'Restore Gagal ! FIle Tidak Valid';
                $this->dataparsing['subtitle'] = 'Restore Data';
                $this->dataparsing['title'] = 'Restore Data';
                $this->dataparsing['active_link'] = 'restore';
                $this->renderView('Setting/restore_finish');
            }
        }
    }

//	public function do_restore() {
//		$config['upload_path'] = '_restoredb/';
//		$config['allowed_types'] = '*';
//		$this->load->library('upload', $config);
//		if (!$this->upload->do_upload('restore_file')) {
//			$this->dataparsing['message'] = 'Restore Gagal ! '.$this->upload->display_errors();
//			$this->dataparsing['subtitle']= 'Restore Data';
//			$this->dataparsing['title']= 'Restore Data';
//			$this->dataparsing['active_link'] = 'restore';
//			$this->renderView('Setting/restore_finish');
//		} else {
//			$upload_data = $this->upload->data();
//			$zip = new ZipArchive();
//			$res = $zip->open('_restoredb/'.$upload_data['file_name']);
//			if ($res === TRUE) {
//				$new_folder_name = str_replace(".gz","",$upload_data['file_name']);
//				$this->session->set_userdata('_newfoldername',$new_folder_name);
//				$zip->extractTo('_restoredb/'.$new_folder_name);
//				$zip->close();
//				$isi_file = file_get_contents('_restoredb/'.$new_folder_name.'/ppgbm_offline.sql');
//				$string_query1 = rtrim( $isi_file, "\n;");
//
//				$this->load->database();
//
//				$patterns = array();
//				$patterns[0] = '/det_asi/';
//				$patterns[3] = '/identitas/';
//				$patterns[4] = '/pmt_detail/';
//				$patterns[5] = '/tindakan/';
//				$patterns[6] = '/ukur/';
//				$patterns[7] = '/posyandu/';
//				$replacements = array();
//				$replacements[7] = 'posyandu_sw';
//				$replacements[6] = 'ukur_sw';
//				$replacements[5] = 'tindakan_sw';
//				$replacements[4] = 'pmt_detail_sw';
//				$replacements[3] = 'identitas_sw';
//				$replacements[0] = 'det_asi_sw';
//				ksort($patterns);
//				ksort($replacements);
//				// Ganti masing2 tabel temporary
//				$query2 = preg_replace($patterns, $replacements, $string_query1);
//				$array_query2 = explode(";", $query2);
//				// Dump ke masing2 tabel temporary
//				foreach($array_query2 as $qry2)
//				{
//					$this->db->query($qry2);
//				}
//			}
//
//			$this->dataparsing['message'] = 'Restore Tahap 1 dari 3 Sukses. Klik Lanjutkan !';
//			$this->dataparsing['subtitle']= 'Restore Data';
//			$this->dataparsing['title']= 'Restore Data';
//			$this->dataparsing['active_link'] = 'restore';
//			$this->renderView('Setting/restore_finish');
//		}
//	}
//	public function do_restore2() {
//			$this->psg->restore_table('det_asi','KTP');
//			$this->psg->restore_table('identitas','KTP');
//			$this->psg->restore_table('pmt_detail','KTP');
//			$this->psg->restore_table('tindakan','KTP');
//			$this->psg->restore_table('ukur','KTP');
//			$this->psg->restore_table('posyandu','KD_POSYANDU');
//
//			$this->dataparsing['message'] = 'Restore Tahap 2 dari 3 Sukses. Klik Lanjutkan !';
//			$this->dataparsing['subtitle']= 'Restore Data';
//			$this->dataparsing['title']= 'Restore Data';
//			$this->dataparsing['active_link'] = 'restore';
//			$this->renderView('Setting/restore_finish2');
//	}
//	public function do_restore3() {
//			$this->psg->insertinto_table('det_asi','KTP');
//			$this->psg->insertinto_table('identitas','KTP');
//			$this->psg->insertinto_table('pmt_detail','KTP');
//			$this->psg->insertinto_table('tindakan','KTP');
//			$this->psg->insertinto_table('ukur','KTP');
//			$this->psg->insertinto_table('posyandu','KD_POSYANDU');
//			$this->dataparsing['message'] = 'Restore Tahap 3 dari 3 Sukses !';
//			$this->dataparsing['subtitle']= 'Restore Data';
//			$this->dataparsing['title']= 'Restore Data';
//			$this->dataparsing['active_link'] = 'restore';
//			$this->renderView('Setting/restore_finish3');
//	}

//    public function restoreFunc($file)
//    {
//        $isi_file = file_get_contents($file);
//        $string_query1 = rtrim( $isi_file, "\n;");
//
//        $this->load->database();
//        $patterns = [
//            'det_asi',
//            'identitas',
//            'pmt_detail',
//            'tindakan',
//            'ukur',
//            'posyandu',
//            "/'",
//            ";E",
//            "HTTP://LOCALHOST:8010",
//            "CEME, DEMANGAN, GONDOKUSUMAN 1",
//            "'12;",
//        ];
//
//        $replacements = [
//            'det_asi_sw',
//            'identitas_sw',
//            'pmt_detail_sw',
//            'tindakan_sw',
//            'ukur_sw',
//            'posyandu_sw',
//            '',
//            'E',
//            "''",
//            "CEME, DEMANGAN, GONDOKUSUMAN 1'",
//            "'12",
//        ];
//
//        // Ganti masing2 tabel temporary
//        $query2 = str_replace($patterns, $replacements, $string_query1);
//        $array_query2 = explode(";", $query2);
//        // Dump ke masing2 tabel temporary
//        foreach($array_query2 as $qry2)
//        {
//            $this->db->simple_query($qry2);
//        }
//
//        $this->psg->restore_table('det_asi','KTP');
//        $this->psg->restore_table('identitas','KTP');
//        $this->psg->restore_table('pmt_detail','KTP');
//        $this->psg->restore_table('tindakan','KTP');
//        $this->psg->restore_table('ukur','KTP');
//        $this->psg->restore_table('posyandu','KD_POSYANDU');
//
//        $this->psg->insertinto_table('det_asi','KTP');
//        $this->psg->insertinto_table('identitas','KTP');
//        $this->psg->insertinto_table('pmt_detail','KTP');
//        $this->psg->insertinto_table('tindakan','KTP');
//        $this->psg->insertinto_table('ukur','KTP');
//        $this->psg->insertinto_table('posyandu','KD_POSYANDU');
//    }
//
//	public function restoreAll()
//	{
//        ini_set('memory_limit', -1);
//
//		$dir = FCPATH . '_restoredb/';
////		$dir = 'C:/Users/Alice/Documents/BK_KEMENKES/_restoredb/';
//
//		if ($handle = opendir($dir)) {
//            $i = 0;
//            $files = [];
//            while (false !== ($entry = readdir($handle))) {
//                if ($entry != "." && $entry != "..") {
//                    if (is_dir($dir . $entry)) {
//                        $newDir = $dir . $entry;
//                        $hand = opendir($newDir);
//                        while (false !== ($entryb = readdir($hand))) {
//                            if ($entryb != "." && $entryb != "..") {
//                                if ($entryb == 'ppgbm_offline.sql') {
//                                    $files[filemtime($dir . $entry . '/' . $entryb)] = $dir . $entry . '/' . $entryb;
//                                }
//                            }
//                        }
//
//                        closedir($hand);
//                    }
//
//                    $i++;
//                }
//            }
//
//            closedir($handle);
//            ksort($files);
//
//            $jml =  count($files);
//
//            foreach ($files as $key => $file) {
//                $this->restoreFunc($file);
//                unlink($file);
//                echo --$jml. "\n";
//			}
//		}
//
//    }
}
?>
