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

const modelContainer = document.getElementById('model_container');
const closeBtn = document.getElementById('close');

// Get all "BOOK NOW" buttons
const openButtons = document.querySelectorAll('.appointment-btn1');

// Add event listener to each button
openButtons.forEach(button => {
    button.addEventListener('click', () => {
        if (!button.disabled) {
            modelContainer.classList.add('show');
        }
    });
});

// Close modal events
closeBtn.addEventListener('click', () => {
    modelContainer.classList.remove('show');
});

// Close modal when clicking outside
modelContainer.addEventListener('click', (e) => {
    if (e.target === modelContainer) {
        modelContainer.classList.remove('show');
    }
});