<?php
/*
Plugin Name: Calendar Rezervări Pensiune
Description: Plugin simplu de rezervări pensiune cu calendar admin și afișare front-end.
Version: 1.1.2
Author: Apetrei Iulian-Paul
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: pensiune-reservation
*/

// Safety check
if (!defined('ABSPATH')) exit;

class PensiuneReservation {
    private $option_dates = 'pensiune_reservation_zile_ocupate';
    private $option_logs = 'pensiune_reservation_logs';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_shortcode('pensiune_calendar_public', [$this, 'public_calendar_shortcode']);

        add_action('wp_ajax_pensiune_get_occupied_dates', [$this, 'ajax_get_occupied_dates']);
        add_action('wp_ajax_nopriv_pensiune_get_occupied_dates', [$this, 'ajax_get_occupied_dates']);
        add_action('wp_ajax_pensiune_add_range', [$this, 'ajax_add_range']);
        add_action('wp_ajax_pensiune_remove_date', [$this, 'ajax_remove_date']);
        add_action('wp_ajax_pensiune_export_csv', [$this, 'ajax_export_csv']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Calendar Rezervări Pensiune',
            'Rezervări Pensiune',
            'manage_options',
            'pensiune-reservation',
            [$this, 'admin_page'],
            'dashicons-calendar-alt',
            20
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_pensiune-reservation') return;

        $plugin_url = plugin_dir_url(__FILE__);

        wp_enqueue_style('fullcalendar-css', $plugin_url . 'assets/fullcalendar/main.min.css');
        wp_enqueue_script('fullcalendar-js', $plugin_url . 'assets/fullcalendar/main.min.js', ['jquery'], null, true);
        wp_enqueue_script('fullcalendar-locales', $plugin_url . 'assets/fullcalendar/locales-all.min.js', ['fullcalendar-js'], null, true);
        wp_enqueue_script('pensiune-admin-js', $plugin_url . 'assets/admin-calendar.js', ['fullcalendar-js', 'fullcalendar-locales', 'jquery'], null, true);

        wp_localize_script('pensiune-admin-js', 'pensiuneAdminAjax', [
            'ajax_url' => esc_url(admin_url('admin-ajax.php')),
            'nonce'    => wp_create_nonce('pensiune_admin_nonce'),
        ]);

        wp_enqueue_style('pensiune-admin-css', $plugin_url . 'assets/admin-style.css');
    }

    public function enqueue_frontend_scripts() {
        $plugin_url = plugin_dir_url(__FILE__);

        wp_enqueue_style('fullcalendar-css', $plugin_url . 'assets/fullcalendar/main.min.css');
        wp_enqueue_script('fullcalendar-js', $plugin_url . 'assets/fullcalendar/main.min.js', [], null, true);
        wp_enqueue_script('fullcalendar-locales', $plugin_url . 'assets/fullcalendar/locales-all.min.js', ['fullcalendar-js'], null, true);
        wp_enqueue_script('pensiune-public-js', $plugin_url . 'assets/public-calendar.js', ['fullcalendar-js', 'fullcalendar-locales'], null, true);

        wp_localize_script('pensiune-public-js', 'pensiunePublicAjax', [
            'ajax_url' => esc_url(admin_url('admin-ajax.php')),
            'nonce'    => wp_create_nonce('pensiune_public_nonce'),
        ]);
    }

