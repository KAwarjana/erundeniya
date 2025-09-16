(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();
    
    
    // Initiate the wowjs
    new WOW().init();


    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.sticky-top').addClass('shadow-sm').css('top', '0px');
        } else {
            $('.sticky-top').removeClass('shadow-sm').css('top', '-150px');
        }
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Modal Video
    var $videoSrc;
    $('.btn-play').click(function () {
        $videoSrc = $(this).data("src");
    });
    console.log($videoSrc);
    $('#videoModal').on('shown.bs.modal', function (e) {
        $("#video").attr('src', $videoSrc + "?autoplay=1&amp;modestbranding=1&amp;showinfo=0");
    })
    $('#videoModal').on('hide.bs.modal', function (e) {
        $("#video").attr('src', $videoSrc);
    })


    // Product carousel
    $(".product-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        margin: 25,
        loop: true,
        center: true,
        dots: false,
        nav: true,
        navText : [
            '<i class="bi bi-chevron-left"></i>',
            '<i class="bi bi-chevron-right"></i>'
        ],
        responsive: {
			0:{
                items:1
            },
            576:{
                items:1
            },
            768:{
                items:2
            },
            992:{
                items:3
            }
        }
    });


    // Testimonial carousel
     $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        items: 1,
        loop: true,
        dots: true,
        nav: true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ]
    });
    
})(jQuery);


document.querySelectorAll('.service-item').forEach((item, index) => {
    const button = item.querySelector('button[data-bs-target]');
    const collapse = item.querySelector('.collapse');

    if (button && collapse) {
        // Set unique IDs (your existing functionality)
        const uniqueId = `collapse-${index}`;
        collapse.id = uniqueId;
        button.setAttribute('data-bs-target', `#${uniqueId}`);

        // Add click event listener to toggle classes
        button.addEventListener('click', function() {
            // Toggle the button classes
            button.classList.toggle('toggle-btn-collapsed');
            button.classList.toggle('toggle-btn-expanded');
            
            // Update aria-expanded attribute
            const isExpanded = button.classList.contains('toggle-btn-expanded');
            button.setAttribute('aria-expanded', isExpanded);
        });
    }
});




class CustomImagePopup {
            constructor() {
                this.popup = document.getElementById('imagePopup');
                this.popupImage = document.getElementById('popupImage');
                this.closeBtn = document.getElementById('closePopup');
                this.prevBtn = document.getElementById('prevImage');
                this.nextBtn = document.getElementById('nextImage');
                this.counter = document.getElementById('popupCounter');
                
                this.images = [];
                this.currentIndex = 0;
                
                this.init();
            }
            
            init() {
                // Collect all gallery images
                const galleryItems = document.querySelectorAll('.gallery-item');
                galleryItems.forEach((item, index) => {
                    const imageUrl = item.dataset.popupImage || item.querySelector('img').src;
                    const altText = item.querySelector('img').alt || `Gallery Image ${index + 1}`;
                    this.images.push({ url: imageUrl, alt: altText });
                    
                    // Add click event to gallery item
                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.openPopup(index);
                    });

                    // Add click event to button inside gallery item
                    const button = item.querySelector('button');
                    if (button) {
                        button.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            this.openPopup(index);
                        });
                    }
                });
                
                // Event listeners
                this.closeBtn.addEventListener('click', () => this.closePopup());
                this.prevBtn.addEventListener('click', () => this.prevImage());
                this.nextBtn.addEventListener('click', () => this.nextImage());
                
                // Close on background click
                this.popup.addEventListener('click', (e) => {
                    if (e.target === this.popup) {
                        this.closePopup();
                    }
                });
                
                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
                    if (!this.popup.classList.contains('active')) return;
                    
                    switch(e.key) {
                        case 'Escape':
                            this.closePopup();
                            break;
                        case 'ArrowLeft':
                            this.prevImage();
                            break;
                        case 'ArrowRight':
                            this.nextImage();
                            break;
                    }
                });
            }
            
            openPopup(index) {
                this.currentIndex = index;
                this.updatePopup();
                this.popup.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            closePopup() {
                this.popup.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            prevImage() {
                this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
                this.updatePopup();
            }
            
            nextImage() {
                this.currentIndex = (this.currentIndex + 1) % this.images.length;
                this.updatePopup();
            }
            
            updatePopup() {
                const currentImage = this.images[this.currentIndex];
                this.popupImage.src = currentImage.url;
                this.popupImage.alt = currentImage.alt;
                this.counter.textContent = `${this.currentIndex + 1} / ${this.images.length}`;
                
                // Hide nav buttons if only one image
                if (this.images.length <= 1) {
                    this.prevBtn.style.display = 'none';
                    this.nextBtn.style.display = 'none';
                } else {
                    this.prevBtn.style.display = 'flex';
                    this.nextBtn.style.display = 'flex';
                }
            }
        }
        
        // Initialize WOW.js
        new WOW().init();
        
        // Initialize the popup when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new CustomImagePopup();
        });