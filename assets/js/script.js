// Hamburger Menu
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');

if (hamburger && mobileMenu) {
  const openMenu = () => {
    hamburger.classList.add('open');
    mobileMenu.classList.add('open');
    hamburger.setAttribute('aria-expanded', 'true');
    document.body.classList.add('menu-open');
  };

  const closeMenu = () => {
    hamburger.classList.remove('open');
    mobileMenu.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('menu-open');
  };

  hamburger.setAttribute('aria-expanded', 'false');

  hamburger.addEventListener('click', () => {
    const isOpen = hamburger.classList.contains('open');
    if (isOpen) {
      closeMenu();
      return;
    }
    openMenu();
  });
  
  mobileMenu.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', closeMenu);
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (
      hamburger.classList.contains('open') &&
      !mobileMenu.contains(target) &&
      !hamburger.contains(target)
    ) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMenu();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
      closeMenu();
    }
  });
}

// Catalog Search
const catalogSearch = document.getElementById('catalogSearch');
const catalogItems = document.querySelectorAll('#catalogGrid .feature-item');
const catalogEmpty = document.getElementById('catalogEmpty');

if (catalogSearch && catalogItems.length > 0) {
  catalogSearch.addEventListener('input', () => {
    const keyword = catalogSearch.value.trim().toLowerCase();
    let visibleCount = 0;

    catalogItems.forEach((item) => {
      const searchableText = item.dataset.search || item.textContent.toLowerCase();
      const isMatch = searchableText.includes(keyword);
      item.style.display = isMatch ? '' : 'none';
      if (isMatch) visibleCount += 1;
    });

    if (catalogEmpty) {
      catalogEmpty.classList.toggle('show', visibleCount === 0);
    }
  });
}