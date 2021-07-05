<?php

class ModelExtensionPaymentJokul extends Model {

	public function install() {
		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "jokul` (
		`trx_id` INT(11) NOT NULL AUTO_INCREMENT,
		`ip_address` VARCHAR(16),
		`process_type` VARCHAR(15),
		`process_datetime` DATETIME NULL, 
		`doku_payment_datetime` DATETIME NULL,   
		`invoice_number` VARCHAR(30),
		`amount` DECIMAL(20,2) NOT NULL DEFAULT 0,
		`notify_type` VARCHAR(1),
		`response_code` VARCHAR(4),
		`status_code` VARCHAR(4),
		`result_msg` VARCHAR(20),
		`reversal` INT(1) NOT NULL DEFAULT 0,
		`approval_code` CHAR(20),
		`payment_channel` VARCHAR(20),
		`payment_code` VARCHAR(20),
		`bank_issuer` VARCHAR(100),
		`creditcard` VARCHAR(16),
		`words` VARCHAR(200),
		`session_id` VARCHAR(48),
		`check_status` INT(1) NOT NULL DEFAULT 0,
		`count_check_status` INT(1) NOT NULL DEFAULT 0,
		`message` VARCHAR(512),
		PRIMARY KEY (`trx_id`)
		) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

	}


	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "jokul`;");
	}

}
