    </div> <!-- End of container -->

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-compass me-2"></i>Career Roadmap Generator</h5>
                    <p class="text-muted">Helping you plan and achieve your career goals through personalized roadmaps and skill development.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="dashboard.php" class="text-muted">Dashboard</a></li>
                        <li><a href="skill_assessment.php" class="text-muted">Skill Assessment</a></li>
                        <li><a href="career_goal.php" class="text-muted">Career Goals</a></li>
                        <li><a href="roadmap.php" class="text-muted">My Roadmap</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Resources</h5>
                    <ul class="list-unstyled">
                        <li><a href="resources.php" class="text-muted">Learning Materials</a></li>
                        <li><a href="quiz.php" class="text-muted">Skill Quizzes</a></li>
                        <li><a href="portfolio.php" class="text-muted">Portfolio</a></li>
                        <li><a href="profile.php" class="text-muted">Profile</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Career Roadmap Generator. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-muted me-3"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-muted me-3"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="text-muted"><i class="bi bi-github"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Initialize AOS animations
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
            
            // Add animation classes to elements
            document.querySelectorAll('.card').forEach(function(card, index) {
                card.setAttribute('data-aos', 'fade-up');
                card.setAttribute('data-aos-delay', (index * 100).toString());
            });
            
            // Add hover effects to buttons
            document.querySelectorAll('.btn').forEach(function(btn) {
                btn.addEventListener('mouseenter', function() {
                    this.classList.add('animate-pulse');
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.classList.remove('animate-pulse');
                });
            });
            
            // Add animation to progress bars
            document.querySelectorAll('.progress-bar').forEach(function(bar) {
                const width = bar.style.width;
                bar.style.width = '0';
                
                setTimeout(function() {
                    bar.style.width = width;
                }, 300);
            });
            
            // Add animation to match bars
            document.querySelectorAll('.match-fill').forEach(function(fill) {
                const width = fill.style.width;
                fill.style.width = '0';
                
                setTimeout(function() {
                    fill.style.width = width;
                }, 300);
            });
        });
    </script>
    
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 