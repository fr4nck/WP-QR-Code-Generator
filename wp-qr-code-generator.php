<?php
/**
 * Plugin Name: WP QR Code Generator
 * Plugin URI: https://wordpress.org/plugins/
 * Description: Générateur autonome de QR codes texte, lien web, Wi-Fi, téléphone, e-mail, SMS, GPS et contact, avec logos optionnels choisis depuis la médiathèque WordPress.
 * Version: 1.0
 * Author: Franck Bellardie
 * License: GPL-2.0-or-later
 * Text Domain: wp-qr-code-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

final class WPQR_Plugin {
    private const OPTION_KEY = 'wpqr_options';
    private const VERSION = '1.0';

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

        $ratio = isset($input['center_image_size_ratio']) ? (float) $input['center_image_size_ratio'] : (float) $defaults['center_image_size_ratio'];
        $ratio = max(0.08, min(0.30, $ratio));
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
            'qr_light' => '#fff7eb',
            'default_size' => '320',
            'default_margin' => '16',
            'logo_enabled' => '0',
            'center_image_id' => 0,
            'center_image_size_ratio' => '0.18',
        ];
    }

    private function get_options(): array {
        $saved = get_option(self::OPTION_KEY, null);
        if (!is_array($saved)) {
            $saved = [];
        }
        return wp_parse_args($saved, $this->get_default_options());
    }

    private function resolve_image_url(array $options, string $idKey): string {
        $attachmentId = !empty($options[$idKey]) ? absint($options[$idKey]) : 0;
        if ($attachmentId === 0) {
            return '';
        }

        $url = wp_get_attachment_image_url($attachmentId, 'full');
        return is_string($url) ? $url : '';
    }

    private function render_media_field(string $name, int $value, string $imageUrl): void {
        ?>
        <div class="wpqr-media-field">
            <input type="hidden" class="wpqr-media-input" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string) $value); ?>">
            <div class="wpqr-media-preview <?php echo $imageUrl ? '' : 'is-empty'; ?>">
                <?php if ($imageUrl) : ?>
                    <img src="<?php echo esc_url($imageUrl); ?>" alt="">
                <?php else : ?>
                    Aucune image sélectionnée
                <?php endif; ?>
            </div>
            <div class="wpqr-media-actions">
                <button type="button" class="button wpqr-media-select">Choisir une image</button>
                <button type="button" class="button wpqr-media-remove">Retirer</button>
            </div>
            <p class="wpqr-image-field-note">Utilisez de préférence une image carrée et nette. Le logo central ne doit pas être trop grand pour préserver la lisibilité du QR.</p>
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
                    '<label><input type="checkbox" name="%1$s" value="1" %2$s> Utiliser le logo au centre des nouveaux QR codes</label><p class="description">Le logo ne sera inséré que si une image centrale est choisie ci-dessous.</p>',
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
                    '<input type="number" min="0" max="80" step="1" name="%1$s" value="%2$s"> <span class="description">en pixels</span>',
                    esc_attr($name),
                    esc_attr((string) $value)
                );
                break;
            case 'center_image_size_ratio':
                printf(
                    '<input type="number" min="0.08" max="0.30" step="0.01" name="%1$s" value="%2$s"> <p class="description">0.18 est un bon point de départ. Au-delà, la lecture peut devenir moins fiable.</p>',
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
            <p>Cette version propose des onglets prêts à l’emploi : Texte, Lien web, Wi-Fi, Téléphone, E-mail, SMS, GPS et Contact.</p>
            <p>Les contenus saisis par les visiteurs ne sont pas enregistrés. Ils servent uniquement à générer le QR code dans leur navigateur.</p>
            <p><strong>Aucun logo n’est fourni par défaut.</strong> Vous pouvez choisir librement un logo d’en-tête et un logo central depuis la médiathèque.</p>
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

    private function render_help_intro(): void {
        ?>
        <section class="wpqr-guidance" aria-label="Conseils d’utilisation">
            <div class="wpqr-helpbox wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Conseils rapides</h3>
                <div class="wpqr-helpbox-content">
                    <ul>
                        <li>Choisissez l’onglet correspondant au besoin pour éviter de saisir les formats techniques à la main.</li>
                        <li>Pour l’impression, augmentez la taille et gardez une marge claire autour du code.</li>
                        <li>Avec un logo central, le niveau de correction d’erreur <strong>H</strong> est recommandé.</li>
                        <li>Testez toujours le scan sur plusieurs téléphones avant diffusion.</li>
                    </ul>
                </div>
            </div>
        </section>
        <?php
    }

    private function render_panel_text(): void {
        ?>
        <div class="wpqr-panel is-active" data-panel="text">
            <label>
                <span>Texte à encoder</span>
                <textarea name="text_payload" rows="4" placeholder="Bienvenue !"></textarea>
            </label>
            <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Aide pour le mode Texte</h3>
                <div class="wpqr-helpbox-content">
                    <p>Utilisez ce mode pour un message libre, une consigne, un code d’accès, une référence ou n’importe quel contenu simple.</p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_panel_url(): void {
        ?>
        <div class="wpqr-panel" data-panel="url">
            <label>
                <span>Adresse web</span>
                <input type="url" name="url_value" placeholder="https://exemple.org/">
            </label>
            <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Aide pour le mode Lien web</h3>
                <div class="wpqr-helpbox-content">
                    <p>Collez ici l’URL complète d’un site, d’un formulaire, d’un PDF ou d’une page d’inscription.</p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_panel_wifi(): void {
        ?>
        <div class="wpqr-panel" data-panel="wifi">
            <label>
                <span>Nom du réseau (SSID)</span>
                <input type="text" name="wifi_ssid" placeholder="Mon réseau Wi-Fi">
            </label>
            <label>
                <span>Mot de passe</span>
                <input type="text" name="wifi_password" placeholder="Mot de passe Wi-Fi">
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
            <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Aide pour le mode Wi-Fi</h3>
                <div class="wpqr-helpbox-content">
                    <p>Indiquez le nom exact du réseau et le bon type de sécurité. Cochez <strong>Réseau masqué</strong> seulement si le SSID n’est pas diffusé.</p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_panel_phone(): void {
        ?>
        <div class="wpqr-panel" data-panel="phone">
            <label>
                <span>Numéro de téléphone</span>
                <input type="text" name="phone_number" placeholder="+33299000000">
            </label>
            <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Aide pour le mode Téléphone</h3>
                <div class="wpqr-helpbox-content">
                    <p>Le scan proposera directement de lancer un appel. Utilisez de préférence un numéro au format international.</p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_panel_email(): void {
        ?>
        <div class="wpqr-panel" data-panel="email">
            <label>
                <span>Adresse e-mail</span>
                <input type="email" name="email_to" placeholder="contact@exemple.org">
            </label>
            <label>
                <span>Sujet</span>
                <input type="text" name="email_subject" placeholder="Demande d’information">
            </label>
            <label>
                <span>Message</span>
                <textarea name="email_body" rows="3" placeholder="Bonjour,"></textarea>
            </label>
            <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Aide pour le mode E-mail</h3>
                <div class="wpqr-helpbox-content">
                    <p>Le scan ouvrira un nouveau mail prérempli avec le destinataire, le sujet et éventuellement le message.</p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_panel_sms(): void {
        ?>
        <div class="wpqr-panel" data-panel="sms">
            <label>
                <span>Numéro</span>
                <input type="text" name="sms_number" placeholder="+33600000000">
            </label>
            <label>
                <span>Message</span>
                <textarea name="sms_body" rows="3" placeholder="Bonjour, je souhaite avoir un renseignement."></textarea>
            </label>
            <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Aide pour le mode SMS</h3>
                <div class="wpqr-helpbox-content">
                    <p>Le scan ouvrira l’application de SMS avec le numéro et le message préremplis.</p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_panel_gps(): void {
        ?>
        <div class="wpqr-panel" data-panel="gps">
            <div class="wpqr-grid wpqr-grid--two">
                <label>
                    <span>Latitude</span>
                    <input type="text" name="gps_lat" placeholder="48.101">
                </label>
                <label>
                    <span>Longitude</span>
                    <input type="text" name="gps_lng" placeholder="-1.674">
                </label>
            </div>
            <label>
                <span>Nom du lieu (facultatif)</span>
                <input type="text" name="gps_label" placeholder="Mon lieu">
            </label>
            <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Aide pour le mode GPS</h3>
                <div class="wpqr-helpbox-content">
                    <p>Le scan ouvrira généralement l’application de cartographie avec le point géographique indiqué.</p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_panel_contact(): void {
        ?>
        <div class="wpqr-panel" data-panel="contact">
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
                    <input type="text" name="contact_phone" placeholder="+33299000000">
                </label>
                <label>
                    <span>E-mail</span>
                    <input type="email" name="contact_email" placeholder="contact@exemple.org">
                </label>
            </div>
            <label>
                <span>Site web</span>
                <input type="url" name="contact_url" placeholder="https://exemple.org/">
            </label>
            <label>
                <span>Adresse</span>
                <textarea name="contact_address" rows="3" placeholder="Adresse postale"></textarea>
            </label>
            <label>
                <span>Note</span>
                <textarea name="contact_note" rows="3" placeholder="Informations complémentaires"></textarea>
            </label>
            <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                <h3 class="wpqr-helpbox-title">Aide pour le mode Contact</h3>
                <div class="wpqr-helpbox-content">
                    <p>Ce mode génère une fiche contact de type vCard pour enregistrer rapidement une personne ou une structure dans le téléphone.</p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_shortcode($atts = []): string {
        $options = $this->get_options();
        $headerLogoUrl = $this->resolve_image_url($options, 'header_logo_id');
        $centerLogoUrl = $this->resolve_image_url($options, 'center_image_id');

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
            'logoEnabled' => $atts['logo'] === '1' && $centerLogoUrl !== '',
            'centerImageUrl' => $centerLogoUrl,
            'centerImageSizeRatio' => $options['center_image_size_ratio'],
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
                <header class="wpqr-header <?php echo $headerLogoUrl ? '' : 'is-text-only'; ?>">
                    <?php if ($headerLogoUrl) : ?>
                        <a class="wpqr-brand" href="<?php echo esc_url($options['header_link_url']); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo esc_url($headerLogoUrl); ?>" alt="">
                        </a>
                    <?php endif; ?>
                    <div class="wpqr-header-text">
                        <h2><?php echo esc_html($options['header_title']); ?></h2>
                        <p><?php echo esc_html($options['subtitle']); ?></p>
                    </div>
                </header>
            <?php endif; ?>

            <div class="wpqr-tabs" role="tablist" aria-label="Type de QR code">
                <button type="button" class="wpqr-tab is-active" data-tab="text">Texte</button>
                <button type="button" class="wpqr-tab" data-tab="url">Lien web</button>
                <button type="button" class="wpqr-tab" data-tab="wifi">Wi-Fi</button>
                <button type="button" class="wpqr-tab" data-tab="phone">Téléphone</button>
                <button type="button" class="wpqr-tab" data-tab="email">E-mail</button>
                <button type="button" class="wpqr-tab" data-tab="sms">SMS</button>
                <button type="button" class="wpqr-tab" data-tab="gps">GPS</button>
                <button type="button" class="wpqr-tab" data-tab="contact">Contact</button>
            </div>

            <?php $this->render_help_intro(); ?>

            <form class="wpqr-form" novalidate>
                <?php
                $this->render_panel_text();
                $this->render_panel_url();
                $this->render_panel_wifi();
                $this->render_panel_phone();
                $this->render_panel_email();
                $this->render_panel_sms();
                $this->render_panel_gps();
                $this->render_panel_contact();
                ?>

                <div class="wpqr-grid">
                    <label>
                        <span>Taille</span>
                        <input type="number" name="size" min="150" max="1200" step="10" value="<?php echo esc_attr($options['default_size']); ?>">
                        <small class="wpqr-field-note">Augmentez-la pour une impression ou un affichage à distance.</small>
                    </label>
                    <label>
                        <span>Marge</span>
                        <input type="number" name="margin" min="0" max="80" step="1" value="<?php echo esc_attr($options['default_margin']); ?>">
                        <small class="wpqr-field-note">Laissez une bordure claire autour du QR pour améliorer la lecture.</small>
                    </label>
                    <label>
                        <span>Correction d’erreur</span>
                        <select name="ecLevel">
                            <option value="L">L - 7%</option>
                            <option value="M" <?php selected($data['logoEnabled'], false); ?>>M - 15%</option>
                            <option value="Q">Q - 25%</option>
                            <option value="H" <?php selected($data['logoEnabled'], true); ?>>H - 30%</option>
                        </select>
                        <small class="wpqr-field-note">Avec un logo central, le niveau <strong>H</strong> est recommandé.</small>
                    </label>
                </div>

                <div class="wpqr-helpbox wpqr-helpbox-inline wpqr-helpbox-static">
                    <h3 class="wpqr-helpbox-title">Conseils de paramétrage</h3>
                    <div class="wpqr-helpbox-content">
                        <ul>
                            <li><strong>Taille</strong> : 300 à 400 px conviennent bien pour un usage écran ; augmentez pour l’impression.</li>
                            <li><strong>Marge</strong> : gardez une zone blanche autour du code, surtout si le fond de la page est coloré.</li>
                            <li><strong>Correction d’erreur</strong> : plus elle est élevée, plus le QR tolère les petits défauts ou la présence du logo.</li>
                        </ul>
                    </div>
                </div>

                <div class="wpqr-actions">
                    <button type="submit" class="wpqr-button">Générer</button>
                    <button type="button" class="wpqr-button is-secondary" data-action="reset">Réinitialiser</button>
                </div>

                <p class="wpqr-help">Le QR code est généré localement dans le navigateur, sans service externe.</p>
                <div class="wpqr-privacy" role="note" aria-label="Confidentialité">
                    <strong>Confidentialité :</strong> les contenus saisis dans ce formulaire ne sont pas enregistrés sur le site. Ils servent uniquement à générer le QR code dans votre navigateur.
                </div>
            </form>

            <section class="wpqr-output" hidden>
                <div class="wpqr-output-frame">
                    <canvas class="wpqr-canvas" width="320" height="320"></canvas>
                </div>

                <div class="wpqr-links">
                    <a class="wpqr-button" data-role="download" href="#" download="qr-code.png">Télécharger le QR</a>
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
                            <li>Si le scan est difficile, augmentez la taille, la marge, ou réduisez l’importance du logo central.</li>
                            <li>Pour le Wi-Fi, vérifiez l’orthographe du SSID et le type de sécurité choisi.</li>
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
