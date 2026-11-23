<?php
/**
 * Admin Footer
 */
$lang = getCurrentLang();
?>
            </main>
        </div>
    </div>
    
    <script src="<?php echo asset('js/main.js'); ?>"></script>
    <script>
    // Mobile menu toggle
    (function() {
        const menuToggle = document.querySelector('.menu-toggle');
        if (menuToggle && window.innerWidth <= 1024) {
            menuToggle.style.display = 'flex';
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('admin-sidebar');
            if (sidebar && sidebar.classList.contains('active')) {
                if (!sidebar.contains(e.target) && !e.target.closest('.menu-toggle')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    })();
    </script>
</body>
</html>
