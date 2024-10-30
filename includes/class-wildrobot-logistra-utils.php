<?php

class Wildrobot_Logistra_Utils
{

	public static function init()
	{
	}


	public static function is_test_enviroment()
	{
		$test_sites = apply_filters("logistra-robots-test-sites", array("192.168.99.100", "demo2.wildrobot.app", "localhost:8000", "localhost:8002"));
		$current_site = get_permalink(wc_get_page_id('shop'));
		$is_test = false;
		if (get_option("wildrobot_logistra_enviroment") === "DEV") {
			$is_test = true;
		}
		foreach ($test_sites as $site) {
			if (is_numeric(strpos($current_site, $site))) {
				$is_test = true;
				break;
			}
		}
		return  $is_test;
	}

	public static function get_local_utc_time()
	{
		$nowLOCAL = strToTime(current_time('mysql'));
		// Save the current default timezone
		$tz = date_default_timezone_get();
		// Set the default timezone to UTC
		date_default_timezone_set('UTC');
		// Get the current UTC time
		$nowUTC = time();
		// Reset the default timezone back to the original
		date_default_timezone_set($tz);
		// Calculate the difference between UTC and local time
		$diff = $nowUTC - $nowLOCAL;
		// Adjust the local time to UTC
		$utc = $nowLOCAL + $diff;
		return $utc;
	}
}
// Wildrobot_Logistra_Utils::init();
