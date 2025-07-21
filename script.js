// TMDB API configuration
const API_KEY = 'b6b677eb7d4ec17f700e3d4dfc31d005';
const BASE_URL = 'https://api.themoviedb.org/3';
const IMG_BASE_URL = 'https://image.tmdb.org/t/p';
const POSTER_SIZE = '/w342';
const BACKDROP_SIZE = '/w1280';

// DOM elements
const moviesGrid = document.querySelector('.movies-grid');
const moviesList = document.querySelector('.movies-list');
const loadingElement = document.getElementById('loading');
const paginationElement = document.querySelector('.pagination');
const searchInput = document.getElementById('search-input');
const searchButton = document.getElementById('search-button');
const viewButtons = document.querySelectorAll('.view-option');
const applyFiltersButton = document.querySelector('.apply-filters');
const genreSelect = document.getElementById('genre-filter');
const yearSelect = document.getElementById('year-filter');
const sortSelect = document.getElementById('sort-filter');

// State management
let currentPage = 1;
let totalPages = 0;
let currentView = 'grid';
let currentSearch = '';
let currentGenre = '';
let currentYear = '';
let currentSort = 'popularity.desc';

// Initialize the app
document.addEventListener('DOMContentLoaded', () => {
    // Generate filter options
    generateGenreOptions();
    generateYearOptions();
    
    // Set up event listeners
    setupEventListeners();
    
    // Load initial movies
    fetchMovies();
});

// Set up event listeners for various UI interactions
function setupEventListeners() {
    // Search functionality
    searchButton.addEventListener('click', handleSearch);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    });
    
    // View toggle (grid/list)
    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            viewButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            currentView = button.getAttribute('data-view');
            toggleView();
        });
    });
    
    // Apply filters
    applyFiltersButton.addEventListener('click', () => {
        currentGenre = genreSelect.value;
        currentYear = yearSelect.value;
        currentSort = sortSelect.value;
        currentPage = 1;
        fetchMovies();
    });
}

// Handle search functionality
function handleSearch() {
    const query = searchInput.value.trim();
    
    if (query) {
        currentSearch = query;
        currentPage = 1;
        fetchMovies();
    }
}

// Toggle between grid and list view
function toggleView() {
    if (currentView === 'grid') {
        moviesGrid.style.display = 'grid';
        moviesList.style.display = 'none';
    } else {
        moviesGrid.style.display = 'none';
        moviesList.style.display = 'flex';
    }
}

