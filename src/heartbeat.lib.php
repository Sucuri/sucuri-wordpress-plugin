<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Heartbeat library.
 *
 * The purpose of the Heartbeat API is to simulate bidirectional connection
 * between the browser and the server. Initially it was used for autosave, post
 * locking and log-in expiration warning while a user is writing or editing. The
 * idea was to have an API that sends XHR (XML HTTP Request) requests to the
 * server every fifteen seconds and triggers events (or callbacks) on receiving
 * data.
 *
 * @see https://core.trac.wordpress.org/ticket/23216
 */
class SucuriScanHeartbeat extends SucuriScanOption
{

    /**
     * Stop execution of the heartbeat API in certain parts of the site.
     *
     * @return void
     */
    public static function register_script()
    {
        global $pagenow;

        $status = SucuriScanOption::get_option(':heartbeat');

        // Enable heartbeat everywhere.
        if ($status == 'enabled') {
            /* Do nothing */
        } // Disable heartbeat everywhere.
        elseif ($status == 'disabled') {
            wp_deregister_script('heartbeat');
        } // Disable heartbeat only on the dashboard and home pages.
        elseif ($status == 'dashboard'
            && $pagenow == 'index.php'
        ) {
            wp_deregister_script('heartbeat');
        } // Disable heartbeat everywhere except in post edition.
        elseif ($status == 'addpost'
            && $pagenow != 'post.php'
            && $pagenow != 'post-new.php'
        ) {
            wp_deregister_script('heartbeat');
        }
    }

    /**
     * Update the settings of the Heartbeat API according to the values set by an
     * administrator. This tool may cause an increase in the CPU usage, a bad
     * configuration may cause low account to run out of resources, but in better
     * cases it may improve the performance of the site by reducing the quantity of
     * requests sent to the server per session.
     *
     * @param  array $settings Heartbeat settings.
     * @return array           Updated version of the heartbeat settings.
     */
    public static function update_settings($settings = array())
    {
        $pulse = SucuriScanOption::get_option(':heartbeat_pulse');
        $autostart = SucuriScanOption::get_option(':heartbeat_autostart');

        if ($pulse < 15 || $pulse > 60) {
            SucuriScanOption::delete_option(':heartbeat_pulse');
            $pulse = 15;
        }

        $settings['interval'] = $pulse;
        $settings['autostart'] = ( $autostart == 'disabled' ? false : true );

        return $settings;
    }

    /**
     * Respond to the browser according to the data received.
     *
     * @param  array  $response  Response received.
     * @param  array  $data      Data received from the beat.
     * @param  string $screen_id Identifier of the screen the heartbeat occurred on.
     * @return array             Response with new data.
     */
    public static function respond_to_received($response = array(), $data = array(), $screen_id = '')
    {
        $interval = SucuriScanOption::get_option(':heartbeat_interval');

        if ($interval == 'slow'
            || $interval == 'fast'
            || $interval == 'standard'
        ) {
            $response['heartbeat_interval'] = $interval;
        } else {
            SucuriScanOption::delete_option(':heartbeat_interval');
        }

        return $response;
    }

    /**
     * Respond to the browser according to the data sent.
     *
     * @param  array  $response  Response sent.
     * @param  string $screen_id Identifier of the screen the heartbeat occurred on.
     * @return array             Response with new data.
     */
    public static function respond_to_send($response = array(), $screen_id = '')
    {
        return $response;
    }

    /**
     * Allowed values for the heartbeat status.
     *
     * @return array Allowed values for the heartbeat status.
     */
    public static function statuses_allowed()
    {
        return array(
            'enabled' => 'Enable everywhere',
            'disabled' => 'Disable everywhere',
            'dashboard' => 'Disable on dashboard page',
            'addpost' => 'Everywhere except post addition',
        );
    }

    /**
     * Allowed values for the heartbeat intervals.
     *
     * @return array Allowed values for the heartbeat intervals.
     */
    public static function intervals_allowed()
    {
        return array(
            'slow' => 'Slow interval',
            'fast' => 'Fast interval',
            'standard' => 'Standard interval',
        );
    }

    /**
     * Allowed values for the heartbeat pulses.
     *
     * @return array Allowed values for the heartbeat pulses.
     */
    public static function pulses_allowed()
    {
        $pulses = array();

        for ($i = 15; $i <= 60; $i++) {
            $pulses[ $i ] = sprintf('Run every %d seconds', $i);
        }

        return $pulses;
    }
}
