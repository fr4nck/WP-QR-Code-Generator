=== WP QR Code Generator ===
Contributors: fr4nck
Tags: qr-code, generator, wifi, vcard, event, privacy
Requires at least: 6.0
Stable tag: 1.3.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Générateur autonome de QR codes pour WordPress, sans stockage des contenus ni service externe.

== Description ==

WP QR Code Generator permet d’intégrer un générateur complet dans une page WordPress avec le shortcode [wpqr].

Types pris en charge : texte, lien web, contenu WordPress, Wi-Fi, téléphone, e-mail, SMS, GPS, événement iCalendar et contact vCard.

Les coordonnées générales d’un organisme peuvent être enregistrées dans les réglages afin de préremplir facultativement les fiches de contact. Les données propres à chaque personne ne sont pas conservées.

Les QR codes sont générés localement dans le navigateur et peuvent être téléchargés en PNG ou SVG. Le mode Contenu WordPress encode une URL stable /qr/{ID}/ qui redirige en HTTP 302 vers le permalien actuel du contenu publié sélectionné.

== Installation ==

1. Téléversez le dossier du plugin dans /wp-content/plugins/ ou installez l’archive ZIP depuis WordPress.
2. Activez l’extension.
3. Ouvrez Réglages > WP QR Code Generator.
4. Configurez l’apparence et les logos si nécessaire.
5. Insérez le shortcode [wpqr] dans une page ou un article.

== Confidentialité ==

Les contenus saisis ne sont pas enregistrés dans WordPress et ne sont pas transmis à un service externe. Le plugin ne crée aucun CPT, aucune table, aucun historique et aucun objet QR stocké.

== Changelog ==

= 1.3.0 =
* Ajout du mode « Contenu WordPress » avec recherche par autocomplétion.
* Encodage local d’URL stables /qr/{ID}/ basées sur les identifiants natifs WordPress.
* Ajout de la règle de réécriture /qr/{ID}/ avec redirection 302 vers le permalien actuel.
* Ajout d’une page noindex « QR code indisponible » avec retour à l’accueil et bouton de contact optionnel.
* Ajout d’un réglage de page de contact publiée sans saisie directe d’e-mail ou de téléphone.


= 1.2.0 =
* Ajout d’une section « Coordonnées de l’organisme » dans les réglages.
* Préremplissage facultatif des fiches de contact vCard.
* Ajout des champs fonction, service, code postal, ville et pays.
* Amélioration du format vCard pour les coordonnées professionnelles.
* Les coordonnées ponctuelles saisies dans le générateur restent non enregistrées.

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
