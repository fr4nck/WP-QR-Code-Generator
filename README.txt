# WP QR Code Generator

Plugin WordPress permettant d’intégrer un générateur de QR codes complet, personnalisable et respectueux de la confidentialité.

## Objectif

WP QR Code Generator sert à générer différents types de QR codes directement depuis une page WordPress.

Le navigateur génère les QR codes localement. WordPress fournit l’interface et les réglages.

Aucune donnée saisie n’est enregistrée par le plugin ni transmise à un service externe de génération de QR codes.

## Fonctionnalités

- shortcode unique `[wpqr]` ;
- génération de QR codes contenant du texte ;
- liens vers des pages web ;
- informations de connexion Wi-Fi ;
- numéros de téléphone ;
- e-mails avec destinataire, objet et message ;
- SMS préremplis ;
- coordonnées GPS ;
- fiches de contact au format vCard ;
- téléchargement des QR codes au format PNG ;
- génération locale dans le navigateur ;
- interface responsive ;
- consignes d’utilisation intégrées ;
- logo d’en-tête facultatif ;
- logo facultatif au centre du QR code ;
- sélection des logos depuis la médiathèque WordPress ;
- personnalisation des couleurs, de la taille et des marges ;
- aucun service externe nécessaire ;
- aucune donnée utilisateur enregistrée.

## Exemple

    [wpqr]

Ajoutez simplement ce shortcode dans une page ou un article WordPress pour afficher le générateur complet.

La page contenant le générateur peut être publique, privée ou protégée par un mot de passe avec les fonctions natives de WordPress.

## Types de QR codes

### Texte

Encode un texte libre, une consigne ou toute autre information.

### Lien web

Ouvre directement une adresse web dans le navigateur.

### Wi-Fi

Permet de rejoindre un réseau Wi-Fi à partir de son nom, de son type de sécurité et de son mot de passe.

### Téléphone

Propose d’appeler directement le numéro renseigné.

### E-mail

Prépare un e-mail avec une adresse de destination, un objet et un message facultatifs.

### SMS

Prépare un SMS à destination du numéro indiqué.

### GPS

Ouvre des coordonnées géographiques dans une application cartographique compatible.

### Contact

Crée une fiche de contact au format vCard pouvant contenir un nom, une organisation, un téléphone, une adresse e-mail et une adresse postale.

## Confidentialité

Les QR codes sont générés localement dans le navigateur de l’utilisateur.

Les informations saisies ne sont :

- ni enregistrées dans WordPress ;
- ni conservées dans un historique ;
- ni envoyées à un service externe de génération de QR codes.

Seuls les réglages généraux du plugin sont enregistrés dans WordPress.

## Documentation

Les principales consignes d’utilisation sont affichées directement dans l’interface du générateur.

Le plugin se configure depuis :

    Réglages > WP QR Code Generator

## Bibliothèque utilisée

La génération des QR codes repose sur la bibliothèque JavaScript `qrcode-generator`, embarquée localement dans le plugin.

Les informations relatives aux composants tiers sont disponibles dans le fichier `LICENSE-THIRD-PARTY.txt`.

## Licence

GNU GPL v2 ou version ultérieure.
