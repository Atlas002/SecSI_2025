
# Rapport détaillé sur le déploiement du site web

## Objectif

L'objectif est de déployer un site web (développé avec HTML, CSS, PHP et Docker) sur une machine virtuelle (VM) dans le cloud via Amazon Web Services (AWS). Ce déploiement doit permettre des mises à jour régulières du site à l'aide d'un script automatisé, en s'assurant que les modifications locales sont intégrées à la VM à chaque mise à jour.

---

## 1. Configuration du Virtual Private Cloud (VPC) sur AWS

Avant de créer la machine virtuelle (EC2), un **Virtual Private Cloud (VPC)** a été configuré. Le VPC permet d'isoler et de sécuriser les ressources du cloud, offrant un environnement réseau privé dans lequel les instances EC2 peuvent être lancées.

### 1.1 Création d'un VPC :
- Le VPC est une infrastructure réseau isolée qui permet de contrôler le trafic entrant et sortant entre les ressources dans le cloud AWS.
- **Plage d'adresses IP privées** : Une plage d'adresses IP privées a été définie pour le VPC, typiquement `10.0.0.0/16`. Cela définit l'ensemble des adresses IP que les ressources au sein du VPC peuvent utiliser.
- **Sous-réseau public et privé** : Un sous-réseau public a été créé pour héberger l'instance EC2, ce qui permet à cette instance d’être accessible depuis l'internet. Ce sous-réseau a été configuré avec une passerelle Internet (Internet Gateway), permettant aux instances EC2 de communiquer avec l'internet.

### 1.2 Table de routage :
- Le VPC est associé à une table de routage qui détermine comment le trafic doit être dirigé. La table de routage est configurée pour diriger le trafic vers la passerelle Internet (Internet Gateway) pour les ressources dans le sous-réseau public.
- **Route vers l'Internet Gateway** : Les instances dans le sous-réseau public peuvent accéder à l'internet grâce à cette route.

---

## 2. Création de la machine virtuelle (EC2) sur AWS

Après avoir configuré le VPC et les sous-réseaux, une instance EC2 a été créée et déployée dans le sous-réseau public du VPC.

### 2.1 Lancement de l'instance EC2 :
- **Image (AMI)** : L'instance EC2 a été lancée à partir de l'image Ubuntu Server 20.04, choisie pour sa compatibilité avec les technologies utilisées dans le projet (Docker, Apache, PHP).
- **Type d'instance** : Un type d'instance `t2.micro` a été sélectionné pour bénéficier du niveau gratuit d'AWS (si applicable), avec des ressources de base.
- **Clé SSH** : Une clé SSH privée a été générée et associée à l'instance afin de permettre un accès sécurisé à la machine via SSH.
- **VPC et sous-réseau** : L'instance EC2 a été déployée dans le VPC et sous-réseau publics précédemment configurés.

### 2.2 Configuration du groupe de sécurité :
- Le groupe de sécurité est un pare-feu virtuel pour contrôler le trafic réseau entrant et sortant des instances EC2.
- **Règles de groupe de sécurité** :
  - **Port 22 (SSH)** : Ouvert uniquement pour l'adresse IP de l'utilisateur, afin de permettre un accès SSH sécurisé à la VM.
  - **Port 80 (HTTP)** : Ouvert à partir de n'importe quelle adresse IP (`0.0.0.0/0`), afin de rendre le site web accessible publiquement.

### 2.3 Obtention de l'adresse publique :
- Pour se connecter à la machine virtuelle (VM) et accéder au site, il est nécessaire de connaître l'adresse IP publique de l'instance EC2.
- L'adresse IP publique peut être trouvée dans la console AWS, sous la section **"Instances EC2"**. Dans notre cas, l'adresse IP publique de l'instance est `35.181.168.33`.

