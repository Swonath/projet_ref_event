# ANNEXE 9-1-B — Fiche descriptive de réalisation professionnelle
## BTS SIO SLAM — Session 2025 — AUDET Nathan — N° 0182835530

---

## RECTO

**N° réalisation :** 1

**Nom, prénom :** AUDET Nathan
**N° candidat :** 0182835530
**Épreuve ponctuelle :** ☒
**Date :** 25 / 11 / 2025

---

### Organisation support de la réalisation professionnelle

Projet personnel réalisé dans le cadre de la formation BTS SIO option SLAM — Paris 75005

---

### Intitulé de la réalisation professionnelle

**Développement d'une plateforme web de location d'espaces événementiels**

---

**Période de réalisation :** 2025
**Lieu :** Paris 75005
**Modalité :** ☒ Seul(e)

---

### Compétences travaillées

- ☒ Concevoir et développer une solution applicative
- ☒ Assurer la maintenance corrective ou évolutive d'une solution applicative
- ☒ Gérer les données

---

### Conditions de réalisation (ressources fournies, résultats attendus)

**Contexte :**
Dans le cadre d'un projet personnel, il s'agissait de concevoir et développer une marketplace web
permettant à des centres commerciaux de publier des annonces de location d'espaces événementiels,
et à des locataires professionnels de consulter, réserver et payer ces espaces en ligne.

**Résultats attendus :**
- Application web multi-rôles fonctionnelle (centre commercial, locataire, administrateur)
- Système de réservation avec calcul automatique des prix (tarifs jour/semaine/mois, commission de 25 %, TVA de 20 %, caution)
- Paiement en ligne sécurisé via l'API Stripe (PaymentIntent, webhooks, remboursements)
- Messagerie interne entre locataires et centres commerciaux
- Système d'avis et de notation multi-critères
- Gestion des périodes d'indisponibilité des espaces
- Tableau de bord avec indicateurs clés (KPI) pour chaque type d'utilisateur
- Interface de recherche avec filtres avancés (surface, type, prix, localisation)

---

### Description des ressources documentaires, matérielles et logicielles utilisées

**Ressources documentaires :**
- Documentation officielle Symfony 7 (symfony.com/doc)
- Documentation de l'API Stripe (stripe.com/docs)
- Documentation Doctrine ORM 3 (doctrine-project.org)
- Documentation Twig (twig.symfony.com)
- Documentation PHP 8.2 (php.net)

**Ressources matérielles :**
- MacBook (environnement de développement local)
- Serveur local MAMP (Apache, MySQL 10.4.32 MariaDB, PHP 8.2)
- Docker / Docker Compose (conteneurisation)

**Ressources logicielles :**
- PHP 8.2
- Symfony 7.0 (framework back-end)
- Doctrine ORM 3.5 (mapping objet-relationnel)
- Twig 3 (moteur de templates)
- MySQL / MariaDB 10.4 (SGBD)
- Stripe PHP SDK v19.3 (paiement en ligne)
- Stimulus 2.30 + Turbo 2.30 (JavaScript côté client)
- Symfony Asset Mapper 7.0 (gestion des assets)
- Composer (gestionnaire de dépendances PHP)
- Visual Studio Code (éditeur de code)
- Git (gestion de versions)
- PHPUnit 11.5 (tests unitaires)

---

### Modalités d'accès aux productions et à leur documentation

