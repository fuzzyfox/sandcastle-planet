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
	 * Planet Model
	 * 
	 * Provides the needed functions to make running a basic planet a piece of
	 * cake. More specifically it deals with the database side of things.
	 *
	 * When talking about the "database result object" this refers to the
	 * CodeIgniter database result object, i.e. `$query->result();`
	 *
	 * @package 	SandCastle
	 * @subpackage 	Models
	 * @category 	Planet
	 * @author 		William Duyck <wduyck@gmail.com>
	 * @copyright 	Copyleft 2012, William Duyck
	 * @license 	https://www.mozilla.org/MPL/2.0/ MPL v2.0
	 * @link 		http://www.wduyck.com/ wduyck.com
	 *
	 * @todo Planet Features
	 * 
	 */
	class Planet_model extends CI_Model
	{
		/**
		 * Constructor
		 *
		 * Initialises the model and laods the database connection.
		 */
		public function __construct()
		{
			parent::__construct();
			$this->load->database();
		}
		
		/**
		 * Add feed
		 *
		 * Adds a feed url to the database
		 *
		 * @param	string	$userEmail	the user to associate the feed with's email
		 * @param	string	$feedURL	the url of the feed to add
		 * @return	boolean	TRUE on succes
		 */
		public function add_feed($email, $feed_url)
		{
			return ($this->db->insert('feed', array(
				'email'		=> $email,
				'feed_url'	=> rtrim($feed_url, '/')
			))) ? TRUE : FALSE;
		}
		
		/**
		 * Delete feed
		 *
		 * Removes a feed from the database
		 *
		 * @param	string	$feed_url the url of the feed to delete
		 * @return	boolean	TRUE on success
		 */
		public function delete_feed($feed_url)
		{
			return ($this->db->delete('feed', array(
				'feed_url' => $feed_url
			))) ? TRUE : FALSE;
		}
		
		/**
		 * Get feed
		 *
		 * Returns an individual feed from the database
		 *
		 * @param	string	$feedURL	the url of the feed to get
		 * @return	mixed	database result object on success, FALSE on fail
		 */
		public function get_feed($feed_url)
		{
			$feed = $this->db->get_where('feed', array(
				'feed_url' => $feed_url
			));
			
			return ($feed->num_rows() === 1) ? $feed->result() : FALSE;
		}
		
		/**
		 * Get feeds
		 *
		 * Returns multiple feeds details (owner, url) in the database, defaults
		 * to all
		 *
		 * Usage:
		 * 		// all feeds
		 * 		$feeds = $this->planet_model->get_feeds();
		 *
		 * 		// selected feeds
		 *		$feeds = $this->planet_model->get_feeds(array(
		 *			'http://www.example.com/feed.rss',
		 * 			'http://www.anotherexample.com/feed.rss'
		 *		));
		 *
		 * @param	array	$feeds	an array of feeds to get
		 * @return	mixed	database result object on success, FALSE on fail
		 */
		public function get_feeds($feeds = null)
		{
			// specific feeds requested
			if(is_array($feeds))
			{
				$this->db->where('feed_url', $feeds[0]);
				for($i = 1, $j = count($feeds); $i < $j; $i++)
				{
					$this->db->or_where('feed_url', $feeds[$i]);
				}
				$feeds = $this->db->get('feed');
				return ($feeds->num_rows() > 0) ? $feeds->result() : FALSE;
			}
			
			// all feeds
			$feeds = $this->db->get('feed');
			return ($feeds->num_rows() > 0) ? $feeds->result() : FALSE;
		}
	}