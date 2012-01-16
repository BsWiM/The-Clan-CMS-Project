<?php
/**
 * Clan CMS
 *
 * An open source application for gaming clans
 *
 * @package		Clan CMS
 * @author		co[dezyne]
 * @copyright		Copyright (c) 2011 - 2012 co[dezyne]
 * @license		http://codezyne.me
 * @link			http://codezyne.me
 * @since			Version 0.6.1
 */

// ------------------------------------------------------------------------

/**
 * Clan CMS Gallery Controller
 *
 * @package			Clan CMS
 * @subpackage		Controllers
 * @category			Controllers
* @author				co[dezyne]
 * @link				http://codezyne.me
 */
class Gallery extends CI_Controller {
	
	/**
	 * Constructor
	 *
	 */	
	function __construct()
	{
		// Call the Controller constructor
		parent::__construct();
		
		// Load the Gallery model
		$this->load->model('Gallery_model', 'gallery');
		
		// Load Download helper
		$this->load->helper('download');
		
		// Load the typography library
		$this->load->library('typography');
		
		// Load the bbcode library
		$this->load->library('BBCode');
		
		// Load the text helper
		$this->load->helper('text');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Index
	 *
	 * Display's the gallery
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		// Video Constraints
		$ns = array
		(
		        'content' => 'http://purl.org/rss/1.0/modules/content/',
		        'wfw' => 'http://wellformedweb.org/CommentAPI/',
		        'dc' => 'http://purl.org/dc/elements/1.1/'
		);
		$video = array();
		$blog_url = 'http://gdata.youtube.com/feeds/api/users/bluexephos/uploads';
		$rawFeed = file_get_contents($blog_url);
		$data['sxml'] = new SimpleXmlElement($rawFeed);

		// Display all uploaded images
		$data['images'] = $this->gallery->get_images();
		
		// Retrieve our forms
		$gallery_upload = $this->input->post('upload');
		
		// Check it update gallery has been posted
		if($gallery_upload)
		{
			// Set form validation rules
			$this->form_validation->set_rules('title', 'title', 'trim|required');
			$this->form_validation->set_rules('userfile', 'file|required');
		
			// Form validation passed, so continue
			if (!$this->form_validation->run() == FALSE)
			{
				$this->gallery->add_image();
			}
			
		}

		$this->load->view(THEME . 'gallery', $data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Image View
	 *
	 * Display's an uploaded image & it's comments
	 *
	 * @access	public
	 * @return	void
	 */
	 
	function image()
	{ 
		
		// Retrieve the image if it exists or redirect to gallery
		$image = $this->gallery->get_image(array('image_slug' => $this->uri->segment(3, '')));
		
		if(!$image)
		{
			$this->session->set_flashdata('message', 'The image you requested was not found');
			redirect('gallery');
		}
		
		// Format Timezone
		$image->date = $this->ClanCMS->timezone($image->date);
		
		// Aspect ratio
		if($image->width && $image->height)
		{
			// Determine GCD
			function GCD($a, $b) 
			{  
				while ($b != 0)  
				{
					$remainder = $a % $b;  
					$a = $b;  
					$b = $remainder;  
				}  
				return abs ($a);  
			}  
			
			// Compute AR
			$a = $image->width;
			$b = $image->height; 
			$gcd = GCD($a, $b);  
			$a = $a/$gcd;  
			$b = $b/$gcd;  
			$image->ratio = $a . ":" . $b; 
		}
		
		
		// Retrieve Uploader's avatar
		$user = $this->users->get_user(array('user_name' => $image->uploader));
		$image->avatar = $user->user_avatar;
		$image->uploader_id = $user->user_id;
		
		// Retrieve uploader's group
		if($group = $this->users->get_group(array('group_id' => $user->group_id)))
		{
			// Group exist's assign user group
			$image->group = $group->group_title;
		}
		else
		{
			// Group doesn't exist, assign user group
			$image->group = '';
		}	
		
		// Retrieve our forms
		$desc = $this->input->post('add_desc');
		$comment = $this->input->post('add_comment');
		
		// Someone is updating the description
		if($desc && $this->user->logged_in())
		{
			// Set form validation rules
			$this->form_validation->set_rules('desc', 'Description', 'trim|required');
			
			// Form validation passed & checked if gallery allows comments, so continue
			if (!$this->form_validation->run() == FALSE)
			{	
				// Set up our data
				$data = array (
					'desc'		=> $this->input->post('desc'),
					);
			
				// Insert the comment into the database
				$this->gallery->edit_desc($data, $image->gallery_id);
				
				// Alert the user
				$this->session->set_flashdata('message', 'Your description has been edited!');
				
				// Redirect the user
				redirect('gallery/image/' . $image->image_slug);
			}
		}
		
		// Check if add comment has been posted and check if the user is logged in
		if($comment && $this->user->logged_in())
		{
			// Set form validation rules
			$this->form_validation->set_rules('comment', 'Comment', 'trim|required');
			
			// Form validation passed & checked if gallery allows comments, so continue
			if (!$this->form_validation->run() == FALSE)
			{	
				// Set up our data
				$data = array (
					'gallery_id'		=> $image->gallery_id,
					'user_id'			=> $this->session->userdata('user_id'),
					'comment_title'		=> $this->input->post('comment'),
					'comment_date'	=> mdate('%Y-%m-%d %H:%i:%s', now())
				);
			
				// Insert the comment into the database
				$this->gallery->insert_comment($data);
				
				// Alert the user
				$this->session->set_flashdata('message', 'Your comment has been posted!');
				
				// Redirect the user
				redirect('gallery/image/' . $image->image_slug);
			}
		}		
		
		// Retrieve the current page
		$page = $this->uri->segment(5, '');
	
		// Check if page exists
		if($page == '')
		{
			// Page doesn't exist, assign it
			$page = 1;
		}
	
		//Set up the variables
		$per_page = 15;
		$total_results = $this->gallery->count_comments(array('gallery_id' => $image->gallery_id));
		$offset = ($page - 1) * $per_page;
		$pages->total_pages = 0;
		
		// Create the pages
		for($i = 1; $i < ($total_results / $per_page) + 1; $i++)
		{
			// Itterate pages
			$pages->total_pages++;
		}
				
		// Check if there are no results
		if($total_results == 0)
		{
			// Assign total pages
			$pages->total_pages = 1;
		}
		
		// Set up pages
		$pages->current_page = $page;
		$pages->pages_left = 9;
		$pages->first = (bool) ($pages->current_page > 5);
		$pages->previous = (bool) ($pages->current_page > '1');
		$pages->next = (bool) ($pages->current_page != $pages->total_pages);
		$pages->before = array();
		$pages->after = array();
		
		// Check if the current page is towards the end
		if(($pages->current_page + 5) < $pages->total_pages)
		{
			// Current page is not towards the end, assign start
			$start = $pages->current_page - 4;
		}
		else
		{
			// Current page is towards the end, assign start
			$start = $pages->current_page - $pages->pages_left + ($pages->total_pages - $pages->current_page);
		}
		
		// Assign end
		$end = $pages->current_page + 1;
		
		// Loop through pages before the current page
		for($page = $start; ($page < $pages->current_page); $page++)
		{
			// Check if the page is vaild
			if($page > 0)
			{
				// Page is valid, add it the pages before, increment pages left
				$pages->before = array_merge($pages->before, array($page));
				$pages->pages_left--;
			}
		}
		
		// Loop through pages after the current page
		for($page = $end; ($pages->pages_left > 0 && $page <= $pages->total_pages); $page++)
		{
			// Add the page to pages after, increment pages left
			$pages->after = array_merge($pages->after, array($page));
			$pages->pages_left--;
		}
		
		// Set up pages
		$pages->last = (bool) (($pages->total_pages - 5) > $pages->current_page);
		
		$comments = $this->gallery->get_comments($per_page, $offset, array('gallery_id' => $image->gallery_id));
			
		// Check if comments exist
		if($comments)
		{
			// Comments exist, loop through each comment
			foreach($comments as $comment)
			{
				// Retrieve the user
				if($user = $this->users->get_user(array('user_id' => $comment->user_id)))
				{
					// User exists, assign comment author & comment avatar
					$comment->author = $user->user_name;
					$comment->avatar = $user->user_avatar;
				}
				else
				{
					// User doesn't exist, assign comment author & comment avatar
					$comment->author = '';
					$comment->avatar = '';
				}
				
				// Format and assign the comment date
				$comment->date = $this->ClanCMS->timezone($comment->comment_date);
				
				// Do not count uploader comments
				if($comment->author == $image->uploader)
				{
					// Assign 0 for count
					$comment->count = 0;
				}
				else
				{
					// Give a count point
					$comment->count = 1;
				}
				
				// Create array to hold comment points
				$count[] = $comment->count;
			}
			
		}
		
		// Assign comment points
		$image->comments = array_sum($count);
		
		// Fetch active user
		$user = $this->users->get_user(array('user_id' => $this->session->userdata('user_id')));
		
		// Block user for gaining self-views
		if($user && $user->user_name != $image->uploader)
		{
			// Hot update view count
			$views = ($image->views + 1);
			$image->views = $views;
			$this->db->where('gallery_id', $image->gallery_id)
				->update('gallery', array('views' => $image->views));
		}
		
		
		// Create references
		$this->data->image =& $image;
		$this->data->comments =& $comments;
		$this->data->pages =& $pages;
		$this->data->user =& $user;
		
		// Load the gallery view
		$this->load->view(THEME . 'image', $this->data);
	}
	
	// -------------------------------------------------------------------
	
	/**
	 * Downloader
	 *
	 * @access	public
	 */
	 
	 function download()
	 {
	 	// Check to see if user is logged in
		if(!$this->user->logged_in())
		{
			// User is not logged, redirect them
			redirect('account/login');
		}
		
	 	// Retrive image
	 	$file = $this->gallery->get_image(array('image_slug' => $this->uri->segment(3)));
	 	
	 	// Set image location
	 	$path = file_get_contents(UPLOAD . 'gallery/' .$file->image);
	 	
	 	if($path)
	 	{
	 		// Set image name
		 	$name = $file->image;
		 	
		 	$user = $this->users->get_user(array('user_id' => $this->session->userdata('user_id')));

			// Block user for gaining self-downloads
			if($user->user_name != $file->uploader)
			{
			 	// Update download counts
			 	$downloads = ($file->downloads + 1);
				$file->downloads = $downloads;
				$this->db->update('gallery', array('downloads' => $file->downloads));
			}
			
			// Send download request
		 	force_download($name, $path);
		 }else {
		 	
		 	$this->session->set_flashdata('message', $file->image .' could not be downloaded.  Check if the file still exists.');
		 	
		 	}
	 }
	 
	// --------------------------------------------------------------------
	/** User Gallery
	 *
	 * Display's user's Gallery
	 *
	 *@access public
	 *@param array
	 *
	 */
	 function user()
	 {
	 	// Retrieve the user slug
		$user_slug = $this->uri->segment(3, '');
		
		// Retrieve the user or show 404
		($user = $this->users->get_user(array('user_name' => $this->users->user_name($user_slug)))) or show_404();
		
		$images = $this->gallery->user_images($user->user_name);
		
		if($images)
		{
			// Iterate through objects to make arrays
			foreach($images as $image)
			{
				// Retrieve user joined, format timezone & assign user joined
				$image->date = $this->ClanCMS->timezone($image->date);
				
				$views[] = $image->views;
				
				$comments[] = $image->comments;
				
				$favors[] = $image->favors;
				
				$downloads[] = $image->downloads;
				
				$size[] = $image->size;
			}
			
			// Count and sum all elements
			$stats->uploads = $this->gallery->count_images(array('uploader' => $user->user_name));
			$stats->views = array_sum($views);
			$stats->comments = array_sum($comments);
			$stats->favors = array_sum($favors);
			$stats->downloads = array_sum($downloads);
			$stats->disk_use = round((array_sum($size) / 1024), 2) . ' MB';
		}
		
		// Create refrences
		$this->data->stats =& $stats;
		$this->data->images =& $images;
		$this->data->user =& $user;
		
		// Load view
		$this->load->view(THEME . 'media', $this->data);
	 }
	 
	// --------------------------------------------------------------------
	/**
	 * Delete Image
	 *
	 *  Removes an image
	 *
	 * @access	public
	 * @return	array
	 */
	function del_image()
	{ 
		// Set up the data
		$data = array(
			'gallery_id'	=>	$this->uri->segment(3, '')
		);

		// Retrieve the header by file_name
		if(!$image = $this->gallery->get_image($data))
		{
			// Image doesn't exist, alert the administrator
			$this->session->set_flashdata('message', 'The image was not found!');
		
			// Redirect the administrator
			redirect($this->session->userdata('previous'));
		}

		// Delete the header from gallery & thumbs folders
		unlink(UPLOAD . 'gallery/' .$image->image);
		unlink(UPLOAD . 'gallery/thumbs/' . $image->image);
		
		
		// Sumbit image for deletion
		$this->gallery->delete_image($image->gallery_id, $data);
		
		// Alert the administrator
		$this->session->set_flashdata('message', 'The image <span class="bold">' . $image->title . '</span> was successfully deleted!');
				
		// Redirect the administrator
		redirect('gallery');
	
	}	
	
	// -------------------------------------------------------------------------
	/**
	 * Delete Comment
	 *
	 * Delete's a gallery comment from the databse
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_comment()
	{
		// Set up our data
		$data = array(
			'comment_id'	=>	$this->uri->segment(3)
		);
		
		// Retrieve the article comment
		if(!$comment = $this->gallery->get_comment($data))
		{
			// Comment doesn't exist, alert the administrator
			$this->session->set_flashdata('message', 'The comment was not found!');
		
			// Redirect the user
			redirect($this->session->userdata('previous'));
		}
		
		// Check if the user is an administrator
		if(!$this->user->is_administrator() && $this->session->userdata('user_id') != $comment->user_id)
		{
			// User isn't an administrator or the comment user, alert the user
			$this->session->set_flashdata('message', 'You are not allowed to delete this comment!');
			
			// Redirect the user
			redirect($this->session->userdata('previous'));
		}
				
		// Delete the article comment from the database
		$this->gallery->delete_comment($comment->comment_id, $data);
		
		// Alert the administrator
		$this->session->set_flashdata('message', 'The comment was successfully deleted!');
				
		// Redirect the administrator
		redirect($this->session->userdata('previous'));
	}

/* End of file gallery.php */
/* Location: ./clancms/controllers/gallery.php */
}