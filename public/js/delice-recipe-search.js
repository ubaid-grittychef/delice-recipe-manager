
/**
 * Recipe enhanced search functionality
 */
(function($) {
    'use strict';
    
    // When document is ready
    $(document).ready(function() {
        initRecipeSearch();
    });
    
    /**
     * Initialize recipe search
     */
    function initRecipeSearch() {
        // Get search form if it exists
        var $searchForm = $('.delice-recipe-search-form');
        
        if ($searchForm.length === 0) {
            return;
        }
        
        // Form submit event
        $searchForm.on('submit', function(e) {
            e.preventDefault();
            
            var $resultsContainer = $('#delice-recipe-search-results');
            var searchTerm = $searchForm.find('input[name="recipe_search"]').val();
            var cuisine = $searchForm.find('select[name="recipe_cuisine"]').val();
            var course = $searchForm.find('select[name="recipe_course"]').val();
            var dietary = $searchForm.find('select[name="recipe_dietary"]').val();
            var difficulty = $searchForm.find('select[name="recipe_difficulty"]').val();
            
            // Show loading indicator
            $resultsContainer.html('<div class="delice-recipe-search-loading">' + deliceRecipeSearch.searching + '</div>');
            
            // Send AJAX request
            $.ajax({
                url: deliceRecipeSearch.ajaxurl,
                method: 'POST',
                data: {
                    action: 'delice_recipe_search',
                    nonce: deliceRecipeSearch.nonce,
                    search: searchTerm,
                    cuisine: cuisine,
                    course: course,
                    dietary: dietary,
                    difficulty: difficulty
                },
                success: function(response) {
                    if (response.success) {
                        displaySearchResults(response.data, $resultsContainer);
                    } else {
                        $resultsContainer.html('<div class="delice-recipe-search-error">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $resultsContainer.html('<div class="delice-recipe-search-error">Error processing request.</div>');
                }
            });
        });
        
        // Initialize filter toggles
        $('.delice-recipe-search-filters-toggle').on('click', function() {
            $('.delice-recipe-search-filters').slideToggle();
            $(this).toggleClass('active');
        });
    }
    
    /**
     * Escape a string for safe insertion into HTML.
     */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Display search results
     */
    function displaySearchResults(data, $container) {
        var html = '';

        if (data.count === 0) {
            html = '<div class="delice-recipe-search-no-results">' + escHtml(deliceRecipeSearch.noResults) + '</div>';
        } else {
            html = '<div class="delice-recipe-search-count">' + parseInt(data.count, 10) + ' ' + (data.count === 1 ? 'recipe' : 'recipes') + ' found</div>';
            html += '<div class="delice-recipe-search-results-grid">';

            // Loop through results
            data.results.forEach(function(recipe) {
                var safePermalink = escHtml(recipe.permalink);
                var safeTitle     = escHtml(recipe.title);

                html += '<div class="delice-recipe-search-result">';

                // Thumbnail
                if (recipe.thumbnail) {
                    html += '<div class="delice-recipe-search-result-image">';
                    html += '<a href="' + safePermalink + '"><img src="' + escHtml(recipe.thumbnail) + '" alt="' + safeTitle + '" /></a>';
                    html += '</div>';
                }

                // Content
                html += '<div class="delice-recipe-search-result-content">';

                // Title
                html += '<h3 class="delice-recipe-search-result-title">';
                html += '<a href="' + safePermalink + '">' + safeTitle + '</a>';
                html += '</h3>';

                // Meta
                html += '<div class="delice-recipe-search-result-meta">';

                // Time
                if (recipe.total_time) {
                    html += '<span class="delice-recipe-search-result-time">';
                    html += '<i class="fas fa-clock"></i> ' + parseInt(recipe.total_time, 10) + ' min';
                    html += '</span>';
                }

                // Difficulty — whitelist to safe CSS class suffix values only
                var allowedDifficulty = { easy: 'Easy', medium: 'Medium', hard: 'Hard' };
                if (recipe.difficulty && allowedDifficulty[recipe.difficulty]) {
                    html += '<span class="delice-recipe-search-result-difficulty delice-recipe-difficulty-' + recipe.difficulty + '">';
                    html += '<i class="fas fa-chart-line"></i> ' + allowedDifficulty[recipe.difficulty];
                    html += '</span>';
                }

                // Rating
                if (recipe.rating && recipe.rating.average > 0) {
                    html += '<span class="delice-recipe-search-result-rating">';
                    html += '<i class="fas fa-star"></i> ' + parseFloat(recipe.rating.average).toFixed(1);
                    html += '</span>';
                }

                html += '</div>'; // End meta

                // Excerpt — server returns sanitized HTML; strip tags for safety
                if (recipe.excerpt) {
                    var $tmp = $('<div>').html(recipe.excerpt);
                    html += '<div class="delice-recipe-search-result-excerpt">' + escHtml($tmp.text()) + '</div>';
                }

                // Read more
                html += '<a href="' + safePermalink + '" class="delice-recipe-search-result-link">View Recipe</a>';

                html += '</div>'; // End content
                html += '</div>'; // End result
            });

            html += '</div>'; // End grid
        }

        $container.html(html);
    }
    
})(jQuery);
