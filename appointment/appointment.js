// Fixed appointment.js with improved animations and search
class AppointmentSystem {
    constructor() {
        this.selectedSlot = null;
        this.consultationDates = [];
        this.baseUrl = 'appointment_handler.php';
        this.searchTimeouts = new Map(); // For debounced search
        this.init();
    }

    init() {
        this.testConnection();
        this.bindEvents();
    }

    // Test connection first
    async testConnection() {
        try {
            const formData = new FormData();
            formData.append('action', 'test');

            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            console.log('Raw response:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                this.showError('Server returned invalid response. Please check browser console and PHP errors.');
                return;
            }

            if (data.success) {
                console.log('Connection test passed:', data);
                this.loadConsultationDates();
            } else {
                this.showError('Connection test failed: ' + data.message);
            }
        } catch (error) {
            console.error('Connection test failed:', error);
            this.showError('Cannot connect to server. Please check if appointment_handler.php exists and is accessible.');
        }
    }

    bindEvents() {
        // Use event delegation for all clicks
        document.addEventListener('click', (e) => {
            console.log('Click detected on:', e.target.className, e.target.id); // Debug
            
            // Handle BOOK NOW buttons
            if (e.target.classList.contains('appointment-btn1') && !e.target.disabled) {
                e.preventDefault();
                this.selectedSlot = e.target.getAttribute('data-slot-id');
                console.log('Selected slot ID:', this.selectedSlot);
                this.openModal();
                return;
            }

            // Handle close buttons
            if (e.target.id === 'close' || e.target.classList.contains('model-btn2')) {
                e.preventDefault();
                this.closeModal();
                return;
            }

            // Handle form submission
            if (e.target.classList.contains('model-btn1')) {
                e.preventDefault();
                this.bookAppointment();
                return;
            }
        });

        // Real-time search functionality with debouncing
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('time-search')) {
                this.debounceSearch(e.target, 300); // 300ms delay
            }
        });

        // Improved collapsible sections with smooth animations
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('checkbox-input')) {
                this.handleCollapsibleToggle(e.target);
            }
        });
    }

    // Debounced search to prevent excessive filtering
    debounceSearch(searchInput, delay) {
        const sectionId = searchInput.closest('.collapsible').getAttribute('data-date');
        
        // Clear existing timeout for this section
        if (this.searchTimeouts.has(sectionId)) {
            clearTimeout(this.searchTimeouts.get(sectionId));
        }

        // Set new timeout
        const timeoutId = setTimeout(() => {
            this.filterTimeSlots(searchInput);
            this.searchTimeouts.delete(sectionId);
        }, delay);

        this.searchTimeouts.set(sectionId, timeoutId);
    }

    // Improved collapsible handling with proper animations
    handleCollapsibleToggle(checkbox) {
        const dateSection = checkbox.closest('.collapsible');
        const cardsContainer = dateSection.querySelector('.appointment-cards');
        const date = dateSection.getAttribute('data-date');

        if (checkbox.checked) {
            // Expanding - load data if needed
            if (date && !cardsContainer.hasAttribute('data-loaded')) {
                this.loadTimeSlotsForDate(date, dateSection);
            } else {
                // Just animate if data already loaded
                this.animateExpand(cardsContainer);
            }
        } else {
            // Collapsing - animate collapse
            this.animateCollapse(cardsContainer);
        }
    }

    // Smooth expand animation
    animateExpand(container) {
        // Set initial state
        container.style.maxHeight = '0px';
        container.style.opacity = '0';
        
        // Force reflow
        container.offsetHeight;
        
        // Animate to full height
        container.style.transition = 'max-height 0.4s ease-out, opacity 0.3s ease-out';
        container.style.maxHeight = '600px'; // Sufficient for most content
        container.style.opacity = '1';
    }

    // Smooth collapse animation
    animateCollapse(container) {
        container.style.transition = 'max-height 0.3s ease-in, opacity 0.2s ease-in';
        container.style.maxHeight = '0px';
        container.style.opacity = '0';
    }

    async loadConsultationDates() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_consultation_dates');

            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                this.showError('Server error: Invalid response format. Please check PHP errors.');
                return;
            }

            if (data.success) {
                this.consultationDates = data.dates;
                this.renderConsultationDates();
            } else {
                this.showError('Failed to load consultation dates: ' + data.message);
            }
        } catch (error) {
            console.error('Error loading consultation dates:', error);
            this.showError('Network error: Cannot load consultation dates');
        }
    }

    renderConsultationDates() {
        const container = document.querySelector('.appointment-sec1-div7');
        if (!container) {
            console.error('Container .appointment-sec1-div7 not found');
            return;
        }

        container.innerHTML = '';

        if (this.consultationDates.length === 0) {
            container.innerHTML = `<div style="text-align: center; padding: 40px;"><p>No consultation dates available</p></div>`;
            return;
        }

        this.consultationDates.forEach((dateInfo, index) => {
            const isFirstSection = index === 0;
            const sectionHtml = this.createDateSectionHTML(dateInfo, index, isFirstSection);
            container.insertAdjacentHTML('beforeend', sectionHtml);

            // Auto-load first section with slight delay for smooth animation
            if (isFirstSection) {
                setTimeout(() => {
                    const firstSection = container.querySelector(`[data-date="${dateInfo.date}"]`);
                    const firstCheckbox = firstSection.querySelector('.checkbox-input');
                    if (firstSection && firstCheckbox) {
                        firstCheckbox.checked = true;
                        this.loadTimeSlotsForDate(dateInfo.date, firstSection);
                    }
                }, 200);
            }
        });
    }

    createDateSectionHTML(dateInfo, index, isChecked = false) {
        return `
            <div class="collapsible" data-date="${dateInfo.date}">
                <input type="checkbox" id="collapsible-head-${index}" class="checkbox-input" ${isChecked ? 'checked' : ''}>
                <div class="appointment-expand">
                    <label for="collapsible-head-${index}" class="appointment-expand-span">${dateInfo.display_date}</label>
                    <div class="appointment-sec1-div8">
                        <img src="img/arrow_down.png" class="appointment-expand-img">
                    </div>
                </div>

                <hr class="appointment-hr2">

                <div class="appointment-cards-search">
                    <input type="text" placeholder="Search Your Time Slot.." class="time-search">
                    <button class="search-btn">
                        <img src="img/search.png" class="search-btn-img" alt="">
                    </button>
                </div>

                <br>

                <div class="appointment-cards collapsible-cards" data-date="${dateInfo.date}" style="max-height: 0; opacity: 0; overflow: hidden;">
                    <div class="loading-slots" style="text-align: center; padding: 20px;">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p>Loading slots...</p>
                    </div>
                </div>
            </div>
            ${index < this.consultationDates.length - 1 ? '<br><br>' : ''}
        `;
    }

    async loadTimeSlotsForDate(date, sectionElement) {
        const cardsContainer = sectionElement.querySelector('.appointment-cards');
        if (!cardsContainer) return;

        try {
            // Show loading state
            cardsContainer.innerHTML = `
                <div class="loading-slots" style="text-align: center; padding: 20px;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p>Loading available slots...</p>
                </div>
            `;

            // Animate expand while loading
            this.animateExpand(cardsContainer);

            const formData = new FormData();
            formData.append('action', 'get_time_slots');
            formData.append('date', date);

            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                cardsContainer.innerHTML = `<div style="text-align: center; padding: 20px;"><p class="text-danger">Server error</p></div>`;
                return;
            }

            if (data.success) {
                this.renderTimeSlots(cardsContainer, data.slots);
                cardsContainer.setAttribute('data-loaded', 'true'); // Mark as loaded
            } else {
                cardsContainer.innerHTML = `<div style="text-align: center; padding: 20px;"><p class="text-danger">Failed to load slots</p></div>`;
            }
        } catch (error) {
            console.error('Error loading time slots:', error);
            cardsContainer.innerHTML = `<div style="text-align: center; padding: 20px;"><p class="text-danger">Network error</p></div>`;
        }
    }

    renderTimeSlots(container, slots) {
        console.log(`Rendering ${slots.length} slots`); // Debug

        if (slots.length === 0) {
            container.innerHTML = `<div style="text-align: center; padding: 20px;"><p>No time slots available</p></div>`;
            return;
        }

        let slotsHtml = '';
        slots.forEach(slot => {
            slotsHtml += this.createTimeSlotHTML(slot);
        });

        container.innerHTML = slotsHtml;
        console.log(`Rendered ${container.querySelectorAll('.appointment-card').length} cards`); // Debug
    }

    createTimeSlotHTML(slot) {
        const statusClass = slot.is_available ? 'appointment-status' : 'appointment-status1';
        const buttonDisabled = slot.is_available ? '' : 'disabled';
        const buttonText = slot.is_available ? 'BOOK NOW' : 'BOOKED';
        const displayNumber = String(slot.id).padStart(5, '0');

        return `
            <div class="appointment-card" data-search-text="${slot.display_time.toLowerCase()}">
                <div class="${statusClass}"></div>
                <div class="appointment-card-details">
                    <div class="appointment-details">
                        <span class="appointment-card-span1">Time</span>
                        <span class="appointment-card-span2">${slot.display_time}</span>
                    </div>
                    <div class="appointment-details">
                        <span class="appointment-card-span1">Slot No:</span>
                        <span class="appointment-card-span2">${displayNumber}</span>
                    </div>
                    <div class="appointment-details">
                        <span class="appointment-card-span1">Status</span>
                        <span class="appointment-card-span2">${slot.is_available ? 'Available' : 'Booked'}</span>
                    </div>
                    <button class="appointment-btn1" data-slot-id="${slot.id}" ${buttonDisabled}>
                        ${buttonText}
                    </button>
                </div>
            </div>
        `;
    }

    // Improved search with better performance
    filterTimeSlots(searchInput) {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const cardsContainer = searchInput.closest('.collapsible').querySelector('.appointment-cards');
        const cards = cardsContainer.querySelectorAll('.appointment-card');

        let visibleCount = 0;

        cards.forEach(card => {
            const searchText = card.getAttribute('data-search-text') || '';
            const slotNumber = card.querySelector('.appointment-card-span2') ? 
                card.querySelector('.appointment-card-span2').textContent.toLowerCase() : '';
            
            // Search in time and slot number
            const shouldShow = searchTerm === '' || 
                searchText.includes(searchTerm) || 
                slotNumber.includes(searchTerm);

            if (shouldShow) {
                card.style.display = 'flex';
                card.style.opacity = '1';
                card.style.transform = 'scale(1)';
                visibleCount++;
            } else {
                card.style.display = 'none';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
            }
        });

        // Show "No results" message if no cards are visible
        const existingNoResults = cardsContainer.querySelector('.no-search-results');
        if (visibleCount === 0 && searchTerm !== '') {
            if (!existingNoResults) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'no-search-results';
                noResultsDiv.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #666;">
                        <p>No time slots found for "${searchTerm}"</p>
                        <small>Try searching with different time format (e.g., "09:00", "2:00 PM")</small>
                    </div>
                `;
                cardsContainer.appendChild(noResultsDiv);
            }
        } else if (existingNoResults) {
            existingNoResults.remove();
        }

        console.log(`Search: "${searchTerm}" - ${visibleCount} results found`);
    }

    openModal() {
        console.log('Opening modal...'); // Debug
        const modal = document.getElementById('model_container');
        console.log('Modal found:', !!modal); // Debug
        
        if (modal) {
            modal.classList.add('show');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Load slot details into modal
            this.loadSlotDetailsToModal();
            
            console.log('Modal opened successfully'); // Debug
        } else {
            console.error('Modal element not found!');
        }
    }

    // Load selected slot details into modal
    async loadSlotDetailsToModal() {
        if (!this.selectedSlot) return;

        try {
            const formData = new FormData();
            formData.append('action', 'get_slot_details');
            formData.append('slot_id', this.selectedSlot);

            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                return;
            }

            if (data.success && data.slot) {
                // Update modal with slot information
                this.updateModalWithSlotInfo(data.slot);
            }
        } catch (error) {
            console.error('Error loading slot details:', error);
        }
    }

    // Update modal with dynamic slot information
    updateModalWithSlotInfo(slot) {
        const modal = document.getElementById('model_container');
        if (!modal) return;

        // Update modal title or add slot info somewhere visible
        const modalTitle = modal.querySelector('.model-span1');
        if (modalTitle) {
            modalTitle.textContent = `Book Appointment - ${slot.display_date} at ${slot.display_time}`;
        }

        // You can add more dynamic content here based on the slot data
        console.log('Modal updated with slot info:', slot);
    }

    closeModal() {
        console.log('Closing modal...'); // Debug
        const modal = document.getElementById('model_container');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            this.resetForm();
            console.log('Modal closed successfully'); // Debug
        }
    }

    resetForm() {
        const modal = document.getElementById('model_container');
        if (!modal) return;
        
        const inputs = modal.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (input.type === 'text' || input.type === 'email' || input.type === 'tel') {
                input.value = '';
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            }
        });
    }

    async bookAppointment() {
        if (!this.selectedSlot) {
            this.showError('Please select a time slot');
            return;
        }

        const modal = document.getElementById('model_container');
        
        // Get form values with fallback selectors
        const title = modal.querySelector('#title').value;
        const nameInput = modal.querySelector('#patient_name') || modal.querySelector('input[placeholder*="name"]');
        const mobileInput = modal.querySelector('#patient_mobile') || modal.querySelector('input[type="tel"]');
        const emailInput = modal.querySelector('#patient_email') || modal.querySelector('input[type="email"]');
        const noteInput = modal.querySelector('#patient_note') || modal.querySelector('input[placeholder*="note"]');

        const name = nameInput ? nameInput.value.trim() : '';
        const mobile = mobileInput ? mobileInput.value.trim() : '';
        const email = emailInput ? emailInput.value.trim() : '';
        const note = noteInput ? noteInput.value.trim() : '';

        // Validation
        if (!name) {
            this.showError('Please enter patient name');
            return;
        }

        if (!mobile || mobile.length < 10) {
            this.showError('Please enter a valid mobile number (at least 10 digits)');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'book_appointment');
        formData.append('slot_id', this.selectedSlot);
        formData.append('title', title);
        formData.append('name', name);
        formData.append('mobile', mobile);
        formData.append('email', email);
        formData.append('note', note);

        try {
            const payButton = modal.querySelector('.model-btn1');
            payButton.disabled = true;
            payButton.textContent = 'Processing...';

            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                this.showError('Server error during booking');
                return;
            }

            if (data.success) {
                this.showSuccess(`Appointment booked successfully!\nAppointment Number: ${data.appointment_number}\nTotal Amount: Rs. ${data.total_amount}`);
                this.closeModal();
                this.refreshCurrentTimeSlots();
            } else {
                this.showError(data.message || 'Failed to book appointment');
            }
        } catch (error) {
            console.error('Error booking appointment:', error);
            this.showError('Network error occurred while booking appointment');
        } finally {
            const payButton = modal.querySelector('.model-btn1');
            if (payButton) {
                payButton.disabled = false;
                payButton.textContent = 'Pay Now';
            }
        }
    }

    refreshCurrentTimeSlots() {
        const openSection = document.querySelector('.checkbox-input:checked');
        if (openSection) {
            const collapsible = openSection.closest('.collapsible');
            const date = collapsible.getAttribute('data-date');
            if (date) {
                // Clear loaded flag to force refresh
                const cardsContainer = collapsible.querySelector('.appointment-cards');
                cardsContainer.removeAttribute('data-loaded');
                this.loadTimeSlotsForDate(date, collapsible);
            }
        }
    }

    showError(message) {
        console.error('Appointment System Error:', message);
        // You can replace this with a better notification system
        alert('Error: ' + message);
    }

    showSuccess(message) {
        console.log('Appointment System Success:', message);
        // You can replace this with a better notification system
        alert('Success: ' + message);
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing Enhanced Appointment System...');
    window.appointmentSystem = new AppointmentSystem();
});

// Debug functions
function testModal() {
    if (window.appointmentSystem) {
        window.appointmentSystem.selectedSlot = 'test-123';
        window.appointmentSystem.openModal();
    }
}

function testSearch() {
    const searchInput = document.querySelector('.time-search');
    if (searchInput) {
        searchInput.value = '09:00';
        searchInput.dispatchEvent(new Event('input'));
    }
}

console.log('Enhanced appointment system loaded. Available test functions: testModal(), testSearch()');