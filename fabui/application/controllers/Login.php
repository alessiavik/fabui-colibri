<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 defined('BASEPATH') OR exit('No direct script access allowed');
 
 class Login extends FAB_Controller {
 	
	public function index(){
		
		//load configs
		$this->config->load('fabtotum');
		if(file_exists($this->config->item('autoinstall_file'))){
			redirect('install');
		}
		verify_keep_me_logged_in_cookie();
		
		$fabid = $this->input->get('fabid');
		$data['alert'] = $this->session->flashdata('alert');
		$data['fabid'] = $fabid == 'no' ? false : true;
		$this->load->helper('os_helper');
		$this->content = $this->load->view('login/login_form', $data, true);
		$this->addJsInLine($this->load->view('login/login_js', $data, true));
		$this->addJSFile('/assets/js/plugin/jquery-validate/jquery.validate.min.js');
		$this->addJSFile('/assets/js/plugin/moment/moment.min.js'); //moment
		$this->loginLayout();
	}
	
	//do login
	public function doLogin()
	{	
		if($this->input->method() == 'get') redirect('login'); //accessible only passing trough the login form
		$postData = $this->input->post();
		if(count($postData) == 0) redirect('login'); //if post data is empty
		
		$remember = false;
		if(isset($postData['remember'])){ // remember user?
			$remember= $postData['remember'] == 'on';
			unset($postData['remember']);
		}
		if(isset($postData['browser-date'])){
			$this->load->helpers('os_helper');
			//if(!isInternetAvaialable()){
			//setSystemDate($postData['browser-date']);
			//}
			unset($postData['browser-date']);
		}
		$postData['password'] = md5($postData['password']);
		//load libraries, models, helpers
		$this->load->model('User', 'user');
		$user = $this->user->get($postData, 1);
		
		if($user == false){ //if user doesn't exists
			//TO DO add flash message
			$this->session->mark_as_flash('alert');
			$this->session->set_flashdata('alert', array('type' => 'alert-danger', 'message'=> '<i class="fa fa-fw fa-warning"></i> '._("Please check your email or password") ));
			redirect('login?fabid=no');
		}
		
		if($remember == true){ //keep me logged in
			set_keep_me_looged_in_cookie($postData['email'], $postData['password']);
		}
		
		//update user last login column
		$last_login = date('Y-m-d H:i:s');
		$this->user->update($user['id'], array('last_login' => $last_login));
		
		$user['settings'] = json_decode($user['settings'], true);
		$user['last_login'] = $last_login;
		
		//load hardware settings
		$this->load->helper(array('fabtotum_helper', 'language_helper', 'myfabtotum_helper'));
		$hardwareSettings = loadSettings();
		
		
		if(!isset($user['settings']['locale'])) {
			if(isset($hardwareSettings['locale'])) $user['settings']['locale'] = $hardwareSettings['locale'];
			else $user['settings']['locale'] = 'en_US';
		}
		
		//create valid session for fabui
		//$this->session->loggedIn = true;
		//$this->session->user = $user;
		
		$this->session->set_userdata('user', $user);
		$this->session->set_userdata('loggedIn', true);
		
		if($user['role'] = 'administrator'){
			setLanguage($user['settings']['locale']);
		}
		
		reload_myfabtotum();
		//save hardware settings on session
		$this->session->set_userdata('settings', $hardwareSettings);
		redirect('#dashboard');
	}
	
	public function fabid()
	{
		$data = $this->input->post();
		$fabid = $data['fabid'];
		
		//load helpers
		$this->load->helper(array('fabtotum_helper', 'language_helper', 'myfabtotum_helper', 'os_helper'));
		
		if(isInternetAvaialable()){ //only if printer is connected to internet
			//1. check if fabid exists
			//2. get printers lists - if this printer exists in printers list i can login
			$fabidExists = fab_is_fabid_registered($fabid);
			if($fabidExists){
				$printers = fab_my_printers_list($fabid);
				if($printers){
					$iCanUse = i_can_use_this_printer($printers);
					if($iCanUse){ //if this printer is on my printers list i can access to it
						$this->load->model('User', 'user');
						$user = $this->user->getByFABID($fabid);
						$hardwareSettings = loadSettings();
						
						if($user){ //if exists a user with that fabid then login
							
							//update user last login column
							$last_login = date('Y-m-d H:i:s');
							$this->user->update($user['id'], array('last_login' => $last_login));
							
							$user['settings']  = json_decode($user['settings'], true);
							$user['settings']['fabid']['logged_in'] = true;
							$user['last_login'] = $last_login;
							
							if(!isset($user['settings']['locale'])) {
								if(isset($hardwareSettings['locale'])) $user['settings']['locale'] = $hardwareSettings['locale'];
								else $user['settings']['locale'] = 'en_US';
							}
							
							//create valid session for fabui
							//$this->session->loggedIn = true;
							//$this->session->user = $user;
							
							$this->session->set_userdata('user', $user);
							$this->session->set_userdata('loggedIn', true);
							
							if($user['role'] = 'administrator'){
								setLanguage($user['settings']['locale']);
							}
							
							reload_myfabtotum();
							//save hardware settings on session
							//$this->session->settings = $hardwareSettings;
							$this->session->set_userdata('settings', $hardwareSettings);
							redirect('#dashboard');
							
							
						}else{ //create a guest user
							
							$settings = array (
								'fabid' => array(
									'email' => $fabid,
									'logged_in' => true
								),
								'locale' => $hardwareSettings['locale']
							);
							
							$user['email']      = $fabid;
							$user['first_name'] = 'Guest';
							$user['last_name']  = 'Guest';
							$user['role']       = 'user';
							$user['session_id'] = $this->session->session_id;
							$user['last_login'] = date('Y-m-d H:i:s');
							$user['settings']   = json_encode($settings);
							
							$newUserID = $this->user->add($user);
							$user['id'] = $newUserID;
							
							//$this->session->loggedIn = true;
							//$this->session->user = $user;
							
							$this->session->set_userdata('user', $user);
							$this->session->set_userdata('loggedIn', true);
							
							reload_myfabtotum();
							//save hardware settings on session
							//$this->session->settings = $hardwareSettings;
							$this->session->set_userdata('settings', $hardwareSettings);
							redirect('#dashboard');
							
						}
					}
				}
				//no access to this printer with fabid
				$this->session->set_flashdata('alert', array('type' => 'alert-danger', 'message'=> '<i class="fa fa-fw fa-warning"></i> '._("You don't have the permission for this printer") ));
				redirect('login/?fabid=no');
			}
			//fabid was not recognized
			$this->session->set_flashdata('alert', array('type' => 'alert-danger', 'message'=> '<i class="fa fa-fw fa-warning"></i> '._("FABID doesn't exists") ));
			redirect('login/?fabid=no');
		
		}
		//printer not connected to internet
		$this->session->set_flashdata('alert', array('type' => 'alert-danger', 'message'=> '<i class="fa fa-fw fa-warning"></i> '._("Internet connection not available. Sign-in to your local access and then connect to internet") ));
		redirect('login/?fabid=no');
		
	}
	
	//log out
	public function out()
	{
		delete_cookie("fabkml", $this->input->server('HTTP_HOST'));
		//destroy session and redirect to login
		$this->session->set_userdata('loggedIn', false);
		$this->session->unset_userdata('user');
		$this->session->unset_userdata('settings');
		redirect('login/?fabid=no');
	}
	
	/**
	 * add new account page
	 */
	public function newAccount()
	{
		$this->load->helper('fabtotum_helper');
		$this->load->helper('os_helper');
		$this->content = $this->load->view('login/register_form', '', true);
		$this->addJSFile('/assets/js/plugin/jquery-validate/jquery.validate.min.js');
		$this->addJsInLine($this->load->view('login/register_js', '', true));
		$this->loginLayout('register');
	}
	
	/**
	 * craete new account (post from register form)
	 */
	public function doNewAccount()
	{
		if($this->input->method() == 'get') redirect('login'); //accessible only passing trough the login form
		$postData = $this->input->post();
		if($postData['terms'] != 'on') redirect('login'); //You must agree with Terms and Conditions
		if($postData['passwordConfirm'] != $postData['password']) redirect('login'); //passwords needs to be equals
		//unset unuseful data
		unset($postData['passwordConfirm']);
		unset($postData['terms']);
		$postData['session_id'] = $this->session->session_id;
		$postData['settings'] = '{}';
		$postData['role']     = 'user';
		$postData['password'] = md5($postData['password']);
		//load libraries, models, helpers
		$this->load->model('User', 'user');
		$newUserID = $this->user->add($postData);
		$this->session->set_flashdata('alert', array('type' => 'alert-success', 'message'=> '<i class="fa fa-fw fa-check"></i> '._("New user created successfully") ));
		redirect('login/?fabid=no');
		
	}
	
	/**
	 * reset user password
	 */
	public function resetPassword($token)
	{
		$this->load->model('User', 'user');
		
		$user = $this->user->getByToken($token);
		if($user)
		{
			$this->load->helper('fabtotum_helper');
			$this->load->helper('os_helper');
			
			$data = array();
			$data['user'] = $user;
			$data['token'] = $token;
			
			$this->content = $this->load->view('login/reset_form', $data, true);
			$this->addJsInLine($this->load->view('login/reset_js', $data, true));
			$this->addJSFile('/assets/js/plugin/jquery-validate/jquery.validate.min.js');
			$this->loginLayout('reset');
		}
		else
		{
			redirect('login?fabid=no');
		}
	}
	
	public function doReset()
	{
		$this->load->model('User', 'user');
		
		$token = $this->input->post('token');
		$new_password = $this->input->post('password');
		
		$user = $this->user->getByToken($token);
		if($user)
		{
			$user_settings = json_decode($user['settings'], true);
			$user_settings['token'] = '';
			
			$data_update['password'] = md5($new_password);
			$data_update['settings'] = json_encode($user_settings);
			
			$this->user->update($user['id'], $data_update);
			
			$_SESSION['new_reset'] = true;
		}
		redirect('login?fabid=no');
	}
	
	public function sendResetEmail()
	{
		$email = $this->input->post('email');
		
		$this->load->helper('fabtotum_helper');
		$this->load->model('User', 'user');

		$response = array();
		$response['user'] = false;
		$response['sent'] = false;
		
		$user = $this->user->getByEmail($email);
		if($user)
		{
			$response['user'] = true;
		}
		
		$sent = send_password_reset($email);
		$response['sent'] = $sent;
		
		$this->output->set_content_type('application/json')->set_output(json_encode($response));
	}
	
 }
 
?>
