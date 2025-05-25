<?php
/*
Plugin Name: Pensiune Reservation
Description: Plugin simplu de rezervări pensiune cu calendar admin și afișare front-end.
Version: 1.0.4
Author: Apetrei Iulian-Paul
*/

if (!defined('ABSPATH')) exit;

class PensiuneReservation {
    public function __construct() {
        // Admin menu & scripts
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Frontend scripts & shortcode
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_shortcode('pensiune_calendar_public', [$this, 'public_calendar_shortcode']);

        // AJAX handlers - admin + frontend (nopriv)
        add_action('wp_ajax_pensiune_get_occupied_dates', [$this, 'ajax_get_occupied_dates']);
        add_action('wp_ajax_nopriv_pensiune_get_occupied_dates', [$this, 'ajax_get_occupied_dates']);

        add_action('wp_ajax_pensiune_add_range', [$this, 'ajax_add_range']);
        add_action('wp_ajax_pensiune_remove_date', [$this, 'ajax_remove_date']);
        add_action('wp_ajax_pensiune_export_csv', [$this, 'ajax_export_csv']);
    }

    // Admin menu
    public function add_admin_menu() {
        add_menu_page(
            'Rezervări Pensiune',
            'Rezervări Pensiune',
            'manage_options',
            'pensiune-reservation',
            [$this, 'admin_page'],
            'dashicons-calendar-alt',
            20
        );
    }

    // Enqueue admin scripts & styles
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_pensiune-reservation') return;

        wp_enqueue_style('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css');
        wp_enqueue_script('fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js', ['jquery'], null, true);
        wp_enqueue_script('fullcalendar-locales', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js', ['fullcalendar-js'], null, true);

        wp_enqueue_script('pensiune-admin-js', plugin_dir_url(__FILE__) . 'assets/admin-calendar.js', ['fullcalendar-js', 'fullcalendar-locales', 'jquery'], null, true);

        wp_localize_script('pensiune-admin-js', 'pensiuneAdminAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('pensiune_admin_nonce'),
        ]);

        wp_enqueue_style('pensiune-admin-css', plugin_dir_url(__FILE__) . 'assets/admin-style.css');
    }

    // Enqueue frontend scripts & styles
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css');
        wp_enqueue_script('fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js', [], null, true);
        wp_enqueue_script('fullcalendar-locales', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js', ['fullcalendar-js'], null, true);

        wp_enqueue_script('pensiune-public-js', plugin_dir_url(__FILE__) . 'assets/public-calendar.js', ['fullcalendar-js', 'fullcalendar-locales'], null, true);

        wp_localize_script('pensiune-public-js', 'pensiunePublicAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('pensiune_public_nonce'),
        ]);
    }

    // Admin page HTML
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Calendar Rezervări Pensiune</h1>

            <div style="margin-bottom:20px; padding:10px; background:#f9f9f9; border-left: 4px solid #0073aa;">
                <strong>Ghid utilizare:</strong>
                <ul style="list-style: disc inside; margin-top:8px;">
                    <li>✅ <strong>Adăugare zi:</strong> Click pe o zi liberă pentru a o marca ca ocupată.</li>
                    <li>✅ <strong>Adăugare interval de zile:</strong> Selectează un interval tragând cu mouse-ul pe calendar și confirmă adăugarea.</li>
                    <li>✅ <strong>Ștergere zi:</strong> Click pe o zi ocupată pentru a o șterge.</li>
                </ul>
                <p><strong>Shortcode pentru afișare calendar front-end:</strong> <code>[pensiune_calendar_public]</code></p>
            </div>

            <div id="pensiune-admin-calendar" style="max-width:900px;margin-top:20px;"></div>

            <div style="margin-top: 20px;">
                <button id="export-csv" class="button button-primary">Exportă CSV</button>
            </div>
        </div>
        <?php
    }

    // Shortcode for public calendar (read-only)
    public function public_calendar_shortcode() {
        return '<div id="pensiune-public-calendar" style="max-width:900px; margin: 20px auto;"></div>';
    }

    // AJAX: Get occupied dates (admin + public)
    public function ajax_get_occupied_dates() {
        if (
            !wp_verify_nonce($_POST['nonce'] ?? '', 'pensiune_admin_nonce') &&
            !wp_verify_nonce($_POST['nonce'] ?? '', 'pensiune_public_nonce')
        ) {
            wp_send_json_error('Nonce invalid.');
        }

        $dates = get_option('pensiune_zile_ocupate', []);
        wp_send_json_success($dates);
    }

    // AJAX: Add occupied range (admin only)
    public function ajax_add_range() {
        check_ajax_referer('pensiune_admin_nonce', 'nonce');

        $start = sanitize_text_field($_POST['start_date'] ?? '');
        $end = sanitize_text_field($_POST['end_date'] ?? '');

        if (!$start || !$end) wp_send_json_error('Datele de început și sfârșit sunt necesare.');

        $start_ts = strtotime($start);
        $end_ts = strtotime($end);

        if (!$start_ts || !$end_ts || $start_ts > $end_ts) wp_send_json_error('Interval invalid.');

        $occupied = get_option('pensiune_zile_ocupate', []);

        for ($i = $start_ts; $i <= $end_ts; $i += 86400) {
            $day = date('Y-m-d', $i);
            if (in_array($day, $occupied)) {
                wp_send_json_error("Ziua $day este deja ocupată.");
            }
        }

        for ($i = $start_ts; $i <= $end_ts; $i += 86400) {
            $day = date('Y-m-d', $i);
            $occupied[] = $day;
        }

        $occupied = array_unique($occupied);
        sort($occupied);

        update_option('pensiune_zile_ocupate', $occupied);

        wp_send_json_success();
    }

    // AJAX: Remove occupied date (admin only)
    public function ajax_remove_date() {
        check_ajax_referer('pensiune_admin_nonce', 'nonce');

        $date = sanitize_text_field($_POST['date'] ?? '');

        if (!$date) wp_send_json_error('Data este necesară.');

        $occupied = get_option('pensiune_zile_ocupate', []);

        if (($key = array_search($date, $occupied)) !== false) {
            unset($occupied[$key]);
            $occupied = array_values($occupied);
            update_option('pensiune_zile_ocupate', $occupied);
            wp_send_json_success();
        } else {
            wp_send_json_error('Data nu a fost găsită.');
        }
    }

    // AJAX: Export CSV (admin only)
    public function ajax_export_csv() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu ai permisiunea.');
        }

        check_ajax_referer('pensiune_admin_nonce', 'nonce');

        $occupied = get_option('pensiune_zile_ocupate', []);
        if (empty($occupied)) {
            wp_send_json_error('Nu există date de exportat.');
        }

        $csv = "Data\n";
        foreach ($occupied as $date) {
            $csv .= $date . "\n";
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="rezervari_pensiune.csv"');
        echo $csv;
        exit;
    }
}

new PensiuneReservation();
