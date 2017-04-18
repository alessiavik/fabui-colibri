<?php
/**
 * 
 * @author Daniel Kesler
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 defined('BASEPATH') OR exit('No direct script access allowed');
 
class Std extends FAB_Controller {

	protected $runningTask = false;

	function __construct(){
		parent::__construct();
		if(!$this->input->is_cli_request()){
			$this->load->model('Tasks', 'tasks');
			$this->runningTask = $this->tasks->getRunning();
		}
	}

	public function index(){
		
	}
	
	public function storePosition($task_type = '')
	{
		$data = $this->input->post();
		$this->load->helper('fabtotum_helper');
		
		$result = false;
		if($task_type)
		{
			savePosition($data['x'], $data['y'], $data['z'], $task_type);
			$result = true;
		}
		
		$this->output->set_content_type('application/json')->set_output(json_encode(array("result"=>$result, "data"=>$data)));
	}

	/**
	 * @task_type type (additive, subtractive)
	 * @return json object for dataTables plugin
	 * get all files of task_type
	 */
	public function getFiles($task_type = '')
	{
		//load libraries, models, helpers
		$this->load->model('Files', 'files');
		$files = $this->files->getForCreate( $task_type );
		$aaData = $this->dataTableFormat($files);
		$this->output->set_content_type('application/json')->set_output(json_encode(array('aaData' => $aaData)));
	}
	
	/**
	 * @task_type type (additive, subtractive)
	 * @return json object for dataTables plugin
	 * get recent files of task_type
	 */
	public function getRecentFiles($task_type = '')
	{
		//load libraries, models, helpers
		$this->load->model('Tasks', 'tasks');
		$files  = $this->tasks->getLastCreations($task_type);
		$aaData = $this->dataTableFormat($files);
		$this->output->set_content_type('application/json')->set_output(json_encode(array('aaData' => $aaData)));
	}

	/**
	 * @param $data (list)
	 * return array data for dataTable pluguin
	 */
	private function dataTableFormat($data)
	{
		//load text helper
		$this->load->helper('text_helper');
		$aaData = array();
		foreach($data as $file){ 
			$td0 = '<label class="radio"><input type="radio" name="create-file" value="'.$file['id_file'].'"><i></i></label>';
			$td1 = '<i class="fa fa-cube hidden-xs"></i> <span class="hidden-xs">'.$file['client_name'].'</span><span class="hidden-md hidden-sm hidden-lg">'.ellipsize($file['orig_name'], 35).'</span>';
			$td2 = '<i class="fa fa-cubes"></i> <span class="hidden-xs">'.$file['name'].'</span><span class="hidden-md hidden-sm hidden-lg">'.ellipsize($file['name'], 35).'</span>';
			$td3 = $file['id_file'];
			$td4 = $file['id_object'];
			$aaData[] = array($td0, $td1, $td2, $td3, $td4);
		}
		return $aaData;
	}
	
	public function saveQualityRating($taskID, $rating)
	{
		$this->load->model('Tasks', 'tasks');
		
		$result = false;
		
		$task = $this->tasks->get($taskID, 1);
		if($task)
		{
			$attributes = json_decode(utf8_encode(preg_replace('!\\r?\\n!', "<br>", $task['attributes'])), true);
			$attributes['rating'] = $rating;
			
			$json = json_encode( $attributes );
			
			
			$taskData = array(
				'attributes' => $json
			);
			$this->tasks->update($taskID, $taskData);
			
			$result = true;
		}
		
		$this->output->set_content_type('application/json')->set_output(json_encode(array($result)));
	}
	
	public function safetyCheck($feature, $bed_in_place)
	{
		$this->load->helper('fabtotum_helper');
		$result = safetyCheck($feature, ($bed_in_place == "yes") );
		$this->output->set_content_type('application/json')->set_output(json_encode($result));
	}

	public function email_test()
	{
		$this->load->helper('fabtotum_helper');
		$this->load->model('User', 'user');
		$this->load->database();
		$this->load->library(array('session', 'parser'));
		$this->load->helper(array('url', 'language', 'update'));
		
		$email = 'kesler.daniel@gmail.com';
		
		//~ $result = send_via_noreply('kesler.daniel@gmail.com', 'Daniel', 'Kessler', 'FABTotum email test', 'Some kind of <strong>HTML capable</strong> content');
		//$user =  $this->user->getByEmail($email); //array();
		
		/*$token = md5($user['id'] . '-' . $email . '-' . time());
		
		$user_settings = json_decode($user['settings'], 1);
		$user_settings['token'] = $token;
		
		$data_update['settings'] = json_encode($user_settings);
		
		//$this->user->update( $user['id'], $data_update);
		
		//$user =  $this->user->getByEmail($email);
		$result['token'] = $token;
		$result['email'] = $email;
		$result['site_url'] = site_url();
		$result['first_name'] = $user['first_name'];
		$result['last_name'] = $user['last_name'];*/
		
		$result = send_password_reset($email);
		
		$this->output->set_content_type('application/json')->set_output(json_encode($result));
	}

 }
 
?>

