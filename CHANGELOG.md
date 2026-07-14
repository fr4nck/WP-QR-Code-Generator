# Journal des modifications

## 1.3.0

- ajout du mode public « Contenu WordPress » dans le shortcode `[wpqr]` ;
- ajout d’une recherche par autocomplétion sur les articles, pages et types de contenus personnalisés publics publiés, hors pièces jointes ;
- génération locale d’une URL stable `/qr/{ID}/` basée sur l’identifiant natif WordPress ;
- ajout de la règle de réécriture `/qr/{ID}/` et d’une redirection HTTP 302 vers le permalien actuel ;
- ajout d’une page `noindex` « QR code indisponible » lorsque le contenu n’est plus accessible publiquement ;
- ajout d’un réglage permettant de choisir une page WordPress publiée comme page de contact, sans saisie directe d’e-mail ou de téléphone ;
- conservation du principe sans CPT, sans table, sans historique et sans objet QR stocké.


## 1.2.0

- ajout d’une section « Coordonnées de l’organisme » dans les réglages WordPress ;
- préremplissage facultatif des fiches de contact vCard ;
- ajout des champs fonction, service, code postal, ville et pays ;
- amélioration du format vCard pour les coordonnées professionnelles ;
- conservation du principe de confidentialité : seules les coordonnées générales configurées par l’administrateur sont enregistrées.

## 1.1.0

- ajout du QR code Événement au format iCalendar ;
- ajout du téléchargement SVG ;
- correction d’erreur H forcée lorsqu’un logo central est actif ;
- limitation du ratio du logo à 22 % ;
- garantie d’une zone calme minimale de quatre modules ;
- amélioration de l’accessibilité des onglets et des messages ;
- prise en charge explicite de l’UTF-8 ;
- nettoyage des anciens identifiants de style.

## 1.0

- première version publique.
