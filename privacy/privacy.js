  // Get all elements
        const filterBtns = document.querySelectorAll('.filter-btn');
        const tabItems = document.querySelectorAll('.tab-item');
        const tabContainer = document.querySelector('.tab-filter-item-container');

        // Function to set container height
        function setContainerHeight() {
            const activeTab = document.querySelector('.tab-item.select_tab');
            if (activeTab) {
                tabContainer.style.height = activeTab.scrollHeight + 'px';
            }
        }

        // Function to activate a specific tab
        function activateTab(tabName, shouldScroll = true) {
            console.log('Activating tab:', tabName); // Debug log
            
            // Remove active states
            filterBtns.forEach(btn => btn.classList.remove('active'));
            tabItems.forEach(tab => tab.classList.remove('select_tab'));
            
            // Find elements by data-tab and class
            const targetBtn = document.querySelector(`[data-tab="${tabName}"]`);
            const targetTab = document.querySelector(`.tab-item.${tabName}`);
            
            console.log('Found button:', targetBtn); // Debug log
            console.log('Found tab:', targetTab); // Debug log
            
            if (targetBtn && targetTab) {
                // Activate the tab
                targetBtn.classList.add('active');
                targetTab.classList.add('select_tab');
                
                // Update height
                setTimeout(setContainerHeight, 100);
                
                // Scroll to tab container with offset for fixed header
                if (shouldScroll) {
                    setTimeout(() => {
                        const tabContainer = document.querySelector('.tab-container');
                        const headerOffset = 100; // Adjust this value based on your header height
                        const elementPosition = tabContainer.offsetTop;
                        const offsetPosition = elementPosition - headerOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }, 200); // Delay to ensure tab transition is complete
                }
                
                return true;
            }
            return false;
        }

        // Handle initial page load
        function handlePageLoad() {
            const hash = window.location.hash.substring(1); // Remove #
            console.log('Page hash:', hash); // Debug log
            
            if (hash === 'privacy' || hash === 'terms') {
                // Don't scroll on initial page load if hash is present
                activateTab(hash, true);
            } else {
                // Default to privacy, no scroll needed
                activateTab('privacy', false);
            }
        }

        // Handle tab button clicks
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const tabName = this.getAttribute('data-tab');
                activateTab(tabName, false); // Don't scroll when clicking buttons
                
                // Update URL hash without triggering scroll
                history.pushState(null, null, '#' + tabName);
            });
        });

        // Handle hash changes (from external navigation)
        window.addEventListener('hashchange', function() {
            const hash = window.location.hash.substring(1);
            if (hash === 'privacy' || hash === 'terms') {
                activateTab(hash, true); // Scroll when coming from external navigation
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            setTimeout(setContainerHeight, 100);
        });

        // Initialize when page loads
        window.addEventListener('load', function() {
            handlePageLoad();
        });

        // Also run on DOM ready as backup
        document.addEventListener('DOMContentLoaded', function() {
            handlePageLoad();
        });