// Store current slide index per slider  
const sliders = {};
const autoSlideIntervals = {};

// Initialize all sliders on page load
window.addEventListener('DOMContentLoaded', () => {
  console.log('Initializing sliders...');
  document.querySelectorAll('.slider').forEach(slider => {
    const id = slider.id;
    console.log('Found slider:', id);
    sliders[id] = 0;
    
    // Ensure first image is visible immediately
    const images = slider.querySelectorAll('.slider-img');
    const dots = slider.querySelectorAll('.slider-dot');
    console.log(`Slider ${id} has ${images.length} images`);
    
    if (images.length > 0) {
      // Hide all images first
      images.forEach((img, index) => {
        img.classList.remove('active');
        img.style.opacity = '0';
        img.style.visibility = 'hidden';
        img.style.zIndex = '1';
        console.log(`Image ${index} src:`, img.src);
      });
      
      // Show first image
      images[0].classList.add('active');
      images[0].style.opacity = '1';
      images[0].style.visibility = 'visible';
      images[0].style.zIndex = '2';
      
      // Set first dot as active
      if (dots.length > 0) {
        dots.forEach(dot => dot.classList.remove('active'));
        dots[0].classList.add('active');
      }
      
      // Update counter
      updateImageCounter(id, 1, images.length);
    }
    
    // Optionally start auto-slide here:
    // startAutoSlide(id, 5000);
  });
});

// Go to specific slide for slider with given id
function goToSlide(id, index) {
  console.log(`Going to slide ${index} for slider ${id}`);
  
  const slider = document.getElementById(id);
  if (!slider) {
    console.error(`Slider with id "${id}" not found`);
    return;
  }

  const images = slider.querySelectorAll('.slider-img');
  const dots = slider.querySelectorAll('.slider-dot');
  const total = images.length;
  
  if (total === 0) {
    console.warn(`No images found in slider "${id}"`);
    return;
  }

  // Wrap index within bounds
  if (index < 0) index = total - 1;
  if (index >= total) index = 0;

  sliders[id] = index;
  console.log(`Setting slide ${index} as active for slider ${id}`);

  // Hide all images first
  images.forEach((img, i) => {
    img.classList.remove('active');
    img.style.opacity = '0';
    img.style.visibility = 'hidden';
    img.style.zIndex = '1';
    console.log(`Hiding image ${i}`);
  });

  // Show active image
  if (images[index]) {
    images[index].classList.add('active');
    images[index].style.opacity = '1';
    images[index].style.visibility = 'visible';
    images[index].style.zIndex = '2';
    console.log(`Showing image ${index}:`, images[index].src);
  }

  // Update dots active state
  dots.forEach((dot, i) => {
    dot.classList.toggle('active', i === index);
  });

  // Update counter
  updateImageCounter(id, index + 1, total);
}

// Update image counter
function updateImageCounter(id, current, total) {
  const slider = document.getElementById(id);
  if (!slider) return;
  
  const counter = slider.querySelector('.current-image');
  if (counter) {
    counter.textContent = current;
  }
}

// Show next slide
function slideNext(id) {
  console.log(`Next slide for ${id}`);
  if (!(id in sliders)) sliders[id] = 0;
  goToSlide(id, sliders[id] + 1);
}

// Show previous slide
function slidePrev(id) {
  console.log(`Previous slide for ${id}`);
  if (!(id in sliders)) sliders[id] = 0;
  goToSlide(id, sliders[id] - 1);
}

// Start auto sliding for given slider id (interval in ms)
function startAutoSlide(id, interval = 5000) {
  const slider = document.getElementById(id);
  if (!slider) return;

  const images = slider.querySelectorAll('.slider-img');
  if (images.length <= 1) return; // Don't auto-slide if only one image

  if (autoSlideIntervals[id]) clearInterval(autoSlideIntervals[id]);
  
  autoSlideIntervals[id] = setInterval(() => {
    slideNext(id);
  }, interval);
  
  console.log(`Auto-slide started for ${id} with ${interval}ms interval`);
}

// Stop auto sliding for given slider id
function stopAutoSlide(id) {
  if (autoSlideIntervals[id]) {
    clearInterval(autoSlideIntervals[id]);
    delete autoSlideIntervals[id];
    console.log(`Auto-slide stopped for ${id}`);
  }
}

// Dropdown toggle for user menu
function toggleDropdown() {
  const dropdown = document.getElementById('userDropdown');
  if (dropdown) {
    dropdown.classList.toggle('hidden');
  }
}

// Hide dropdown when clicking outside
document.addEventListener('click', (event) => {
  const dropdown = document.getElementById('userDropdown');
  const userToggle = document.querySelector('.user-toggle');
  if (
    dropdown && 
    userToggle && 
    !userToggle.contains(event.target) && 
    !dropdown.contains(event.target)
  ) {
    dropdown.classList.add('hidden');
  }
});

// Debug function - call this in browser console to check slider state
function debugSlider(id) {
  const slider = document.getElementById(id);
  if (!slider) {
    console.error(`Slider ${id} not found`);
    return;
  }
  
  const images = slider.querySelectorAll('.slider-img');
  const dots = slider.querySelectorAll('.slider-dot');
  
  console.log(`=== Debug Slider ${id} ===`);
  console.log(`Current index: ${sliders[id]}`);
  console.log(`Total images: ${images.length}`);
  
  images.forEach((img, i) => {
    console.log(`Image ${i}:`, {
      src: img.src,
      active: img.classList.contains('active'),
      opacity: img.style.opacity || getComputedStyle(img).opacity,
      visibility: img.style.visibility || getComputedStyle(img).visibility,
      zIndex: img.style.zIndex || getComputedStyle(img).zIndex
    });
  });
  
  console.log(`Dots:`, dots.length);
  dots.forEach((dot, i) => {
    console.log(`Dot ${i}: active =`, dot.classList.contains('active'));
  });
}

// Make debug function available globally
window.debugSlider = debugSlider;