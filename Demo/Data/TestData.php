<?php
namespace Demo\Data;
use  Framework\Libraries\ControllMysql;

class TestData extends ControllMysql {
	public function __construct(){
		parent::__construct('database_firehose','Demo');
	}
}