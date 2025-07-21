<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details - Cimastream</title>
    <link rel="stylesheet" href="movie_details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <?php include './includes/navbar.php'; ?>
    <div id="loading">
        <div class="spinner"></div>
    </div>

    <div class="movie-container" style="display: none;">
        <div class="movie-backdrop">
            <div class="backdrop-overlay"></div>
        </div>
        
        <div class="movie-details">
            <img class="movie-poster" src="" alt="">
            <div class="movie-info">
                <h1 class="movie-title"></h1>
                <div class="movie-meta">
                    <span class="movie-year"><i class="far fa-calendar-alt"></i> <span id="movie-year"></span></span>
                    <span class="movie-runtime"><i class="far fa-clock"></i> <span id="movie-runtime"></span></span>
                    <span class="movie-rating"><i class="fas fa-star"></i> <span id="movie-rating"></span></span>
                </div>
                <div class="movie-genres"></div>
                <p class="movie-overview"></p>
                <div class="movie-credits">
                    <p><strong>Director:</strong> <span id="movie-director"></span></p>
                    <p><strong>Writers:</strong> <span id="movie-writers"></span></p>
                </div>
            </div>
        </div>
        
        <div class="watch-section">
            <h2>Watch Movie</h2>
            <div class="server-buttons">
                <button class="server-btn active" data-server="server1">Server 1</button>
                <button class="server-btn" data-server="server2">Server 2</button>
                <button class="server-btn" data-server="server3">Server 3</button>
            </div>
            <div class="player-container">
                <iframe id="player-iframe" class="player-iframe" allowfullscreen></iframe>
            </div>
        </div>
        
        <div class="cast-section">
            <h2>Cast</h2>
            <div class="cast-grid" id="cast-container"></div>
        </div>
    </div>

    <?php include './includes/footer.php'; ?>
    <script>
        // API Configuration
        const BASE_URL = 'https://api.themoviedb.org/3';
        const IMG_URL = 'https://image.tmdb.org/t/p/w500';
        const BACKDROP_URL = 'https://image.tmdb.org/t/p/original';
        
        // Streaming Servers
        const SERVERS = {
            server1: 'https://vidsrc.xyz/embed/movie?tmdb=',
            server2: 'https://www.2embed.cc/embed/',
            server3: 'https://vidsrc.to/embed/movie/'
        };

        // DOM Elements
        const loadingElement = document.getElementById('loading');
        const movieContainer = document.querySelector('.movie-container');
        const castContainer = document.getElementById('cast-container');
        const playerIframe = document.getElementById('player-iframe');
        const serverButtons = document.querySelectorAll('.server-btn');
        const searchInput = document.getElementById('search-input');
        const searchButton = document.getElementById('search-button');
        
        // Get movie ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const movieId = urlParams.get('id');
        let currentServer = 'server1';
        
        // Check if we have a movie ID
        if (!movieId) {
            window.location.href = 'home.php';
        }
        
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
        
        // Initialize page
        window.addEventListener('DOMContentLoaded', () => {
            fetchMovieDetails(movieId);
            
            // Set up search
            searchInput.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    handleSearch();
                }
            });
            searchButton.addEventListener('click', handleSearch);
            
            // Set up server switching
            serverButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Update active button
                    serverButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    // Switch server
                    currentServer = button.dataset.server;
                    loadPlayer(movieId, currentServer);
                });
            });
        });

        // Fetch movie details
        async function fetchMovieDetails(movieId) {
            showLoading();
            try {
                // Fetch movie details with proxy
                const movie = await fetchFromAPI(`/movie/${movieId}`, {
                    append_to_response: 'credits,videos'
                });
                
                // Update document title
                document.title = `${movie.title} - Cimastream`;
                
                // Display movie details
                displayMovieDetails(movie);
                
                // Load player
                loadPlayer(movieId, currentServer);
                
                // Show movie container
                movieContainer.style.display = 'block';
            } catch (error) {
                console.error('Error fetching movie details:', error);
                loadingElement.innerHTML = '<p class="error-message">Error loading movie details. Please try again later.</p>';
            } finally {
                hideLoading();
            }
        }

        // Display movie details
        function displayMovieDetails(movie) {
            // Set backdrop
            const backdropElement = document.querySelector('.movie-backdrop');
            if (movie.backdrop_path) {
                backdropElement.style.backgroundImage = `url('${BACKDROP_URL}${movie.backdrop_path}')`;
            } else {
                backdropElement.style.backgroundImage = 'linear-gradient(to bottom, #333, #111)';
            }
            
            // Set poster
            const posterElement = document.querySelector('.movie-poster');
            if (movie.poster_path) {
                posterElement.src = `${IMG_URL}${movie.poster_path}`;
                posterElement.alt = movie.title;
            } else {
                posterElement.src = 'images/no-poster.png';
                posterElement.alt = 'No poster available';
            }
            
            // Set title and overview
            document.querySelector('.movie-title').textContent = movie.title;
            document.querySelector('.movie-overview').textContent = movie.overview;
            
            // Set year, runtime, and rating
            document.getElementById('movie-year').textContent = movie.release_date ? new Date(movie.release_date).getFullYear() : 'N/A';
            document.getElementById('movie-runtime').textContent = movie.runtime ? `${movie.runtime} min` : 'N/A';
            document.getElementById('movie-rating').textContent = movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A';
            
            // Set genres
            const genresElement = document.querySelector('.movie-genres');
            genresElement.innerHTML = '';
            movie.genres.forEach(genre => {
                const genreTag = document.createElement('span');
                genreTag.classList.add('genre-tag');
                genreTag.textContent = genre.name;
                genresElement.appendChild(genreTag);
            });
            
            // Set director and writers
            const directors = movie.credits.crew.filter(person => person.job === 'Director');
            const writers = movie.credits.crew.filter(person => ['Screenplay', 'Writer', 'Story'].includes(person.job));
            
            document.getElementById('movie-director').textContent = directors.map(director => director.name).join(', ') || 'N/A';
            document.getElementById('movie-writers').textContent = writers.map(writer => writer.name).join(', ') || 'N/A';
            
            // Display cast
            displayCast(movie.credits.cast);
        }

        // Display cast
        function displayCast(cast) {
            castContainer.innerHTML = '';
            
            // Display up to 10 cast members
            cast.slice(0, 10).forEach(person => {
                const castCard = document.createElement('div');
                castCard.classList.add('cast-card');
                
                const profileImg = person.profile_path 
                    ? `${IMG_URL}${person.profile_path}`
                    : 'images/no-profile.png';
                
                castCard.innerHTML = `
                    <img src="${profileImg}" alt="${person.name}" class="cast-photo">
                    <div class="cast-info">
                        <div class="cast-name">${person.name}</div>
                        <div class="cast-character">${person.character}</div>
                    </div>
                `;
                
                castContainer.appendChild(castCard);
            });
        }

        // Load player iframe
        function loadPlayer(movieId, server) {
            let src = '';
            
            if (server === 'server1') {
                src = `${SERVERS.server1}${movieId}`;
            } else if (server === 'server2') {
                src = `${SERVERS.server2}${movieId}`;
            } else if (server === 'server3') {
                src = `${SERVERS.server3}${movieId}`;
            }
            
            playerIframe.src = src;
        }

        // Handle search
        function handleSearch() {
            const searchTerm = searchInput.value.trim();
            
            if (searchTerm !== '') {
                window.location.href = `home.php?query=${encodeURIComponent(searchTerm)}`;
            }
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