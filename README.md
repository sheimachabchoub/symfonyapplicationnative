<div align="center">

# 📚 Bibliothèque — Bibliothécaire IA

**Application de gestion de bibliothèque avec agent conversationnel, recherche documentaire et relances WhatsApp**

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-8.1-000000?logo=symfony&logoColor=white)
![Doctrine](https://img.shields.io/badge/Doctrine_ORM-3-FC6A31)
![EasyAdmin](https://img.shields.io/badge/EasyAdmin-5-4F46E5)
![PHPUnit](https://img.shields.io/badge/PHPUnit-13-3C9CD7)
![Statut](https://img.shields.io/badge/statut-en_développement-orange)

[Fonctionnalités](#-fonctionnalités) ·
[Architecture](#-architecture) ·
[Installation](#-installation) ·
[Tests](#-tests) ·
[Feuille de route](#-feuille-de-route) ·
[Cahier des charges](docs/CAHIER_DES_CHARGES.md)

</div>

---

## 🎯 Présentation

Ce projet gère une bibliothèque : catalogue, adhérents, emprunts et administration. Il ajoute un **agent Bibliothécaire IA**, accessible depuis le site puis depuis **WhatsApp**. L'agent répond aux questions, recherche des livres, accompagne les adhérents et réagit automatiquement à certains événements, comme un retard.

La démonstration vise quatre capacités :

| | Capacité | Exemple |
|---|---|---|
| 🧰 | **Choisir un outil métier** adapté à la demande | « Trouve-moi un roman de Victor Hugo disponible » |
| 📖 | **Répondre avec le RAG**, en citant sa source | « Combien de jours puis-je garder un livre ? » |
| ⏰ | **Réagir automatiquement** à un événement | Relance d'un emprunt en retard à J+1 |
| 💬 | **Communiquer via Meta WhatsApp Business** | Envoi, suivi de statut, traitement de la réponse |

Le détail des exigences, règles métier, diagrammes UML et critères de recette se trouve dans le **[cahier des charges](docs/CAHIER_DES_CHARGES.md)**.

---

## ✨ Fonctionnalités

Légende : ✅ disponible · 🚧 en cours ou prévu · ⏳ reporté

### Catalogue et comptes
- ✅ Recherche de livres par titre, auteur ou catégorie
- ✅ Gestion des livres, auteurs, catégories et éditeurs (pages Twig et back-office EasyAdmin)
- ✅ Authentification par formulaire, rôles **adhérent** et **bibliothécaire**
- 🚧 Profil : téléphone, canal préféré, consentement WhatsApp
- ⏳ Connexion via Facebook Login

### Emprunts
- ✅ Prêt et retour, avec contrôle de disponibilité et plafond de 3 emprunts par adhérent
- ✅ Espace adhérent « Mes emprunts »
- ✅ Tableau de bord des retards
- 🚧 Prolongation, réservation et liste d'attente
- ⏳ Pénalités de retard

### Agent Bibliothécaire IA
- 🚧 Discussion web avec outils métier (recherche, disponibilité, mes emprunts, règlement, escalade)
- 🚧 Réponses réglementaires sourcées (RAG)
- 🚧 Journal des décisions et file de validation pour le bibliothécaire
- ⏳ Mascotte animée

### Automatisation et communication
- 🚧 Rappel J‑2, relances J+1 et J+7 (Scheduler + Messenger)
- 🚧 Transport simulé et date simulée pour la démonstration
- 🚧 WhatsApp Business : modèles, webhook, statuts, reprise humaine
- ⏳ Canal Messenger (Facebook)

---

## 🏗 Architecture

```mermaid
flowchart TD
    WEB["Interface Twig / Turbo"] --> AGENT["Agent Bibliothécaire IA"]
    WA["WhatsApp Business"] --> WH["Webhook Meta"]
    WH --> Q["Messenger"]
    S["Scheduler"] --> Q
    Q --> AGENT
    Q --> REL["Service de relances"]
    AGENT --> T["Outils métier"]
    AGENT --> R["RAG"]
    T --> DB[("Doctrine")]
    REL --> DB
    R --> DOC[("Documents indexés")]
    AGENT --> P["Politique des actions"]
    REL --> P
    P --> OUT["WhatsApp / e-mail / simulé"]
    OUT --> LOG[("Journal des décisions")]
```

**Principes clés**

- **La base de données fait foi** pour les faits ; le RAG n'explique que les règles.
- **L'agent consulte et propose ; le bibliothécaire décide** des prêts, retours et modifications du catalogue.
- **L'identité vient du serveur** : aucun outil n'accepte un identifiant d'adhérent fourni par le modèle.
- **Chaque décision est tracée** : déclencheur, sources, action et résultat.

Diagrammes complets (contexte, couches, classes, séquences, états, déploiement) : [cahier des charges, sections 8 à 11](docs/CAHIER_DES_CHARGES.md#8-architecture).

### Stack technique

| Domaine | Technologie | État |
|---|---|---|
| Langage | PHP 8.4 | ✅ |
| Framework | Symfony 8.1 | ✅ |
| Persistance | Doctrine ORM 3, Doctrine Migrations | ✅ |
| Vues | Twig, AssetMapper, Stimulus, UX Turbo, Twig Components | ✅ |
| Administration | EasyAdmin 5 | ✅ |
| Asynchrone | Messenger (transport Doctrine) | ✅ |
| Tâches périodiques | Scheduler | 🚧 |
| Agent et RAG | Symfony AI (Agent, Toolbox, Store) | 🚧 |
| Webhooks | Symfony Webhook / RemoteEvent | 🚧 |
| Fiabilité | Lock, RateLimiter, Clock | 🚧 / ✅ Clock |
| Tests | PHPUnit 13, BrowserKit | ✅ |

---

## 📁 Structure du projet

```text
├── assets/                 # JavaScript et CSS (AssetMapper)
├── config/
│   ├── packages/           # Configuration des bundles
│   └── services.yaml       # Paramètres métier (durée de prêt, plafond)
├── docs/
│   └── CAHIER_DES_CHARGES.md
├── migrations/             # Migrations Doctrine
├── src/
│   ├── Command/            # Commandes console (app:create-user…)
│   ├── Controller/         # Contrôleurs Twig
│   │   └── Admin/          # Back-office EasyAdmin
│   ├── Dto/                # Objets de requête (LivreSearch)
│   ├── Entity/             # Livre, Auteur, Category, Editeur, Emprunt, User
│   ├── Form/               # Types de formulaires
│   ├── Repository/         # Requêtes Doctrine
│   └── Service/            # Logique métier (EmpruntService)
├── templates/              # Vues Twig
└── tests/
    ├── Controller/         # Tests fonctionnels (WebTestCase)
    └── Service/            # Tests de service (KernelTestCase)
```

---

## 🚀 Installation

### Prérequis

- PHP **8.4** ou plus, avec les extensions `ctype`, `iconv`, `intl` et `pdo_mysql`
- [Composer](https://getcomposer.org/) 2
- MariaDB 10.11 ou plus (ou MySQL 8)
- [Symfony CLI](https://symfony.com/download) (recommandé)

### Étapes

**1. Récupérer le projet et ses dépendances**

```bash
git clone <url-du-depot> bibliotheque
cd bibliotheque
composer install
php bin/console importmap:install
```

**2. Configurer l'environnement local**

Créez un fichier `.env.local` (non versionné) et adaptez-le :

```dotenv
APP_SECRET=une_chaine_aleatoire
DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/bibliotheque?serverVersion=10.11.16-MariaDB&charset=utf8mb4"
```

**3. Créer la base de données**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

**4. Créer les comptes**

```bash
# Bibliothécaire (ROLE_ADMIN)
php bin/console app:create-user biblio@exemple.tn MotDePasse123 Ben-Salah Amira --admin

# Adhérent (ROLE_USER)
php bin/console app:create-user adherent@exemple.tn MotDePasse123 Trabelsi Youssef
```

**5. Lancer l'application**

```bash
symfony serve -d
```

L'application est disponible sur <https://127.0.0.1:8000>. Sans Symfony CLI : `php -S 127.0.0.1:8000 -t public`.

**6. Lancer le worker** *(à partir du lot 3 : relances et tâches asynchrones)*

```bash
php bin/console messenger:consume async -vv
```

---

## 🧭 Pages principales

| Page | URL | Accès |
|---|---|---|
| Accueil | `/` | Public |
| Catalogue | `/livre` | Public |
| Connexion | `/connexion` | Public |
| Mes emprunts | `/mes-emprunts` | Adhérent |
| Emprunts | `/emprunt` | Bibliothécaire |
| Adhérents | `/adherent` | Bibliothécaire |
| Tableau de bord | `/tableau-de-bord` | Bibliothécaire |
| Back-office | `/admin` | Bibliothécaire |

Liste complète des routes :

```bash
php bin/console debug:router
```

---

## ⚙️ Configuration métier

Les règles de prêt sont des paramètres de [`config/services.yaml`](config/services.yaml) :

| Paramètre | Rôle | Valeur |
|---|---|---|
| `app.emprunt.max_par_adherent` | Emprunts simultanés autorisés | `3` |
| `app.emprunt.duree_jours` | Durée d'un prêt, en jours | `14` → **15** prévu (règle RG-01) |

Les secrets des intégrations à venir (jeton Meta, clé du modèle de langage) iront dans `.env.local` ou dans le coffre Symfony :

```bash
php bin/console secrets:set META_ACCESS_TOKEN
```

---

## 🧪 Tests

Préparez une base de test une seule fois :

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

Lancez ensuite la suite :

```bash
php bin/phpunit
```

| Suite | Fichier | Couvre |
|---|---|---|
| Service | `tests/Service/EmpruntServiceTest.php` | Dates de prêt, disponibilité, plafond, retour, détection des retards |
| Fonctionnel | `tests/Controller/BibliothequeControllerTest.php` | Recherche, contrôle d'accès, connexion, prêt et retour, tableau de bord, back-office |

Chaque nouvelle fonctionnalité est livrée avec un test qui l'exerce comme un utilisateur réel : une requête HTTP pour un contrôleur, un appel de service pour un service.

---

## 🗺 Feuille de route

| Lot | Contenu | État |
|---|---|---|
| **0** | Socle : catalogue, comptes, emprunts, back-office, tableau de bord | ✅ Stabilisation en cours |
| **1** | Agent web : discussion, outils métier, contrôle des droits, journal | 🚧 |
| **2** | RAG : documents de connaissance, indexation, réponses sourcées | 🚧 |
| **3** | Automatisation : Scheduler, Messenger, relances, réservations, transport simulé | 🚧 |
| **4** | WhatsApp Business : liaison du numéro, modèles, webhook, statuts, reprise humaine | 🚧 |
| **5** | Reporté : Messenger, Facebook Login, pénalités, mascotte, rubrique Exercices | ⏳ |

Les décisions encore ouvertes (mode d'envoi des relances, fournisseur du modèle, stockage vectoriel…) sont listées dans la [section 15 du cahier des charges](docs/CAHIER_DES_CHARGES.md#15-décisions-à-valider).

---

## 🤝 Conventions de développement

- Conventions Symfony : attributs PHP (`#[Route]`, `#[IsGranted]`, `#[Assert\…]`, `#[AsCommand]`), autowiring, contrôleurs minces qui délèguent aux services.
- Nouvelles briques installées avec `composer require`, configurées par les recettes Flex.
- Évolutions du schéma uniquement par migrations : `make:migration` puis `doctrine:migrations:migrate`.
- Norme de code `@Symfony` (php-cs-fixer).
- Aucun secret dans le dépôt : `.env` ne contient que des valeurs par défaut.

---

## 👥 Équipe

| Membre | Rôle |
|---|---|
| *À compléter* | Développement |
| *À compléter* | Développement |

Encadrement : *à compléter* — ISET.

---

## 📄 Licence

Projet académique réalisé dans le cadre de la formation à l'ISET. Usage pédagogique.
