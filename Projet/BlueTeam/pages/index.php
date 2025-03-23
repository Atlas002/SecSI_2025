<?php include('../includes/header.php'); ?>

<!-- Section Hero avec fond d'image amélioré -->
<div class="hero">
    <div class="hero-content">
        <h1>Bienvenue sur le portail de l'ECE</h1>
        <p>Votre plateforme éducative centralisée pour étudiants, enseignants et administration</p>
        <div class="hero-buttons">
            <a href="../login/login.php" class="btn btn-primary">Se connecter</a>
            <a href="https://www.ece.fr/" class="btn btn-secondary">Découvrir l'ECE</a>
        </div>
    </div>
</div>

<!-- Section Actualités -->
<div class="news-banner">
    <div class="container">
        <h3>Actualités</h3>
        <div class="news-slider">
            <div class="news-item">
                <span class="date">22 Mars 2025</span>
                <p>Inscription aux examens du second semestre ouverte</p>
            </div>
            <div class="news-item">
                <span class="date">15 Mars 2025</span>
                <p>Conférence sur l'intelligence artificielle le 28 mars</p>
            </div>
            <div class="news-item">
                <span class="date">10 Mars 2025</span>
                <p>Mise à jour des emplois du temps disponible</p>
            </div>
        </div>
    </div>
</div>

<!-- Section Services avec cartes améliorées -->
<div class="container">
    <section class="features">
        <h2>Nos <span class="highlight">Services</span></h2>
        <p class="section-description">Accédez à tous les outils dont vous avez besoin en fonction de votre profil</p>
        
        <div class="features-container">
            <div class="feature-card">
                <div class="card-icon">
                    <img src="../images/eleve.jpg" alt="Élève">
                </div>
                <h3>Espace Étudiant</h3>
                <p>Consultez vos résultats, emplois du temps, cours en ligne et documents pédagogiques.</p>
                <a href="../login/login.php" class="card-link">Accéder <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="feature-card">
                <div class="card-icon">
                    <img src="../images/prof.jpg" alt="Professeur">
                </div>
                <h3>Espace Enseignant</h3>
                <p>Gérez vos cours, évaluations, communications avec les étudiants et rendus de projets.</p>
                <a href="../login/login.php" class="card-link">Accéder <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="feature-card">
                <div class="card-icon">
                    <img src="../images/admin.jpg" alt="Administrateur">
                </div>
                <h3>Espace Administration</h3>
                <p>Administrez les utilisateurs, programmes, emplois du temps et ressources de l'établissement.</p>
                <a href="../login/login.php" class="card-link">Accéder <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>
    
    <!-- Section Statistiques -->
    <section class="stats">
        <div class="stat-item">
            <span class="stat-number">3500+</span>
            <span class="stat-label">Étudiants</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">250+</span>
            <span class="stat-label">Enseignants</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">45+</span>
            <span class="stat-label">Programmes</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">92%</span>
            <span class="stat-label">Taux d'insertion</span>
        </div>
    </section>
    
    <!-- Section Témoignages -->
    <section class="testimonials">
        <h2>Ce qu'ils disent de <span class="highlight">l'ECE</span></h2>
        <div class="testimonial-container">
            <div class="testimonial">
                <div class="quote">"L'environnement d'apprentissage à l'ECE est exceptionnel, mais ils veulent pas ajouter des prises."</div>
                <div class="author">
                    <div class="author-info">
                        <div class="name">Jules Dias</div>
                        <div class="role">Étudiante en 3ème année</div>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="quote">"En tant qu'enseignant, j'apprécie ce projet et je pense leur mettre 20/20."</div>
                <div class="author">
                    <div class="author-info">
                        <div class="name">Dr. TAJINI BADR</div>
                        <div class="role">Professeur d'Informatique</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Section Calendrier -->
<section class="calendar-section">
    <div class="container">
        <h2>Calendrier <span class="highlight">Académique</span></h2>
        <div class="calendar-wrapper">
            <div class="calendar-event">
                <div class="event-date">
                    <span class="month">AVR</span>
                    <span class="day">15</span>
                </div>
                <div class="event-details">
                    <h4>Examens de mi-semestre</h4>
                    <p>Pour toutes les filières</p>
                </div>
            </div>
            <div class="calendar-event">
                <div class="event-date">
                    <span class="month">MAI</span>
                    <span class="day">05</span>
                </div>
                <div class="event-details">
                    <h4>Journée portes ouvertes</h4>
                    <p>Campus principal, 10h-17h</p>
                </div>
            </div>
            <div class="calendar-event">
                <div class="event-date">
                    <span class="month">JUIN</span>
                    <span class="day">20</span>
                </div>
                <div class="event-details">
                    <h4>Début des examens finaux</h4>
                    <p>Consulter votre emploi du temps personnalisé</p>
                </div>
            </div>
        </div>
        <a href="cal.php" class="btn btn-outlined">Voir tout le calendrier</a>
    </div>
</section>

<?php include('../includes/footer.php'); ?>