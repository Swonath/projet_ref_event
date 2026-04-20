# Cahier des Charges — Plateforme Références Événements

---

## 1. Présentation du projet

**Références Événements** est une plateforme web de mise en relation entre centres commerciaux et entrepreneurs souhaitant louer des emplacements éphémères (stands, kiosques, boutiques temporaires, corners).

Elle permet à un centre commercial de publier ses espaces disponibles, et à un locataire (entrepreneur, marque, association) de les trouver, les réserver et les payer en ligne, le tout sans démarche administrative complexe.

---

## 2. Ce que ça apporte à Références Événements

| Bénéfice | Détail |
|---|---|
| **Revenus récurrents** | Commission de 25 % sur chaque réservation payée via la plateforme |
| **Gain de temps** | Plus aucune gestion manuelle des demandes, des devis ou des paiements |
| **Visibilité nationale** | Les emplacements sont référencés et accessibles à tous les entrepreneurs de France |
| **Traçabilité** | Historique complet des réservations, paiements et documents |
| **Automatisation** | Emails automatiques, factures PDF, gestion des statuts |

---

## 3. Les acteurs de la plateforme

- **Locataire** : entrepreneur, marque ou association cherchant un espace de vente temporaire
- **Centre commercial** : propriétaire de l'espace, publie ses emplacements et reçoit les réservations
- **Administrateur Références Événements** : valide les comptes, supervise les réservations, gère les litiges

---

## 4. Fonctionnalités déjà en place

### 4.1 Inscription et connexion
- Inscription distincte pour les locataires et les centres commerciaux
- Validation du compte centre commercial par un administrateur avant activation
- Connexion sécurisée (email + mot de passe haché)
- Protection contre les tentatives de connexion en masse (rate limiting)

### 4.2 Gestion des emplacements (côté centre commercial)
- Création, modification et suppression d'emplacements
- Champs : titre, description, type (stand, kiosque, boutique, corner), surface, tarif journalier, disponibilité, photos
- Statut de publication (brouillon / actif)

### 4.3 Recherche et découverte (côté locataire)
- Barre de recherche par ville, code postal, type d'emplacement
- Filtres avancés : surface min/max, prix min/max, type
- Carte interactive (Leaflet + OpenStreetMap) avec marqueurs par centre commercial
- Géolocalisation automatique des centres via l'API Nominatim
- Page de profil publique pour chaque centre commercial

### 4.4 Réservation
- Sélection des dates de début et de fin
- Calcul automatique du montant total (tarif × jours + commission 25 % + TVA 20 %)
- Récapitulatif avant paiement

### 4.5 Paiement en ligne (Stripe)
- Paiement sécurisé par carte bancaire via Stripe
- Architecture Stripe Connect : 75 % reversés automatiquement au centre commercial, 25 % retenus par Références Événements
- Remboursement depuis l'interface administrateur

### 4.6 Tableau de bord locataire
- Historique des réservations avec statuts
- Détail de chaque réservation (dates, montants, statut paiement)
- Téléchargement de la facture PDF (après paiement confirmé)
- Système d'avis sur les emplacements (note globale + critères détaillés)

### 4.7 Tableau de bord centre commercial
- Liste des réservations reçues
- Validation ou refus des demandes
- Suivi des paiements entrants

### 4.8 Tableau de bord administrateur
- Liste de tous les utilisateurs (locataires + centres)
- Validation des comptes centres commerciaux
- Vue globale de toutes les réservations
- Forçage du statut d'une réservation
- Déclenchement de remboursements Stripe

### 4.9 Messagerie
- Système de conversation entre locataire et centre commercial

### 4.10 Notifications par email
- Email lors d'une nouvelle réservation (locataire + centre)
- Email de confirmation lors de la validation par le centre
- Email de refus avec motif
- Email en cas d'annulation
- Formulaire de contact envoyé vers l'adresse de Références Événements

### 4.11 Sécurité
- Protection CSRF sur tous les formulaires
- Rate limiting sur la connexion (5 tentatives / 15 min) et le formulaire contact (3 / heure)
- Contrôle d'accès par rôle (ROLE_LOCATAIRE, ROLE_CENTRE, ROLE_ADMIN)
- Popup de connexion requise pour les visiteurs non connectés

---

## 5. Améliorations possibles (futures versions)

### Fonctionnalités utilisateur
- **Agenda de disponibilités** : calendrier visuel sur la fiche emplacement pour voir les dates libres
- **Alertes email** : le locataire est notifié quand un emplacement correspondant à ses critères devient disponible
- **Favoris** : sauvegarder des emplacements pour y revenir plus tard
- **Programme fidélité** : réduction sur les réservations répétées

### Côté centres commerciaux
- **Statistiques détaillées** : taux d'occupation, chiffre d'affaires mensuel, types de locataires les plus fréquents
- **Multi-emplacements par réservation** : réserver plusieurs espaces d'un même centre en une transaction
- **Contrat numérique** : génération et signature électronique du contrat de location

### Côté plateforme
- **Moteur de recommandation** : suggérer des emplacements selon l'historique du locataire
- **Application mobile** (iOS / Android) : accès aux réservations et notifications push
- **Intégration Google Maps** : alternative à Nominatim pour un géocodage plus fiable
- **Tableau de bord analytique** : chiffre d'affaires, taux de conversion, emplacements les plus vus

### Technique
- **Tests automatisés** (PHPUnit + Playwright) pour garantir la stabilité à chaque mise à jour
- **Infrastructure cloud** (AWS ou OVH) avec certificat SSL, sauvegardes automatiques, CDN pour les images
- **RGPD** : export et suppression des données personnelles à la demande

---

## 6. Stack technique

| Composant | Technologie |
|---|---|
| Backend | PHP 8.2 / Symfony 7 |
| Base de données | MySQL / MariaDB (Doctrine ORM) |
| Frontend | Twig, HTML/CSS/JS vanilla |
| Carte interactive | Leaflet.js + OpenStreetMap |
| Paiement | Stripe (Standard + Connect) |
| Emails | Symfony Mailer |
| PDF | dompdf |
| Hébergement cible | Serveur dédié ou VPS (OVH, Ionos...) |

---

*Document établi le 17 avril 2026 — Version 1.0*
