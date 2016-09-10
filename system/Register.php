<?php

/*
 * Class Register
 * fungsi utama : Untuk operasi CURD ke database
 * oleh			: Ardianta Pargo <ardianta_pargo@yahoo.co.id>
 * tanggal		: 10 September 2016, 23:21 WITA
 *
 */ 

class Register extends Database {
	
	private $tabel = "anggota";
	
	function __construct(){
		parent::__construct();
	}
	
	function get_all(){
		return $this->db->query("SELECT * FROM {$this->tabel}")->fetch_all($resulttype = MYSQLI_ASSOC);
	}
	
	function get_one($id){
		return $this->db->query("SELECT * FROM {$this->tabel} WHERE id='{$id}'")->fetch_object();
	}
	
	function get_total(){
		return count($this->get_all());
	}
	
	function insert($data){
		return $this->db->query("INSERT INTO {$this->tabel} VALUES('{$data['id']}','{$data['nama']}','{$data['telepon']}','{$data['email']}')");
	}
	
	function update($data){
		return $this->db->query("UPDATE {$this->tabel} SET id='{$data['id']}',nama_mahasiswa='{$data['nama_mahasiswa']}',alamat='{$data['alamat']}' WHERE id='{$data['id_lama']}'");
	}
	
	function delete($id){
		return $this->db->query("DELETE FROM {$this->tabel} WHERE id='{$id}'");
	}
}