<?php
/**
 * PHP程序时间测试类
 *
 * 关于使用： 在计时的起始位置使用RunTime::start()方法，停止计时的位置使用RunTime::stop()方法。可以
 * 		     在使用start和stop方法时传入参数key，可以标识所计时的组别，即可以同时多组同时计时。使用RunTime::spent()
 * 		     方法获取相应组别的耗时，如果传参数并且参数为空则输出所有stop过 的组别的计时结果。
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class RunTime {
	private static $_start_time = null;
	private static $_stop_time = null;
	/**
	 * 计时开始
	 *
	 * @param string $key='_default_'        	
	 * @return boolean
	 */
	public static function start($key = '_default_') {
		if (empty ( $key )) {
			return false;
		}
		self::$_start_time [$key] = self::get_microtime ();
		return true;
	}
	/**
	 * 计时停止
	 *
	 * @param string $key='_default_'        	
	 * @return boolean
	 */
	public static function stop($key = '_default_') {
		if (empty ( $key )) {
			return false;
		}
		self::$_stop_time [$key] = self::get_microtime ();
		return true;
	}
	/**
	 * 计时计算
	 *
	 * @param string $key='_default_'        	
	 * @return array
	 */
	public static function spent($key = '_default_') {
		$result = array ();
		if (empty ( $key )) {
			if (! empty ( self::$_stop_time )) {
				foreach ( self::$_stop_time as $stop_key => $stop_value ) {
					$result [$stop_key] = self::calc_cost ( $stop_key );
				}
			}
		} else {
			$result [$key] = self::calc_cost ( $key );
		}
		return $result;
	}
	/**
	 * 获取当前时间的毫秒数
	 *
	 * @return float
	 */
	private static function get_microtime() {
		list ( $usec, $sec ) = explode ( ' ', microtime () );
		return (( float ) $usec + ( float ) $sec);
	}
	/**
	 * 计算时间差
	 *
	 * @return float
	 */
	private static function calc_cost($key) {
		return round ( (self::$_stop_time [$key] - self::$_start_time [$key]) * 1000, 1 );
	}
	/**
	 * 清空所有时间
	 *
	 * @return null
	 */
	public static function clearTime(){
		self::$_start_time = null;
		self::$_stop_time = null;
	}
}