// Fetch movies from TMDB API
async function fetchMovies() {
    showLoading();
    
    try {
        let url;
        
        if (currentSearch) {
            // Search for movies
            url = `${BASE_URL}/search/movie?api_key=${API_KEY}&query=${encodeURIComponent(currentSearch)}&page=${currentPage}`;
        } else {
            // Discover movies with filters
            url = `${BASE_URL}/discover/movie?api_key=${API_KEY}&page=${currentPage}`;
            
            // Add filters if selected
            if (currentGenre) url += `&with_genres=${currentGenre}`;
            if (currentYear) url += `&primary_release_year=${currentYear}`;
            if (currentSort) url += `&sort_by=${currentSort}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        totalPages = data.total_pages;
        
        if (data.results.length > 0) {
            displayMovies(data.results);
            createPagination();
        } else {
            showNoResults();
        }
    } catch (error) {
        console.error('Error fetching movies:', error);
        showError();
    } finally {
        hideLoading();
    }
}

// Display movies in both grid and list views
function displayMovies(movies) {
    // Clear previous movies
    moviesGrid.innerHTML = '';
    moviesList.innerHTML = '';
    
    // Generate HTML for each movie
    movies.forEach(movie => {
        // Grid view
        const gridCard = createMovieCard(movie);
        moviesGrid.appendChild(gridCard);
        
        // List view
        const listItem = createMovieListItem(movie);
        moviesList.appendChild(listItem);
    });
    
    // Set the current view
    toggleView();
}

// Create a movie card for grid view
function createMovieCard(movie) {
    const card = document.createElement('div');
    card.className = 'movie-card';
    
    const posterPath = movie.poster_path 
        ? `${IMG_BASE_URL}${POSTER_SIZE}${movie.poster_path}`
        : '/api/placeholder/300/450';
    
    // Format genres
    const movieGenres = movie.genre_ids ? getGenreNames(movie.genre_ids) : [];
    
    card.innerHTML = `
        <div class="movie-poster">
            <img src="${posterPath}" alt="${movie.title}">
            <div class="movie-rating">
                <i class="fas fa-star"></i> ${movie.vote_average.toFixed(1)}
            </div>
        </div>
        <div class="movie-info">
            <h3 class="movie-title">${movie.title}</h3>
            <div class="movie-meta">${movie.release_date ? movie.release_date.split('-')[0] : 'Unknown'}</div>
            <div class="movie-genres">
                ${movieGenres.map(genre => `<span class="movie-genre">${genre}</span>`).join('')}
            </div>
        </div>
    `;
    
    // Add click event to open movie player modal
    card.addEventListener('click', () => openMovieModal(movie));
    
    return card;
}

// Create a movie list item for list view
function createMovieListItem(movie) {
    const listItem = document.createElement('div');
    listItem.className = 'movie-list-item';
    
    const posterPath = movie.poster_path 
        ? `${IMG_BASE_URL}${POSTER_SIZE}${movie.poster_path}`
        : '/api/placeholder/300/450';
    
    // Format genres
    const movieGenres = movie.genre_ids ? getGenreNames(movie.genre_ids) : [];
    
    listItem.innerHTML = `
        <img class="list-poster" src="${posterPath}" alt="${movie.title}">
        <div class="list-info">
            <div>
                <h3 class="list-title">${movie.title}</h3>
                <p class="list-overview">${movie.overview || 'No overview available.'}</p>
            </div>
            <div class="list-meta">
                <div class="list-details">
                    ${movie.release_date ? movie.release_date.split('-')[0] : 'Unknown'} • 
                    ${movieGenres.join(', ')} • 
                    <i class="fas fa-star" style="color: #f5c518;"></i> ${movie.vote_average.toFixed(1)}
                </div>
                <div class="list-action">
                    <button class="watch-button" onclick="openMovieModal(${movie.id})">
                        <i class="fas fa-play"></i> Watch
                    </button>
                    <button class="info-button" onclick="openMovieDetails(${movie.id})">
                        <i class="fas fa-info-circle"></i> Details
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return listItem;
}

// Create pagination controls
function createPagination() {
    paginationElement.innerHTML = '';
    
    // Don't show pagination if there's only one page
    if (totalPages <= 1) return;
    
    // Determine which pages to show
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    // Adjust if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // Previous button
    if (currentPage > 1) {
        const prevButton = document.createElement('button');
        prevButton.className = 'page-button';
        prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevButton.addEventListener('click', () => {
            currentPage--;
            fetchMovies();
        });
        paginationElement.appendChild(prevButton);
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('button');
        pageButton.className = `page-button ${i === currentPage ? 'active' : ''}`;
        pageButton.textContent = i;
        pageButton.addEventListener('click', () => {
            if (i !== currentPage) {
                currentPage = i;
                fetchMovies();
            }
        });
        paginationElement.appendChild(pageButton);
    }
    
    // Next button
    if (currentPage < totalPages) {
        const nextButton = document.createElement('button');
        nextButton.className = 'page-button';
        nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextButton.addEventListener('click', () => {
            currentPage++;
            fetchMovies();
        });
        paginationElement.appendChild(nextButton);
    }
}

// Open movie modal with player or trailer
function openMovieModal(movie) {
    const modal = document.getElementById('movie-modal');
    const modalTitle = document.querySelector('.player-modal-title');
    const playerContainer = document.querySelector('.player-container');
    
    // Set modal title
    modalTitle.textContent = movie.title;
    
    // In a real application, you'd load the trailer from TMDB or another source
    // For this example, we'll just embed a placeholder
    playerContainer.innerHTML = `
        <iframe class="player-iframe" src="/api/placeholder/800/450" frameborder="0" allowfullscreen></iframe>
    `;
    
    // Show modal
    modal.style.display = 'block';
    
    // Close modal when clicking outside
    window.onclick = (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
    
    // Close button
    document.querySelector('.close-modal').onclick = () => {
        modal.style.display = 'none';
    };
}

// Genre mapping - will be populated from API
let genreMap = {};

// Fetch genres and populate the filter
async function generateGenreOptions() {
    try {
        const response = await fetch(`${BASE_URL}/genre/movie/list?api_key=${API_KEY}`);
        const data = await response.json();
        
        genreSelect.innerHTML = '<option value="">All Genres</option>';
        
        data.genres.forEach(genre => {
            // Update genre map for future use
            genreMap[genre.id] = genre.name;
            
            // Add option to select
            const option = document.createElement('option');
            option.value = genre.id;
            option.textContent = genre.name;
            genreSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error fetching genres:', error);
    }
}

// Generate year options for the filter
function generateYearOptions() {
    const currentYear = new Date().getFullYear();
    yearSelect.innerHTML = '<option value="">All Years</option>';
    
    for (let year = currentYear; year >= 1900; year--) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearSelect.appendChild(option);
    }
}

// Get genre names from genre IDs
function getGenreNames(genreIds) {
    if (!genreIds) return [];
    
    // Return only the first 3 genres to avoid cluttering the UI
    return genreIds.slice(0, 3).map(id => genreMap[id] || 'Unknown').filter(name => name !== 'Unknown');
}

// Show loading spinner
function showLoading() {
    loadingElement.style.display = 'flex';
    moviesGrid.style.display = 'none';
    moviesList.style.display = 'none';
}

// Hide loading spinner
function hideLoading() {
    loadingElement.style.display = 'none';
}

// Show error message
function showError() {
    moviesGrid.innerHTML = `
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <p>Oops! Something went wrong. Please try again later.</p>
        </div>
    `;
    moviesGrid.style.display = 'block';
}

// Show no results message
function showNoResults() {
    moviesGrid.innerHTML = `
        <div class="no-results">
            <i class="fas fa-search"></i>
            <p>No movies found. Try different search terms or filters.</p>
        </div>
    `;
    moviesGrid.style.display = 'block';
}