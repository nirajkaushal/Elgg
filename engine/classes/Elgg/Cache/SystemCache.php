<?php
namespace Elgg\Cache;

use Elgg\Profilable;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * @access private
 *
 * @package    Elgg.Core
 * @subpackage Cache
 * @since      1.10.0
 */
class SystemCache {
	use Profilable;

	/**
	 * Global Elgg configuration
	 * 
	 * @var \stdClass
	 */
	private $CONFIG;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $CONFIG;
		$this->CONFIG = $CONFIG;
	}

	/**
	 * Returns an \ElggCache object suitable for caching system information
	 *
	 * @todo Can this be done in a cleaner way?
	 * @todo Swap to memcache etc?
	 *
	 * @return \ElggFileCache
	 */
	function getFileCache() {
		
	
		/**
		 * A default filestore cache using the dataroot.
		 */
		static $FILE_PATH_CACHE;
	
		if (!$FILE_PATH_CACHE) {
			$FILE_PATH_CACHE = new \ElggFileCache($this->CONFIG->dataroot . 'system_cache/');
		}
	
		return $FILE_PATH_CACHE;
	}
	
	/**
	 * Reset the system cache by deleting the caches
	 *
	 * @return void
	 */
	function reset() {
		$this->getFileCache()->clear();
	}
	
	/**
	 * Saves a system cache.
	 *
	 * @param string $type The type or identifier of the cache
	 * @param string $data The data to be saved
	 * @return bool
	 */
	function save($type, $data) {
		
	
		if ($this->CONFIG->system_cache_enabled) {
			return $this->getFileCache()->save($type, $data);
		}
	
		return false;
	}
	
	/**
	 * Retrieve the contents of a system cache.
	 *
	 * @param string $type The type of cache to load
	 * @return string
	 */
	function load($type) {
		
	
		if ($this->CONFIG->system_cache_enabled) {
			$cached_data = $this->getFileCache()->load($type);
	
			if ($cached_data) {
				return $cached_data;
			}
		}
	
		return null;
	}
	
	/**
	 * Enables the system disk cache.
	 *
	 * Uses the 'system_cache_enabled' datalist with a boolean value.
	 * Resets the system cache.
	 *
	 * @return void
	 */
	function enable() {
		
	
		_elgg_services()->datalist->set('system_cache_enabled', 1);
		$this->CONFIG->system_cache_enabled = 1;
		$this->reset();
	}
	
	/**
	 * Disables the system disk cache.
	 *
	 * Uses the 'system_cache_enabled' datalist with a boolean value.
	 * Resets the system cache.
	 *
	 * @return void
	 */
	function disable() {
		
	
		_elgg_services()->datalist->set('system_cache_enabled', 0);
		$this->CONFIG->system_cache_enabled = 0;
		$this->reset();
	}
	
	/**
	 * Loads the system cache during engine boot
	 * 
	 * @see elgg_reset_system_cache()
	 * @access private
	 */
	function loadAll() {
		if ($this->timer) {
			$this->timer->begin([__METHOD__]);
		}

		$this->CONFIG->system_cache_loaded = false;

		if (!_elgg_services()->views->configureFromCache($this)) {
			return;
		}

		$data = $this->load('view_types');
		if (!is_string($data)) {
			return;
		}
		$GLOBALS['_ELGG']->view_types = unserialize($data);

		// Note: We don't need view_overrides for operation. Inspector can pull this from the cache
	
		$this->CONFIG->system_cache_loaded = true;

		if ($this->timer) {
			$this->timer->end([__METHOD__]);
		}
	}
	
	/**
	 * Initializes the simplecache lastcache variable and creates system cache files
	 * when appropriate.
	 * 
	 * @access private
	 */
	function init() {
		if (!$this->CONFIG->system_cache_enabled) {
			return;
		}

		// cache system data if enabled and not loaded
		if (!$this->CONFIG->system_cache_loaded) {
			$this->save('view_types', serialize($GLOBALS['_ELGG']->view_types));

			_elgg_services()->views->cacheConfiguration($this);
		}
	
		if (!$GLOBALS['_ELGG']->i18n_loaded_from_cache) {

			_elgg_services()->translator->reloadAllTranslations();

			foreach ($GLOBALS['_ELGG']->translations as $lang => $map) {
				$this->save("$lang.lang", serialize($map));
			}
		}
	}
}