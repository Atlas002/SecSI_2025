
# Rapport détaillé sur le patch du site web

## Correctifs Implémentés
### 1. Correction des Injections SQL

- Problème initial : La page prof.php utilisait une connexion mysqli vulnérable aux injections SQL via l'input "salle"

- Solution appliquée : Implémentation de requêtes préparées avec PDO sur l'ensemble du site, y compris la page prof.php

- Impact : Protection contre l'extraction non autorisée de données sensibles (comme les informations des utilisateurs) via des requêtes SQL malveillantes

### 2. Correction des Vulnérabilités XSS

- Problème initial : Possibilité d'injection XSS dans la page élève, particulièrement dans le système de téléchargement de fichiers

- Solution appliquée :
Échappement et validation de toutes les entrées utilisateur
Implémentation d'en-têtes de sécurité Content-Security-Policy
Validation stricte du contenu des champs avant affichage

- Impact : Prévention de l'exécution de scripts malveillants dans le contexte de la session d'un utilisateur

### 3. Protection contre les attaques par force brute

- Problème initial
La page de connexion login.php ne disposait d'aucun mécanisme limitant le nombre de tentatives de connexion échouées, ce qui la rendait vulnérable aux attaques par force brute. Un attaquant pouvait effectuer un nombre illimité de tentatives pour deviner les identifiants des utilisateurs, particulièrement problématique avec les mots de passe stockés en MD5.
- Solution appliquée
Nous avons implémenté un système complet de limitation des tentatives de connexion (rate limiting) basé sur l'adresse IP, comprenant :

Suivi des tentatives de connexion :

Création d'une table login_attempts pour enregistrer chaque tentative
Stockage de l'adresse IP, du nom d'utilisateur, du statut de la tentative et de l'horodatage


Verrouillage temporaire des comptes :

Blocage automatique après 5 tentatives échouées en moins de 15 minutes
Période de verrouillage de 15 minutes avant de pouvoir tenter une nouvelle connexion
Nettoyage automatique des anciennes tentatives pour éviter un blocage permanent


Délai de réponse variable :

Introduction d'un délai aléatoire (0,1 à 0,3 seconde) pour ralentir les attaques automatisées
Réduction de l'efficacité des scripts de force brute sans impact notable pour les utilisateurs légitimes


Protection CSRF :

Génération d'un jeton unique pour chaque session
Validation du jeton à chaque tentative de connexion pour prévenir les attaques par falsification de requête

### 4. Déploiement de Snort IDS

- Problème initial : Absence d'un système de détection d'intrusion
Solution appliquée : Installation et configuration de Snort sur le serveur
Impact :

- Détection en temps réel des tentatives d'attaque
Alerte et journalisation des activités suspectes
Surveillance du trafic réseau pour identifier les modèles d'attaque connus