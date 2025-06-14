document.addEventListener("DOMContentLoaded", function() {
    // Create barePlayersPage namespace object
    const barePlayersPage = {
        // Configuration
        itemsPerPage: 10,
        currentPage: 1,
        playerItems: document.querySelectorAll('.player-item'),
        
        get totalPlayers() { 
            return this.playerItems.length; 
        },
        
        get totalPages() { 
            return Math.ceil(this.totalPlayers / this.itemsPerPage); 
        },
        
        // Initialize pagination
        init: function() {
            // Create pagination container
            this.paginationContainer = document.createElement('div');
            this.paginationContainer.className = 'bare-pagination-controls';
            this.paginationContainer.style.marginTop = '15px';
            this.paginationContainer.style.textAlign = 'center';
            
            // Get player list
            const playerList = document.getElementById('player_list');
            if (!playerList) return; // Exit if no player list found
            
            // Insert pagination container after player list
            playerList.parentNode.insertBefore(this.paginationContainer, playerList.nextSibling);
            
            // Create page info display
            this.pageInfo = document.createElement('div');
            this.pageInfo.className = 'bare-page-info';
            this.pageInfo.style.marginBottom = '10px';
            this.paginationContainer.appendChild(this.pageInfo);
            
            // Create pagination buttons container
            this.buttonContainer = document.createElement('div');
            this.buttonContainer.className = 'bare-pagination-buttons';
            this.paginationContainer.appendChild(this.buttonContainer);
            
            // Show first page and initialize filters
            this.showPage(this.currentPage);
            this.initializeFilters();
        },
        
        // Function to update page display
        updatePageInfo: function() {
            this.pageInfo.textContent = `Page ${this.currentPage} of ${this.totalPages} (${this.totalPlayers} players)`;
        },
        
        // Function to create pagination buttons
        createPaginationButtons: function() {
            const self = this; // Store reference to this for event handlers
            this.buttonContainer.innerHTML = '';
            
            // Previous button
            const prevButton = document.createElement('button');
            prevButton.textContent = 'Previous';
            prevButton.disabled = this.currentPage === 1;
            
            prevButton.addEventListener('click', function() {
                if (self.currentPage > 1) {
                    self.currentPage--;
                    self.showPage(self.currentPage);
                }
            });
            this.buttonContainer.appendChild(prevButton);
            
            // Page number buttons
            const maxButtons = 5; // Maximum number of page buttons to show
            let startPage = Math.max(1, this.currentPage - Math.floor(maxButtons / 2));
            let endPage = Math.min(this.totalPages, startPage + maxButtons - 1);
            
            if (endPage - startPage + 1 < maxButtons && startPage > 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }
            
            // First page button (if not in range)
            if (startPage > 1) {
                const firstPageBtn = document.createElement('button');
                firstPageBtn.textContent = '1';
                firstPageBtn.addEventListener('click', function() {
                    self.currentPage = 1;
                    self.showPage(self.currentPage);
                });
                this.buttonContainer.appendChild(firstPageBtn);
                
                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.textContent = '...';
                    ellipsis.style.margin = '0 5px';
                    this.buttonContainer.appendChild(ellipsis);
                }
            }
            
            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                const pageButton = document.createElement('button');
                pageButton.textContent = i;
                if (i === this.currentPage) {
                    pageButton.style.fontWeight = 'bold';
                    pageButton.style.backgroundColor = '#e0e0e0';
                }
                
                (function(pageNum) {
                    pageButton.addEventListener('click', function() {
                        self.currentPage = pageNum;
                        self.showPage(self.currentPage);
                    });
                })(i);
                
                this.buttonContainer.appendChild(pageButton);
            }
            
            // Last page button (if not in range)
            if (endPage < this.totalPages) {
                if (endPage < this.totalPages - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.textContent = '...';
                    ellipsis.style.margin = '0 5px';
                    this.buttonContainer.appendChild(ellipsis);
                }
                
                const lastPageBtn = document.createElement('button');
                lastPageBtn.textContent = this.totalPages;
                lastPageBtn.addEventListener('click', function() {
                    self.currentPage = self.totalPages;
                    self.showPage(self.currentPage);
                });
                this.buttonContainer.appendChild(lastPageBtn);
            }
            
            // Next button
            const nextButton = document.createElement('button');
            nextButton.textContent = 'Next';
            nextButton.disabled = this.currentPage === this.totalPages;
            nextButton.addEventListener('click', function() {
                if (self.currentPage < self.totalPages) {
                    self.currentPage++;
                    self.showPage(self.currentPage);
                }
            });
            this.buttonContainer.appendChild(nextButton);
            
            // Style the buttons
            const allButtons = this.buttonContainer.querySelectorAll('button');
            allButtons.forEach(btn => {
                btn.style.margin = '0 3px';
                btn.style.padding = '5px 10px';
                btn.style.cursor = 'pointer';
                if (!btn.disabled) {
                    btn.style.backgroundColor = '#f0f0f0';
                }
            });
        },
        
        // Function to show specific page
        showPage: function(page) {
            const startIndex = (page - 1) * this.itemsPerPage;
            const endIndex = Math.min(startIndex + this.itemsPerPage - 1, this.totalPlayers - 1);
            
            // Hide all players
            this.playerItems.forEach((item, index) => {
                if (index >= startIndex && index <= endIndex) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
            
            this.updatePageInfo();
            this.createPaginationButtons();
        },
        
        // Initialize filters integration
        initializeFilters: function() {
            this.positionFilter = document.getElementById('filter_position');
            this.clubFilter = document.getElementById('filter_club');
            this.playerSearch = document.getElementById('player_search_from_api');
            this.maxPriceFilter = document.getElementById('max_price_from_api');
            
            const self = this;
            
            this.applyFilters = function() {
                const position = self.positionFilter ? self.positionFilter.value : '';
                const club = self.clubFilter ? self.clubFilter.value : '';
                const searchTerm = self.playerSearch ? self.playerSearch.value.toLowerCase() : '';
                const maxPrice = self.maxPriceFilter ? parseFloat(self.maxPriceFilter.value) || Infinity : Infinity;
                
                // Filter players
                let visibleCount = 0;
                self.playerItems.forEach(item => {
                    const itemPosition = item.getAttribute('data-position');
                    const itemClub = item.getAttribute('data-club');
                    const label = item.querySelector('label').textContent.toLowerCase();
                    
                    // Extract price from label - assuming format includes (PRICE)
                    const priceMatch = label.match(/\(([0-9.]+)\)/);
                    const itemPrice = priceMatch ? parseFloat(priceMatch[1]) : 0;
                    
                    const positionMatch = !position || itemPosition === position;
                    const clubMatch = !club || itemClub === club;
                    const searchMatch = !searchTerm || label.includes(searchTerm);
                    const priceMatch = !maxPrice || itemPrice <= maxPrice;
                    
                    // Set data-filtered attribute for pagination to use
                    if (positionMatch && clubMatch && searchMatch && priceMatch) {
                        item.setAttribute('data-filtered', 'true');
                        visibleCount++;
                    } else {
                        item.setAttribute('data-filtered', 'false');
                        item.style.display = 'none';
                    }
                });
                
                // Update pagination based on filtered items
                self.updatePaginationForFiltered(visibleCount);
            };
            
            // Add event listeners to filters
            if (this.positionFilter) this.positionFilter.addEventListener('change', this.applyFilters);
            if (this.clubFilter) this.clubFilter.addEventListener('change', this.applyFilters);
            if (this.playerSearch) {
                this.playerSearch.addEventListener('input', function() {
                    // Debounce search to avoid too many refreshes
                    clearTimeout(self.playerSearch.debounceTimer);
                    self.playerSearch.debounceTimer = setTimeout(self.applyFilters, 300);
                });
            }
            if (this.maxPriceFilter) {
                this.maxPriceFilter.addEventListener('input', function() {
                    clearTimeout(self.maxPriceFilter.debounceTimer);
                    self.maxPriceFilter.debounceTimer = setTimeout(self.applyFilters, 300);
                });
            }
        },
        
        // Update pagination based on filtered items
        updatePaginationForFiltered: function(visibleCount) {
            const filteredItems = document.querySelectorAll('.player-item[data-filtered="true"]');
            const newTotalPages = Math.max(1, Math.ceil(filteredItems.length / this.itemsPerPage));
            
            // Reset to first page when filters change
            this.currentPage = 1;
            
            // Update page info
            this.pageInfo.textContent = `Page ${this.currentPage} of ${newTotalPages} (${visibleCount} players)`;
            
            // Show filtered items for current page
            const startIndex = (this.currentPage - 1) * this.itemsPerPage;
            const endIndex = Math.min(startIndex + this.itemsPerPage - 1, filteredItems.length - 1);
            
            filteredItems.forEach((item, index) => {
                if (index >= startIndex && index <= endIndex) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Create buttons with new total
            this.createPaginationButtons();
        }
    };
    
    // Initialize pagination if player list exists
    const playerList = document.getElementById('player_list');
    if (playerList) {
        barePlayersPage.init();
    }
});