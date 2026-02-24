
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
     * Display search results
     */
    function displaySearchResults(data, $container) {
        var html = '';
        
        if (data.count === 0) {
            html = '<div class="delice-recipe-search-no-results">' + deliceRecipeSearch.noResults + '</div>';
        } else {
            html = '<div class="delice-recipe-search-count">' + data.count + ' ' + (data.count === 1 ? 'recipe' : 'recipes') + ' found</div>';
            html += '<div class="delice-recipe-search-results-grid">';
            
            // Loop through results
            data.results.forEach(function(recipe) {
                html += '<div class="delice-recipe-search-result">';
                
                // Thumbnail
                if (recipe.thumbnail) {
                    html += '<div class="delice-recipe-search-result-image">';
                    html += '<a href="' + recipe.permalink + '"><img src="' + recipe.thumbnail + '" alt="' + recipe.title + '" /></a>';
                    html += '</div>';
                }
                
                // Content
                html += '<div class="delice-recipe-search-result-content">';
                
                // Title
                html += '<h3 class="delice-recipe-search-result-title">';
                html += '<a href="' + recipe.permalink + '">' + recipe.title + '</a>';
                html += '</h3>';
                
                // Meta
                html += '<div class="delice-recipe-search-result-meta">';
                
                // Time
                if (recipe.total_time) {
                    html += '<span class="delice-recipe-search-result-time">';
                    html += '<i class="fas fa-clock"></i> ' + recipe.total_time + ' min';
                    html += '</span>';
                }
                
                // Difficulty
                if (recipe.difficulty) {
                    var difficultyLabel = '';
                    switch (recipe.difficulty) {
                        case 'easy':
                            difficultyLabel = 'Easy';
                            break;
                        case 'medium':
                            difficultyLabel = 'Medium';
                            break;
                        case 'hard':
                            difficultyLabel = 'Hard';
                            break;
                    }
                    
                    html += '<span class="delice-recipe-search-result-difficulty delice-recipe-difficulty-' + recipe.difficulty + '">';
                    html += '<i class="fas fa-chart-line"></i> ' + difficultyLabel;
                    html += '</span>';
                }
                
                // Rating
                if (recipe.rating && recipe.rating.average > 0) {
                    html += '<span class="delice-recipe-search-result-rating">';
                    html += '<i class="fas fa-star"></i> ' + recipe.rating.average.toFixed(1);
                    html += '</span>';
                }
                
                html += '</div>'; // End meta
                
                // Excerpt
                if (recipe.excerpt) {
                    html += '<div class="delice-recipe-search-result-excerpt">' + recipe.excerpt + '</div>';
                }
                
                // Read more
                html += '<a href="' + recipe.permalink + '" class="delice-recipe-search-result-link">View Recipe</a>';
                
                html += '</div>'; // End content
                html += '</div>'; // End result
            });
            
            html += '</div>'; // End grid
        }
        
        $container.html(html);
    }
    
})(jQuery);
