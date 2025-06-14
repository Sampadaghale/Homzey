
  function toggleDropdown() {
    document.getElementById("userDropdown").classList.toggle("hidden");
  }

  // Optional: hide dropdown when clicking outside
  window.addEventListener('click', function(e) {
    if (!document.querySelector('.user-dropdown-container').contains(e.target)) {
      document.getElementById("userDropdown").classList.add("hidden");
    }
  });

