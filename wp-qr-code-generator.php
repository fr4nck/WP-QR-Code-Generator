<?php
/**
 * Plugin Name: WP QR Code Generator
 * Plugin URI: https://github.com/fr4nck/Wp-Qr-code-Generator
 * Description: Générateur autonome de QR codes texte, lien web, Wi-Fi, téléphone, e-mail, SMS, GPS, événement et contact, avec export PNG/SVG et logos optionnels.
 * Version: 1.1.0
 * Author: Franck Bellardie
 * License: GPL-2.0-or-later
 * Text Domain: wp-qr-code-generator
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

final class WPQR_Plugin {
    private const OPTION_KEY = 'wpqr_options';
    private const VERSION = '1.1.0';
    private const MAX_LOGO_RATIO = 0.22;

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'register_front_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_shortcode('wpqr', [$this, 'render_shortcode']);
    }

    public function register_front_assets(): void {
        wp_register_style(
            'wpqr-style',
            plugin_dir_url(__FILE__) . 'assets/css/front.css',
            [],
            self::VERSION
        );

        wp_register_script(
            'wpqr-lib',
            plugin_dir_url(__FILE__) . 'assets/js/qrcode.js',
            [],
            self::VERSION,
            true
        );

        wp_register_script(
            'wpqr-script',
            plugin_dir_url(__FILE__) . 'assets/js/front.js',
            ['wpqr-lib'],
            self::VERSION,
            true
        );
    }

    public function enqueue_admin_assets(string $hook): void {
        if ($hook !== 'settings_page_wp-qr-code-generator') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'wpqr-admin-style',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'wpqr-admin-script',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            ['jquery', 'media-editor'],
            self::VERSION,
            true
        );
    }

    public function register_admin_menu(): void {
        add_options_page(
            'WP QR Code Generator',
            'WP QR Code Generator',
            'manage_options',
            'wp-qr-code-generator',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        register_setting(
            'wpqr_group',
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_options'],
                'default' => $this->get_default_options(),
            ]
        );

        add_settings_section(
            'wpqr_main_section',
            'Réglages principaux',
            function () {
                echo '<p>Réglez l’apparence du générateur. Aucun logo n’est fourni par défaut. Vous pouvez choisir un logo d’en-tête et/ou un logo central depuis la médiathèque WordPress.</p>';
                echo '<p><strong>Sécurité du logo :</strong> lorsqu’un logo central est utilisé, le niveau de correction H est appliqué automatiquement et le ratio du logo est limité à 22&nbsp;%.</p>';
            },
            'wp-qr-code-generator'
        );

        $fields = [
            'header_title' => 'Titre affiché',
            'header_link_url' => 'Lien d’en-tête',
            'subtitle' => 'Sous-titre',
            'qr_dark' => 'Couleur du QR (avant-plan)',
            'qr_light' => 'Couleur du QR (fond)',
            'default_size' => 'Taille par défaut',
            'default_margin' => 'Marge par défaut',
            'header_logo_id' => 'Logo d’en-tête',
            'logo_enabled' => 'Activer le logo au centre du QR',
            'center_image_id' => 'Logo central du QR',
            'center_image_size_ratio' => 'Taille du logo central (ratio)',
        ];

        foreach ($fields as $key => $label) {
            add_settings_field(
                'wpqr_' . $key,
                $label,
                [$this, 'render_field'],
                'wp-qr-code-generator',
                'wpqr_main_section',
                ['key' => $key]
            );
        }
    }

    public function sanitize_options($input): array {
        $current = $this->get_options();
        $defaults = $this->get_default_options();
        $input = is_array($input) ? $input : [];
        $output = wp_parse_args(is_array($current) ? $current : [], $defaults);

        $output['header_title'] = sanitize_text_field($input['header_title'] ?? $defaults['header_title']);
        $output['header_link_url'] = esc_url_raw($input['header_link_url'] ?? $defaults['header_link_url']);
        $output['subtitle'] = sanitize_text_field($input['subtitle'] ?? $defaults['subtitle']);

        $output['qr_dark'] = $this->sanitize_hex_color_or_default($input['qr_dark'] ?? '', $defaults['qr_dark']);
        $output['qr_light'] = $this->sanitize_hex_color_or_default($input['qr_light'] ?? '', $defaults['qr_light']);

        $size = isset($input['default_size']) ? (int) $input['default_size'] : (int) $defaults['default_size'];
        $output['default_size'] = (string) max(150, min(1200, $size));

        $margin = isset($input['default_margin']) ? (int) $input['default_margin'] : (int) $defaults['default_margin'];
        $output['default_margin'] = (string) max(0, min(80, $margin));

        $output['header_logo_id'] = isset($input['header_logo_id']) ? absint($input['header_logo_id']) : 0;
        $output['center_image_id'] = isset($input['center_image_id']) ? absint($input['center_image_id']) : 0;
        $output['logo_enabled'] = empty($input['logo_enabled']) ? '0' : '1';

        $ratio = isset($input['center_image_size_ratio'])
            ? (float) $input['center_image_size_ratio']
            : (float) $defaults['center_image_size_ratio'];
        $ratio = max(0.08, min(self::MAX_LOGO_RATIO, $ratio));
        $output['center_image_size_ratio'] = number_format($ratio, 2, '.', '');

        return $output;
    }

    private function sanitize_hex_color_or_default(string $value, string $default): string {
        $sanitized = sanitize_hex_color($value);
        return $sanitized ?: $default;
    }

    private function get_default_options(): array {
        return [
            'header_title' => 'Générateur QR',
            'header_link_url' => home_url('/'),
            'header_logo_id' => 0,
            'subtitle' => 'Générez des QR codes utiles au quotidien',
            'qr_dark' => '#1f4b7a',
            'qr_light' => '#ffffff',
            'default_size' => '320',
            'default_margin' => '16',
            'logo_enabled' => '0',
            'center_image_id' => 0,
            'center_image_size_ratio' => '0.18',
        ];
    }

    private function get_options(): array {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args(is_array($saved) ? $saved : [], $this->get_default_options());
    }

    private function resolve_image_url(array $options, string $id_key): string {
        $attachment_id = !empty($options[$id_key]) ? absint($options[$id_key]) : 0;
        if ($attachment_id === 0) {
            return '';
        }

        $url = wp_get_attachment_image_url($attachment_id, 'full');
        return is_string($url) ? $url : '';
    }

    private function render_media_field(string $name, int $value, string $image_url): void {
        ?>
        <div class="wpqr-media-field">
            <input type="hidden" class="wpqr-media-input" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string) $value); ?>">
            <div class="wpqr-media-preview <?php echo $image_url ? '' : 'is-empty'; ?>">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="">
                <?php else : ?>
                    Aucune image sélectionnée
                <?php endif; ?>
            </div>
            <div class="wpqr-media-actions">
                <button type="button" class="button wpqr-media-select">Choisir une image</button>
                <button type="button" class="button wpqr-media-remove">Retirer</button>
            </div>
            <p class="wpqr-image-field-note">Utilisez de préférence une image carrée et nette. Le logo central est limité à 22&nbsp;% pour préserver la lisibilité du QR.</p>
        </div>
        <?php
    }

    public function render_field(array $args): void {
        $key = $args['key'];
        $options = $this->get_options();
        $value = $options[$key] ?? '';
        $name = self::OPTION_KEY . '[' . $key . ']';

        switch ($key) {
            case 'logo_enabled':
                printf(
                    '<label><input type="checkbox" name="%1$s" value="1" %2$s> Utiliser le logo au centre des nouveaux QR codes</label><p class="description">Le logo ne sera inséré que si une image centrale est choisie ci-dessous. La correction H sera imposée automatiquement.</p>',
                    esc_attr($name),
                    checked($value, '1', false)
                );
                break;
            case 'header_logo_id':
                $this->render_media_field(
                    $name,
                    absint($value),
                    $this->resolve_image_url($options, 'header_logo_id')
                );
                break;
            case 'center_image_id':
                $this->render_media_field(
                    $name,
                    absint($value),
                    $this->resolve_image_url($options, 'center_image_id')
                );
                break;
            case 'qr_dark':
            case 'qr_light':
                printf(
                    '<input type="text" class="regular-text" name="%1$s" value="%2$s" placeholder="#1f4b7a">',
                    esc_attr($name),
                    esc_attr((string) $value)
                );
                break;
            case 'default_size':
                printf(
                    '<input type="number" min="150" max="1200" step="10" name="%1$s" value="%2$s"> <span class="description">en pixels</span>',
                    esc_attr($name),
                    esc_attr((string) $value)
                );
                break;
            case 'default_margin':
                printf(
                    '<input type="number" min="0" max="80" step="1" name="%1$s" value="%2$s"> <span class="description">en pixels minimum ; une zone calme de quatre modules est garantie automatiquement</span>',
                    esc_attr($name),
                    esc_attr((string) $value)
                );
                break;
            case 'center_image_size_ratio':
                printf(
                    '<input type="number" min="0.08" max="0.22" step="0.01" name="%1$s" value="%2$s"> <p class="description">0.18 est un bon point de départ. La valeur maximale autorisée est 0.22.</p>',
                    esc_attr($name),
                    esc_attr((string) $value)
                );
                break;
            default:
                printf(
                    '<input type="text" class="regular-text" name="%1$s" value="%2$s">',
                    esc_attr($name),
                    esc_attr((string) $value)
                );
                break;
        }
    }

    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>WP QR Code Generator</h1>
            <p>Shortcode : <code>[wpqr]</code></p>
            <p>Types disponibles : Texte, Lien web, Wi-Fi, Téléphone, E-mail, SMS, GPS, Événement et Contact.</p>
            <p>Les QR codes sont générés localement dans le navigateur et peuvent être téléchargés en PNG ou SVG.</p>
            <p>Les contenus saisis par les visiteurs ne sont ni enregistrés dans WordPress ni envoyés à un service externe.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('wpqr_group');
                do_settings_sections('wp-qr-code-generator');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function panel_open(string $instance_id, string $mode, bool $active = false): void {
        $panel_id = $instance_id . '-panel-' . $mode;
        $tab_id = $instance_id . '-tab-' . $mode;
        ?>
        <div
            id="<?php echo esc_attr($panel_id); ?>"
            class="wpqr-panel<?php echo $active ? ' is-active' : ''; ?>"
            data-panel="<?php echo esc_attr($mode); ?>"
            role="tabpanel"
            aria-labelledby="<?php echo esc_attr($tab_id); ?>"
            <?php echo $active ? '' : 'hidden'; ?>
        >
        <?php
    }

    private function panel_close(): void {
        echo '</div>';
    }

    private function render_help_intro(): void {
        ?>
        <section class="wpqr-guidance" aria-label="Conseils d’utilisation">
            <div class="wpqr-helpbox wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Conseils rapides</h3>
                <div class="wpqr-helpbox-content">
                    <ul>
                        <li>Choisissez l’onglet correspondant au besoin pour éviter de saisir les formats techniques à la main.</li>
                        <li>Pour l’impression, préférez l’export SVG, augmentez la taille et conservez une marge claire autour du code.</li>
                        <li>Avec un logo central, la correction d’erreur H et une taille de logo prudente sont appliquées automatiquement.</li>
                        <li>Testez toujours le scan sur plusieurs téléphones avant diffusion.</li>
                    </ul>
                </div>
            </div>
        </section>
        <?php
    }

    private function render_panel_text(string $instance_id): void {
        $this->panel_open($instance_id, 'text', true);
        ?>
            <label>
                <span>Texte à encoder</span>
                <textarea name="text_payload" rows="4" placeholder="Bienvenue !"></textarea>
            </label>
            <p class="wpqr-field-note">Message libre, consigne, code d’accès, référence ou toute autre information simple.</p>
        <?php
        $this->panel_close();
    }

    private function render_panel_url(string $instance_id): void {
        $this->panel_open($instance_id, 'url');
        ?>
            <label>
                <span>Adresse web</span>
                <input type="url" name="url_value" inputmode="url" placeholder="https://exemple.org/">
            </label>
            <p class="wpqr-field-note">URL complète d’un site, d’un formulaire, d’un PDF ou d’une page d’inscription.</p>
        <?php
        $this->panel_close();
    }

    private function render_panel_wifi(string $instance_id): void {
        $this->panel_open($instance_id, 'wifi');
        ?>
            <label>
                <span>Nom du réseau (SSID)</span>
                <input type="text" name="wifi_ssid" autocomplete="off" placeholder="Mon réseau Wi-Fi">
            </label>
            <label>
                <span>Mot de passe</span>
                <input type="text" name="wifi_password" autocomplete="off" spellcheck="false" placeholder="Mot de passe Wi-Fi">
            </label>
            <label>
                <span>Sécurité</span>
                <select name="wifi_security">
                    <option value="WPA">WPA / WPA2 / WPA3</option>
                    <option value="WEP">WEP</option>
                    <option value="nopass">Aucun mot de passe</option>
                </select>
            </label>
            <label class="wpqr-inline">
                <input type="checkbox" name="wifi_hidden" value="1">
                <span>Réseau masqué</span>
            </label>
            <p class="wpqr-field-note">Indiquez le nom exact du réseau et le bon type de sécurité.</p>
        <?php
        $this->panel_close();
    }

    private function render_panel_phone(string $instance_id): void {
        $this->panel_open($instance_id, 'phone');
        ?>
            <label>
                <span>Numéro de téléphone</span>
                <input type="tel" name="phone_number" inputmode="tel" placeholder="+33299000000">
            </label>
            <p class="wpqr-field-note">Le scan proposera de lancer un appel. Le format international est recommandé.</p>
        <?php
        $this->panel_close();
    }

    private function render_panel_email(string $instance_id): void {
        $this->panel_open($instance_id, 'email');
        ?>
            <label>
                <span>Adresse e-mail</span>
                <input type="email" name="email_to" inputmode="email" placeholder="contact@exemple.org">
            </label>
            <label>
                <span>Sujet</span>
                <input type="text" name="email_subject" placeholder="Demande d’information">
            </label>
            <label>
                <span>Message</span>
                <textarea name="email_body" rows="3" placeholder="Bonjour,"></textarea>
            </label>
            <p class="wpqr-field-note">Le scan ouvrira un nouveau message prérempli.</p>
        <?php
        $this->panel_close();
    }

    private function render_panel_sms(string $instance_id): void {
        $this->panel_open($instance_id, 'sms');
        ?>
            <label>
                <span>Numéro</span>
                <input type="tel" name="sms_number" inputmode="tel" placeholder="+33600000000">
            </label>
            <label>
                <span>Message</span>
                <textarea name="sms_body" rows="3" placeholder="Bonjour, je souhaite avoir un renseignement."></textarea>
            </label>
            <p class="wpqr-field-note">Le scan ouvrira l’application de SMS avec le numéro et le message préremplis.</p>
        <?php
        $this->panel_close();
    }

    private function render_panel_gps(string $instance_id): void {
        $this->panel_open($instance_id, 'gps');
        ?>
            <div class="wpqr-grid wpqr-grid--two">
                <label>
                    <span>Latitude</span>
                    <input type="text" name="gps_lat" inputmode="decimal" placeholder="48.101">
                </label>
                <label>
                    <span>Longitude</span>
                    <input type="text" name="gps_lng" inputmode="decimal" placeholder="-1.674">
                </label>
            </div>
            <label>
                <span>Nom du lieu (facultatif)</span>
                <input type="text" name="gps_label" placeholder="Mon lieu">
            </label>
            <p class="wpqr-field-note">Le scan ouvrira généralement une application de cartographie compatible.</p>
        <?php
        $this->panel_close();
    }

    private function render_panel_event(string $instance_id): void {
        $this->panel_open($instance_id, 'event');
        ?>
            <label>
                <span>Titre de l’événement</span>
                <input type="text" name="event_title" placeholder="Réunion d’équipe">
            </label>
            <label class="wpqr-inline">
                <input type="checkbox" name="event_all_day" value="1">
                <span>Journée entière</span>
            </label>
            <div class="wpqr-grid wpqr-grid--two">
                <label>
                    <span>Date de début</span>
                    <input type="date" name="event_start_date">
                </label>
                <label class="wpqr-event-time-field">
                    <span>Heure de début</span>
                    <input type="time" name="event_start_time">
                </label>
            </div>
            <div class="wpqr-grid wpqr-grid--two">
                <label>
                    <span>Date de fin (facultative)</span>
                    <input type="date" name="event_end_date">
                </label>
                <label class="wpqr-event-time-field">
                    <span>Heure de fin (facultative)</span>
                    <input type="time" name="event_end_time">
                </label>
            </div>
            <label>
                <span>Lieu (facultatif)</span>
                <input type="text" name="event_location" placeholder="Salle polyvalente">
            </label>
            <label>
                <span>Description (facultative)</span>
                <textarea name="event_description" rows="3" placeholder="Ordre du jour, consignes ou informations utiles"></textarea>
            </label>
            <p class="wpqr-field-note">Le QR code contient un événement iCalendar. Si aucune fin n’est indiquée, une durée d’une heure est utilisée, ou une journée complète pour un événement sur la journée.</p>
        <?php
        $this->panel_close();
    }

    private function render_panel_contact(string $instance_id): void {
        $this->panel_open($instance_id, 'contact');
        ?>
            <div class="wpqr-grid wpqr-grid--two">
                <label>
                    <span>Nom</span>
                    <input type="text" name="contact_name" placeholder="Nom">
                </label>
                <label>
                    <span>Organisation</span>
                    <input type="text" name="contact_org" placeholder="Organisation">
                </label>
            </div>
            <div class="wpqr-grid wpqr-grid--two">
                <label>
                    <span>Téléphone</span>
                    <input type="tel" name="contact_phone" inputmode="tel" placeholder="+33299000000">
                </label>
                <label>
                    <span>E-mail</span>
                    <input type="email" name="contact_email" inputmode="email" placeholder="contact@exemple.org">
                </label>
            </div>
            <label>
                <span>Site web</span>
                <input type="url" name="contact_url" inputmode="url" placeholder="https://exemple.org/">
            </label>
            <label>
                <span>Adresse</span>
                <textarea name="contact_address" rows="3" placeholder="Adresse postale"></textarea>
            </label>
            <label>
                <span>Note</span>
                <textarea name="contact_note" rows="3" placeholder="Informations complémentaires"></textarea>
            </label>
            <p class="wpqr-field-note">Ce mode génère une fiche vCard enregistrable dans les contacts du téléphone.</p>
        <?php
        $this->panel_close();
    }

    public function render_shortcode($atts = []): string {
        $options = $this->get_options();
        $header_logo_url = $this->resolve_image_url($options, 'header_logo_id');
        $center_logo_url = $this->resolve_image_url($options, 'center_image_id');

        $atts = shortcode_atts([
            'show_header' => '1',
            'logo' => $options['logo_enabled'],
        ], $atts, 'wpqr');

        wp_enqueue_style('wpqr-style');
        wp_enqueue_script('wpqr-lib');
        wp_enqueue_script('wpqr-script');

        $instance_id = wp_unique_id('wpqr-');
        $data = [
            'dark' => $options['qr_dark'],
            'light' => $options['qr_light'],
            'size' => $options['default_size'],
            'margin' => $options['default_margin'],
            'logoEnabled' => $atts['logo'] === '1' && $center_logo_url !== '',
            'centerImageUrl' => $center_logo_url,
            'centerImageSizeRatio' => min((float) $options['center_image_size_ratio'], self::MAX_LOGO_RATIO),
            'maxLogoRatio' => self::MAX_LOGO_RATIO,
        ];

        $tabs = [
            'text' => 'Texte',
            'url' => 'Lien web',
            'wifi' => 'Wi-Fi',
            'phone' => 'Téléphone',
            'email' => 'E-mail',
            'sms' => 'SMS',
            'gps' => 'GPS',
            'event' => 'Événement',
            'contact' => 'Contact',
        ];

        ob_start();
        ?>
        <section
            id="<?php echo esc_attr($instance_id); ?>"
            class="wpqr-app"
            data-config="<?php echo esc_attr(wp_json_encode($data)); ?>"
            data-mode="text"
        >
            <?php if ($atts['show_header'] === '1') : ?>
                <header class="wpqr-header <?php echo $header_logo_url ? '' : 'is-text-only'; ?>">
                    <?php if ($header_logo_url) : ?>
                        <a class="wpqr-brand" href="<?php echo esc_url($options['header_link_url']); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo esc_url($header_logo_url); ?>" alt="<?php echo esc_attr($options['header_title']); ?>">
                        </a>
                    <?php endif; ?>
                    <div class="wpqr-header-text">
                        <h2><?php echo esc_html($options['header_title']); ?></h2>
                        <p><?php echo esc_html($options['subtitle']); ?></p>
                    </div>
                </header>
            <?php endif; ?>

            <div class="wpqr-tabs" role="tablist" aria-label="Type de QR code">
                <?php foreach ($tabs as $mode => $label) :
                    $active = $mode === 'text';
                    ?>
                    <button
                        id="<?php echo esc_attr($instance_id . '-tab-' . $mode); ?>"
                        type="button"
                        class="wpqr-tab<?php echo $active ? ' is-active' : ''; ?>"
                        data-tab="<?php echo esc_attr($mode); ?>"
                        role="tab"
                        aria-selected="<?php echo $active ? 'true' : 'false'; ?>"
                        aria-controls="<?php echo esc_attr($instance_id . '-panel-' . $mode); ?>"
                        tabindex="<?php echo $active ? '0' : '-1'; ?>"
                    ><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>

            <?php $this->render_help_intro(); ?>

            <form class="wpqr-form" novalidate>
                <?php
                $this->render_panel_text($instance_id);
                $this->render_panel_url($instance_id);
                $this->render_panel_wifi($instance_id);
                $this->render_panel_phone($instance_id);
                $this->render_panel_email($instance_id);
                $this->render_panel_sms($instance_id);
                $this->render_panel_gps($instance_id);
                $this->render_panel_event($instance_id);
                $this->render_panel_contact($instance_id);
                ?>

                <div class="wpqr-grid">
                    <label>
                        <span>Taille</span>
                        <input type="number" name="size" min="150" max="1200" step="10" value="<?php echo esc_attr($options['default_size']); ?>">
                        <small class="wpqr-field-note">Augmentez-la pour une impression ou un affichage à distance.</small>
                    </label>
                    <label>
                        <span>Marge minimale</span>
                        <input type="number" name="margin" min="0" max="80" step="1" value="<?php echo esc_attr($options['default_margin']); ?>">
                        <small class="wpqr-field-note">Une zone calme d’au moins quatre modules est ajoutée automatiquement.</small>
                    </label>
                    <label>
                        <span>Correction d’erreur</span>
                        <select name="ecLevel" <?php echo $data['logoEnabled'] ? 'disabled' : ''; ?>>
                            <option value="L">L - 7%</option>
                            <option value="M" <?php selected($data['logoEnabled'], false); ?>>M - 15%</option>
                            <option value="Q">Q - 25%</option>
                            <option value="H" <?php selected($data['logoEnabled'], true); ?>>H - 30%</option>
                        </select>
                        <small class="wpqr-field-note"><?php echo $data['logoEnabled'] ? '<strong>H est imposé automatiquement car un logo central est actif.</strong>' : 'Augmentez ce niveau si le QR risque d’être partiellement masqué ou dégradé.'; ?></small>
                    </label>
                </div>

                <div class="wpqr-actions">
                    <button type="submit" class="wpqr-button">Générer</button>
                    <button type="button" class="wpqr-button is-secondary" data-action="reset">Réinitialiser</button>
                </div>

                <p class="wpqr-status" data-role="status" role="status" aria-live="polite" tabindex="-1"></p>
                <p class="wpqr-help">Le QR code est généré localement dans le navigateur, sans service externe.</p>
                <div class="wpqr-privacy" role="note" aria-label="Confidentialité">
                    <strong>Confidentialité :</strong> les contenus saisis dans ce formulaire ne sont pas enregistrés sur le site. Ils servent uniquement à générer le QR code dans votre navigateur.
                </div>
            </form>

            <section class="wpqr-output" hidden tabindex="-1" aria-label="Résultat de la génération">
                <h3 class="wpqr-sr-only">QR code généré</h3>
                <div class="wpqr-output-frame">
                    <canvas class="wpqr-canvas" width="320" height="320" role="img" aria-label="QR code généré"></canvas>
                </div>

                <div class="wpqr-links">
                    <a class="wpqr-button" data-role="download" href="#" download="qr-code.png">Télécharger en PNG</a>
                    <a class="wpqr-button" data-role="download-svg" href="#" download="qr-code.svg">Télécharger en SVG</a>
                    <a class="wpqr-button is-secondary" data-role="open" href="#" target="_blank" rel="noopener noreferrer">Ouvrir l’image</a>
                </div>

                <div class="wpqr-raw">
                    <strong>Contenu brut :</strong>
                    <code data-role="payload"></code>
                </div>

                <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static wpqr-output-help">
                    <h3 class="wpqr-helpbox-title">Conseils après génération</h3>
                    <div class="wpqr-helpbox-content">
                        <ul>
                            <li>Testez le QR sur plusieurs smartphones avant diffusion ou impression.</li>
                            <li>Préférez le SVG pour une affiche, un grand format ou un document destiné à l’impression.</li>
                            <li>Si le scan est difficile, augmentez la taille ou simplifiez le contenu.</li>
                        </ul>
                    </div>
                </div>
            </section>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}

new WPQR_Plugin();