### 2.4 Accès SSH :
L'accès à la machine virtuelle se fait via la clé privée SSH associée à l'instance EC2. Pour garantir la sécurité, il est impératif de restreindre les permissions sur la clé privée, afin que seule l'utilisateur qui doit se connecter à l'instance puisse l'utiliser. En effet, si les permissions de la clé privée sont trop larges, la connexion SSH sera bloquée. Il est donc important de réduire les droits d'accès à la clé privée pour les utilisateurs non autorisés.

#### Connexion SSH à l'instance EC2 :
Pour se connecter en SSH à l'instance EC2, on utilise la commande suivante dans le terminal (en remplaçant les chemins et les informations par les valeurs appropriées) :

```bash
ssh -i /chemin/vers/ta/clé/secu-si.pem ubuntu@35.181.168.33
```

Cela ouvrira une session SSH sur la machine virtuelle, où tu pourras déployer ton site ou effectuer toute autre tâche d'administration.


---

## 3. Création et configuration du Dockerfile

Une fois la VM en place, le Dockerfile a été configuré pour exécuter le site web sur un serveur Apache avec PHP.

### 3.1 Image Docker :
Une image Docker a été configurée à partir de `php:8.0-apache`, qui fournit à la fois un serveur Apache et un interpréteur PHP. Le fichier `Dockerfile` utilisé pour la construction de l'image contient les instructions suivantes :
```dockerfile
# Utiliser une image officielle de PHP avec Apache
FROM php:8.0-apache

# Installer l'extension mysqli
RUN docker-php-ext-install mysqli

# Copier les fichiers du projet dans le conteneur
COPY . /var/www/html/

# Modifier les permissions pour l'utilisateur Apache
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80 pour Apache
EXPOSE 80
```

- Cette image Docker utilise PHP et Apache pour servir le site web.
- Les fichiers du site sont copiés dans le répertoire `/var/www/html/` du conteneur Docker.
- Les permissions des fichiers sont modifiées pour s'assurer que l'utilisateur Apache (www-data) a l'autorisation de lire et d'écrire dans ces fichiers.
- Le port 80 est exposé pour que le site web soit accessible via HTTP.

---

## 4. Déploiement du site sur la machine virtuelle

### 4.1 Script de déploiement : `deploy.sh`

Un script shell, `deploy.sh`, a été créé pour automatiser le déploiement du site sur la machine virtuelle à chaque mise à jour du dépôt Git.

Le script effectue les actions suivantes :
1. **Mise à jour du dépôt Git** :
   - Le script commence par se placer dans le répertoire du projet et met à jour le dépôt Git local avec la dernière version du code source en utilisant `git pull origin main`.

2. **Construction de l'image Docker** :
   - Le script utilise la commande `docker build -t site-secu-si .` pour construire une nouvelle image Docker basée sur les modifications apportées au code source.

3. **Suppression de l'ancien conteneur** :
   - Si un conteneur Docker précédent existe, il est supprimé avec `docker rm -f site-secu-si`, garantissant que la nouvelle version du site sera déployée.

4. **Démarrage du nouveau conteneur** :
   - Le conteneur est lancé en arrière-plan avec `docker run -d -p 80:80 --name site-secu-si site-secu-si`.

### 4.2 Authentification Git et permissions SSH

Le script utilise un **Personal Access Token (PAT)** pour éviter d'avoir à saisir les identifiants Git à chaque exécution du script. De plus, pour assurer une connexion sécurisée à la machine virtuelle, les permissions de la clé SSH privée ont été ajustées avec la commande `chmod 600`.

---

## Conclusion

Le site web est désormais déployé sur une machine virtuelle AWS, accessible via le port 80. Cette VM fonctionne en réalité comme un VPS (Virtual Private Server), offrant un environnement dédié avec un système d'exploitation complet et des ressources indépendantes. Cela nous permet de gérer notre serveur comme un serveur dédié tout en profitant des avantages du cloud et permettant de laisser certaines failles pour la Red Team. Le processus de déploiement a été automatisé grâce au script `deploy.sh`, qui gère la mise à jour du code, la reconstruction de l'image Docker et le redémarrage du conteneur. Le VPC a permis de sécuriser le réseau et de configurer l'accès public à l'instance EC2. 
