<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 * Clan CMS
 *
 * An open source application for gaming clans
 *
 * @package		Clan CMS
 * @author		Xcel Gaming Development Team
 * @copyright		Copyright (c) 2010 - 2011, Xcel Gaming, Inc.
 * @license		http://www.xcelgaming.com/about/license/
 * @link			http://www.xcelgaming.com
 * @since			Version 0.6.0
 */

// ------------------------------------------------------------------------

/**
 * Clan CMS Site Stats Widget
 *
 * @package		Clan CMS
 * @subpackage	Widgets
 * @category		Widgets
 * @author		Xcel Gaming Development Team
 * @link			http://www.xcelgaming.com
 */
class Site_stats_widget extends Widget {

	// Widget information
	public $title = 'Site Stats';
	public $description = "Display's the site's stats.";
	public $author = 'Xcel Gaming';
	public $link = 'http://www.xcelgaming.com';
	public $version = '1.1.0';
	public $requires = '0.6.2';
	public $compatible = '0.6.2';
	
	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		// Call the Widget constructor
		parent::__construct();
		
		// Create a instance to CI
		$CI =& get_instance();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Index
	 *
	 * Display's the stats
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		// Load the Articles model
		$this->CI->load->model('Articles_model', 'articles');
		
		// Retrieve the total number of published articles
		$this->data->total_articles_published = $this->CI->articles->count_articles(array('article_status' => 1));
		
		// Retrieve the total number of draft articles
		$this->data->total_articles_drafts = $this->CI->articles->count_articles(array('article_status' => 0));
		
		// Load the Matches model
		$this->CI->load->model('Matches_model', 'matches');
		
		// Retrieve the total number of matches
		$this->data->total_matches = $this->CI->matches->count_matches();
		
		// Retrieve the total number of opponents
		$this->data->total_opponents = $this->CI->matches->count_opponents();
		
		// Load the Squads model
		$this->CI->load->model('Squads_model', 'squads');
		
		// Retrieve the total number of squads
		$this->data->total_squads = $this->CI->squads->count_squads();
				
		// Retrieve the total number of users
		$this->data->total_users = $this->CI->users->count_users();
		
		// Retrieve the total number of default usergroups
		$this->data->total_usergroups_default = $this->CI->users->count_groups(array('group_default' => 1));
		
		// Retrieve the total number of custom usergroups
		$this->data->total_usergroups_custom = $this->CI->users->count_groups(array('group_default' => 0));
		
		// Load the Polls model
		$this->CI->load->model('Polls_model', 'polls');
		
		// Retrieve the total number of polls
		$this->data->total_polls = $this->CI->polls->count_polls();
		
		// Load the Gallery model
		$this->CI->load->model('Gallery_model', 'gallery');
		
		// Retrieve the total number of images and videos
		$this->data->total_images = $this->CI->gallery->count_images();
		$this->data->total_videos = $this->CI->gallery->count_videos();
		
		// Load the Shouts model
		$this->CI->load->model('Shouts_model', 'shouts');
		
		// Retrieve total number of shouts
		$this->data->total_shouts = $this->CI->shouts->count_shouts();
		
		// Load the Events Model
		$this->CI->load->model('Events_model', 'events');
		
		// Retrieve total number of events
		$this->data->total_events = $this->CI->events->count_events();
		
		// Assign the widget info
		$widget->title = 'Site Stats';
		$widget->content = $this->CI->load->view('widgets/site_stats', $this->data, TRUE);
		$widget->tabs = array();
			
		// Load the widget view
		$this->CI->load->view(WIDGET . 'widget', $widget);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Uninstall
	 *
	 * Uninstall's the widget
	 *
	 * @access	public
	 * @return	void
	 */
	function uninstall()
	{
		// Assign files
		$files = array(
			APPPATH . 'views/widgets/site_stats.php'
		);
		
		// Loop through the files
		foreach($files as $file)
		{
			// Check if the file exists
			if(file_exists($file))
			{
				// Delete the file
				unlink($file);
			}
		}
		
		// Delete the widget
		unlink(__FILE__);
	}
}
	
/* End of file site_stats_widget.php */
/* Location: ./clancms/widgets/site_stats_widget.php */