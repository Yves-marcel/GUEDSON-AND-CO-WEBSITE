# Déploiement sur hébergement mutualisé OVH

Ce guide couvre la mise en ligne du site (frontend React + backend PHP/SQLite du
formulaire de contact) sur un hébergement mutualisé OVH, avec une attention
particulière portée à l'envoi effectif des e-mails une fois en production.

## Ce qui est déjà prêt dans le dépôt

Ces éléments sont configurés et validés ; il n'y a **rien à modifier dans le
code** pour déployer :

- **`.env.production`** (racine) : `VITE_API_URL=https://guedsonandcointertrade.com/backend/public`.
  Utilisé automatiquement par `npm run build`.
- **`dist/`** : déjà généré avec cette URL de production (à regénérer seulement
  si `.env.production` change — voir étape 1).
- **`backend/.env.example`** : modèle complet pour la production (SMTP OVH
  `sales@guedsonandcointertrade.com` + CORS verrouillé sur le domaine).
- **Envoi e-mail** : testé de bout en bout contre le vrai relais SMTP OVH
  (`smtp.mail.ovh.net:465`, SSL) — authentification et remise confirmées.
- **CORS** : `CORS_ALLOW_ANY_LOCALHOST=0` désactive réellement l'ouverture
  localhost en production (corrigé dans `backend/config/config.php`).

À faire uniquement **sur le serveur** : déposer les fichiers, créer
`backend/.env` avec le vrai mot de passe, régler les permissions de `data/`.
Voir les étapes ci-dessous.

> ⚠️ Le domaine `guedsonandcointertrade.com` sert actuellement une installation
> WordPress par défaut. Il faut la retirer (ou rediriger) avant de déposer le
> site React à la racine de `www/`.

## Vue d'ensemble

- Le **frontend** (`npm run build`) produit des fichiers statiques dans `dist/` :
  HTML/CSS/JS uniquement, à déposer tels quels dans le dossier `www/` d'OVH.
- Le **backend** (`backend/`) est une API PHP native appelée en JavaScript
  (`fetch`) par le formulaire de contact. Il doit donc être accessible en HTTP
  sur le même hébergement (pas besoin de serveur séparé).
- Aucune base de données MySQL n'est nécessaire : les messages sont stockés
  dans un fichier SQLite (`backend/data/contacts.sqlite`), créé automatiquement.

## Prérequis côté OVH

- Un hébergement mutualisé avec **PHP 8.1+** (Multisite/Perso/Pro/Performance).
  Vérifier/choisir la version PHP dans l'espace client OVH
  (Hébergements → votre hébergement → Multisite → onglet PHP).
