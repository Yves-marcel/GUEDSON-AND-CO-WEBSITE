# GUEDSON AND CO - React JS v3

## Installation
```bash
npm install
npm run dev
```

## Build
```bash
npm run build
```

## Backend (formulaire de contact)
Le formulaire de contact envoie les données à une API PHP (POO, SQLite) qui
enregistre le message puis l'envoie par e-mail. Voir [backend/README.md](backend/README.md)
pour le lancer (`php -S localhost:8000 -t backend/public`) et le configurer.

## Déploiement sur OVH
Guide complet (build, organisation des fichiers, configuration de l'envoi
d'e-mails, CORS, dépannage) : [DEPLOIEMENT_OVH.md](DEPLOIEMENT_OVH.md).

## Version autonome
- Toutes les sections sont basées sur des composants réutilisables.
- Les grands titres utilisent une couleur noire corporate.
- Typographies : Plus Jakarta Sans + Inter.
- Une image de logistique est intégrée localement dans :
  `src/assets/images/logistics.svg`
- Aucune image externe n'est nécessaire pour l'affichage.
