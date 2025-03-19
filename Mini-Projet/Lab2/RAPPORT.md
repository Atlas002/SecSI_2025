# Mini-Projet 2 : Contrôle d'Accès et Cryptographie

PS : Le TP ayant été réalisé à différents moments et non d'une seule traite, les adresses IP des différentes machines ont changé en fonction du réseau sur lequel se trouvait la machine hôte.

## Partie 2A : Configuration des permissions sur un système Linux.


### 1 - Création d'Utilisateurs et Groupes

Création de deux nouveaux utilisateurs.

![image1.png](./screenshots/image1.png)
![image3.png](./screenshots/image3.png)

Création d'un nouveau groupe.

![image2.png](./screenshots/image2.png)

Ajout d'un des utilisateur au groupe.

![image4.png](./screenshots/image4.png)





### 2 - Création de Fichiers et Répertoires de Test

Création d'un nouveau dossier de test.

![image5.png](./screenshots/image5.png)

Création d'un nouveau fichier texte.

![image6.png](./screenshots/image6.png)

Écriture du fichier texte.

![image7.png](./screenshots/image7.png)


### 3 - Affichage des Permissions Actuelles

Affichage des permissions du fichier texte.

![image8.png](./screenshots/image8.png)

Affichage des permissions du dossier de test.

![image9.png](./screenshots/image9.png)

### 4 - Modification des Permissions avec `chmod`

Définition d'`alice` en tant que propriétaire du fichier.

![image10.png](./screenshots/image10.png)

Attribution des droits de lecture et d'écritures (`rw-`) au propriétaire (`alice`), retrait des droits aux autres groupes et utilisateurs.

![image11.png](./screenshots/image11.png)

Changement du propriétaire du dossier test au groupe `dev`.

![image12.png](./screenshots/image12.png)

Attribution de tout les droits (lecture/écriture/exécution) au propriétaire du dossier (`dev`), retrait des droits aux autres groupes et utilisateurs.

![image13.png](./screenshots/image13.png)

 
### 5 - Changement de Propriétaire et de Groupe avec `chown` et `chgr`
Les fonctionnalités d'attribution de droits/possession à un utilisateur ou un groupe précis ont été présentées dans la partie précédente.

Attribution à l'utilisateur `alice` et au groupe `dev` le status de propiétaire du fichier texte.

![image14.png](./screenshots/image14.png)


### 6 - Vérifications

Vérification des attributions de droits pour le dossier et le fichier texte

![image15.png](./screenshots/image15.png)

 
 


## Partie 2B : Mise en place d'un chiffrement basique avec OpenSSL

### 1 - Préparation des Données

Création du contenu du fichier secret.

![image16.png](./screenshots/image16.png)


### 2 - Chiffrement du Fichier avec OpenSSL

Encryption du fichier secret via OpenSSL avec l'algorithme `AES-256-CBC` et le mot de passe `"ma_super_cle_secrete"`.

![image17.png](./screenshots/image17.png)

### 3 -  Visualisation du Fichier Chiffré

Affichage du contenu du fichier encrypté, résultant en un texte chiffré.

![image18.png](./screenshots/image18.png)

### 4 - Déchiffrement du Fichier avec OpenSSL

Déchiffrement du fichier encrypté, spécifiant l'algorithme et la clé utilisés.

![image19.png](./screenshots/image19.png)


### 5 - Vérification du Fichier Déchiffré 

Affichage du contenu du fichier déchiffré, contenant le texte original.

![image20.png](./screenshots/image20.png)


## Partie 2C : Création et manipulation de certificats auto-signés

### 1 - Génération d'une Clé Privée

Utilisation d'OpenSSL pour générer une clé privée secrète d'une taille de 2048 bits.

![image21.png](./screenshots/image21.png)


### 2 - Génération d'une Requête de Signature de Certificat (CSR)

Utilisation d'OpenSSL pour créer une requête de signature de certificat (`CSR`).

![image22.png](./screenshots/image22.png)

### 3 - Génération du Certificat Auto-Signé 

Signature du CSR en utilisant la clé privée générée précédement.

![image23.png](./screenshots/image23.png)

### 4 - Visualisation du Certificat

Affichage des données contenues dans le certificat en format textuel. 

![image24.png](./screenshots/image24.png)
 

### 5 - Manipulation des Certificats

Convertion du certificat du format `PEM` au format `DER`.

![image25.png](./screenshots/image25.png)

Extraction de la clé publique depuis le certificat.

![image26.png](./screenshots/image26.png)

## Partie 2D : Mise en place d'un VPN (OpenVPN) 

### 1 - Installation d'OpenVPN

Vérification de l'installation et de la version d'``OpenVPN`` et du module `easy-rsa` sur la machine virtuelle.

![image27.png](./screenshots/image27.png)

![image28.png](./screenshots/image28.png)

### 2 - Configuration du Serveur OpenVPN

Création du dossier `server` dans le dosser `openvpn`.

![image29.png](./screenshots/image29.png)

![image30.png](./screenshots/image30.png)

Copie des fichiers de configuration server dans le dossier créé.

![image31.png](./screenshots/image31.png)

### 3 -  Génération des Clés et des Certificats pour le Serveur
- Initialisation du dossier et des outils `easy-rsa`

![image32.png](./screenshots/image32.png)

![image33.png](./screenshots/image33.png)

![image34.png](./screenshots/image34.png)

- Création de l'autorité de certification 

![image35.png](./screenshots/image35.png)

- Création du certificat du serveur

![image36.png](./screenshots/image36.png)

- Génération des paramètres Diffie-Hellman

![image37.png](./screenshots/image37.png)

- Copie des clés et certificats dans le dossier `openvpn`

![image38.png](./screenshots/image38.png)

- Adaptation des droits `root`

![image39.png](./screenshots/image39.png)


### 4 -  Configuration du Client VPN

Création du certificat de `ClientA`.

![image40.png](./screenshots/image40.png)

Vérification des fichiers d'authentification de `ClientA` du côté serveur.

![image41.png](./screenshots/image41.png)

Contenu du fichier `ClientA.ovpn`.
 
![image42.png](./screenshots/image42.png)

Tranfer réussi des fichiers relatif à `ClientA` sur la machine client.

![image43.png](./screenshots/image43.png)


### 5 - Démarrage du Serveur OpenVPN

Édition du fichier de configuration serveur 

![image44.png](./screenshots/image44.png)

Autorisation et démarrage du service `OpenVPN` sur la machine serveur.

![image45.png](./screenshots/image45.png)

![image46.png](./screenshots/image46.png)

Confirmation du fonctionnement du service.

![image47.png](./screenshots/image47.png)

### 6 - Connexion du Client VPN

Confirmation de la connection du client au service avec l'argument `Initialization Sequence Completed` 

![image48.png](./screenshots/image48.png)

**END**














