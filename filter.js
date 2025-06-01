document.addEventListener("DOMContentLoaded", function () {
    // 🔹 Explore Categories Filtering
    const exploreLinks = document.querySelectorAll('.explore-filter a');
    const exploreCards = document.querySelectorAll('.explore-items .category-card');

    exploreLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const selected = this.dataset.filter;

            exploreCards.forEach(card => {
                const cat = card.dataset.category;
                card.style.display = (selected === 'all' || cat === selected) ? 'block' : 'none';
            });

            exploreLinks.forEach(link => link.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // 🔹 Featured Products Filtering
    const featuredLinks = document.querySelectorAll('.featured-filter a');
    const featuredCards = document.querySelectorAll('.featured-items .product-card');

    featuredLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const selected = this.dataset.filter;

            featuredCards.forEach(card => {
                const cat = card.dataset.category;
                card.style.display = (selected === 'all' || cat === selected) ? 'block' : 'none';
            });

            featuredLinks.forEach(link => link.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const filterLinks = document.querySelectorAll(".daily-sells-filter a");
    const productCards = document.querySelectorAll(".daily-sells-items .product-card");
  
    filterLinks.forEach(link => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
  
        const selected = this.dataset.filter;
  
        productCards.forEach(card => {
          const type = card.dataset.type;
          card.style.display = (type === selected) ? "block" : "none";
        });
  
        filterLinks.forEach(l => l.classList.remove("active"));
        this.classList.add("active");
      });
    });
  
    // Show 'featured' by default
    document.querySelector('[data-filter="featured"]').click();
  });