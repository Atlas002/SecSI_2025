# Mini-Projet 1 : Fondamentaux et Identification/Authentification

## Partie 1-B : Mise en place d'une politique de mot de passe (complexité, renouvellement)

### Configuration de la politique de mot de passe
### Modification de `/etc/security/pwquality.conf`
```bash
sudo nano /etc/security/pwquality.conf
```
Ajout des paramètres :
```
minlen = 12
minclass = 4
ucredit = -1
lcredit = -1
dcredit = -1
ocredit = -1
maxrepeat = 3
maxsequence = 3
difok = 5
gecoscheck = 1
```
![screen1.png](./screenshots/screen1.png)

### Application via PAM
### Modification de `/etc/pam.d/common-password`
```bash
sudo nano /etc/pam.d/common-password
```
Ajout de :
```
password requisite pam_pwquality.so retry=3
password required pam_unix.so sha512 shadow nullok try_first_pass use_authtok
```
![screen2.png](./screenshots/screen2.png)

### Configuration du renouvellement des mots de passe
### Modification de `/etc/login.defs`
```bash
sudo nano /etc/login.defs
```
Configuration :
```
PASS_MAX_DAYS 90
PASS_MIN_DAYS 0
PASS_WARN_AGE 7
```
![screen3.png](./screenshots/screen3.png)

### Application à un utilisateur spécifique
```bash
sudo chage -M 90 kali
sudo chage -l kali
```
![screen4.png](./screenshots/screen4.png)

### Test de la politique de mot de passe
```bash
passwd  kali
```
Tests avec différentes contraintes :
- Trop court → refusé
- Sans chiffres → refusé
- Ne respectant pas toutes les classes → refusé
- Conforme aux règles → accepté

![screen5.png](./screenshots/screen5.png)


## Partie 1-C : Configuration de l'authentification SSH avec échange de clé

### Générer la paire de clés SSH (sur la VM Kali - client)
```bash
ssh-keygen
```
- Accepter l'emplacement par défaut.
- Définir une phrase secrète (passphrase) pour protéger la clé privée.

![screen6.png](./screenshots/screen6.png)

### Copier la clé publique vers le serveur (VM Ubuntu)
```bash
ssh-copy-id utilisateur@adresse_ip_ubuntu
```
- Saisir le mot de passe de l'utilisateur sur Ubuntu.

![screen7.png](./screenshots/screen7.png)

![screen8.png](./screenshots/screen8.png)

### Tester la connexion SSH par clé
```bash
ssh tdutrey@172.20.210.5
```

![screen9.png](./screenshots/screen9.png)

## Partie 1-D : Introduction aux outils d'attaque et de test (Wireshark, Nmap, etc.)

### Wireshark : Capture et Analyse de Base
### Générer du trafic réseau

Pour générer du trafic on utilise la commande Ping qui permet d'envoyer des paquets vers une adresse IP ciblé
```bash
ping -c 5 172.20.10.4
```
![screen10.png](./screenshots/screen10.png)

On repère les paquets depuis wireshark pour les analyser:

![screen11.png](./screenshots/screen11.png)

### Filtrage et Analyse des Paquets

Depuis Wireshark on filtre en tapant "ICMP" dans la barre du haut :

![screen12.png](./screenshots/screen12.png)
on remarque alors l'adresse IP source : 172.20.10.2 
ainsi que l'adresse IP de destination : 172.20.10.4

### Nmap : Scan de Ports de Base

- **Scan SYN des ports ouverts**
Commande exécutée depuis la VM Attaquant :
```bash
nmap -sS 172.20.10.4
```
![screen13.png](./screenshots/screen13.png)

- **Détection des versions des services**
Commande exécutée :
```bash
nmap -sV 172.20.10.4
```

![screen14.png](./screenshots/screen14.png)


## Partie 1-E : Mise en place de protection basique (exemple : firewall basique)

