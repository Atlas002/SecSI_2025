
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


### 4. Réflexion sur l’intégration de Snort IDS

- **Problème initial** : Le site ne disposait d’aucun système de détection d’intrusion (IDS), ce qui laissait l’infrastructure vulnérable à des attaques réseau non détectées, comme des scans de ports, des tentatives d’exploitation de failles connues ou des comportements anormaux.

- **Tentatives d’implémentation** :
  - Nous avons d’abord envisagé de déployer **Snort** sur une **instance AWS dédiée** jouant le rôle de **machine d’analyse**. L’objectif était de rediriger le trafic réseau du serveur via le **Traffic Mirroring** d’AWS pour permettre une analyse complète par Snort.
  - Toutefois, cette approche nécessitait l’utilisation d’instances AWS **compatibles avec la technologie Nitro**, ce qui n’est pas le cas des instances gratuites ou basiques. L’activation du Traffic Mirroring aurait donc impliqué des **coûts supplémentaires**, ce que nous avons jugé inadapté dans le cadre d’un projet académique.
  - En alternative, nous avons tenté d’**installer Snort directement sur la VM hébergeant le site**. Cette solution aurait permis une surveillance locale du trafic sans mirroring. Cependant, nous avons été confrontés à des **limitations d’espace disque** sur cette instance. Malgré plusieurs optimisations, il aurait été nécessaire d’**augmenter le volume EBS**, ce qui là encore impliquait un surcoût.

- **Décision prise** :
  - Compte tenu du **contexte pédagogique** du projet et de l’objectif de démonstration, nous avons choisi de **ne pas aller jusqu’au déploiement final de Snort** pour des raisons de **coût** et de **ressources techniques limitées**.
  - Néanmoins, cette réflexion nous a permis de **documenter précisément les étapes nécessaires à une future implémentation réelle** de Snort dans un environnement cloud (type d’instance requis, configuration du mirroring, gestion du stockage, etc.).
  - Cette démarche a enrichi notre vision globale de la sécurité réseau et nous a permis d'anticiper les contraintes techniques et économiques liées à la mise en place d’un IDS dans un environnement de production.
