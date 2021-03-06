<?php
/**
 * Clan CMS
 *
 * An open source application for gaming clans
 *
 * @package		Clan CMS
 * @author		Xcel Gaming Development Team
 * @copyright	Copyright (c) 2010 - 2011, Xcel Gaming, Inc.
 * @license		http://www.xcelgaming.com/about/license/
 * @link		http://www.xcelgaming.com
 * @since		Version 0.5.0
 */

// ------------------------------------------------------------------------

/**
 * Clan CMS Account Controller
 *
 * @package		Clan CMS
 * @subpackage	Controllers
 * @category	Controllers
 * @author		Xcel Gaming Development Team
 * @link		http://www.xcelgaming.com
 */
class Account extends CI_Controller {
	
	/**
	 * Constructor
	 *
	 */	
	function __construct()
	{
		// Call the Controller constructor
		parent::__construct();
		
		// Load the Matches model
		$this->load->model('Matches_model', 'matches');
		
		// Load the Squads model
		$this->load->model('Squads_model', 'squads');
		
		// Load the Social model
		$this->load->model('Social_model', 'social');
		
		// Load the Users model
		$this->load->model('Users_model', 'users');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Index
	 *
	 * Edit's the user
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		// Check to see if the user is logged in
		if (!$this->user->logged_in())
		{
			// User is not logged in, redirect them
			redirect('account/login');
		}
		
		// Retrieve the user
		if(!$user = $this->users->get_user(array('user_id' => $this->session->userdata('user_id'))))
		{
			// User doesn't exist, redirect them
			redirect('account/login');
		}
		
		// Retrieve the forms
		$update_password = $this->input->post('update_password');
		$update_email = $this->input->post('update_email');
		$update_preferences = $this->input->post('update_preferences');
		
		// Check it update password has been posted
		if($update_password)
		{
			// Set form validation rules
			$this->form_validation->set_rules('password', 'Current Password', 'trim|required|callback__check_password');
			$this->form_validation->set_rules('new_password', 'New Password', 'trim|required|min_length[8]');
			$this->form_validation->set_rules('new_password_confirmation', 'Re-type New Password', 'trim|required|matches[new_password]');
		
			// Form validation passed, so continue
			if (!$this->form_validation->run() == FALSE)
			{
				// Set up the data
				$data = array (
					'user_password'	=> $this->encrypt->sha1($user->user_salt . $this->encrypt->sha1($this->input->post('new_password')))
				);
				
				// Update the user in the datbase
				$this->users->update_user($user->user_id, $data);
				
				// Redirect the user
				redirect('account/login');
			}
		}
		
		// Check it update email has been posted
		if($update_email)
		{
			// Set form validation rules
			$this->form_validation->set_rules('new_email', 'New Email', 'trim|required|valid_email|callback__check_email');
			$this->form_validation->set_rules('new_email_confirmation', 'Re-type New Email', 'trim|required|matches[new_email]');
		
			// Form validation passed, so continue
			if (!$this->form_validation->run() == FALSE)
			{
				// Set up the data
				$data = array (
					'user_email'	=> $this->input->post('new_email')
				);
			
				// Update the user in the database
				$this->users->update_user($user->user_id, $data);
				
				// Alert the user
				$this->session->set_flashdata('message', 'Your email has been updated!');
				
				// Redirect the user
				redirect('account');
			}
		}
		
		// Check it update preferences has been posted
		if($update_preferences)
		{
			// Set form validation rules
			$this->form_validation->set_rules('avatar', 'Avatar', 'trim');
			$this->form_validation->set_rules('timezone', 'Timezone', 'trim|required');
			$this->form_validation->set_rules('daylight_savings', 'Daylight Savings', 'trim|required');
		
			// Form validation passed, so continue
			if (!$this->form_validation->run() == FALSE)
			{
				// Check if avatar exists
				if($_FILES['avatar']['name'])
				{
					// Set up upload config
					$config['upload_path'] = UPLOAD . 'avatars';
					$config['allowed_types'] = 'gif|jpg|png|bmp';
					$config['encrypt_name'] = TRUE;
				
					// Avatar exists, load the upload library
					$this->load->library('upload', $config);
			
					// Check to see if the avatar was uploaded
					if(!$this->upload->do_upload('avatar'))
					{
						// Avatar wasn't uploaded, display errors
						$upload->errors = $this->upload->display_errors();
					}
					else
					{
						// Upload was successful, retrieve the data
						$data = array('upload_data' => $this->upload->data());
					}
				
					// Change the avatar
					$avatar = $data['upload_data']['file_name'];

					// Check if avatar exists
					if(file_exists(UPLOAD . 'avatars/' . $user->user_avatar))
					{
						// Avatar eixsts, remove the avatar
						unlink(UPLOAD . 'avatars/' . $user->user_avatar);
					}
				}
				else
				{
					// Keep avatar the same
					$avatar = $user->user_avatar;
				}
				
				// Set up the data
				$data = array (
					'user_timezone'				=> $this->input->post('timezone'),
					'user_daylight_savings'		=> $this->input->post('daylight_savings'),
					'user_avatar'				=> $avatar
				);
			
				// Update the user in the database
				$this->users->update_user($user->user_id, $data);
				
				// Alert the user
				$this->session->set_flashdata('message', 'Your preferences have been updated!');
				
				// Redirect the user
				redirect('account');
			}
		}
		
		// Create a reference to user
		$this->data->user =& $user;
		
		// Load the account view
		$this->load->view(THEME . 'account', $this->data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Profile
	 *
	 * Display's a user profile
	 *
	 * @access	public
	 * @return	void
	 */
	function profile()
	{
		// Retrieve the user slug
		$user_slug = $this->uri->segment(3, '');
		
		// Retrieve the user or show 404
		($user = $this->users->get_user(array('user_name' => $this->users->user_name($user_slug)))) or show_404();
		
		// Retrieve user joined, format timezone & assign user joined
		$user->joined = $this->ClanCMS->timezone($user->user_joined);
				
		// Retrieve the group
		if($group = $this->users->get_group(array('group_id' => $user->group_id)))
		{
			// Group exist's assign user group
			$user->group = $group->group_title;
		}
		else
		{
			// Group doesn't exist, assign user group
			$user->group = '';
		}
		
		// Retrieve members
		$members = $this->squads->get_members(array('user_id' => $user->user_id));
		
		// Check if members exist
		if($members)
		{
			// Members exist, loop through each member
			foreach($members as $member)
			{
				// Retrieve the squad
				$squad = $this->squads->get_squad(array('squad_id' => $member->squad_id));
				
				// Assign squad slug
				$member->squad_slug = $squad->squad_slug;
				
				// Assign squad title
				$member->squad_title = $squad->squad_title;
				
				// Retrieve the total number of member wins
				$member->total_wins = $this->squads->count_wins(array('member_id' => $member->member_id));
				
				// Retrieve the total number of member losses
				$member->total_losses = $this->squads->count_losses(array('member_id' => $member->member_id));
				
				// Retrieve the total number of member ties
				$member->total_ties = $this->squads->count_ties(array('member_id' => $member->member_id));
				
				// Retrieve the total number of member kills
				$member->total_kills = $this->squads->count_kills(array('member_id' => $member->member_id));
						
				// Retrieve the total number of member deaths
				$member->total_deaths = $this->squads->count_deaths(array('member_id' => $member->member_id));
				
				// Check if member total deaths equals 0
				if($member->total_deaths == 0)
				{
					// Member total deaths equals 0, format kills & deaths, assign member kd
					$member->kd = number_format(($member->total_kills / 1), 2, '.', '');
				}
				else
				{
					// Member total deaths doesn't equal 0, format kills & deaths, assign member kd
					$member->kd = number_format(($member->total_kills / $member->total_deaths), 2, '.', '');
				}
				
				// Retrieve the member squad
				$member->squad = $this->squads->get_squad(array('squad_id' => $member->squad_id));
				
				// Retrieve matches
				//$members->matches = $this->matches->get_players(array('member_id' => $user->user_id));
				
				// Retrieve the total matches
				$member->total_matches = $this->matches->count_matches(array('squad_id' => $member->squad_id));
				
				// Retrieve the total matches played
				$member->total_matches_played = $this->matches->count_players(array('member_id' => $member->member_id));
			
				// Check if total matches equals 0
				if($member->total_matches == 0)
				{
					// Format matches total matches played & total matches, assign matches percent
					$member->matches_percent = number_format((100 * ($member->total_matches_played / 1)), 0, '.', '');
				}
				else
				{
					// Format matches total matches played & total matches, assign matches percent
					$member->matches_percent = number_format((100 * ($member->total_matches_played / $member->total_matches)), 0, '.', '');
				}
			}
		}
		
		// Retrieve matches
		if($matches = $this->matches->get_matches())
		{
			// Assign recent matches
			$recent_matches = array();
				
			// Assign matches count
			$matches_count = 0;
			
			// Matches exist, loop through each match
			foreach($matches as $match)
			{
				// Retrieve the opponent
				$opponent = $this->matches->get_opponent(array('opponent_id' => $match->opponent_id));
				
				// Check if opponent exists
				if($opponent)
				{
					// Opponent exists, assign opponent & opponent slug
					$match->opponent = $opponent->opponent_title;
					$match->opponent_slug = $opponent->opponent_slug;
				}
				else
				{
					// Opponent doesn't exist, don't assign it
					$match->opponent = "";
					$match->opponent_slug = "";
				}
		
				// Retrieve the squad
				$squad = $this->squads->get_squad(array('squad_id' => $match->squad_id));
				
				// Assign match squad
				$match->squad = $squad->squad_title;
				
				// Assign match squad slug
				$match->squad_slug = $squad->squad_slug;
				
				// Check if members exist
				if($members)
				{
					// Members exist, loop through each member
					foreach($members as $member)
					{
						// Retrieve the player
						if($player = $this->matches->get_player(array('match_id' => $match->match_id, 'member_id' => $member->member_id)))
						{
							// Assign match kills
							$match->kills = $player->player_kills;
							
							// Assign match deaths
							$match->deaths = $player->player_deaths;
							
							// Check if match deaths equals 0
							if($match->deaths == 0)
							{
								// Match deaths equals 0, format kills & deaths, assign match kd
								$match->kd = number_format(($match->kills / 1), 2, '.', '');
							}
							else
							{
								// Match deaths doesn't equal 0, format kills & deaths, assign match kd
								$match->kd = number_format(($match->kills / $match->deaths), 2, '.', '');
							}
				
							// Check if matches count is less then 5
							if($matches_count < 5)
							{
								// Matches count it less then 5, player exists & assign recent matches
								$recent_matches = array_merge($recent_matches, array($match));
							}
							
							// Itterate matches count
							$matches_count =+ 1;
						}
					}
				}
			}
		}
		
		// Create a reference to user, members & matches
		$this->data->user =& $user;
		$this->data->members =& $members;
		$this->data->matches =& $recent_matches;
		$this->data->social =& $social;
		$social =& $this->social->get_social($this->uri->segment(3));
		// Load the profile view
		$this->load->view(THEME . 'profile', $this->data);
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Login
	 *
	 * Login's a user
	 *
	 * @access	public
	 * @return	void
	 */
	function login()
	{
		// Check to see if the user is logged in
		if ($this->user->logged_in())
		{
			// User is logged in, redirect them
			redirect('account');
		}
		
		// Retrieve the forms
		$redirect = $this->input->post('redirect');
		
		// Set form validation rules
		$this->form_validation->set_rules('username', 'User Name', 'trim|required|callback__check_login');
		$this->form_validation->set_rules('password', 'Password', 'trim|required');
		
		// Form validation passed, so continue
		if (!$this->form_validation->run() == FALSE)
		{
			// Login the user
			if(!$this->user->login($this->input->post('username'), $this->input->post('password'), $this->input->post('remember')))
			{
				// Login failed, alert the user
				$this->form_validation->set_message('login_failed', 'Invalid username or password');
			}
			else
			{
				// Redirect the user
				redirect($redirect);
			}
		}
		
		// Load the login view
		$this->load->view(THEME . 'login');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Logout
	 *
	 * Logout's a user
	 *
	 * @access	public
	 * @return	void
	 */
	function logout()
	{
		// Logout the user
		$this->user->logout();
		
		// Redirect the user
		redirect($this->session->userdata('previous'));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Activate
	 *
	 * Activate's a user account
	 *
	 * @access	public
	 * @return	void
	 */
	function activate()
	{
		// Check to see if the user is logged in
		if ($this->user->logged_in())
		{
			// User is logged in, redirect them
			redirect('account');
		}
		
		// Set up the data
		$data = array(
			'user_activation'	=> $this->uri->segment(3, '')
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// User exists, set up the data
			$data = array(
				'user_activation'	=> 1
			);
			
			// Update the user in the database
			$this->users->update_user($user->user_id, $data);
			
			// Assign page title
			$page->title = "Activation Success";
		
			// Assign page content
			$page->content = 'Your account has been activated!' . br(2) . 'You should now be able to ' . anchor('account/login', 'login') . ' and interact with the site.' . br(2) . 'Thanks for Registering!' . br() . CLAN_NAME . br() . anchor(site_url());
			
			// Create a reference to page
			$this->data->page =& $page;
		
			// Load the page view
			$this->load->view(THEME . 'page', $this->data);
		}
		else
		{
			// Assign page title
			$page->title = "Activation Error";
		
			// Assign page content
			$page->content = 'This is an invalid activation link!' . br(2) . 'Please try again. If you are still having issues please ' . safe_mailto($this->ClanCMS->get_setting('site_email'), 'contact a site administrator') . br(2) . 'Thanks for Registering!' . br() . CLAN_NAME . br() . anchor(site_url());
			
			// Create a reference to page
			$this->data->page =& $page;
			
			// Load the page view
			$this->load->view(THEME . 'page', $this->data);
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Forgot
	 *
	 * Retrieve's a user account
	 *
	 * @access	public
	 * @return	void
	 */
	function forgot()
	{		
		// Check to see if the user is logged in
		if ($this->user->logged_in())
		{
			// User is logged in, redirect them
			redirect('account');
		}
		
		// Set form validation rules
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|callback__check_forgot');

		// Form validation passed, so continue
		if (!$this->form_validation->run() == FALSE)
		{
			// Retrieve the user
			if($user = $this->users->get_user(array('user_email' => $this->input->post('email'))))
			{
				// Load the email library
				$this->load->library('email');
			
				// Set up the email data
				$this->email->from($this->ClanCMS->get_setting('site_email'), CLAN_NAME);
				$this->email->to($this->input->post('email'));
				$this->email->subject('Account information requested on ' . CLAN_NAME);
				$this->email->message("Hello " . $user->user_name . ",\n\nYou have requested your account information on " . CLAN_NAME . ". Here is your account information:\n\nUsername: " . $user->user_name . "\n\nIf you forgot your password please click the link below to reset your password:\n\n" . site_url() . "account/reset/" . $this->encrypt->sha1($user->user_password) . "/user/" . $user->user_id . "\n\nThanks for Registering!\n" . CLAN_NAME . "\n" . site_url());	

				// Email the user the activation code
				$this->email->send();
			
				// Assign page title
				$page->title = "Account Information Sent";
		
				// Assign page content
				$page->content = 'An email containing your username and instructions on changing your password has been sent to your email address.'. br(2) . 'Thanks for Registering!' . br() . CLAN_NAME . br() . anchor(site_url());
	
				// Create a reference to page
				$this->data->page =& $page;
				
				// Load the page view
				$this->load->view(THEME . 'page', $this->data);
			}
		}
		else
		{
		
			// Load the forgot view
			$this->load->view(THEME . 'forgot');
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Reset
	 *
	 * Reset's a user password
	 *
	 * @access	public
	 * @return	void
	 */
	function reset()
	{
		// Check to see if the user is logged in
		if ($this->user->logged_in())
		{
			// User is logged in, redirect them
			redirect('account');
		}
		
		// Retrieve the user
		if(!$user = $this->users->get_user(array('user_id' => $this->uri->segment(5, ''))))
		{
			// User is logged in, redirect them
			redirect('account');
		}
		
		// Assign user reset code
		$user->reset_code = $this->encrypt->sha1($user->user_password);
		
		// Check if reset code is valid
		if($user->reset_code == $this->uri->segment(3, ''))
		{
			// Set form validation rules
			$this->form_validation->set_rules('new_password', 'New Password', 'trim|required|min_length[8]');
			$this->form_validation->set_rules('new_password_confirmation', 'Re-type New Password', 'trim|required|matches[new_password]');
		
			// Form validation passed, so continue
			if (!$this->form_validation->run() == FALSE)
			{
				// Set up the data
				$data = array (
					'user_password'	=> $this->encrypt->sha1($user->user_salt . $this->encrypt->sha1($this->input->post('new_password')))
				);
				
				// Update the user in the datbase
				$this->users->update_user($user->user_id, $data);
				
				// Load the email library
				$this->load->library('email');
			
				// Set up the email data
				$this->email->from($this->ClanCMS->get_setting('site_email'), CLAN_NAME);
				$this->email->to($user->user_email);
				$this->email->subject('Account information updated on ' . CLAN_NAME);
				$this->email->message("Hello " . $user->user_name . ",\n\nYour account information has been updated on " . CLAN_NAME . ". Here is your account information:\n\nUsername: " . $user->user_name . "\nPassword: " . $this->input->post('new_password') . "\n\nThanks for Registering!\n" . CLAN_NAME . "\n" . site_url());	
				// Email the user the activation code
				$this->email->send();
		
				// Assign page title
				$page->title = 'Password Reset';
				
				// Assign page content
				$page->content = 'You have sucessfully reset your password!' . br(2) . 'An email containing your account infromation has been sent to you in case you forget again.' . br(2) . 'Thanks for Registering!' . br() . CLAN_NAME . br() . anchor(site_url());
			
				// Create a reference to page
				$this->data->page =& $page;
				
				// Load the page view
				$this->load->view(THEME . 'page', $this->data);
			}
			else
			{
				// Create a reference to user
				$this->data->user =& $user;
				
				// Load the forgot view
				$this->load->view(THEME . 'reset', $this->data);
			}
		}
		else
		{
			// Assign page title
			$page->title = 'Invalid Reset Code';
			
			// Assign page content
			$page->content = 'This is not a valid password reset code!';
			
			// Create a reference to page
			$this->data->page =& $page;
		
			// Load the page view
			$this->load->view(THEME . 'page', $this->data);
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Check Password
	 *
	 * Check's to see if the user's password is valid
	 *
	 * @access	private
	 * @param	string
	 * @return	bool
	 */
	function _check_password($user_password = '')
	{
		// Retrieve the user
		if(!$user = $this->users->get_user(array('user_id' => $this->session->userdata('user_id'))))
		{
			// User doesn't exist, alert the user & return FALSE
			$this->form_validation->set_message('_check_password', 'That is not your current password');
			return FALSE;
		}
		
		// Create the password to be checked
		$check_password = $this->encrypt->sha1($user->user_salt . $this->encrypt->sha1($this->input->post('password')));
		
		// Check if user password equals check password
		if($user->user_password == $check_password)
		{
			// User password equals check password, return TRUE
			return TRUE;
		}
		else
		{
			// User password doesn't equal check password, alert the user & return FALSE
			$this->form_validation->set_message('_check_password', 'That is not your current password');
			return FALSE;
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Check Email
	 *
	 * Check's to see if a email is unique
	 *
	 * @access	private
	 * @param	string
	 * @return	bool
	 */
	function _check_email($user_email = '')
	{
		// Set up the data
		$data = array(
			'user_email'	=> $user_email
		);
		
		// Retrieve the user
		if(!$user = $this->users->get_user($data))
		{
			// User doesn't exist, return TRUE
			return TRUE;
		}
		else
		{
			// User exists, alert the user & return FALSE
			$this->form_validation->set_message('_check_email', 'That email is already taken.');
			return FALSE;
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Check Login
	 *
	 * Check's to see if the user's login credentials are valid
	 *
	 * @access	private
	 * @param	string
	 * @return	bool
	 */
	function _check_login($user_name = '')
	{
		// Set up the data
		$data = array(
			'user_name'			=> $user_name
		);
		
		// Retrieve the user
		if(!$user = $this->users->get_user($data))
		{
			// User doesn't exist, alert the user & return FALSE
			$this->form_validation->set_message('_check_login', 'Invalid username or password');
			return FALSE;
		}
		
		// Set up the data
		$data = array(
			'user_name'			=> $user_name,
			'user_password'		=> $this->encrypt->sha1($user->user_salt . $this->encrypt->sha1($this->input->post('password')))
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// Check is user activation equals 1
			if($user->user_activation == 1)
			{
				// User exists, return TRUE
				return TRUE;
			}
			else
			{
				// User activation doesn't equal 1, alert the user & return FALSE
				$this->form_validation->set_message('_check_login', 'Your account has not been activated.');
				return FALSE;
			}
		}
		else
		{
			// User doesn't exist, alert the user & return FALSE
			$this->form_validation->set_message('_check_login', 'Invalid username or password.');
			return FALSE;
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Check Forgot
	 *
	 * Check's to see if a email exists
	 *
	 * @access	private
	 * @param	string
	 * @return	bool
	 */
	function _check_forgot($user_email = '')
	{
		// Set up the data
		$data = array(
			'user_email'	=> $user_email
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// User exists, return TRUE
			return TRUE;
		}
		else
		{
			// User doesn't exist, alert the user & return FALSE
			$this->form_validation->set_message('_check_forgot', 'There are no users with that email.');
			return FALSE;
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Media
	 *
	 * Social media connections
	 *
	 * @access	private
	 * @param	array
	 */		 
	 function media()
	 {
	 	// Retrieve the user slug
		$user_slug = $this->uri->segment(3);
		
		// Retrieve the user or show 404
		($user = $this->users->get_user(array('user_name' => $this->users->user_name($user_slug)))) or show_404();
		
		
	 	$this->load->model('Gallery_model', 'gallery');
	 	
	 	// Create a reference to user
		$this->data->user =& $user;
		
	 	$this->load->view(THEME . 'media', $this->data);
	 }
	 
	// --------------------------------------------------------------------
	
	/**
	 * Wall
	 *
	 * Interactive User Wall
	 *
	 * @access	private
	 * @param	array
	 */		 
	 function wall()
	 {
		
		// Load the typography library
		$this->load->library('typography');
		
		// Load the bbcode library
		$this->load->library('BBCode');
		
		// Load the text helper
		$this->load->helper('text');
		
	 	// Retrieve the user slug
		$user_slug = $this->uri->segment(3);
		
		// Retrieve the user or show 404
		($user = $this->users->get_user(array('user_name' => $this->users->user_name($user_slug)))) or show_404();

		// Retrieve our forms
		$status = $this->input->post('add_status');
		$comment = $this->input->post('add_comment');
		
		// Someone is updating the description
		if($status && $this->user->logged_in())
		{
			// Set form validation rules
			$this->form_validation->set_rules('status', 'Statuts', 'trim|required');
			
			// Form validation passed & checked if gallery allows comments, so continue
			if (!$this->form_validation->run() == FALSE)
			{
				// Set up our data
				$data = array (
					'status'	=> $this->input->post('status'),
					);
			
				// Insert the comment into the database
				$this->users->edit_status($data, $user->user_id);
				
				// Alert the user
				$this->session->set_flashdata('wall', 'Your status has been updated!');
				
				// Redirect the user
				redirect($this->session->userdata('previous'));
			}
		}
		
		// Check if add comment has been posted and check if the user is logged in
		if($comment && $this->user->logged_in() && $this->user->has_voice())
		{
			// Set form validation rules
			$this->form_validation->set_rules('comment', 'Comment', 'trim|required');
			
			// Form validation passed & checked if gallery allows comments, so continue
			if (!$this->form_validation->run() == FALSE)
			{	
				// Set up our data
				$data = array (
					'wall_owner_id'	=> $user->user_id,
					'commenter_id'	=> $this->session->userdata('user_id'),
					'comment'		=> $this->input->post('comment'),
					'comment_date'	=> mdate('%Y-%m-%d %H:%i:%s', now())
				);
			
				// Insert the comment into the database
				$this->users->insert_comment($data);
				
				// Alert the user
				$this->session->set_flashdata('wall', 'Your comment has been posted!');
				
				// Redirect
				redirect($this->session->userdata('previous'));
				
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
		$total_results = $this->users->count_comments(array('wall_owner_id' => $user->user_id));
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
		
		$comments = $this->users->get_comments($per_page, $offset, array('wall_owner_id' => $user->user_id));

		// Check if comments exist
		if($comments)
		{
			// Comments exist, loop through each comment
			foreach($comments as $comment)
			{
				// Retrieve the user
				if($commenter = $this->users->get_user(array('user_id' => $comment->commenter_id)))
				{
					// User exists, assign comment author & comment avatar
					$comment->author = $commenter->user_name;
					$comment->avatar = $commenter->user_avatar;
				}
				else
				{
					// User doesn't exist, assign comment author & comment avatar
					$comment->author = '';
					$comment->avatar = '';
				}
				
				// Format Timezone
				$comment->comment_date = $this->ClanCMS->timezone($comment->comment_date);
				
				// Format wall comment to bbcode
				$comment->comment = auto_link($this->typography->auto_typography($this->bbcode->to_html($comment->comment)), 'url');
				
			}			
		}
		
		if($user->status)
		{
			// Format status comment to bbcode
			$user->status_bb = auto_link($this->typography->auto_typography($this->bbcode->to_html($user->status)), 'url');
		}
		
		// Query tracking table
		if($user)
		{
			// set up data
			$data = array(
				'controller_name'	=>	$this->uri->segment(1),
				'controller_method'	=>	$this->uri->segment(2),
				'controller_item_id'	=>	$this->uri->segment(3),
				'user_id'			=>	$user->user_id,
				);
			
			// Check user against tracker
			$track = $this->tracker->check($data);
			
			if(!$track)
			{
				// Object is new to user
				$this->tracker->track($data);
			}

		}
		
		// Create references
		$this->data->comments =& $comments;
		$this->data->commenter =& $commenter;
		$this->data->pages =& $pages;
		$this->data->user =& $user;
		
		// Load the view
	 	$this->load->view(THEME . 'user_wall', $this->data);
	 }
	 
	 // ---------------------------------------------------------------
	 /**
	  * Wall Status
	  * Enables / Disables a user's wall
	  *
	  * @access	private
	  * @return	null
	  */
	 function wall_status($id)
	 {
	 	// Check to see if the user is logged in
		if (!$this->user->logged_in())
		{
			// User is not logged in, redirect them
			redirect('account/login');
		}
		
		// Retrieve the user
		if(!$user = $this->users->get_user(array('user_id' => $id)))
		{
			// User doesn't exist, redirect them
			redirect('account/login');
		}
		
	 	if((bool)$user->wall_enabled)
	 	{
	 		// Wall is enabled, disable it
	 		$this->users->edit_status(array('wall_enabled' => 0), $id);
	 		
	 		// Alert user
	 		$this->session->flashdata('wall', 'The wall has been closed.');
	 		
	 		// Redirect
	 		redirect($this->session->userdata('previous'));
	 	}
	 	else
	 	{
	 		// Wall is enabled, disable it
	 		$this->users->edit_status(array('wall_enabled' => 1), $id);
	 		
	 		// Alert user
	 		$this->session->flashdata('wall', 'The wall has been opened.');
	 		
	 		// Redirect
	 		redirect($this->session->userdata('previous'));
	 	}
	 }

	 // --------------------------------------------------------------------
	
	/**
	 * Mute
	 *
	 * Mutes a user
	 *
	 * @access	private
	 * @return	void
	 */
	function mute()
	{
		// Check to see if the user is logged in
		if (!$this->user->logged_in())
		{
			// User is not logged in, redirect them
			redirect('account/login');
		}
		
		// Set up the data
		$data = array(
			'user_name'	=> $this->uri->segment(3, '')
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// User exists, set up the data
			$data = array(
				'has_voice'	=> 0
				);
			
			// Update the user in the database
			$this->users->update_user($user->user_id, $data);
			
			// Alert admin
	 		$this->session->set_flashdata('message', 'You have muted ' . $user->user_name);
	 		
	 		// Redirect the administrator
			redirect($this->session->userdata('previous'));
		}
	}
	
	 // --------------------------------------------------------------------
	/**
	 * Unmute
	 *
	 * Unmutes a user
	 *
	 * @access	private
	 * @return	void
	 */
	function unmute()
	{
		// Check to see if the user is logged in
		if (!$this->user->logged_in())
		{
			// User is not logged in, redirect them
			redirect('account/login');
		}
		
		// Set up the data
		$data = array(
			'user_name'	=> $this->uri->segment(3, '')
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// User exists, set up the data
			$data = array(
				'has_voice'	=> 1
				);
			
			// Update the user in the database
			$this->users->update_user($user->user_id, $data);
			
			// Alert admin
	 		$this->session->set_flashdata('message', 'You have unmuted ' . $user->user_name);
	 		
	 		// Redirect the administrator
			redirect($this->session->userdata('previous'));
		}
	}
	

	 // --------------------------------------------------------------------
	
	/**
	 * Upload_no
	 *
	 * Revokes a user's right to upload
	 *
	 * @access	private
	 * @return	void
	 */
	function upload_no()
	{
		// Check to see if the user is logged in
		if (!$this->user->logged_in())
		{
			// User is not logged in, redirect them
			redirect('account/login');
		}
		
		// Set up the data
		$data = array(
			'user_name'	=> $this->uri->segment(3, '')
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// User exists, set up the data
			$data = array(
				'can_upload'	=> 0
				);
			
			// Update the user in the database
			$this->users->update_user($user->user_id, $data);
			
			// Alert admin
	 		$this->session->set_flashdata('message', 'You have revoked ' . $user->user_name . '\'s ability to upload');
	 		
	 		// Redirect the administrator
			redirect($this->session->userdata('previous'));
		}
	}
	
	 // --------------------------------------------------------------------
	
	/**
	 * Upload_yes
	 *
	 * Returns a user's ability to upload
	 *
	 * @access	private
	 * @return	void
	 */
	function upload_yes()
	{
		// Check to see if the user is logged in
		if (!$this->user->logged_in())
		{
			// User is not logged in, redirect them
			redirect('account/login');
		}
		
		// Set up the data
		$data = array(
			'user_name'	=> $this->uri->segment(3, '')
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// User exists, set up the data
			$data = array(
				'can_upload'	=> 1
				);
			
			// Update the user in the database
			$this->users->update_user($user->user_id, $data);
			
			// Alert admin
	 		$this->session->set_flashdata('message', 'You have enabled ' . $user->user_name . '\'s ability to upload');
	 		
	 		// Redirect the administrator
			redirect($this->session->userdata('previous'));
		}
	}
	
	 // --------------------------------------------------------------------
	/**
	 * Shout_no
	 *
	 * Restricts a user from the shoutbox
	 *
	 * @access	private
	 * @return	void
	 */
	function shout_no()
	{
		// Check to see if the user is logged in
		if (!$this->user->logged_in())
		{
			// User is not logged in, redirect them
			redirect('account/login');
		}
		
		// Set up the data
		$data = array(
			'user_name'	=> $this->uri->segment(3, '')
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// User exists, set up the data
			$data = array(
				'has_voice'	=> 1,
				'can_shout'	=> 0
				);
			
			// Update the user in the database
			$this->users->update_user($user->user_id, $data);
			
			// Alert admin
	 		$this->session->set_flashdata('message', 'You have unmuted ' . $user->user_name);
	 		
	 		// Redirect the administrator
			redirect($this->session->userdata('previous'));
		}
	}
	

	 // --------------------------------------------------------------------
	/**
	 * Shout_yes
	 *
	 * Restricts a user from the shoutbox
	 *
	 * @access	private
	 * @return	void
	 */
	function shout_yes()
	{
		// Check to see if the user is logged in
		if (!$this->user->logged_in())
		{
			// User is not logged in, redirect them
			redirect('account/login');
		}
		
		// Set up the data
		$data = array(
			'user_name'	=> $this->uri->segment(3, '')
		);
		
		// Retrieve the user
		if($user = $this->users->get_user($data))
		{
			// User exists, set up the data
			$data = array(
				'can_shout'	=> 1
				);
			
			// Update the user in the database
			$this->users->update_user($user->user_id, $data);
			
			// Alert admin
	 		$this->session->set_flashdata('message', 'You have revoked ' . $user->user_name . '\'s ability to upload');
	 		
	 		// Redirect the administrator
			redirect($this->session->userdata('previous'));
		}
	}

	 // --------------------------------------------------------------------	
	/**
	 * Delete Comment
	 *
	 * Deletes a wall comment from the databse
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
		if(!$comment = $this->users->get_comment($data))
		{
			// Comment doesn't exist, alert the administrator
			$this->session->set_flashdata('wall', 'The comment was not found!');
		
			// Redirect the user
			redirect($this->session->userdata('previous'));
		}
		
		// Check if the user is an administrator
		if(!$this->user->is_administrator() && $this->session->userdata('user_id') != $comment->user_id)
		{
			// User isn't an administrator or the comment user, alert the user
			$this->session->set_flashdata('wall', 'You are not allowed to delete this comment!');
			
			// Redirect the user
			redirect($this->session->userdata('previous'));
		}
				
		// Delete the article comment from the database
		$this->users->delete_comment($comment->comment_id, $data);
		
		// Alert the administrator
		$this->session->set_flashdata('wall', 'The comment was successfully deleted!');
				
		// Redirect the administrator
		redirect($this->session->userdata('previous'));
	}
}

/* End of file account.php */
/* Location: ./clancms/controllers/account.php */