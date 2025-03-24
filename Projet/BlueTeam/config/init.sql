CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('eleve', 'prof', 'admin') NOT NULL DEFAULT 'eleve',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    date_naissance DATE NOT NULL,
    classe VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS profs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    date_naissance DATE NOT NULL,
    matiere VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prof_id INT NOT NULL,
    classe VARCHAR(50) NOT NULL,
    matiere VARCHAR(50) NOT NULL,
    horaire DATETIME NOT NULL,
    salle VARCHAR(50) NOT NULL,
    FOREIGN KEY (prof_id) REFERENCES profs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eleve_id INT NOT NULL,
    cours_id INT NOT NULL,
    note DECIMAL(4,2) NOT NULL,
    commentaire TEXT,
    date_evaluation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id) ON DELETE CASCADE,
    FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE
); 

CREATE TABLE IF NOT EXISTS travaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eleve_id INT NOT NULL,
    cours_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    descriptions TEXT,
    nom_fichier VARCHAR(255),
    chemin_fichier VARCHAR(255),
    date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('soumis', 'évalué') DEFAULT 'soumis',
    FOREIGN KEY (eleve_id) REFERENCES eleves(id),
    FOREIGN KEY (cours_id) REFERENCES cours(id)
);

CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@admin.com', MD5('adminIo05t6qS'), 'admin'); -- 1

-- Ajout des utilisateurs (élèves et professeurs)
INSERT INTO users (username, email, password, role) VALUES
('barthur', 'arthur.berret@edu.ece.fr', MD5('u9n_83zQw-Tg2a'), 'eleve'), -- 2
('djules', 'jules.dias@edu.ece.fr', MD5('l0_97yHf24b/i'), 'eleve'), -- 3
('dtheophile', 'theophile.dutrey@edu.ece.fr', MD5('of9è3gE6=0Kf'), 'eleve'), -- 4
('ralexis', 'alexis.raynal@edu.ece.fr', MD5('z7n4-RqwuT-Rffff'), 'eleve'),   -- 5
('gmathis', 'mathis.gras@edu.ece.fr', MD5('bonjourcenestpaspossibledemebrutforce'), 'eleve'), -- 6
('mlefevre', 'm.lefevre@mail.com', MD5('Jackiequinoa'), 'prof'),   -- 7
('vmorel', 'v.morel@mail.com', MD5('P_Ut4FCccx4tmP'), 'prof'),  -- 8
('btajini', 'b.tajini@mail.com', MD5('oibueqf8_7t3vz,09'), 'prof'),  -- 9
('apoireaux', 'antoine.poireaux@edu.ece.fr', MD5('0iJccoucouoiseau'), 'eleve'), -- 10
('bvallange', 'berenice.vallange@edu.ece.fr', MD5('Uc5-YrSw0_7'), 'eleve'), -- 11
('pfourtou', 'p.fourtou@mail.com', MD5('password'), 'prof'), -- 12
('jfhittenger', 'j.fhittenger@mail.com', MD5('=P0TgD4sX21___DsQ'), 'prof'),  -- 13
('atagoniste', 'alban.tagoniste@edu.ece.fr' , MD5('hnedf7_5o0c-1&AwPgf5'), 'eleve'); -- 14

-- Ajout des élèves
INSERT INTO eleves (user_id, nom, prenom, date_naissance, classe) VALUES
(2, 'BERRET', 'Arthur', '2003-01-01', 'Cyb Grp 1'), 
(3, 'DIAS', 'Jules', '2003-01-02', 'Cyb Grp 2'),
(4, 'DUTREY', 'Théophile', '2003-01-03', 'Cyb Grp 3'),
(5, 'RAYNAL', 'Alexis', '2003-01-04', 'Cyb Grp 2'),
(6, 'GRAS', 'Mathis', '2003-01-05', 'Cyb Grp 3'),
(10, 'POIREAUX', 'Antoine', '2003-01-06', 'PEI Grp 1'),
(11, 'VALLANGE', 'Bérénice', '2003-01-07', 'PEI Grp 2'),
(14, 'TAGONISTE', 'Alban', '2003-01-08', 'Cyb Grp 1');

-- Ajout des professeurs
INSERT INTO profs (user_id, nom, prenom, date_naissance, matiere) VALUES
(7, 'Lefevre', 'Mathieu', '1985-09-10', 'Prout'),
(8, 'Morel', 'Vincent', '1985-09-10', 'Mathematiques'),
(9, 'Tajini', 'BADR', '1985-09-10', 'Securite des SI'),
(12, 'Fourtou', 'Pierre', '1985-09-10', 'Deep Learning'),
(13, 'Hittenger', 'Jean-François', '1985-09-10', 'Physique');

-- Ajout des cours
INSERT INTO cours (prof_id, classe, matiere, horaire, salle) VALUES
(1, 'Cyb Grp 2', 'Prout', '2025-03-25 10:00:00', 'Salle 101'),
(1, 'Cyb Grp 3', 'Prout', '2025-03-26 14:00:00', 'Salle 202'),
(1, 'Cyb Grp 1', 'Prout', '2025-03-27 16:00:00', 'Salle 303'),
(2, 'Cyb Grp 1', 'Mathematiques', '2025-03-25 10:00:00', 'Salle 101'),
(2, 'PEI Grp 1', 'Mathematiques', '2025-03-26 14:00:00', 'Salle 202'),
(2, 'Cyb Grp 3', 'Mathematiques', '2025-03-27 16:00:00', 'Salle 303'),
(3, 'Cyb Grp 1', 'Securite des SI', '2025-03-25 10:00:00', 'Salle 101'),
(3, 'Cyb Grp 2', 'Securite des SI', '2025-03-26 14:00:00', 'Salle 202'),
(3, 'Cyb Grp 3', 'Securite des SI', '2025-03-27 16:00:00', 'Salle 303'),
(4, 'Cyb Grp 1', 'Deep Learning', '2025-03-25 10:00:00', 'Salle 101'),
(4, 'Cyb Grp 2', 'Deep Learning', '2025-03-26 14:00:00', 'Salle 202'),
(4, 'PEI Grp 2', 'Deep Learning', '2025-03-27 16:00:00', 'Salle 303'),
(5, 'Cyb Grp 1', 'Physique', '2025-03-25 10:00:00', 'Salle 101'),
(5, 'PEI Grp 1', 'Physique', '2025-03-26 14:00:00', 'Salle 202'),
(5, 'PEI Grp 2', 'Physique', '2025-03-27 16:00:00', 'Salle 303');

