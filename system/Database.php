<?php

class Database {
	
	public $db;
	
	function __construct(){
		$this->db = new mysqli("localhost", "root", "kopi", "kbl_bumigora");
	}
	
	function __destruct(){
		$this->db->close();
	}
}