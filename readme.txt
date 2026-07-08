=== WP QR Code Generator ===
Contributors: fr4nck
Tags: qr-code, generator, wifi, vcard, event, privacy
Requires at least: 6.0
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Générateur autonome de QR codes pour WordPress, sans stockage des contenus ni service externe.

== Description ==

WP QR Code Generator permet d’intégrer un générateur complet dans une page WordPress avec le shortcode [wpqr].

Types pris en charge : texte, lien web, Wi-Fi, téléphone, e-mail, SMS, GPS, événement iCalendar et contact vCard.

Les QR codes sont générés localement dans le navigateur et peuvent être téléchargés en PNG ou SVG.

== Installation ==

1. Téléversez le dossier du plugin dans /wp-content/plugins/ ou installez l’archive ZIP depuis WordPress.
2. Activez l’extension.
3. Ouvrez Réglages > WP QR Code Generator.
4. Configurez l’apparence et les logos si nécessaire.
5. Insérez le shortcode [wpqr] dans une page ou un article.

== Confidentialité ==

Les contenus saisis ne sont pas enregistrés dans WordPress et ne sont pas transmis à un service externe.

== Changelog ==

= 1.1.0 =
* Ajout du QR code Événement au format iCalendar.
* Ajout de l’export SVG.
* Correction H automatique avec un logo central.
* Limitation du logo central à 22 %.
* Garantie d’une zone calme minimale de quatre modules.
* Amélioration de la navigation au clavier et des attributs ARIA.
* Ajout de messages d’erreur et de confirmation accessibles.
* Activation explicite de l’encodage UTF-8.
* Nettoyage des anciens noms internes de styles.

= 1.0 =
* Première version publique.
