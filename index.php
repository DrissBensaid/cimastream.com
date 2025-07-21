<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieStream - Find Your Favorite Movies & TV Shows</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles for index page */
        .hero-section {
            height: 80vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-dark), rgba(9, 6, 31, 0.9));
        }
        
        .hero-content {
            max-width: 800px;
            margin-bottom: 2rem;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(90deg, var(--text-light), var(--primary-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-gray);
            margin-bottom: 2.5rem;
        }
        
        .main-search-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }
        
        .main-search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 3px solid var(--primary-accent);
            border-radius: 50px;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            font-size: 1.1rem;
            outline: none;
            transition: all 0.3s;
            box-shadow: 0 0 15px rgba(247, 0, 255, 0.3);
        }
        
        .main-search-input:focus {
            background-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 25px rgba(247, 0, 255, 0.5);
        }
        
        .main-search-input::placeholder {
            color: var(--text-gray);
        }
        
        .main-search-button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--primary-accent);
            color: var(--text-light);
            border: none;
            border-radius: 50px;
            padding: 0.7rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .main-search-button:hover {
            background-color: var(--accent-hover);
        }
        
        .popular-searches {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .popular-searches span {
            background-color: rgba(247, 0, 255, 0.15);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .popular-searches span:hover {
            background-color: rgba(247, 0, 255, 0.3);
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .main-search-input {
                padding: 0.8rem 1.2rem;
                font-size: 1rem;
            }
            
            .main-search-button {
                padding: 0.6rem 1.2rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-title {
                font-size: 1.8rem;
            }
            
            .hero-subtitle {
                font-size: 0.9rem;
                margin-bottom: 1.5rem;
            }
            
            .popular-searches {
                gap: 0.4rem;
            }
            
            .popular-searches span {
                font-size: 0.8rem;
                padding: 0.3rem 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include './includes/navbar.php'; ?>
    
    <main class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Discover Movies & TV Shows</h1>
            <p class="hero-subtitle">Search for your favorite movies and TV shows from our vast collection</p>
        </div>
        
        <div class="main-search-container">
            <input type="text" id="main-search-input" class="main-search-input" placeholder="Search movies, TV shows, actors..." autofocus>
            <button id="main-search-button" class="main-search-button">Search</button>
        </div>
        
        <div class="popular-searches">
            <span>Action</span>
            <span>Comedy</span>
            <span>Drama</span>
            <span>Sci-Fi</span>
            <span>Horror</span>
            <span>Animation</span>
        </div>
    </main>
    
    <?php include './includes/footer.php'; ?>
    
    <script>
        // API Configuration - Same as the home page
        const BASE_URL = 'https://api.themoviedb.org/3';
        const IMG_URL = 'https://image.tmdb.org/t/p/w500';

        // DOM Elements
        const mainSearchInput = document.getElementById('main-search-input');
        const mainSearchButton = document.getElementById('main-search-button');
        const popularSearches = document.querySelectorAll('.popular-searches span');
        
        // Event listeners
        mainSearchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        mainSearchButton.addEventListener('click', performSearch);
        
        // Add event listeners to popular search tags
        popularSearches.forEach(tag => {
            tag.addEventListener('click', () => {
                mainSearchInput.value = tag.textContent;
                performSearch();
            });
        });
        
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
        
        // Perform search directly using the home page's search functionality
        function performSearch() {
            const searchTerm = mainSearchInput.value.trim();
            
            if (searchTerm === '') {
                return;
            }
            
            // Navigate to home page and immediately execute the search function
            window.location.href = 'home.php';
            
            // Store the search term in localStorage to be picked up by home.php
            localStorage.setItem('pendingSearch', searchTerm);
        }
    </script>
</body>
</html>