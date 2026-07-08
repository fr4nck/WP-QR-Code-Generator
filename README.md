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
- événements au format iCalendar ;
- fiches de contact au format vCard ;
- coordonnées générales d’un organisme enregistrables dans les réglages ;
- préremplissage facultatif des vCards à partir de ces coordonnées ;
- téléchargement au format PNG ;
- export vectoriel au format SVG ;
- génération locale dans le navigateur ;
- prise en charge UTF-8 ;
- interface responsive ;
- navigation accessible au clavier ;
- messages d’erreur et de confirmation accessibles ;
- logo d’en-tête facultatif ;
- logo facultatif au centre du QR code ;
- sélection des logos depuis la médiathèque WordPress ;
- correction d’erreur H automatique avec un logo central ;
- limitation automatique de la taille du logo ;
- zone calme minimale de quatre modules ;
- aucune donnée utilisateur enregistrée ;
- aucun service externe nécessaire.

## Exemple

```text
[wpqr]
```

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

### Événement

Crée un événement iCalendar avec titre, date, horaires, lieu et description.

### Contact

Crée une fiche de contact au format vCard pouvant contenir un nom, une fonction, un organisme, un service, un téléphone, une adresse e-mail, un site internet et une adresse postale.

Les coordonnées générales d’une association, d’une entreprise, d’une collectivité, d’une école ou de tout autre organisme peuvent être enregistrées dans les réglages WordPress. Un bouton permet ensuite de les charger dans le formulaire Contact sans enregistrer les informations propres à la personne.

## Installation

1. Téléchargez l’archive ZIP de la dernière version.
2. Dans WordPress, ouvrez **Extensions > Ajouter une extension**.
3. Cliquez sur **Téléverser une extension**.
4. Sélectionnez l’archive ZIP.
5. Installez puis activez le plugin.
6. Ouvrez **Réglages > WP QR Code Generator**.
7. Ajoutez le shortcode `[wpqr]` dans une page ou un article.

## Personnalisation

Depuis les réglages WordPress, il est possible de configurer :

- le titre et le sous-titre ;
- le lien d’en-tête ;
- le logo d’en-tête ;
- le logo central ;
- la taille du logo central ;
- les couleurs du QR code ;
- la taille et la marge par défaut ;
- les coordonnées générales de l’organisme utilisées pour préremplir les vCards.

Aucun logo n’est imposé ou fourni par défaut.

## Confidentialité

Les QR codes sont générés localement dans le navigateur de l’utilisateur.

Les informations saisies ne sont :

- ni enregistrées dans WordPress ;
- ni conservées dans un historique ;
- ni envoyées à un service externe de génération de QR codes.

Seuls les réglages généraux du plugin et les coordonnées de l’organisme saisies volontairement par un administrateur sont enregistrés dans WordPress.

## Bibliothèque utilisée

La génération des QR codes repose sur la bibliothèque JavaScript `qrcode-generator`, embarquée localement dans le plugin.

Les informations relatives aux composants tiers sont disponibles dans le fichier `LICENSE-THIRD-PARTY.txt`.

## Licence

GNU GPL v2 ou version ultérieure.