- Extension `pdo_sqlite` activée (activée par défaut sur OVH).
- Un accès FTP/SFTP (identifiants dans l'espace client OVH) ou SSH si disponible
  sur votre offre.
- Le nom de domaine du site déjà pointé sur l'hébergement.

## Étape 1 — Builder le frontend avec la bonne URL d'API

Vite injecte les variables `VITE_*` **au moment du build**, pas à l'exécution :
il faut donc indiquer l'URL de production de l'API *avant* de builder.

Le fichier `.env.production` est déjà présent à la racine du projet (ne pas
modifier `.env`, qui reste pour le développement local) :

```bash
# .env.production
VITE_API_URL=https://guedsonandcointertrade.com/backend/public
```

Le dossier `dist/` fourni est **déjà buildé avec cette URL** : si `backend/` est
déposé sous `www/` (option simple de l'étape 2), il n'y a rien à refaire ici.

Ne relancer un build que si l'URL doit changer (autre emplacement de l'API,
autre domaine) :

```bash
# 1. modifier .env.production   2. puis :
npm run build
```

> - `.env` (sans suffixe) sert au **dev local** uniquement — il pointe vers
>   `http://127.0.0.1:8001` (le port de `npm run dev:api`). Vite l'ignore lors
>   d'un `npm run build`.
> - `.env.production` est le seul fichier utilisé par `npm run build`. Son URL
>   doit correspondre **exactement** à l'emplacement de `contact.php` en ligne
>   (voir étape 2).

## Étape 2 — Organiser les fichiers à envoyer sur OVH

### Option simple (recommandée, FTP uniquement)

Tout dans `www/` :

```
www/
├── index.html          ← contenu de dist/
├── assets/              ← contenu de dist/
├── ...                  ← reste de dist/
└── backend/             ← dossier backend/ entier, tel quel
    ├── .htaccess         ← bloque .env, .htaccess, *.sqlite (sous-dossiers inclus)
    ├── .env              ← à créer/déposer sur le serveur (voir étape 3)
    ├── bootstrap.php
    ├── public/
    │   ├── contact.php
    │   └── .htaccess     ← en-tête no-store (pas de blocage)
    ├── src/
    ├── config/
    └── data/
        └── .htaccess     ← "Require all denied" (protège contacts.sqlite)
```

URL de l'API : `https://guedsonandcointertrade.com/backend/public/contact.php`
→ donc `VITE_API_URL=https://guedsonandcointertrade.com/backend/public`
(valeur déjà en place dans `.env.production`).

Le `backend/.htaccess` **ne fait pas** de `Require all denied` global (sur
certains hébergements il n'est pas correctement ré-ouvert pour `public/`, ce qui
casse l'API en `403`). Il bloque nommément les fichiers sensibles : `.env`,
tout fichier commençant par un point, et les bases `*.sqlite`. Les fichiers
`.php` de `src/`, `config/` et `bootstrap.php` restent techniquement joignables
mais, appelés directement, ils ne produisent **aucune sortie** (ils ne font que
`return`) : aucune donnée ni identifiant n'est exposé. `data/.htaccess`
protège en plus le fichier SQLite.

> ⚠️ Bien envoyer les fichiers **`.htaccess`** (fichiers cachés) : dans
> FileZilla, activer *Serveur → Forcer l'affichage des fichiers cachés*.

### Option avancée (si accès SSH/gestionnaire de fichiers hors `www/`)

Placer `backend/` **en dehors** de `www/` (à la racine du compte
d'hébergement, à côté de `www/`) — il devient alors totalement inaccessible
depuis le web, sans dépendre d'un `.htaccess` :

```
/                         ← racine du compte OVH
├── www/                  ← document root public
│   ├── index.html
│   ├── assets/
│   └── api/
│       └── contact.php   ← petit fichier pont, voir ci-dessous
└── backend/               ← hors du web, inaccessible en HTTP
    ├── public/contact.php
    ├── src/ config/ data/ .env
```

Contenu de `www/api/contact.php` :

```php
<?php
require __DIR__ . '/../../backend/public/contact.php';
```

URL de l'API : `https://guedsonandcointertrade.com/api/contact.php`
→ donc `VITE_API_URL=https://guedsonandcointertrade.com/api`
(⚠️ dans ce cas, corriger `.env.production` **avant** `npm run build`).

## Étape 2 bis — Ce qu'on envoie par FTP (FileZilla), option simple

FileZilla : panneau **local** (à gauche) = dossier du projet ; panneau
**distant** (à droite) = `www/` du serveur OVH. Activer d'abord
*Serveur → Forcer l'affichage des fichiers cachés* (pour voir les `.htaccess`
et `.env`).

### 1. Le contenu de `dist/`  →  `www/`

Glisser **le contenu** de `dist/` (pas le dossier `dist/` lui-même) à la racine
de `www/` :

```
LOCAL  dist/index.html            →  DISTANT  www/index.html
LOCAL  dist/assets/  (23 fichiers) →  DISTANT  www/assets/
```

### 2. Le dossier `backend/`  →  `www/backend/`

Glisser le dossier `backend/` entier dans `www/` :

```
LOCAL  backend/                   →  DISTANT  www/backend/
```

Fichiers effectivement transférés (⚠️ `.htaccess` inclus) :

```
www/backend/
├── .htaccess                 (Require all denied)
├── bootstrap.php
├── public/
│   ├── contact.php
│   └── .htaccess             (Require all granted)
├── config/config.php
├── src/…                     (11 fichiers .php)
└── data/
    ├── .gitkeep
    └── .htaccess             (Require all denied)
```

### À NE PAS envoyer

`node_modules/`, `src/` (sources React à la racine), `dist/` comme dossier
parent, `.git/`, `package.json`, `vite.config.js`, `.env`, `.env.production`,
`backend/README.md`, `backend/.gitignore` — inutiles en ligne.

### `backend/.env` : cas particulier

Le fichier `backend/.env` **local** contient déjà les bons identifiants OVH,
sauf deux points à ajuster pour la prod :

- `SMTP_PASSWORD` : mettre le vrai mot de passe de la boîte (s'il n'y est pas
  déjà en clair) ;
- `CORS_ALLOW_ANY_LOCALHOST` : passer de `1` à `0`.

Deux façons de faire :
- éditer ces deux lignes en local **avant** de glisser le fichier dans
  `www/backend/`, ou
- ne pas l'envoyer et le recréer sur le serveur (voir étape 3).

## Étape 3 — Créer `backend/.env` sur le serveur

**Ne jamais** committer ni uploader de mot de passe dans le dépôt Git
(`backend/.env` est dans `.gitignore`). Une fois les fichiers déposés sur OVH,
créez `backend/.env` directement sur le serveur (via FTP en éditant un nouveau
fichier, ou en SSH), en copiant `backend/.env.example` et en remplaçant le seul
champ `SMTP_PASSWORD` par le mot de passe réel de la boîte
`sales@guedsonandcointertrade.com` :

```bash
# backend/.env  (contenu final attendu en production)
SMTP_HOST=smtp.mail.ovh.net
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
SMTP_USERNAME=sales@guedsonandcointertrade.com
SMTP_PASSWORD=<mot de passe réel de la boîte>
MAIL_FROM_EMAIL=sales@guedsonandcointertrade.com
MAIL_FROM_NAME=Site Guedson & Co
MAIL_TO_EMAIL=sales@guedsonandcointertrade.com
MAIL_TO_NAME=Guedson & Co

CORS_ALLOWED_ORIGINS=https://guedsonandcointertrade.com,https://www.guedsonandcointertrade.com
CORS_ALLOW_ANY_LOCALHOST=0
```

Détails et variantes d'envoi (Gmail, `mail()` natif…) à l'étape 5, réglage
CORS à l'étape 6.

## Étape 4 — Permissions du dossier `data/`

PHP doit pouvoir créer et écrire le fichier SQLite :

```bash
chmod 775 backend/data
```

(775 suffit sur OVH ; évitez 777 sauf si strictement nécessaire). Si le
gestionnaire de fichiers OVH ne permet pas `chmod` en ligne, faites un clic
droit sur le dossier → Permissions/Droits.

## Étape 5 — Configurer l'envoi d'e-mails (le point critique)

Sur un mutualisé, **la fonction `mail()` de PHP fonctionne dès l'installation**,
mais elle est souvent filtrée en spam ou carrément rejetée si l'adresse
d'expéditeur (`From`) n'appartient pas à un domaine réellement hébergé sur le
compte OVH (mesure anti-usurpation). Trois options, de la plus simple à la
plus fiable :

### Option A — `mail()` native (déjà active par défaut, aucune config)

Ne fonctionne de façon fiable **que si** `MAIL_FROM_EMAIL` utilise le nom de
domaine réel de l'hébergement OVH (celui qui pointe vers ce compte). Dans
`backend/.env` :

```bash
MAIL_FROM_EMAIL=no-reply@guedsonandcointertrade.com
MAIL_FROM_NAME=Site Guedson & Co
MAIL_TO_EMAIL=sales@guedsonandcointertrade.com
MAIL_TO_NAME=Guedson & Co
```

`no-reply@guedsonandcointertrade.com` n'a pas besoin d'être une vraie boîte
mail créée : c'est uniquement l'adresse déclarée dans l'en-tête `From`. En
revanche le **domaine** (`guedsonandcointertrade.com`) doit être celui de
l'hébergement, sinon OVH peut rejeter l'envoi ou le classer en spam.

Vérification côté DNS (optionnel mais recommandé) : dans l'espace client OVH
→ Domaine → Zone DNS, un enregistrement `TXT` de type SPF doit exister
(OVH le configure automatiquement dès qu'une zone DNS OVH est active), du
type :

```
v=spf1 include:mx.ovh.com ~all
```

Si absent, il n'y a rien à ajouter manuellement pour `mail()` native tant que
le domaine est bien celui de l'hébergement.

### Option B — Relais SMTP authentifié OVH (recommandé, boîte mail OVH `sales@guedsonandcointertrade.com`)

La boîte `sales@guedsonandcointertrade.com` existe sur l'offre **Emails OVH**
du domaine `guedsonandcointertrade.com`. On utilise le relais SMTP officiel
plutôt que `mail()` : moins de risque de spam, envoi confirmé par accusé SMTP.

```bash
# backend/.env
SMTP_HOST=smtp.mail.ovh.net
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
SMTP_USERNAME=sales@guedsonandcointertrade.com
SMTP_PASSWORD=mot-de-passe-de-la-boite-mail
MAIL_FROM_EMAIL=sales@guedsonandcointertrade.com
MAIL_FROM_NAME=Site Guedson & Co
MAIL_TO_EMAIL=sales@guedsonandcointertrade.com
MAIL_TO_NAME=Guedson & Co
```

Dès que `SMTP_HOST` est renseigné, `backend/public/contact.php` bascule
automatiquement sur `SmtpMailer` (client SMTP intégré, sans dépendance) à la
place de `NativeMailer`. Rien d'autre à changer dans le code.

> ✅ Cette configuration a été testée de bout en bout : connexion `ssl://` sur
> le port 465, `AUTH LOGIN` accepté, message accepté par le serveur (`250`),
> e-mail reçu dans la boîte. Le premier essai en production sert donc à
> confirmer, pas à découvrir.

> Paramètres OVH pour cette boîte (espace client OVH → Emails → Configurer mon
> logiciel de messagerie) :
> - SMTP (envoi) : `smtp.mail.ovh.net`, port `465`, SSL
> - IMAP (réception) : `imap.mail.ovh.net`, port `993`, SSL
>
> Le port `587` (`SMTP_ENCRYPTION=tls`, STARTTLS) fonctionne aussi si `465` est
> bloqué en sortie.
>
> `MAIL_FROM_EMAIL` doit rester `sales@guedsonandcointertrade.com` (l'adresse
> authentifiée) : OVH refuse un `From` d'un autre compte.

### Option C — Garder Gmail en SMTP (si vous préférez continuer avec le compte Gmail existant)

Fonctionne aussi bien depuis OVH que depuis n'importe quel hébergeur, à
condition que le port sortant 587 ne soit pas bloqué (il ne l'est pas sur les
mutualisés OVH) :

```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=guedsonandcointertrade@gmail.com
SMTP_PASSWORD=mot-de-passe-application-16-caracteres
MAIL_FROM_EMAIL=guedsonandcointertrade@gmail.com
MAIL_FROM_NAME=Site Guedson & Co
MAIL_TO_EMAIL=sales@guedsonandcointertrade.com
MAIL_TO_NAME=Guedson & Co
```

Le mot de passe d'application se génère sur le compte Google concerné
(validation en 2 étapes requise) via
`myaccount.google.com/apppasswords`.

**Recommandation** : Option B (la boîte `sales@guedsonandcointertrade.com`
existe déjà pour le domaine). Option A comme repli si le SMTP sortant est
bloqué, en vérifiant que `MAIL_FROM_EMAIL` utilise `guedsonandcointertrade.com`.
Option C uniquement si l'on veut repasser par l'ancien compte Gmail.

## Étape 6 — CORS en production

Dans `backend/.env`, restreindre les origines autorisées au vrai domaine :

```bash
CORS_ALLOWED_ORIGINS=https://guedsonandcointertrade.com,https://www.guedsonandcointertrade.com
CORS_ALLOW_ANY_LOCALHOST=0
```

- Mettre **toutes** les variantes réellement servies (avec et sans `www`, et
  `http://` si le site n'est pas encore en HTTPS forcé). Une origine absente =
  réponse sans en-tête `Access-Control-Allow-Origin` = requête bloquée par le
  navigateur (le formulaire affiche « Impossible de contacter le serveur »).
- `CORS_ALLOW_ANY_LOCALHOST=0` coupe réellement l'autorisation automatique de
  `localhost`/`127.0.0.1` (à laisser à `1` seulement en dev). En son absence, la
  valeur par défaut reste `1`.
- Le CORS est une protection **navigateur** : il empêche un autre site web de
  lire la réponse, mais **pas** un `POST` scripté (curl, bot) d'atteindre
  l'API et de déclencher un e-mail. Les garde-fous contre ce cas sont déjà en
  place dans le code : champ piège anti-robot + limite de 5 envois / 15 min par
  IP.

## Étape 7 — Vérifications après mise en ligne

L'envoi SMTP ayant déjà été validé (étape 5, option B), ces vérifications
servent surtout à confirmer le câblage frontend ↔ API en ligne (URL, CORS,
permissions).

1. Ouvrir le site en production et soumettre le formulaire de contact avec de
   vraies données.
2. Vérifier la réponse : un message de succès doit s'afficher (toast vert).
3. Vérifier la réception de l'e-mail sur `sales@guedsonandcointertrade.com`
   **y compris le dossier Spam** lors du premier test.
4. Vérifier que la ligne est bien enregistrée dans
   `backend/data/contacts.sqlite` (téléchargeable via FTP pour inspection avec
   un client SQLite, ou via un script PHP temporaire).
5. Consulter les logs d'erreurs PHP si besoin : espace client OVH →
   Hébergement → Statistiques → Logs, ou fichier `error_log` généré par PHP
   dans le dossier concerné selon la configuration.

## Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| Le formulaire répond « Impossible de contacter le serveur » | `VITE_API_URL` pointe vers `localhost` (build fait sans `.env.production`) | Rebuilder avec `.env.production` correctement renseigné |
| « Impossible de contacter le serveur », et l'onglet Réseau montre un `404` sur `contact.php` | `VITE_API_URL` ne correspond pas au chemin réel de `backend/public/contact.php` en ligne | Aligner `.env.production` sur l'emplacement choisi (étape 2) puis rebuilder |
| Réponse HTTP bloquée par CORS (visible dans la console navigateur) | `CORS_ALLOWED_ORIGINS` ne contient pas le domaine exact (avec/sans `www`, `http`/`https`) | Ajouter toutes les variantes utilisées du domaine |
| `emailSent: false` en boucle | `MAIL_FROM_EMAIL` sur un domaine non hébergé chez OVH (option A), ou identifiants SMTP invalides (option B/C) | Vérifier le domaine du From, ou retester les identifiants SMTP en isolant l'erreur via les logs (`error_log`) |
| E-mail reçu mais en Spam | Domaine sans SPF/DKIM alignés, ou envoi via `mail()` avec un From générique | Basculer sur l'option B (relais SMTP OVH authentifié), qui aligne correctement SPF/DKIM |
| Erreur 500 générique | Voir les logs PHP (étape 7.5) — l'API ne renvoie jamais le détail de l'erreur au client par sécurité | Consulter `error_log` |
| Permission refusée sur `contacts.sqlite` | Dossier `backend/data/` non inscriptible | `chmod 775 backend/data` |

## Développement local

```bash
npm install
npm run dev:all      # front (Vite) + API PHP en parallèle
```

- Front : `http://localhost:5173` — API : `http://127.0.0.1:8001`
  (`.env` → `VITE_API_URL=http://127.0.0.1:8001`, script `dev:api` dans
  `package.json`).
- `backend/.env` local : mêmes identifiants SMTP OVH qu'en production, mais
  `CORS_ALLOW_ANY_LOCALHOST=1` pour autoriser le serveur de dev.

### Gotcha : « Impossible de contacter le serveur » en local

Vu pendant la mise au point : un **autre** serveur PHP (projet Laravel sous
XAMPP) occupait déjà `127.0.0.1:8000`. `php -S localhost:8000` ne s'attachant
qu'à l'IPv6 `::1`, le navigateur (qui résout `localhost` en IPv4) tombait sur
le mauvais serveur → `fetch` en échec.

Parades :
- L'API de dev est fixée sur le port **8001** et l'adresse **`127.0.0.1`**
  explicite (pas `localhost`), des deux côtés.
- En cas de nouveau conflit : `netstat -ano | findstr :8001`, choisir un port
  libre, et le reporter **à la fois** dans `package.json` (`dev:api`) et dans
  `.env` (`VITE_API_URL`), puis redémarrer `npm run dev:all`.
