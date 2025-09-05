// Get all elements
const filterBtns = document.querySelectorAll('.filter-btn');
const tabItems = document.querySelectorAll('.tab-item1');
const tabContainer = document.querySelector('.tab-filter-item-container1');

// Function to set container height
function setContainerHeight() {
    const activeTab = document.querySelector('.tab-item1.select_tab');
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
    const targetTab = document.querySelector(`.tab-item1.${tabName}`);

    console.log('Found button:', targetBtn); // Debug log
    console.log('Found tab:', targetTab); // Debug log

    if (targetBtn && targetTab) {
        // Activate the tab
        targetBtn.classList.add('active');
        targetTab.classList.add('select_tab');

        // Update height
        // setTimeout(setContainerHeight, 100);

        // Scroll to tab container with offset for fixed header
        // if (shouldScroll) {
        //     setTimeout(() => {
        //         const tabContainer = document.querySelector('.tab-container1');
        //         if (tabContainer) {
        //             const headerOffset = 120; // Adjust based on your header height
        //             const elementPosition = tabContainer.offsetTop;
        //             const offsetPosition = elementPosition - headerOffset;

        //             window.scrollTo({
        //                 top: offsetPosition,
        //                 behavior: 'smooth'
        //             });
        //         }
        //     }, 200);
        // }

        return true;
    }
    return false;
}

// Function to determine which tab to show on page load
function getInitialTab() {
    const hash = window.location.hash.substring(1); // Remove #
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');

    // Priority: hash > URL parameter > default to privacy
    if (hash === 'privacypg' || hash === 'termspg' || hash === 'refundpg') {
        return hash;
    } else if (tabParam === 'privacy' || tabParam === 'privacypg') {
        return 'privacypg';
    } else if (tabParam === 'terms' || tabParam === 'termspg') {
        return 'termspg';
    } else if (tabParam === 'refund' || tabParam === 'refundpg') {
        return 'refundpg';
    } else {
        // Default to privacy tab
        return 'privacypg';
    }
}

// Handle initial page load
function handlePageLoad() {
    const initialTab = getInitialTab();
    console.log('Initial tab:', initialTab);

    // Check if we're coming from external navigation (not just a hash change)
    const isExternalNavigation = !document.referrer.includes(window.location.host) ||
        document.referrer === '' ||
        !window.location.hash;

    // Scroll to tabs if coming from external navigation or if hash is present
    const shouldScroll = isExternalNavigation || window.location.hash !== '';

    activateTab(initialTab, shouldScroll);
}

// Handle tab button clicks
filterBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const tabName = this.getAttribute('data-tab');
        activateTab(tabName, false); // Don't scroll when clicking buttons

        // Update URL hash without triggering scroll
        const newUrl = window.location.pathname + '#' + tabName;
        history.pushState(null, null, newUrl);
    });
});

// Handle hash changes (from external navigation or back/forward buttons)
window.addEventListener('hashchange', function () {
    const hash = window.location.hash.substring(1);
    if (hash === 'privacypg' || hash === 'termspg') {
        activateTab(hash, true); // Scroll when coming from external navigation
    } else if (hash === '') {
        // If hash is cleared, default to privacy
        activateTab('privacypg', false);
    }
});

// Handle back/forward button navigation
window.addEventListener('popstate', function () {
    const initialTab = getInitialTab();
    activateTab(initialTab, true);
});

// Handle window resize
window.addEventListener('resize', function () {
    setTimeout(setContainerHeight, 100);
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Small delay to ensure all elements are loaded
    setTimeout(() => {
        handlePageLoad();
    }, 100);
});

// Also run on window load as backup
window.addEventListener('load', function () {
    setTimeout(() => {
        handlePageLoad();
    }, 100);
});

// Additional function to handle direct navigation to privacy page
// This can be called from other pages when navigating to privacy
function navigateToPrivacyTab(tabName = 'privacypg') {
    if (tabName === 'privacy') tabName = 'privacypg';
    if (tabName === 'terms') tabName = 'termspg';

    // Update URL and activate tab
    window.location.hash = tabName;
    activateTab(tabName, true);
}

// Expose function globally for external navigation
window.navigateToPrivacyTab = navigateToPrivacyTab;