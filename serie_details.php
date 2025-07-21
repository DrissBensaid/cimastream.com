<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Series Details - Cimastream</title>
    <link rel="stylesheet" href="serie_details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="ttt.js"></script>
</head>
<body>
    <?php include './includes/navbar.php'; ?>

    <div id="loading">
        <div class="spinner"></div>
    </div>

    <div class="series-container" style="display: none;">
        <div class="series-backdrop">
            <div class="backdrop-overlay"></div>
        </div>
        
        <div class="series-details">
            <img class="series-poster" src="" alt="">
            <div class="series-info">
                <h1 class="series-title"></h1>
                <div class="series-meta">
                    <span class="series-year"><i class="far fa-calendar-alt"></i> <span id="series-year"></span></span>
                    <span class="series-status"><i class="fas fa-info-circle"></i> <span id="series-status"></span></span>
                    <span class="series-rating"><i class="fas fa-star"></i> <span id="series-rating"></span></span>
                </div>
                <div class="series-genres"></div>
                <p class="series-overview"></p>
                <div class="series-credits">
                    <p><strong>Created by:</strong> <span id="series-creators"></span></p>
                    <p><strong>Network:</strong> <span id="series-network"></span></p>
                </div>
            </div>
        </div>
        
        <div class="seasons-section">
            <h2>Seasons</h2>
            <div class="seasons-tabs" id="seasons-tabs">
                <!-- Season tabs will be populated dynamically -->
            </div>
            
            <div class="episodes-container" id="episodes-container">
                <!-- Episodes will be populated dynamically -->
            </div>
        </div>
        
        <div class="cast-section">
            <h2>Cast</h2>
            <div class="cast-grid" id="cast-container"></div>
        </div>
    </div>

    <!-- Episode player modal -->
    <div id="player-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3 class="player-modal-title" id="modal-episode-title">Episode Title</h3>
            
            <div class="server-options">
                <button class="server-option active" data-server="server1">Server 1</button>
                <button class="server-option" data-server="server2">Server 2</button>
                <button class="server-option" data-server="server3">Server 3</button>
            </div>
            
            <div class="player-container">
                <iframe id="player-iframe" class="player-iframe" allowfullscreen></iframe>
            </div>
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
            server1: 'https://vidsrc.xyz/embed/tv?tmdb=',
            server2: 'https://www.2embed.cc/embedtv/',
            server3: 'https://vidsrc.to/embed/tv/'
        };

        // DOM Elements
        const loadingElement = document.getElementById('loading');
        const seriesContainer = document.querySelector('.series-container');
        const seasonsTabsContainer = document.getElementById('seasons-tabs');
        const episodesContainer = document.getElementById('episodes-container');
        const castContainer = document.getElementById('cast-container');
        const playerModal = document.getElementById('player-modal');
        const playerIframe = document.getElementById('player-iframe');
        const modalEpisodeTitle = document.getElementById('modal-episode-title');
        const serverOptions = document.querySelectorAll('.server-option');
        const closeModal = document.querySelector('.close-modal');
        const searchInput = document.getElementById('search-input');
        const searchButton = document.getElementById('search-button');
        
        // Get series ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const seriesId = urlParams.get('id');
        
        // Series data
        let seriesData = null;
        let seasonsData = {};
        let currentSeason = 1;
        let currentServer = 'server1';
        
        // Check if we have a series ID
        if (!seriesId) {
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
            fetchSeriesDetails(seriesId);
            
            // Set up search
            searchInput.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    handleSearch();
                }
            });
            searchButton.addEventListener('click', handleSearch);
            
            // Set up modal close
            closeModal.addEventListener('click', () => {
                playerModal.style.display = 'none';
                playerIframe.src = '';
            });
            
            window.addEventListener('click', (e) => {
                if (e.target === playerModal) {
                    playerModal.style.display = 'none';
                    playerIframe.src = '';
                }
            });
            
            // Set up server switching
            serverOptions.forEach(option => {
                option.addEventListener('click', () => {
                    // Update active option
                    serverOptions.forEach(opt => opt.classList.remove('active'));
                    option.classList.add('active');
                    
                    // Switch server
                    currentServer = option.dataset.server;
                    
                    // Update iframe source
                    const episodeId = playerIframe.dataset.episodeId;
                    const seasonNum = playerIframe.dataset.season;
                    const episodeNum = playerIframe.dataset.episode;
                    
                    if (episodeId) {
                        loadEpisodePlayer(seriesId, seasonNum, episodeNum, currentServer);
                    }
                });
            });
        });

        // Fetch TV series details
        async function fetchSeriesDetails(seriesId) {
            showLoading();
            try {
                // Fetch series details with proxy
                const seriesData = await fetchFromAPI(`/tv/${seriesId}`, {
                    append_to_response: 'credits,videos,external_ids'
                });
                
                // Update document title
                document.title = `${seriesData.name} - Cimastream`;
                
                // Display series details
                displaySeriesDetails(seriesData);
                
                // Fetch first season details
                await fetchSeasonDetails(seriesId, 1);
                
                // Show series container
                seriesContainer.style.display = 'block';
            } catch (error) {
                console.error('Error fetching series details:', error);
                loadingElement.innerHTML = '<p class="error-message">Error loading series details. Please try again later.</p>';
            } finally {
                hideLoading();
            }
        }

        // Display series details
        function displaySeriesDetails(series) {
            // Set backdrop
            const backdropElement = document.querySelector('.series-backdrop');
            if (series.backdrop_path) {
                backdropElement.style.backgroundImage = `url('${BACKDROP_URL}${series.backdrop_path}')`;
            } else {
                backdropElement.style.backgroundImage = 'linear-gradient(to bottom, #333, #111)';
            }
            
            // Set poster
            const posterElement = document.querySelector('.series-poster');
            if (series.poster_path) {
                posterElement.src = `${IMG_URL}${series.poster_path}`;
                posterElement.alt = series.name;
            } else {
                posterElement.src = 'images/no-poster.png';
                posterElement.alt = 'No poster available';
            }
            
            // Set title and overview
            document.querySelector('.series-title').textContent = series.name;
            document.querySelector('.series-overview').textContent = series.overview;
            
            // Set year, status, and rating
            document.getElementById('series-year').textContent = series.first_air_date ? new Date(series.first_air_date).getFullYear() : 'N/A';
            document.getElementById('series-status').textContent = series.status || 'N/A';
            document.getElementById('series-rating').textContent = series.vote_average ? series.vote_average.toFixed(1) : 'N/A';
            
            // Set genres
            const genresElement = document.querySelector('.series-genres');
            genresElement.innerHTML = '';
            series.genres.forEach(genre => {
                const genreTag = document.createElement('span');
                genreTag.classList.add('genre-tag');
                genreTag.textContent = genre.name;
                genresElement.appendChild(genreTag);
            });
            
            // Set creators and network
            const creators = series.created_by.map(creator => creator.name).join(', ') || 'N/A';
            const network = series.networks && series.networks.length > 0 ? series.networks[0].name : 'N/A';
            
            document.getElementById('series-creators').textContent = creators;
            document.getElementById('series-network').textContent = network;
            
            // Display cast
            displayCast(series.credits.cast);
            
            // Create season tabs
            createSeasonTabs(series.seasons);
        }

        // Create season tabs
        function createSeasonTabs(seasons) {
            seasonsTabsContainer.innerHTML = '';
            
            // Filter out specials and duplicates
            const filteredSeasons = seasons.filter(season => 
                season.season_number > 0
            );
            
            filteredSeasons.forEach(season => {
                const seasonTab = document.createElement('button');
                seasonTab.classList.add('season-tab');
                seasonTab.textContent = `Season ${season.season_number}`;
                seasonTab.dataset.season = season.season_number;
                
                if (season.season_number === 1) {
                    seasonTab.classList.add('active');
                }
                
                seasonTab.addEventListener('click', async () => {
                    const seasonNum = parseInt(seasonTab.dataset.season);
                    
                    // Update active tab
                    document.querySelectorAll('.season-tab').forEach(tab => {
                        tab.classList.remove('active');
                    });
                    seasonTab.classList.add('active');
                    
                    // Update current season
                    currentSeason = seasonNum;
                    
                    // Fetch season details if not already fetched
                    if (!seasonsData[seasonNum]) {
                        showLoading();
                        await fetchSeasonDetails(seriesId, seasonNum);
                        hideLoading();
                    }
                    
                    // Display episodes
                    displayEpisodes(seasonsData[seasonNum].episodes);
                });
                
                seasonsTabsContainer.appendChild(seasonTab);
            });
        }

        // Fetch season details
        async function fetchSeasonDetails(seriesId, seasonNum) {
            try {
                // Use proxy to fetch season details
                const seasonData = await fetchFromAPI(`/tv/${seriesId}/season/${seasonNum}`);
                
                // Store season data
                seasonsData[seasonNum] = seasonData;
                
                // Display episodes
                displayEpisodes(seasonData.episodes);
            } catch (error) {
                console.error(`Error fetching Season ${seasonNum} details:`, error);
                episodesContainer.innerHTML = '<p class="error-message">Error loading episodes. Please try again later.</p>';
            }
        }

        // Display episodes
        function displayEpisodes(episodes) {
            episodesContainer.innerHTML = '';
            
            if (!episodes || episodes.length === 0) {
                episodesContainer.innerHTML = '<p class="no-results">No episodes found for this season.</p>';
                return;
            }
            
            episodes.forEach(episode => {
                const episodeItem = document.createElement('div');
                episodeItem.classList.add('episode-item');
                
                const episodeImg = episode.still_path 
                    ? `${IMG_URL}${episode.still_path}`
                    : 'images/no-episode.png';
                
                const airDate = episode.air_date 
                    ? new Date(episode.air_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
                    : 'TBA';
                
                episodeItem.innerHTML = `
                    <img src="${episodeImg}" alt="Episode ${episode.episode_number}" class="episode-img">
                    <div class="episode-info">
                        <h3 class="episode-title">Episode ${episode.episode_number}: ${episode.name}</h3>
                        <div class="episode-meta">
                            <span>${airDate}</span> | <span>${episode.runtime ? `${episode.runtime} min` : 'N/A'}</span>
                        </div>
                        <p>${episode.overview || 'No description available.'}</p>
                        <button class="watch-btn" data-episode="${episode.episode_number}" data-season="${currentSeason}" data-id="${episode.id}">
                            <i class="fas fa-play"></i> Watch Episode
                        </button>
                    </div>
                `;
                
                // Add event listener to watch button
                const watchButton = episodeItem.querySelector('.watch-btn');
                watchButton.addEventListener('click', () => {
                    const episodeNum = watchButton.dataset.episode;
                    const seasonNum = watchButton.dataset.season;
                    
                    // Set modal title
                    modalEpisodeTitle.textContent = `Season ${seasonNum}, Episode ${episodeNum}: ${episode.name}`;
                    
                    // Load player
                    loadEpisodePlayer(seriesId, seasonNum, episodeNum, currentServer);
                    
                    // Show modal
                    playerModal.style.display = 'block';
                });
                
                episodesContainer.appendChild(episodeItem);
            });
        }

        // Load episode player
        function loadEpisodePlayer(seriesId, seasonNum, episodeNum, server) {
            let src = '';
            
            if (server === 'server1') {
                src = `${SERVERS.server1}${seriesId}&s=${seasonNum}&e=${episodeNum}`;
            } else if (server === 'server2') {
                src = `${SERVERS.server2}${seriesId}&s=${seasonNum}&e=${episodeNum}`;
            } else if (server === 'server3') {
                src = `${SERVERS.server3}${seriesId}/${seasonNum}/${episodeNum}`;
            }
            
            playerIframe.src = src;
            playerIframe.dataset.episodeId = seriesId;
            playerIframe.dataset.season = seasonNum;
            playerIframe.dataset.episode = episodeNum;
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