- **Dépôt Git :** disponible sur GitHub (lien fourni à l'examinateur)
- **Installation locale :**
  1. `composer install`
  2. Configurer le fichier `.env.local` (base de données, clés Stripe)
  3. `php bin/console doctrine:migrations:migrate`
  4. `php bin/console doctrine:fixtures:load` (données de démonstration)
  5. `symfony serve`
- **Comptes de démonstration :** identifiants fournis à l'examinateur lors de l'épreuve
- **Base de données :** MySQL `references_evenements`, schéma versionnalisé par migrations Doctrine

---

## VERSO — Descriptif de la réalisation professionnelle

### 1. Présentation générale

J'ai développé seul une plateforme web de type marketplace baptisée **Références Événements**,
permettant la mise en relation entre des **centres commerciaux** qui disposent d'espaces
événementiels à louer et des **locataires professionnels** souhaitant réserver ces espaces.

L'application est construite avec le framework **Symfony 7.0** (PHP 8.2), utilise **Doctrine ORM**
pour la persistance des données dans une base **MySQL/MariaDB**, et intègre l'API de paiement
**Stripe** pour la gestion des transactions financières.

---

### 2. Compétences mobilisées

#### A. Concevoir et développer une solution applicative

**Architecture MVC avec Symfony 7**

L'application repose sur le patron d'architecture **Modèle-Vue-Contrôleur** imposé par Symfony.
Chaque composant a un rôle clairement défini :

- **Modèle :** 15 entités PHP annotées (Doctrine ORM), organisées en couche métier. La logique
  de calcul des prix est isolée dans un service dédié (`ReservationCalculatorService`) et la
  logique de paiement dans un autre (`StripeService`), conformément au principe de responsabilité
  unique (SRP).
- **Vue :** templates **Twig** organisés par domaine (`locataire/`, `centre_commercial/`, `admin/`)
  avec des composants réutilisables (partials) pour éviter la duplication de code HTML (barre de
  navigation, cartes d'emplacements, alertes…).
- **Contrôleur :** chaque contrôleur traite les requêtes HTTP, orchestre les services, et renvoie
  une réponse (page HTML ou redirection). Ils sont regroupés par périmètre fonctionnel :
  `CentreCommercial/`, `Locataire/`, `Admin/` et contrôleurs publics.
- **Routage :** déclaré via les attributs PHP `#[Route]` directement sur les méthodes de
  contrôleur, ce qui rend la lecture du code plus directe.
- **Sécurité :** le pare-feu (firewall) Symfony contrôle l'accès selon trois rôles :
  `ROLE_CENTRE`, `ROLE_LOCATAIRE`, `ROLE_ADMIN`. Chaque espace est protégé par des
  annotations `#[IsGranted]` ou des vérifications dans `security.yaml`.

**Modélisation objet avec Doctrine ORM**

Le modèle de données est entièrement défini en PHP sous forme de classes annotées.
Doctrine ORM traduit automatiquement ces classes en tables SQL via des migrations versionnalisées.

Choix de conception notables :

- **Héritage JOINED** pour les utilisateurs : la table `user` contient les champs communs
  (email, mot de passe, rôles), et trois tables filles (`centre_commercial`, `locataire`,
  `administrateur`) stockent les données spécifiques. Cela évite la duplication de colonnes tout
  en gardant des entités PHP distinctes.
- **Enum PHP natif** `StatutReservation` pour les 6 états possibles d'une réservation
  (en_attente, validée, en_cours, terminée, refusée, annulée). L'utilisation d'un enum garantit
  qu'aucune valeur invalide ne peut être persistée en base.
- **Repository personnalisés** pour chaque entité : les requêtes complexes (filtres de recherche
  combinés, vérification de chevauchement de dates, calcul d'indicateurs) sont encapsulées dans
  des méthodes dédiées, séparant la logique d'accès aux données des contrôleurs.

| Entité principale | Relation clé |
|---|---|
| CentreCommercial → Emplacement | OneToMany (un centre possède plusieurs espaces) |
| Emplacement → Reservation | OneToMany (un espace a plusieurs réservations) |
| Reservation → Paiement | OneToOne (une réservation = une transaction) |
| Locataire → Conversation | OneToMany (messagerie) |
| Emplacement → Photo | OneToMany ordonnée (galerie) |
| Emplacement → PeriodeIndisponibilite | OneToMany (blocage calendrier) |

**Interface utilisateur web (Twig + Stimulus JS)**

- Les vues sont construites en **Twig** : héritage de templates (`extends base.html.twig`),
  blocs surchargeables (`{% block content %}`), filtres personnalisés et macros pour
  formater les prix et les dates.
- **Stimulus JS** (framework JavaScript léger) est utilisé pour les interactions dynamiques :
  mise à jour du récapitulatif de prix à la sélection des dates, gestion des formulaires
  multi-étapes, prévisualisation des photos avant upload.
- **Turbo** (Hotwire) permet des navigations sans rechargement complet de page, rendant
  l'expérience plus fluide sans écrire de SPA complexe.
- **Asset Mapper** de Symfony gère les fichiers CSS et JavaScript sans nécessiter de bundler
  externe (Webpack/Vite), simplifiant le pipeline de build.

---

### 3. Fonctionnalités implémentées

#### Module Authentification — 3 rôles

L'authentification repose sur le **Security Bundle de Symfony** avec un système de formulaire
de connexion classique et hachage des mots de passe (bcrypt).

- **Inscription différenciée :** deux formulaires d'inscription distincts selon le profil
  (centre commercial ou locataire), avec des champs de validation métier spécifiques à chaque rôle
  (SIRET, IBAN, numéro de TVA pour les centres ; type d'activité et adresse de facturation
  pour les locataires).
- **Firewall Symfony :** chaque espace de l'application (`/centre/`, `/locataire/`, `/admin/`)
  est protégé par un contrôle d'accès basé sur les rôles. Un locataire ne peut jamais accéder
  à l'espace d'un centre, et vice-versa.
- **Validation des comptes centres :** un centre commercial nouvellement inscrit a le statut
  `en_attente`. L'administrateur doit valider manuellement le compte (vérification SIRET/IBAN)
  avant que le centre puisse publier des annonces. Cela protège la plateforme contre les
  inscriptions frauduleuses.
- **Déconnexion sécurisée** avec invalidation de session côté serveur.

#### Module Tableau de bord — Admin, Centre, Locataire

Chaque rôle dispose d'un tableau de bord personnalisé affichant des **indicateurs clés (KPI)**
calculés dynamiquement depuis la base de données :

- **Administrateur :** liste des centres en attente de validation, nombre total d'utilisateurs,
  de réservations actives, d'espaces publiés. Actions : valider/suspendre un compte centre,
  modérer les avis, gérer les paramètres de la plateforme (taux de commission, TVA).
- **Centre commercial :** nombre de réservations en attente de confirmation, chiffre d'affaires
  du mois en cours, taux d'occupation des espaces, avis récents reçus, liste des espaces publiés
  avec leur statut.
- **Locataire :** réservations en cours, historique des locations passées, messages non lus,
  liste des favoris enregistrés, accès rapide aux documents de réservation.

#### Module Emplacements — CRUD + Photos

Ce module permet aux centres commerciaux de gérer leur catalogue d'espaces.

- **CRUD complet :** création, modification, publication et suppression d'une annonce d'espace.
  Chaque espace est défini par un titre, une description, une surface (m²), un type
  (stand, scène, hall, extérieur…), une ville, et trois tarifs (jour, semaine, mois).
- **Gestion des photos :** upload multiple de fichiers image avec contrôle du type MIME et de
  la taille. Les photos sont ordonnées (champ `ordre`) pour définir l'image principale affichée
  en tête de liste.
- **Périodes d'indisponibilité :** le centre peut bloquer des créneaux calendaires (travaux,
  événement réservé hors plateforme). Ces périodes sont vérifiées lors de chaque demande de
  réservation pour éviter les conflits.
- **Statut de publication :** une annonce peut être en brouillon (`inactive`) ou publiée
  (`active`), permettant au centre de préparer ses annonces avant de les rendre visibles.

#### Module Réservations & Paiements

C'est le cœur fonctionnel de la plateforme. Il repose sur un **workflow en plusieurs étapes** :

1. **Sélection des dates :** le locataire choisit les dates de début et de fin.
   La disponibilité est vérifiée en base en excluant les réservations existantes et les
   périodes d'indisponibilité définies par le centre.

2. **Calcul automatique du prix** (`ReservationCalculatorService`) :
   - Calcul du nombre de jours entre les deux dates
   - Application du tarif le plus avantageux selon la durée (jour / semaine / mois)
   - Ajout de la commission plateforme (25 %)
   - Application de la TVA à 20 % sur le sous-total (location + commission)
   - Ajout de la caution (remboursable, non soumise à TVA)
   - Résultat : détail ligne à ligne du montant affiché au locataire

3. **Paiement Stripe :**
   - Le serveur crée un **PaymentIntent** via le SDK Stripe PHP et retourne la clé cliente
     au navigateur
   - La page de paiement intègre **Stripe.js** pour afficher un formulaire de carte bancaire
     sécurisé (PCI-DSS, données bancaires jamais transmises au serveur)
   - En cas de succès, Stripe envoie un **webhook** `payment_intent.succeeded` au serveur,
     qui crée l'entité `Paiement` et change le statut de la réservation
   - Les **remboursements** sont gérés via l'API Stripe en cas d'annulation selon les
     conditions définies

4. **Cycle de vie de la réservation** (enum `StatutReservation`) :
   `en_attente` → `validée` (par le centre) → `en_cours` (date de début atteinte)
   → `terminée` (date de fin atteinte) ou `refusée` / `annulée`

#### Module Messagerie interne

Le système de messagerie permet aux locataires et aux centres commerciaux de communiquer
directement au sein de la plateforme, sans exposer leurs coordonnées personnelles.

- **Conversations** : une conversation est créée lors d'une prise de contact ou à partir d'une
  réservation existante. Elle possède un sujet et relie un locataire à un centre commercial.
- **Messages** : chaque message est daté, lié à son expéditeur, et dispose d'un indicateur
  `estLu`. Le compteur de messages non lus est affiché dans la barre de navigation de chaque
  espace utilisateur.
- **Archivage** : les conversations peuvent être archivées pour désencombrer l'interface
  sans supprimer l'historique des échanges.
- **Sécurité** : un utilisateur ne peut accéder qu'aux conversations qui lui appartiennent
  (vérification dans le contrôleur).

#### Module Avis & Favoris

**Système d'avis :**
- Un locataire peut déposer un avis uniquement après qu'une réservation est passée au statut
  `terminée`, garantissant l'authenticité des retours.
- La notation est **multi-critères** : note globale, propreté et conformité, qualité de
  l'emplacement, rapport qualité/prix, communication avec le centre.
- Le centre commercial peut rédiger une **réponse publique** à chaque avis, comme sur les
  grandes plateformes de réservation.
- L'administrateur peut **modérer** (publier ou masquer) tout avis signalé comme inapproprié.

**Favoris :**
- Un locataire peut ajouter un espace à sa liste de favoris d'un simple clic (ajout/suppression
  sans rechargement de page grâce à Stimulus JS).
- La liste des favoris est accessible depuis le tableau de bord locataire et permet de retrouver
  rapidement des espaces consultés.

#### Module Recherche & Carte interactive

- **Recherche plein texte** sur plusieurs champs simultanément : titre de l'annonce, description,
  type d'espace, nom du centre, ville. La requête est construite dynamiquement dans le Repository
  Doctrine avec des critères AND/OR selon les filtres actifs.
- **Filtres combinables** : fourchette de surface (m² min/max), fourchette de prix, type
  d'emplacement, ville. Chaque filtre est optionnel et peut être combiné avec les autres.
- **Carte interactive** : les emplacements sont affichés sur une carte géographique. Chaque
  marqueur correspond à un espace publié et permet d'accéder directement à sa fiche détail.
  Cela facilite la recherche géographique, notamment pour des locataires cherchant un espace
  proche d'un lieu précis.
- **Page de détail** : chaque espace dispose d'une fiche publique présentant la galerie photos,
  les caractéristiques, la grille tarifaire, les avis déposés et un calendrier d'indisponibilité.

---

### 4. Maintenance corrective et évolutive réalisée

**Maintenance corrective :**

| Anomalie constatée | Cause identifiée | Correction apportée |
|---|---|---|
| Les emplacements n'apparaissaient pas dans les résultats de recherche | Le statut stocké en base était `publiee` mais la requête filtrait sur `active` | Correction de la valeur du statut dans les fixtures et le formulaire de création |
| Les images ne s'affichaient pas sur la page de détail | Le chemin relatif généré par Twig ne correspondait pas au répertoire de stockage réel | Correction du chemin dans le template Twig et uniformisation de la constante de répertoire |

**Maintenance évolutive :**

| Évolution demandée | Travail réalisé |
|---|---|
| Ajout de la TVA à 20 % sur le prix affiché | Refactorisation du `ReservationCalculatorService` pour distinguer montant HT, commission, TVA et total TTC |
| Intégration du paiement en ligne | Création du `StripeService`, ajout des routes webhook, refonte du tunnel de réservation en plusieurs étapes |

---

### 5. Gestion des données

- Base de données relationnelle **MySQL/MariaDB** avec 15 tables générées automatiquement
  par Doctrine ORM à partir des annotations des entités PHP.
- **Migrations Doctrine** : chaque évolution du schéma (ajout de colonne, nouvelle table,
  modification de contrainte) est versionnalisée dans un fichier de migration PHP. Cela permet
  de rejouer l'historique des modifications sur n'importe quel environnement.
- **DataFixtures** : des jeux de données de démonstration (centres, emplacements, locataires,
  réservations) sont générés via les fixtures Doctrine pour faciliter les tests et la démonstration.
- **Repository Doctrine personnalisés** : les requêtes métier complexes sont encapsulées dans
  des méthodes dédiées (ex. `findAvailableEmplacements()` qui exclut les créneaux occupés,
  `findByFilters()` pour la recherche multi-critères).
- **Enum PHP natif** `StatutReservation` : garantit l'intégrité des transitions d'état en base
  et permet à Doctrine de valider automatiquement les valeurs persistées.
