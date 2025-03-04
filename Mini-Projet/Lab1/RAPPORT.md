# Mini-Projet 1 : Fondamentaux et Identification/Authentification

## Partie 1-B : : Mise en place d'une politique de mot de passe (complexité, renouvellement)

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
![screen1.png]

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

### Configuration du renouvellement des mots de passe
### Modification de `/etc/login.defs`
```bash
sudo nano /etc/login.defs
```
Configuration :
```
PASS_MAX_DAYS 90
PASS_MIN_DAYS 1
PASS_WARN_AGE 7
```

### Application à un utilisateur spécifique
```bash
sudo chage -M 90 kali
sudo chage -l kali
```

## Partie 1-C : Configuration de l'authentification SSH avec échange de clé


## Partie 1-D : Introduction aux outils d'attaque et de test (Wireshark, Nmap, etc.)


## Partie 1-E : Mise en place de protection basique (exemple : firewall basique)