    public function admin_page() {
        $logs = get_option($this->option_logs, []);
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

            <h2 style="margin-top:30px;">Jurnal activități</h2>
            <ul>
                <?php foreach (array_reverse($logs) as $log): ?>
                    <li><?php echo esc_html($log); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <script>
        (function($){
            $('#export-csv').on('click', function(e){
                e.preventDefault();
                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'pensiune_export_csv',
                        nonce: '<?php echo esc_js(wp_create_nonce('pensiune_admin_nonce')); ?>'
                    })
                })
                .then(response => {
                    const ct = response.headers.get('Content-Type') || '';
                    if (ct.indexOf('application/json') !== -1) {
                        return response.json().then(data => alert(data.data || 'Nu există date de exportat.'));
                    } else if (ct.indexOf('text/csv') !== -1) {
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'rezervari_pensiune.csv';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(url);
                        });
                    } else {
                        alert('Răspuns necunoscut de la server.');
                    }
                })
                .catch(() => alert('Eroare la export.'));
            });
        })(jQuery);
        </script>
        <?php
    }

    public function public_calendar_shortcode() {
        return '<div id="pensiune-public-calendar" style="max-width:900px; margin: 20px auto;"></div>';
    }

    public function ajax_get_occupied_dates() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '';
        }
        $key = 'pensiune_reservation_ip_' . md5($ip);
        $count = (int) get_transient($key);
        if ($count > 10) {
            wp_send_json_error('Prea multe cereri. Încearcă mai târziu.');
        }
        set_transient($key, $count + 1, 600);

        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

        if (
            !wp_verify_nonce($nonce, 'pensiune_admin_nonce') &&
            !wp_verify_nonce($nonce, 'pensiune_public_nonce')
        ) {
            wp_send_json_error('Nonce invalid.');
        }

        $dates = get_option($this->option_dates, []);
        wp_send_json_success($dates);
    }

    public function ajax_add_range() {
        check_ajax_referer('pensiune_admin_nonce', 'nonce');

        $start = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : '';
        $end = isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : '';

        $start = substr($start, 0, 10);
        $end = substr($end, 0, 10);

        if (!$start || !$end) wp_send_json_error('Datele de început și sfârșit sunt necesare.');

        $start_ts = strtotime($start);
        $end_ts = strtotime($end);

        if (!$start_ts || !$end_ts || $start_ts > $end_ts) wp_send_json_error('Interval invalid.');

        $occupied = get_option($this->option_dates, []);

        for ($i = $start_ts; $i <= $end_ts; $i += 86400) {
            $day = gmdate('Y-m-d', $i);
            if (in_array($day, $occupied)) {
                wp_send_json_error("Ziua $day este deja ocupată.");
            }
        }

        for ($i = $start_ts; $i <= $end_ts; $i += 86400) {
            $occupied[] = gmdate('Y-m-d', $i);
        }

        $occupied = array_unique($occupied);
        sort($occupied);

        update_option($this->option_dates, $occupied);
        $this->log_action("Adăugat interval: $start → $end");

        wp_send_json_success();
    }

    public function ajax_remove_date() {
        check_ajax_referer('pensiune_admin_nonce', 'nonce');

        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
        $date = substr($date, 0, 10);

        if (!$date) wp_send_json_error('Data este necesară.');

        $occupied = get_option($this->option_dates, []);

        if (($key = array_search($date, $occupied)) !== false) {
            unset($occupied[$key]);
            $occupied = array_values($occupied);
            update_option($this->option_dates, $occupied);
            $this->log_action("Ștersă ziua: $date");
            wp_send_json_success();
        } else {
            wp_send_json_error('Data nu a fost găsită.');
        }
    }

    public function ajax_export_csv() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu ai permisiunea.');
        }
        check_ajax_referer('pensiune_admin_nonce', 'nonce');

        $occupied = get_option($this->option_dates, []);
        if (empty($occupied)) {
            wp_send_json_error('Nu există date de exportat.');
        }

        $this->log_action('Exportat CSV');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rezervari_pensiune.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_send_json_error('Nu se poate deschide stream-ul pentru export.');
        }

        fputcsv($output, ['Data ocupată']);
        foreach ($occupied as $date) {
            fputcsv($output, [$date]);
        }

        exit;
    }

    private function log_action($message) {
        $logs = get_option($this->option_logs, []);
        $date = gmdate('Y-m-d H:i:s');  // <-- schimbat din date() în gmdate()
        $logs[] = "[$date] $message";
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        update_option($this->option_logs, $logs);
    }
}

new PensiuneReservation();
