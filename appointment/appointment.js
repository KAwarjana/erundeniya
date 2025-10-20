// Updated appointment.js with holiday rescheduling support

class AppointmentSystemDynamic {
    constructor() {
        this.selectedSlot = null;
        this.consultationDates = [];
        this.baseUrl = 'appointment_handler.php';
        this.searchTimeouts = new Map();
        this.tempBookingId = null;
        this.availableDates = []; // Store available consultation dates including rescheduled ones
        this.init();
    }

    init() {
        this.testConnection();
        this.bindEvents();
    }

    /**
     * Test server connection and load available dates
     */
    async testConnection() {
        try {
            const formData = new FormData();
            formData.append('action', 'test');

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
                this.showError('Server returned invalid response');
                return;
            }

            if (data.success) {
                console.log('Connection test passed - Holiday support enabled');
                await this.loadAvailableDates(); // Load available dates first
                this.loadConsultationDates();
            } else {
                this.showError('Connection test failed: ' + data.message);
            }
        } catch (error) {
            console.error('Connection test failed:', error);
            this.showError('Cannot connect to server');
        }
    }

    /**
     * Load all available consultation dates (including rescheduled ones)
     */
    async loadAvailableDates() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_all_available_dates');
            
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.availableDates = data.dates.map(d => d.date);
                console.log('Available dates loaded:', this.availableDates);
            }
        } catch (error) {
            console.error('Error loading available dates:', error);
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('appointment-btn1') && !e.target.disabled) {
                e.preventDefault();
                this.selectedSlot = e.target.getAttribute('data-slot-id');
                this.openModal();
                return;
            }

            if (e.target.id === 'close' || e.target.classList.contains('model-btn2')) {
                e.preventDefault();
                this.closeModal();
                return;
            }

            if (e.target.classList.contains('model-btn1')) {
                e.preventDefault();
                this.processBooking();
                return;
            }
        });

        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('time-search')) {
                this.debounceSearch(e.target, 300);
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('checkbox-input')) {
                this.handleCollapsibleToggle(e.target);
            }
        });
    }

    /**
     * Debounce search input
     */
    debounceSearch(searchInput, delay) {
        const sectionId = searchInput.closest('.collapsible').getAttribute('data-date');

        if (this.searchTimeouts.has(sectionId)) {
            clearTimeout(this.searchTimeouts.get(sectionId));
        }

        const timeoutId = setTimeout(() => {
            this.filterTimeSlots(searchInput);
            this.searchTimeouts.delete(sectionId);
        }, delay);

        this.searchTimeouts.set(sectionId, timeoutId);
    }

    /**
     * Handle collapsible toggle
     */
    handleCollapsibleToggle(checkbox) {
        const dateSection = checkbox.closest('.collapsible');
        const cardsContainer = dateSection.querySelector('.appointment-cards');
        const date = dateSection.getAttribute('data-date');

        if (checkbox.checked) {
            if (date && !cardsContainer.hasAttribute('data-loaded')) {
                this.loadTimeSlotsForDate(date, dateSection);
            } else {
                this.animateExpand(cardsContainer);
            }
        } else {
            this.animateCollapse(cardsContainer);
        }
    }

    /**
     * Animate expand
     */
    animateExpand(container) {
        container.style.maxHeight = '0px';
        container.style.opacity = '0';
        container.style.overflow = 'hidden';

        container.offsetHeight;

        container.classList.add('expanded');
        container.style.transition = 'max-height 0.4s ease-out, opacity 0.3s ease-out';

        const cardCount = container.querySelectorAll('.appointment-card').length;
        const estimatedHeight = this.calculateOptimalHeight(cardCount);

        container.style.maxHeight = estimatedHeight + 'px';
        container.style.opacity = '1';
        container.style.overflowY = 'auto';
        container.style.overflowX = 'hidden';

        setTimeout(() => {
            this.checkScrollability(container);
        }, 400);
    }

    /**
     * Calculate optimal height for container
     */
    calculateOptimalHeight(cardCount) {
        const cardHeight = 200;
        const cardsPerRow = this.getCardsPerRow();
        const rows = Math.ceil(cardCount / cardsPerRow);
        const calculatedHeight = rows * cardHeight + 40;

        const minHeight = 200;
        const maxHeight = window.innerHeight * 0.6;

        return Math.min(Math.max(calculatedHeight, minHeight), maxHeight);
    }

    /**
     * Get cards per row based on screen width
     */
    getCardsPerRow() {
        const screenWidth = window.innerWidth;
        if (screenWidth < 450) return 1;
        if (screenWidth < 950) return 2;
        if (screenWidth < 1200) return 3;
        return 4;
    }

    /**
     * Check if container is scrollable
     */
    checkScrollability(container) {
        if (container.scrollHeight > container.clientHeight) {
            container.classList.add('has-scroll');
        } else {
            container.classList.remove('has-scroll');
        }
    }

    /**
     * Animate collapse
     */
    animateCollapse(container) {
        container.style.transition = 'max-height 0.3s ease-in, opacity 0.2s ease-in';
        container.style.maxHeight = '0px';
        container.style.opacity = '0';
        container.style.overflow = 'hidden';
        container.classList.remove('expanded', 'has-scroll');
    }

    /**
     * Load consultation dates (with holiday support and rescheduled days)
     */
    async loadConsultationDates() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_consultation_dates');
            formData.append('limit', '4'); // Show 4 dates

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
                this.showError('Server error: Invalid response format');
                return;
            }

            if (data.success) {
                // Filter out holidays and show only available consultation days
                this.consultationDates = data.dates.filter(date =>
                    !date.is_holiday && date.is_consultation_day
                );

                // If no dates available, show message
                if (this.consultationDates.length === 0) {
                    this.showNoDatesAvailable();
                    return;
                }

                this.renderConsultationDates();
            } else {
                this.showError('Failed to load consultation dates: ' + data.message);
            }
        } catch (error) {
            console.error('Error loading consultation dates:', error);
            this.showError('Network error: Cannot load consultation dates');
        }
    }

    /**
     * Show message when no consultation dates are available
     */
    showNoDatesAvailable() {
        const container = document.querySelector('.appointment-sec1-div7');
        container.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-calendar-times" style="font-size: 48px; color: #999; margin-bottom: 15px;"></i>
            <p style="color: #666;">No consultation dates available at the moment.</p>
            <p style="color: #999; font-size: 14px;">Please contact us directly for appointments.</p>
        </div>`;
    }

    /**
     * Render consultation dates with special badges for rescheduled days
     */
    renderConsultationDates() {
        const container = document.querySelector('.appointment-sec1-div7');
        if (!container) {
            console.error('Container not found');
            return;
        }

        container.innerHTML = '';

        if (this.consultationDates.length === 0) {
            this.showNoDatesAvailable();
            return;
        }

        let visibleIndex = 0;
        this.consultationDates.forEach((dateInfo, index) => {
            // Skip holidays completely
            if (dateInfo.is_holiday) {
                return;
            }

            const isFirstSection = visibleIndex === 0;
            const sectionHtml = this.createDateSectionHTML(dateInfo, visibleIndex, isFirstSection);
            container.insertAdjacentHTML('beforeend', sectionHtml);

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

            visibleIndex++;
        });
    }

    /**
     * Create date section HTML with holiday/temporary day support
     */
    createDateSectionHTML(dateInfo, index, isChecked = false) {
        // Check if it's a temporary consultation day (rescheduled from holiday)
        const isTemporary = dateInfo.is_temporary || false;
        const isHoliday = dateInfo.is_holiday || false;

        // Skip holidays completely
        if (isHoliday) {
            return '';
        }

        // Special badge for rescheduled/temporary days
        const badgeHtml = isTemporary ? `
        <div style="display: inline-flex; align-items: center; gap: 8px; margin-left: 10px; padding: 4px 12px; background: linear-gradient(135deg, #66ea87ff 0%, #4ba252ff 100%); color: white; border-radius: 20px; font-size: 12px; font-weight: 600; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
            <i class="fas fa-star" style="font-size: 10px;"></i>
            <span>Rescheduled Day</span>
        </div>
    ` : '';

        // Reason message for temporary/rescheduled days
        const reasonHtml = isTemporary && dateInfo.reason ? `
        <div style="margin-top: 8px; padding: 8px 12px; background: #e3f2fd; border-left: 3px solid #2196f3; border-radius: 4px;">
            <div style="display: flex; align-items: start; gap: 8px;">
                <i class="fas fa-info-circle" style="color: #1976d2; margin-top: 2px; font-size: 14px;"></i>
                <span style="color: #1565c0; font-size: 13px; line-height: 1.4;"><strong>Note:</strong> ${dateInfo.reason}</span>
            </div>
        </div>
    ` : '';

        return `
        <div class="collapsible" data-date="${dateInfo.date}" ${isTemporary ? 'data-temporary="true"' : ''}>
            <input type="checkbox" id="collapsible-head-${index}" class="checkbox-input" ${isChecked ? 'checked' : ''}>
            <div class="appointment-expand">
                <label for="collapsible-head-${index}" class="appointment-expand-span">
                    ${dateInfo.display_date}
                    ${badgeHtml}
                </label>
                <div class="appointment-sec1-div8">
                    <img src="img/arrow_down.png" class="appointment-expand-img">
                </div>
            </div>

            ${reasonHtml}

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

    /**
     * Load time slots for specific date
     */
    async loadTimeSlotsForDate(date, sectionElement) {
        const cardsContainer = sectionElement.querySelector('.appointment-cards');
        if (!cardsContainer) return;

        try {
            cardsContainer.innerHTML = `
            <div class="loading-slots" style="text-align: center; padding: 20px;">
                <div class="spinner-border text-primary" role="status"></div>
                <p>Loading available slots...</p>
            </div>
        `;

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
                cardsContainer.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #dc3545; margin-bottom: 10px;"></i>
                    <p class="text-danger">Server error</p>
                </div>`;
                return;
            }

            if (data.success) {
                this.renderTimeSlots(cardsContainer, data.slots);
                cardsContainer.setAttribute('data-loaded', 'true');

                setTimeout(() => {
                    const cardCount = data.slots.length;
                    const newHeight = this.calculateOptimalHeight(cardCount);
                    cardsContainer.style.maxHeight = newHeight + 'px';
                    this.checkScrollability(cardsContainer);
                }, 100);
            } else {
                let errorMessage = data.message || 'Failed to load slots';
                let errorIcon = 'fa-exclamation-circle';
                let errorColor = '#dc3545';

                if (data.error === 'Holiday') {
                    errorIcon = 'fa-calendar-times';
                    errorColor = '#ff9800';
                } else if (data.error === 'This is not a consultation day') {
                    errorIcon = 'fa-calendar-day';
                    errorColor = '#6c757d';
                }

                cardsContainer.innerHTML = `
                <div style="text-align: center; padding: 30px;">
                    <i class="fas ${errorIcon}" style="font-size: 48px; color: ${errorColor}; margin-bottom: 15px;"></i>
                    <p style="font-size: 16px; color: #333; font-weight: 600; margin-bottom: 8px;">
                        ${errorMessage}
                    </p>
                    <p style="font-size: 14px; color: #666;">
                        ${data.error === 'Holiday' ? 'This day has been marked as a holiday. Please check rescheduled dates.' :
                        data.error === 'This is not a consultation day' ? 'Only Wednesday, Sunday, and rescheduled days are available' :
                            'Please try a different date'}
                    </p>
                </div>`;
            }
        } catch (error) {
            console.error('Error loading time slots:', error);
            cardsContainer.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-wifi" style="font-size: 36px; color: #dc3545; margin-bottom: 10px;"></i>
                <p class="text-danger">Network error</p>
            </div>`;
        }
    }

    /**
     * Render time slots
     */
    renderTimeSlots(container, slots) {
        if (slots.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-calendar-times" style="font-size: 36px; color: #999; margin-bottom: 10px;"></i>
                    <p>No time slots available for this date</p>
                </div>`;
            return;
        }

        let slotsHtml = '';
        slots.forEach(slot => {
            slotsHtml += this.createTimeSlotHTML(slot);
        });

        container.innerHTML = slotsHtml;
    }

    /**
     * Create time slot HTML
     */
    createTimeSlotHTML(slot) {
        const statusClass = slot.is_available ? 'appointment-status' : 'appointment-status1';
        const buttonDisabled = slot.is_available ? '' : 'disabled';
        const buttonText = slot.is_available ? 'BOOK NOW' : (slot.is_blocked ? 'BLOCKED' : 'BOOKED');
        const displayNumber = String(slot.slot_number).padStart(5, '0');
        const slotId = slot.date + '_' + slot.time;

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
                        <span class="appointment-card-span2">${slot.status}</span>
                    </div>
                    <button class="appointment-btn1" data-slot-id="${slotId}" ${buttonDisabled}>
                        ${buttonText}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Filter time slots based on search
     */
    filterTimeSlots(searchInput) {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const cardsContainer = searchInput.closest('.collapsible').querySelector('.appointment-cards');
        const cards = cardsContainer.querySelectorAll('.appointment-card');

        let visibleCount = 0;

        cards.forEach(card => {
            const searchText = card.getAttribute('data-search-text') || '';
            const slotNumber = card.querySelector('.appointment-card-span2') ?
                card.querySelector('.appointment-card-span2').textContent.toLowerCase() : '';

            const normalizedSearch = searchTerm.replace(/\./g, ':');
            const dotSearch = searchTerm.replace(/:/g, '.');

            const shouldShow = searchTerm === '' ||
                searchText.includes(normalizedSearch) ||
                searchText.includes(dotSearch) ||
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

        setTimeout(() => {
            if (cardsContainer.classList.contains('expanded')) {
                const newHeight = this.calculateOptimalHeight(visibleCount);
                cardsContainer.style.maxHeight = newHeight + 'px';
                this.checkScrollability(cardsContainer);
            }
        }, 300);

        const existingNoResults = cardsContainer.querySelector('.no-search-results');
        if (visibleCount === 0 && searchTerm !== '') {
            if (!existingNoResults) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'no-search-results';
                noResultsDiv.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #666;">
                        <p>No time slots found for "${searchTerm}"</p>
                    </div>
                `;
                cardsContainer.appendChild(noResultsDiv);
            }
        } else if (existingNoResults) {
            existingNoResults.remove();
        }
    }

    /**
     * Open booking modal
     */
    openModal() {
        const modal = document.getElementById('model_container');
        if (modal) {
            modal.classList.add('show');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            this.loadSlotDetailsToModal();
        }
    }

    /**
     * Load slot details to modal
     */
    loadSlotDetailsToModal() {
        if (!this.selectedSlot) return;

        const [date, time] = this.selectedSlot.split('_');
        const slot = this.findSlotByDateTime(date, time);

        if (slot) {
            const modal = document.getElementById('model_container');
            const modalTitle = modal.querySelector('.model-span1');
            if (modalTitle) {
                const dateObj = new Date(date);
                const displayDate = dateObj.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                modalTitle.innerHTML = `Book Appointment<br><small style="font-size: 0.9rem; color: #666;">${displayDate} at ${slot.display_time}</small>`;
            }
        }
    }

    /**
     * Find slot by date and time
     */
    findSlotByDateTime(date, time) {
        const dateSection = document.querySelector(`[data-date="${date}"]`);
        if (!dateSection) return null;

        const cards = dateSection.querySelectorAll('.appointment-card');
        for (let card of cards) {
            const btn = card.querySelector('.appointment-btn1');
            if (btn && btn.getAttribute('data-slot-id') === `${date}_${time}`) {
                const timeText = card.querySelector('.appointment-card-span2') ?
                    card.querySelector('.appointment-card-span2').textContent : '';
                return { display_time: timeText };
            }
        }
        return null;
    }

    /**
     * Process booking
     */
    async processBooking() {
        if (!this.selectedSlot) {
            this.showError('Please select a time slot');
            return;
        }

        const modal = document.getElementById('model_container');

        const title = modal.querySelector('#title').value;
        const nameInput = modal.querySelector('#patient_name');
        const mobileInput = modal.querySelector('#patient_mobile');
        const emailInput = modal.querySelector('#patient_email');
        const noteInput = modal.querySelector('#patient_note');

        const name = nameInput ? nameInput.value.trim() : '';
        const mobile = mobileInput ? mobileInput.value.trim() : '';
        const email = emailInput ? emailInput.value.trim() : '';
        const note = noteInput ? noteInput.value.trim() : '';

        if (!name) {
            this.showError('Please enter patient name');
            nameInput?.focus();
            return;
        }

        if (!mobile || mobile.length < 10) {
            this.showError('Please enter a valid mobile number');
            mobileInput?.focus();
            return;
        }

        if (email && !this.isValidEmail(email)) {
            this.showError('Please enter a valid email address');
            emailInput?.focus();
            return;
        }

        try {
            const payButton = modal.querySelector('.model-btn1');
            const cancelButton = modal.querySelector('.model-btn2');

            payButton.disabled = true;
            cancelButton.disabled = true;
            payButton.textContent = 'Processing...';

            const [date, time] = this.selectedSlot.split('_');

            const formData = new FormData();
            formData.append('action', 'create_pending_appointment');
            formData.append('date', date);
            formData.append('time', time);
            formData.append('title', title);
            formData.append('name', name);
            formData.append('mobile', mobile);
            formData.append('email', email);
            formData.append('note', note);

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
                this.showError('Server error. Please try again.');
                return;
            }

            if (data.success) {
                // Redirect to payment checkout
                window.location.href = `payment/payment_checkout.php?appointment_number=${data.appointment_number}`;
            } else {
                this.showError(data.message || 'Booking failed. Please try again.');
            }

        } catch (error) {
            console.error('Error creating booking:', error);
            this.showError('Network error. Please check your connection.');
        } finally {
            const payButton = modal.querySelector('.model-btn1');
            const cancelButton = modal.querySelector('.model-btn2');
            if (payButton && cancelButton) {
                payButton.disabled = false;
                cancelButton.disabled = false;
                payButton.textContent = 'Pay Now';
            }
        }
    }

    /**
     * Validate email
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Close modal
     */
    closeModal() {
        const modal = document.getElementById('model_container');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            this.resetForm();
        }
    }

    /**
     * Reset form
     */
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

    /**
     * Refresh current time slots and reload available dates
     */
    async refreshCurrentTimeSlots() {
        await this.loadAvailableDates(); // Reload available dates first
        
        const openSection = document.querySelector('.checkbox-input:checked');
        if (openSection) {
            const collapsible = openSection.closest('.collapsible');
            const date = collapsible.getAttribute('data-date');
            if (date) {
                const cardsContainer = collapsible.querySelector('.appointment-cards');
                cardsContainer.removeAttribute('data-loaded');
                this.loadTimeSlotsForDate(date, collapsible);
            }
        }
        
        // Reload consultation dates list
        this.loadConsultationDates();
    }

    /**
     * Show error notification
     */
    showError(message) {
        const errorHtml = `
            <div style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; max-width: 400px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
                    <span>${message}</span>
                </div>
            </div>
        `;

        const errorDiv = document.createElement('div');
        errorDiv.innerHTML = errorHtml;
        document.body.appendChild(errorDiv.firstElementChild);

        setTimeout(() => {
            const notification = document.body.querySelector('[style*="position: fixed"]');
            if (notification) {
                notification.style.transition = 'opacity 0.3s';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }
        }, 4000);
    }

    /**
     * Show success notification
     */
    showSuccess(message) {
        const successHtml = `
            <div style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; max-width: 400px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
                    <span>${message}</span>
                </div>
            </div>
        `;

        const successDiv = document.createElement('div');
        successDiv.innerHTML = successHtml;
        document.body.appendChild(successDiv.firstElementChild);

        setTimeout(() => {
            const notification = document.body.querySelector('[style*="position: fixed"]');
            if (notification) {
                notification.style.transition = 'opacity 0.3s';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }
        }, 4000);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    console.log('Initializing Dynamic Appointment System with Holiday Rescheduling...');
    window.appointmentSystem = new AppointmentSystemDynamic();
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.appointmentSystem) {
        document.querySelectorAll('.appointment-cards.expanded').forEach(container => {
            const cardCount = container.querySelectorAll('.appointment-card').length;
            const newHeight = window.appointmentSystem.calculateOptimalHeight(cardCount);
            container.style.maxHeight = newHeight + 'px';
            window.appointmentSystem.checkScrollability(container);
        });
    }
});

console.log('Appointment system with Holiday Rescheduling loaded successfully!');