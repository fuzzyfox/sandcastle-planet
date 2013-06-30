<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
	
	/**
	 * SandCastle
	 *
	 * An opensource set of tools for making a basic community site.
	 *
	 * @package 	SandCastle
	 * @author 		William Duyck <wduyck@gmail.com>
	 * @copyright 	Copyleft 2012, William Duyck
	 * @license 	https://www.mozilla.org/MPL/2.0/ MPL v2.0
	 * @link 		http://www.wduyck.com/ wduyck.com
	 * @filesource
	 */
	
	// -------------------------------------------------------------------------
	
	/**
	 * Planet Library
	 * 
	 * Provides the needed functions to make running a basic planet a piece of
	 * cake.
	 *
	 * Supports RSS 2.0 and ATOM feeds
	 *
	 * @package 	SandCastle
	 * @subpackage 	Libraries
	 * @category 	Planet
	 * @author 		William Duyck <wduyck@gmail.com>
	 * @copyright 	Copyleft 2012, William Duyck
	 * @license 	https://www.mozilla.org/MPL/2.0/ MPL v2.0
	 * @link 		http://www.wduyck.com/ wduyck.com
	 *
	 * @todo Planet Features
	 * * add RSS 1.0 support
	 * * write testcases
	 */
	class Planet
	{
		/** @var	CodeIgniter	CodeIgniter instance */
		private $CI;
		/** @var	assoc_array	processed feeds */
		private $processed_feeds = array();
		/** @var	object		configuration for planet*/
		private $config;
		
		/**
		 * Constructor
		 *
		 * Gets the CodeIgniter instance, the CodeIgniter file helper and cache
		 * driver, and loads the planet configuration
		 */
		public function __construct()
		{
			// get CodeIgniter instance
			$this->CI =& get_instance();
			// get CodeIgniter file helper
			$this->CI->load->helper('file');
			// get CodeIgniter cache driver
			$this->CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
			// get config
			$this->config = (object)$this->CI->config->item('sandcastle-planet');
			// correct cache time to convert minutes into seconds.
			$this->config->cache_expires *= 60;
		}
		
		/**
		 * Gets a feed from its source
		 *
		 * Gets feed(s) from the site provided to the function using the SimpleXML
		 * php extension. This function works recursively to get feeds if an array
		 * of urls is passed in.
		 *
		 * Usage:
		 *		// single feed
		 * 		$feed = $this->planet->get_feed('http://www.example.com/feed.rss');
		 *
		 * 		// multiple feeds
		 * 		$feeds = $this->planet->get_feed(array(
		 * 			'http://www.example.com/feed.rss',
		 * 			'http://www.anotherexample.com/feed.rss'
		 * 		));
		 *
		 * @param	mixed	$url	either a string url or array of urls of feeds to process
		 * @return	mixed	feed object sorted by time or FALSE if feed not found or supported 
		 */
		public function get_feed($url)
		{
			// recursively addd the feeds if $url is an array
			if(is_array($url))
			{
				foreach($url as $feed)
				{
					$currentFeed = (array)$this->get_feed($feed);
					if($currentFeed !== FALSE)
					{
						$this->processed_feeds = array_merge($this->processed_feeds, $currentFeed);
					}
				}
				
				if(count($this->processed_feeds) > 0)
				{
					$this->osort($this->processed_feeds, 'datetime');
				}
				
				return (count($this->processed_feeds) > 0) ? (object)$this->processed_feeds : FALSE;
			}
			
			// check if the url is valid and accessible
			if(!$this->valid_feed($url))
			{
				return FALSE;
			}
			
			// $url was not an array determine if it is RSS 2.0, ATOM, or other
			$feed = $this->get_simplexml($url);
			if($feed->getName() === 'rss')
			{
				// indeed, lets process
				$feed = $this->process_rss($feed);
				return $feed;
			}
			elseif($feed->getName() === 'feed')
			{
				$feed = $this->process_atom($feed);
				return $feed;
			}
		}
		
		/**
		 * Get the title of a feed
		 *
		 * @param	string	$url	The url of the feed to get the title of
		 * @return	mixed	Title of feed on success, FALSE if feed is invalid
		 */
		public function get_feed_title($url)
		{
			if($this->valid_feed($url))
			{
				$feed = $this->get_simplexml($url);
				if($feed->getName() === 'rss')
				{
					return (string)$feed->channel->title;
				}
				elseif($feed->getName() === 'feed')
				{
					return (string)$feed->title;
				}
			}
		}
		
		/**
		 * Gets the SimpleXMLElement for feed
		 *
		 * Gets the SimpleXMLElement for the feed from the cache OR if cache has
		 * expired from URL
		 *
		 * @param	string	$url	the url for the feed
		 * @return	SimpleXMLElement	The SimpleXMLElement for the feed provided
		 */
		private function get_simplexml($url)
		{
			$cache = $this->CI->cache->get($this->config->cache_prefix . md5($url));
			if($cache === FALSE)
			{
				// feed not in cache so grab it again
				$feed = @file_get_contents($url) or '';
				// save feed to cache
				$this->CI->cache->save($this->config->cache_prefix . md5($url), $feed, $this->config->cache_expires);
				// load SimpleXMLElement
				return new SimpleXMLElement($feed);
			}
			
			return new SimpleXMLElement($cache);
		}
		
		/**
		 * Processes an RSS 2.0 feed
		 *
		 * Takes a SimpleXMLElement of an RSS 2.0 feed and extracts:
		 * 
		 * * published datetime
		 * * title
		 * * link
		 * * content
		 * 
		 * for each item in the feed
		 *
		 * @param	SimpleXMLElement	$feed	the feed to process
		 * @return	object	the processed feed
		 */
		private function process_rss($feed)
		{
			$return = array();
			
			// add the items to the return
			foreach($feed->channel->item as $item)
			{
				$result = new stdClass;
				$result->title 			= (string)$item->title;
				$result->link 			= (string)$item->link;
				$result->feed_title		= (string)$feed->channel->title;
				$result->full			= $item;
				
				if(isset($item->children('http://purl.org/rss/1.0/modules/content/')->encoded))
				{
					$result->content = (string)$item->children('http://purl.org/rss/1.0/modules/content/')->encoded;
				}
				else
				{
					$result->content = (string)$item->description;
				}
				
				// published date is not required by RSS spec
				$result->datetime 	= (isset($item->pubDate)) ? strtotime((string)$item->pubDate) : NULL;
				
				// add this item to the return
				array_push($return, $result);
			}
			
			return (object)$return;
		}
		
		/**
		 * Processes an ATOM feed
		 *
		 * Takes a SimpleXMLElement of and ATOM feed and extracts:
		 *
		 * * published datetime
		 * * title
		 * * link
		 * * content
		 *
		 * for each item in the feed
		 *
		 * @param	SimpleXMLElement	$feed	the feed to process
		 * @return	object 	the processed feed
		 */
		public function process_atom($feed)
		{
			$return = array();
			
			// add the items to the return
			foreach($feed->entry as $item)
			{
				$result = new stdClass;
				$result->datetime 	= strtotime((string)$item->updated);
				$result->title 		= (string)$item->title;
				$result->feed_title	= (string)$feed->title;
				$result->full		= $item;
				
				// content of some form is not required by the ATOM spec
				if(isset($item->content))
				{
					$result->content = (string)$item->content;
				}
				elseif(isset($item->summary))
				{
				 	$result->content = (string)$item->summary;
				}
				else
				{
					$result->content = NULL;
				}
				
				// getting the link is a little more complex in atom
				$link = $item->link[0]->attributes();
				$result->link = $link->href;
				
				// add this item to return
				array_push($return, $result);
			}
			
			return (object)$return;
		}
		
		/**
		 * Sorts an array object based on a specific property in descending order
		 *
		 * @param	array	&$array	the array to sort
		 * @param	string	$prop	the property to sort by
		 */
		private function osort(&$array, $prop)
		{
			usort($array, function($a, $b) use ($prop) {
				return ($a->$prop < $b->$prop) ? 1 : -1;
			}); 
		}
		
		/**
		 * Resets current instance of Planet
		 */
		public function reset_instance()
		{
			$this->processed_feeds = array();
		}
		
		/**
		 * Validates a feed exists and checks if it is a supported version.
		 *
		 * @param	string	$url	The URL of the feed to check
		 * @return	boolean	TRUE on success
		 */
		public function valid_feed($url)
		{
			// check the URL is valid before proceeding
			if(preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url))
			{
				// parse the url into its components
				$parsed_url = parse_url($url);
				// open a socket to the url provided to check if the feed exists
				$fhandle = fsockopen($parsed_url['host'], (isset($parsed_url['port']) ? $parsed_url['port'] : 80));
				if($fhandle)
				{
					// close the open socket... don't need to keep that open
					fclose($fhandle);
					
					// get the feed so we can determine if it is a supported one
					$feed = $this->get_simplexml($url);
					if($feed->getName() === 'rss')
					{
						// feed is rss, lets check it is the supported version
						$rssAttr = $feed->attributes();
						
						if((string)$rssAttr->version === '2.0')
						{
							return TRUE;
						}
					}
					elseif($feed->getName() === 'feed')
					{
						// feed is atom... we support this!
						return TRUE;
					}
				}
			}
			
			return FALSE;
		}
	}