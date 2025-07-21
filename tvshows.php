<?php include './includes/navbar.php'; ?>

<div class="container">
    <header class="page-header">
        <h1>All TV Shows</h1>
    </header>

    <div id="loading">
        <div class="spinner"></div>
    </div>

    <div class="content-grid" id="content-container"></div>

    <div class="pagination">
        <button id="prev-page" disabled>Previous</button>
        <span id="current-page">Page 1</span>
        <button id="next-page">Next</button>
    </div>
</div>

<?php include './includes/footer.php'; ?>

<script>
    // API Configuration
    const BASE_URL = 'https://api.themoviedb.org/3';
    const IMG_URL = 'https://image.tmdb.org/t/p/w500';

    // Streaming Servers
    const SERVERS = {
        server1: 'https://www.2embed.skin/',
        server2: 'https://vidsrc.xyz/',
        server3: 'https://vidsrc.to/'
    };

    // DOM Elements
    const contentContainer = document.getElementById('content-container');
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const currentPageSpan = document.getElementById('current-page');
    const loadingElement = document.getElementById('loading');
    const pageHeader = document.querySelector('.page-header h1');

    // State variables
    let currentPage = 1;
    let totalPages = 0;
    let currentSearchTerm = '';
    let isSearchActive = false;

    // Initialize the page with trending TV shows content
    window.addEventListener('DOMContentLoaded', () => {
        fetchTrendingTVShows(currentPage);
    });

    // Event listeners
    searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    });
    searchButton.addEventListener('click', handleSearch);
    prevPageBtn.addEventListener('click', handlePrevPage);
    nextPageBtn.addEventListener('click', handleNextPage);

    // Helper function to make API requests through your backend proxy
    async function fetchFromAPI(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = `proxy.php?endpoint=${encodeURIComponent(endpoint)}${queryString ? '&' + queryString : ''}`;

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`API request failed with status ${response.status}`);
        }
        return response.json();
    }

    // Fetch trending TV shows
    async function fetchTrendingTVShows(page) {
        showLoading();
        isSearchActive = false;
        currentSearchTerm = '';
        pageHeader.textContent = 'All TV Shows';

        try {
            const data = await fetchFromAPI('/trending/tv/week', {
                page
            });

            displayResults(data.results);
            updatePagination(data.page, data.total_pages);
        } catch (error) {
            console.error('Error fetching trending TV shows:', error);
            contentContainer.innerHTML = '<p class="error-message">Error loading content. Please try again later.</p>';
        } finally {
            hideLoading();
        }
    }

    // Search for movies and TV shows
    async function searchContent(query, page) {
        showLoading();
        isSearchActive = true;
        currentSearchTerm = query;
        pageHeader.textContent = `Search Results for "${query}"`;

        try {
            const data = await fetchFromAPI('/search/multi', {
                query: encodeURIComponent(query),
                page
            });

            displayResults(data.results);
            updatePagination(data.page, data.total_pages);
        } catch (error) {
            console.error('Error searching content:', error);
            contentContainer.innerHTML = '<p class="error-message">Error searching content. Please try again later.</p>';
        } finally {
            hideLoading();
        }
    }

    // Display search results
    function displayResults(results) {
        contentContainer.innerHTML = '';

        if (results.length === 0) {
            contentContainer.innerHTML = '<p class="no-results">No results found.</p>';
            return;
        }

        results.forEach(item => {
            // Skip items without title/name or that are people
            if ((!item.title && !item.name) || (item.media_type === 'person')) {
                return;
            }

            const title = item.title || item.name;
            const releaseDate = item.release_date || item.first_air_date || 'Unknown';
            const posterPath = item.poster_path;
            const rating = item.vote_average;
            // Set default media type depending on if searching or not
            const mediaType = isSearchActive ? (item.media_type || 'tv') : 'tv';
            const id = item.id;

            const card = document.createElement('div');
            card.classList.add('content-card');

            let posterContent;
            if (posterPath) {
                posterContent = `<img src="${IMG_URL}${posterPath}" alt="${title}" class="content-poster">`;
            } else {
                posterContent = `<div class="no-poster">No poster available</div>`;
            }

            // Set the correct detail page based on media type
            const detailPage = mediaType === 'tv' ? 'serie_details.php' : 'movie_details.php';

            card.innerHTML = `
                <a href="${detailPage}?id=${id}&type=${mediaType}">
                    ${posterContent}
                    <div class="content-info">
                        <h3 class="content-title">${title}</h3>
                        <p class="content-date">${formatDate(releaseDate)}</p>
                        <span class="rating">${rating ? rating.toFixed(1) : 'N/A'}</span>
                        <span class="media-type">${mediaType === 'tv' ? 'TV Show' : 'Movie'}</span>
                    </div>
                </a>
            `;

            contentContainer.appendChild(card);
        });
    }

    // Format date to a more readable format
    function formatDate(dateStr) {
        if (!dateStr || dateStr === 'Unknown') return 'Unknown';

        const date = new Date(dateStr);
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return date.toLocaleDateString('en-US', options);
    }

    // Handle search input
    function handleSearch() {
        const searchTerm = searchInput.value.trim();

        if (searchTerm === '') {
            fetchTrendingTVShows(1);
            return;
        }

        // Search in the current page instead of redirecting
        searchContent(searchTerm, 1);
    }

    // Update pagination controls
    function updatePagination(page, totalPages) {
        currentPage = page;
        currentPageSpan.textContent = `Page ${page}`;

        prevPageBtn.disabled = page <= 1;
        nextPageBtn.disabled = page >= totalPages;
    }

    // Handle previous page
    function handlePrevPage() {
        if (currentPage > 1) {
            currentPage--;
            if (isSearchActive) {
                searchContent(currentSearchTerm, currentPage);
            } else {
                fetchTrendingTVShows(currentPage);
            }
            window.scrollTo(0, 0);
        }
    }

    // Handle next page
    function handleNextPage() {
        currentPage++;
        if (isSearchActive) {
            searchContent(currentSearchTerm, currentPage);
        } else {
            fetchTrendingTVShows(currentPage);
        }
        window.scrollTo(0, 0);
    }

    // Show loading spinner
    function showLoading() {
        loadingElement.style.display = 'flex';
    }

    // Hide loading spinner
    function hideLoading() {
        loadingElement.style.display = 'none';
    }
</script>
</body>

</html>