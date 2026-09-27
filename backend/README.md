# Backend — Formulaire de contact

API PHP native (POO, sans dépendance externe) qui reçoit les soumissions du
formulaire de contact, les enregistre dans une base SQLite et envoie un
e-mail de notification.

## Structure

```
backend/
  config/config.php   Configuration (base de données, SMTP, CORS)
  src/                 Classes de l'application (namespace App\)
  public/contact.php   Point d'entrée HTTP (unique endpoint POST)
  data/                Fichier SQLite généré automatiquement
```

## Lancer en développement

```bash
npm run dev:all        # depuis la racine : front Vite + API PHP
# ou l'API seule :
npm run dev:api        # = php -S 127.0.0.1:8001 -t backend/public
```

Le frontend Vite pointe vers l'API via `VITE_API_URL` (fichier `.env` à la
racine, réglé sur `http://127.0.0.1:8001`). En cas de conflit de port, changer
la valeur **dans `package.json` (`dev:api`) et dans `.env`** de façon
cohérente.

## Configurer l'envoi d'e-mails

Le plus simple : créer `backend/.env` (chargé automatiquement par
`bootstrap.php`) à partir de `backend/.env.example`. À défaut, les mêmes clés
peuvent être passées en variables d'environnement (`export …`) avant de lancer
PHP.

```bash
# backend/.env  (config OVH utilisée en dev comme en prod)
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

Par défaut (sans `SMTP_HOST`), `NativeMailer` utilise la fonction `mail()` de
PHP. Dès que `SMTP_HOST` est renseigné, `backend/public/contact.php` utilise
automatiquement `SmtpMailer` (client SMTP par sockets, STARTTLS pour le port
587 / SSL pour le port 465, avec authentification `AUTH LOGIN`).

Détails, variantes (Gmail, `mail()` natif) et mise en production :
`../DEPLOIEMENT_OVH.md`.

## Base de données

Le fichier SQLite est créé automatiquement au premier appel dans
`backend/data/contacts.sqlite`, avec une table `contacts` (nom, email, objet,
message, ip, user-agent, statut d'envoi de l'e-mail, date). Ce fichier n'est
pas versionné (voir `.gitignore`).

## Sécurité

- Validation stricte des champs côté serveur (longueur, format e-mail).
- Champ piège anti-spam (`company`) : une soumission remplie est ignorée
  silencieusement (réponse « succès » sans enregistrement ni envoi).
- CORS : origines listées dans `CORS_ALLOWED_ORIGINS` (vide par défaut) ;
  `CORS_ALLOW_ANY_LOCALHOST` autorise en plus tout `localhost`/`127.0.0.1`
  (défaut `1` en dev, à mettre à `0` en production).
- Limite anti-abus : 5 soumissions par tranche de 15 min et par IP.
