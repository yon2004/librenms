<?php
/*
 * codec states for raspberry pi
 * requires snmp extend agent script from librenms-agent
 */
if (!empty($pre_cache['raspberry_pi_sensors'])) {
    $state_name = "raspberry_codec";
    $oid = '.1.3.6.1.4.1.8072.1.3.2.4.1.2.9.114.97.115.112.98.101.114.114.121.';
    for ($codec = 8; $codec < 14; $codec++) {
        switch ($codec) {
            case "8":
                $descr = "H264 codec";
                break;
            case "9":
                $descr = "MPG2 codec";
                break;
            case "10":
                $descr = "WVC1 codec";
                break;
            case "11":
                $descr = "MPG4 codec";
                break;
            case "12":
                $descr = "MJPG codec";
                break;
            case "13":
                $descr = "WMV9 codec";
                break;
        }
        $value = current($pre_cache['raspberry_pi_sensors']["raspberry." . $codec]);
        if (stripos($value, 'abled') !== false) {
            $states = [
                ['value' => 2, 'generic' => 0, 'graph' => 1, 'descr' => 'enabled'],
                ['value' => 3, 'generic' => 2, 'graph' => 1, 'descr' => 'disabled'],
            ];
            create_state_index($state_name, $states);

            discover_sensor($valid['sensor'], 'state', $device, $oid . $codec, $codec, $state_name, $descr, 1, 1, null, null, null, null, $value, 'snmp', $codec);
            create_sensor_to_state_index($device, $state_name, $codec);
        } else {
            break;
        }
    }
}

$oids = '.1.3.6.1.4.1.8072.1.3.2.4.1.2.7.117.112.115.45.110.117.116.9';
$value = snmp_get($device, $oids, '-Osqnv');

if (!empty($value)) {

	/*
    * All the possible states from https://networkupstools.org/docs/developer-guide.chunked/ar01s04.html#_status_data
    */

	$sensors = [
		['value' => 'OL'      , 'genericT' => 0, 'genericF' => 1, 'state_name' => 'ups_nut_OnLine'            , 'descr' => 'On line (mains is present)'],
        ['value' => 'OB'      , 'genericT' => 1, 'genericF' => 0, 'state_name' => 'ups_nut_OnBattery'         , 'descr' => 'On battery (mains is not present)'],
        ['value' => 'LB'      , 'genericT' => 2, 'genericF' => 0, 'state_name' => 'ups_nut_LowBattery'        , 'descr' => 'Low battery'],
        ['value' => 'HB'      , 'genericT' => 1, 'genericF' => 0, 'state_name' => 'ups_nut_HighBattery'       , 'descr' => 'High battery'],
        ['value' => 'RB'      , 'genericT' => 1, 'genericF' => 0, 'state_name' => 'ups_nut_BatteryReplace'    , 'descr' => 'The battery needs to be replaced'],
        ['value' => 'CHRG'    , 'genericT' => 0, 'genericF' => 0, 'state_name' => 'ups_nut_BatteryCharging'   , 'descr' => 'The battery is charging'],
        ['value' => 'DISCHRG' , 'genericT' => 1, 'genericF' => 0, 'state_name' => 'ups_nut_BatteryDischarging', 'descr' => 'The battery is discharging (inverter is providing load power)'],
        ['value' => 'BYPASS'  , 'genericT' => 1, 'genericF' => 0, 'state_name' => 'ups_nut_UPSBypass'         , 'descr' => 'UPS bypass circuit is active - no battery protection is available' ],
        ['value' => 'CAL'     , 'genericT' => 0, 'genericF' => 0, 'state_name' => 'ups_nut_RuntimeCalibration', 'descr' => 'UPS is currently performing runtime calibration (on battery)'],
        ['value' => 'OFF'     , 'genericT' => 2, 'genericF' => 0, 'state_name' => 'ups_nut_Offline'           , 'descr' => 'UPS is offline and is not supplying power to the load'],
        ['value' => 'OVER'    , 'genericT' => 2, 'genericF' => 0, 'state_name' => 'ups_nut_UPSOverloaded'     , 'descr' => 'UPS is overloaded. '],
        ['value' => 'TRIM'    , 'genericT' => 0, 'genericF' => 0, 'state_name' => 'ups_nut_UPSBuck'           , 'descr' => 'UPS is trimming incoming voltage (called "buck" in some hardware)'],
        ['value' => 'BOOST'   , 'genericT' => 0, 'genericF' => 0, 'state_name' => 'ups_nut_ups_nut_UPSBoost'  , 'descr' => 'UPS is boosting incoming voltage'],
        ['value' => 'FSD'     , 'genericT' => 2, 'genericF' => 0, 'state_name' => 'ups_nut_ForcedShutdown'    , 'descr' => 'Forced Shutdown (restricted use)'],
	];
	
    /*
    * $value = OL RB
    */
	
	$values = explode(" ", $value);	

	foreach ($sensors as $index => $sensor){
		$state_name = $sensor['state_name'];
		$states = [
			['value' => 0, 'generic' => $sensor['genericF'], 'graph' => 0, 'descr' => 'False'],
			['value' => 1, 'generic' => $sensor['genericT'], 'graph' => 0, 'descr' => 'True']
		];
		
		$return_value = 0;
		if(in_array($sensor['value'], $values)){
			$return_value = 1; 
		}
		
		create_state_index($state_name, $states);
		
		//Discover Sensors
        discover_sensor($valid['sensor'], 'state', $device, $oids.$index, $index, $state_name, $sensor['descr'], '1', '1', null, null, null, null, $return_value, 'snmp', $index);

        //Create Sensor To State Index
        create_sensor_to_state_index($device, $state_name, $index);
	}
}
