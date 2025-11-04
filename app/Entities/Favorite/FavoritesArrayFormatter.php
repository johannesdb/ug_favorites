<?php
namespace Favorites\Entities\Favorite;

use Favorites\Entities\Post\FavoriteCount;
use Favorites\Entities\Favorite\FavoriteButton;
use Favorites\Entities\FavoriteList\FavoriteList;

/**
* Format the user's favorite array to include additional post data
* OPTIMIZED: Removed unused data (title, permalink, thumbnails, excerpt, button, total)
* to reduce response size and eliminate expensive postmeta queries
*/
class FavoritesArrayFormatter
{
	/**
	* Formatted favorites array
	*/
	private $formatted_favorites;

	/**
	* Post ID to add to return array
	* For adding/removing session/cookie favorites for current request
	* @var int
	*/
	private $post_id;

	/**
	* Site ID for post to add to array
	* For adding/removing session/cookie favorites for current request
	* @var int
	*/
	private $site_id;

	/**
	* Status for post to add to array
	* For adding/removing session/cookie favorites for current request
	* @var string
	*/
	private $status;

	public function format($favorites, $post_id = null, $site_id = null, $status = null)
	{
		$this->formatted_favorites = $favorites;
		$this->post_id = $post_id;
		$this->site_id = $site_id;
		$this->status = $status;
		$this->resetIndexes();
		return $this->formatted_favorites;
	}

	/**
	* Reset the favorite indexes and reverse order
	*/
	private function resetIndexes()
	{
		foreach ( $this->formatted_favorites as $site => $site_favorites ){
			// Make older posts compatible with new name
			if ( !isset($site_favorites['posts']) ) {
				$site_favorites['posts'] = $site_favorites['site_favorites'];
				unset($this->formatted_favorites[$site]['site_favorites']);
			}
			foreach ( $site_favorites['posts'] as $key => $favorite ){
				unset($this->formatted_favorites[$site]['posts'][$key]);
				$this->formatted_favorites[$site]['posts'][$favorite]['post_id'] = $favorite;
			}
			$this->formatted_favorites[$site] = array_reverse($this->formatted_favorites[$site]);
		}
	}

	/**
	* Make sure the current post is updated in the array
	* (for cookie/session favorites, so AJAX response returns array with correct posts without page refresh)
	*/
	private function checkCurrentPost()
	{
		if ( !isset($this->post_id) || !isset($this->site_id) ) return;
		if ( is_user_logged_in() ) return;
		foreach ( $this->formatted_favorites as $site => $site_favorites ){
			if ( $site_favorites['site_id'] == $this->site_id ) {
				if ( isset($site_favorites['posts'][$this->post_id]) && $this->status == 'inactive' ){
					unset($this->formatted_favorites[$site]['posts'][$this->post_id]);
				} else {
					$this->formatted_favorites[$site]['posts'][$this->post_id] = array('post_id' => $this->post_id);
				}
			}
		}
	}
}