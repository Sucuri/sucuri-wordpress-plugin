<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Read and parse the content of the heartbeat settings template.
 *
 * @return string Parsed HTML code for the heartbeat settings panel.
 */
function sucuriscan_settings_heartbeat()
{
    // Current values set in the options table.
    $heartbeat_status = SucuriScanOption::get_option(':heartbeat');
    $heartbeat_pulse = SucuriScanOption::get_option(':heartbeat_pulse');
    $heartbeat_interval = SucuriScanOption::get_option(':heartbeat_interval');
    $heartbeat_autostart = SucuriScanOption::get_option(':heartbeat_autostart');

    // Allowed values for each setting.
    $statuses_allowed = SucuriScanHeartbeat::statuses_allowed();
    $pulses_allowed = SucuriScanHeartbeat::pulses_allowed();
    $intervals_allowed = SucuriScanHeartbeat::intervals_allowed();

    // HTML select form fields.
    $heartbeat_options = SucuriScanTemplate::selectOptions($statuses_allowed, $heartbeat_status);
    $heartbeat_pulse_options = SucuriScanTemplate::selectOptions($pulses_allowed, $heartbeat_pulse);
    $heartbeat_interval_options = SucuriScanTemplate::selectOptions($intervals_allowed, $heartbeat_interval);

    $params = array(
        'HeartbeatStatus' => 'Undefined',
        'HeartbeatPulse' => 'Undefined',
        'HeartbeatInterval' => 'Undefined',
        /* Heartbeat Options. */
        'HeartbeatStatusOptions' => $heartbeat_options,
        'HeartbeatPulseOptions' => $heartbeat_pulse_options,
        'HeartbeatIntervalOptions' => $heartbeat_interval_options,
        /* Heartbeat Auto-Start. */
        'HeartbeatAutostart' => 'Enabled',
        'HeartbeatAutostartSwitchText' => 'Disable',
        'HeartbeatAutostartSwitchValue' => 'disable',
        'HeartbeatAutostartSwitchCssClass' => 'button-danger',
    );

    if (array_key_exists($heartbeat_status, $statuses_allowed)) {
        $params['HeartbeatStatus'] = $statuses_allowed[ $heartbeat_status ];
    }

    if (array_key_exists($heartbeat_pulse, $pulses_allowed)) {
        $params['HeartbeatPulse'] = $pulses_allowed[ $heartbeat_pulse ];
    }

    if (array_key_exists($heartbeat_interval, $intervals_allowed)) {
        $params['HeartbeatInterval'] = $intervals_allowed[ $heartbeat_interval ];
    }

    if ($heartbeat_autostart == 'disabled') {
        $params['HeartbeatAutostart'] = 'Disabled';
        $params['HeartbeatAutostartSwitchText'] = 'Enable';
        $params['HeartbeatAutostartSwitchValue'] = 'enable';
        $params['HeartbeatAutostartSwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-heartbeat', $params);
}
