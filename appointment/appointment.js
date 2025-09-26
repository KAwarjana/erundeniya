// const open = document.getElementById('open');
// const model_container = document.getElementById('model_container');
// const close = document.getElementById('close');

// open.addEventListener('click',()=>{
//     model_container.classList.add('show');
// });

// close.addEventListener('click',()=>{
//     model_container.classList.remove('show');
// });


// model_container.addEventListener('click', (e) => {
//     if (e.target === model_container) {
//         model_container.classList.remove('show');
//     }
// });

const model_container = document.getElementById('model_container');
const close = document.getElementById('close');

// Get all "BOOK NOW" buttons using class selector
const openButtons = document.querySelectorAll('.appointment-btn1');

// Add event listener to each button
openButtons.forEach(button => {
    button.addEventListener('click', () => {
        if (!button.disabled) { // Only open if button is not disabled
            model_container.classList.add('show');
        }
    });
});

close.addEventListener('click', () => {
    model_container.classList.remove('show');
});

// Optional: Close modal when clicking outside of it
model_container.addEventListener('click', (e) => {
    if (e.target === model_container) {
        model_container.classList.remove('show');
    }
});