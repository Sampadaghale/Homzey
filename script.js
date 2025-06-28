
  function toggleDropdown() {
    document.getElementById("userDropdown").classList.toggle("hidden");
  }

  // Optional: hide dropdown when clicking outside
  window.addEventListener('click', function(e) {
    if (!document.querySelector('.user-dropdown-container').contains(e.target)) {
      document.getElementById("userDropdown").classList.add("hidden");
    }
  });


// Slider functions
function slideNext(id) {
    const slider = document.querySelector(`#${id} .slider-images`);
    const imgWidth = slider.querySelector('img').clientWidth;
    slider.scrollBy({ left: imgWidth, behavior: 'smooth' });
}

function slidePrev(id) {
    const slider = document.querySelector(`#${id} .slider-images`);
    const imgWidth = slider.querySelector('img').clientWidth;
    slider.scrollBy({ left: -imgWidth, behavior: 'smooth' });
}
 const sliders = {};

function updateDots(id, activeIndex) {
  const slider = document.getElementById(id);
  if (!slider) return;
  const dots = slider.querySelectorAll('.slider-dot');
  dots.forEach((dot, i) => {
    dot.classList.toggle('active', i === activeIndex);
  });
}

function goToSlide(id, index) {
  const slider = document.getElementById(id);
  if (!slider) return;
  const sliderImages = slider.querySelector('.slider-images');
  const total = sliderImages.children.length;
  if (index < 0) index = total - 1;
  if (index >= total) index = 0;

  sliders[id] = index;
  sliderImages.style.transform = `translateX(-${index * 100}%)`;
  updateDots(id, index);
}

// Initialize sliders on DOM ready
window.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.slider').forEach(slider => {
    const id = slider.id;
    sliders[id] = 0;
    goToSlide(id, 0);
  });
});


