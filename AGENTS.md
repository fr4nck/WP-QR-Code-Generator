# AGENTS.md

## Objectif

Développer des logiciels simples, robustes, lisibles et pérennes.

Les développements doivent privilégier la maintenance à long terme, la compréhension du code et la sobriété technique.

## Langue

- Les échanges avec l'utilisateur sont en français.
- Les prompts, analyses, revues de code et comptes rendus sont rédigés en français.
- Les README, CHANGELOG et notes de version sont rédigés en français, sauf demande contraire.
- Les identifiants techniques (variables, fonctions, classes, méthodes, API...) conservent la langue du projet.

## Philosophie

- Résoudre un besoin réel avant d'ajouter une fonctionnalité.
- Privilégier la solution la plus simple.
- Limiter les dépendances.
- Préserver la compatibilité ascendante.
- Respecter la vie privée des utilisateurs.
- Éviter les traitements ou stockages inutiles.

## Architecture

- Utiliser les fonctionnalités natives de la plateforme avant de développer une solution spécifique.
- Ne créer de nouvelles structures (tables, types de contenus, services...) que lorsqu'elles sont réellement nécessaires.
- Limiter le nombre de fichiers.
- Éviter la complexité inutile.
- Favoriser un code lisible plutôt qu'ingénieux.

## Développement

- Une fonctionnalité répond à un objectif clairement identifié.
- Limiter les effets de bord.
- Préserver le comportement existant.
- Ne pas modifier l'architecture sans justification.

## Documentation

Mettre à jour lorsque nécessaire :

- README.md
- CHANGELOG.md
- Documentation utilisateur
- Documentation technique

Les explications doivent être factuelles, concises et directement exploitables.

## Vérifications

Avant toute livraison :

- compilation ou validation syntaxique ;
- absence de régression connue ;
- cohérence de la version ;
- cohérence de la documentation ;
- sécurité des entrées utilisateur ;
- respect de la plateforme cible.

## Communication

- Répondre de manière concise.
- Éviter les répétitions.
- Éviter les formulations promotionnelles.
- Aller directement au résultat.
- En cas de doute, demander une clarification plutôt que supposer.

## Méthode

1. Comprendre le besoin.
2. Proposer la solution la plus simple.
3. Développer.
4. Vérifier.
5. Livrer.
