<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 defined('BASEPATH') OR exit('No direct script access allowed');
 
 class Scan extends FAB_Controller {
 	
 	function __construct()
 	{
 		parent::__construct();
 		if(!$this->input->is_cli_request()){ //avoid this form command line
 			$this->load->model('Tasks', 'tasks');
 			//$this->tasks->truncate();
 			$this->runningTask = $this->tasks->getRunning();
 		}
 	}
 	
 	public function test()
 	{
		$this->config->load('fabtotum');
		$settingsPath = $this->config->item('settings_path');
		$scanconfiguration = json_decode( file_get_contents($settingsPath . 'scan_presets.json'), true);
		
		foreach($scanconfiguration['mode'] as $mode)
		{
			echo '<p>'.$mode['info']['description'].'</p>';
		}
		//~ echo file_get_contents($settingsPath . 'scan_presets.json');
	}
 	
	public function index()
	{
		//load libraries, helpers, model
		$this->load->library('smart');
		$this->load->library('Camera', null, 'camera');
		
		$this->config->load('fabtotum');
		$settingsPath = $this->config->item('settings_path');
		$scanconfiguration = json_decode(file_get_contents($settingsPath . 'scan_presets.json'), true);
		$res_map = $this->camera->getResolutionMapping();
		
		foreach($scanconfiguration['quality'] as $label => $values)
		{
			$res_tuple = array();
			$res_label = $scanconfiguration['quality'][$label]['values']['resolution'];
			$scanconfiguration['quality'][$label]['values']['resolution'] = $res_map[$res_label];
		}
		
		
		//data
		$data = array();
		$data['runningTask'] = $this->runningTask;
		if(!$this->runningTask){
			$data['scanModes'] = $scanconfiguration['mode'];
			$data['scanQualities'] = $scanconfiguration['quality'];
			$data['probingQualities'] = $scanconfiguration['probe_quality'];
			
			// TODO: if needed
			//$data['modesForDropdown'] = $this->scanconfiguration->getModesForDropdown();
			
			$data['step1']  = $this->load->view('scan/wizard/step1', $data, true );
			$data['step2']  = $this->load->view('scan/wizard/step2', null, true );
			$data['step3']  = $this->load->view('scan/wizard/step3', null, true );
		}
		
		$data['step4']  = $this->load->view('scan/wizard/step4', $data, true );
		$data['wizard'] = $this->load->view('scan/wizard/main',  $data, true );
		//main page widget
		$widgetOptions = array(
				'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
				'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-scan';
		$widget->header = array('icon' => 'icon-fab-scan', "title" => "<h2>Scan</h2>");
		$widget->body   = array('content' => $this->load->view('scan/main_widget', $data, true ), 'class'=>'fuelux');
		$this->content = $widget->print_html(true);
		
		$this->addJSFile('/assets/js/plugin/fuelux/wizard/wizard.min.old.js'); //wizard
		$this->addJSFile('/assets/js/plugin/jquery-validate/jquery.validate.min.js'); //validator
		$this->addJSFile('/assets/js/plugin/cropper/cropper.js'); //validator
		$this->addCssFile('/assets/js/plugin/cropper/cropper.min.css');
		$this->addCssFile('/assets/css/scan/style.css');
		$this->addJsFile('/assets/js/controllers/scan/scan.js');
		$this->addJsInLine($this->load->view('scan/js', $data, true),true);
		if($this->runningTask) $this->addJsInLine('<script type="text/javascript">initScanPage(true);</script>');
		else $this->addJsInLine('<script type="text/javascript">initScanPage(false);</script>');
		
		$this->view(); 
	}
	
	/**
	 * @param int scan mode id
	 */
	public function getScanModeInfo($scanModeId)
	{
		//load libraries, helpers, model
		$this->config->load('fabtotum');
		$settingsPath = $this->config->item('settings_path');
		$scanconfiguration = json_decode(file_get_contents($settingsPath . 'scan_presets.json'), true);
		
		$scan = $scanconfiguration['mode'][$scanModeId];
		$this->output->set_content_type('application/json')->set_output($scan['values']);
	}
	
	/**
	 * @param int scan mode id
	 */
	public function getScanModeSettings($scanModeId)
	{
		//load models, libraries, helpers
		$this->config->load('fabtotum');
		$settingsPath = $this->config->item('settings_path');
		$scanconfiguration = json_decode(file_get_contents($settingsPath . 'scan_presets.json'), true);
		
		$this->load->model('Objects', 'objects');
		$this->load->helper('form');
		$scanMode = $scanconfiguration['mode'][$scanModeId];
		$methodName = $scanModeId.'Settings';
		$data['label'] = $scanMode['info']['name'];
		$data['suggestedObjectName'] = $scanMode['info']['name'].' - Object name';
		$data['suggestedFileName'] = $scanMode['info']['name'].' - File name';
		$data['objectsForDropdown'] = $this->objects->getObjectsorDropdown();
		$data['content'] = $this->$methodName();
		$this->load->view('scan/settings/default', $data);
	}
	
	/**
	 * return settings form for sweep mode
	 */
	public function sweepSettings()
	{
		//TODO
		return $this->load->view('scan/settings/sweep', null, true);
	}
	
	/**
	 * return settings form for rotating mode
	 */
	public function rotatingSettings(){
		//TODO
		//load models, libraries, helpers
		return $this->load->view('scan/settings/rotating', null, true);
	}
	
	/**
	 * return settings form for probing mode
	 */
	public function probingSettings(){
		//TODO
		return $this->load->view('scan/settings/probing', null, true);
	}
	
	/**
	 * return settings form for photogrammetry mode
	 */
	public function photogrammetrySettings(){
		//TODO
		return $this->load->view('scan/settings/photogrammetry', null, true);
	}
	
	/**
	 * return get ready instructions
	 */
	public function getReady($scanModeId)
	{
		//load models, libraries, helpers
		$methodName = $scanModeId.'Instructions';
		$data['content'] = method_exists ($this, $methodName) ? $this->$methodName() : '';
		$this->load->view('scan/instructions/default', $data);
	}
	
	/**
	 * return instructions for rotating mode
	 */
	public function rotatingInstructions()
	{
		//TODO
		return $this->load->view('scan/instructions/rotating', null, true);
	}
	
	/**
	 * return instructions for sweep mode
	 */
	public function sweepInstructions()
	{
		//TODO
		return $this->load->view('scan/instructions/sweep', null, true);
	}
	
	/**
	 * return instructions for probing mode
	 */
	public function probingInstructions()
	{
		//TODO
		return $this->load->view('scan/instructions/probing', null, true);
	}
	
	/**
	 * return instructions for photogrammetry mode
	 */
	public function photogrammetryInstructions()
	{
		//TODO
		return $this->load->view('scan/instructions/photogrammetry', null, true);
	}
	
	/**
	 * prepare scan macro
	 */
	public function prepareScan()
	{
		//load helpers
		$this->load->helpers('fabtotum_helper');
		$checkPreScanResult = doMacro('check_pre_scan');
		$this->output->set_content_type('application/json')->set_output(json_encode(array('response' => $checkPreScanResult['response'], 'trace' => $checkPreScanResult['trace'])));
	}
	
	/**
	 * start scan
	 */
	public function startScan($scanModeId)
	{
		//load models, libraries, helpers
		$methodName = $scanModeId.'Start';
		$params = $this->input->post();
		if(method_exists ($this, $methodName)) $this->$methodName($params);
	}
	
	/**
	 * start rotating mode task
	 */
	public function rotatingStart($params)
	{
		//load helpers
		$this->load->helpers('fabtotum_helper');
		
		//preparing printer
		$checkPreScanResult = doMacro('check_pre_scan');
		if($checkPreScanResult['response'] == false){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'trace' => $checkPreScanResult['trace'])));
			return;
		}
		
		$rScanResult = doMacro('start_rotary_scan');
		if($rScanResult['response'] == false){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'trace' => $rScanResult['trace'])));
			return;
		}
		//create db record
		$this->load->model('Tasks', 'tasks');
		$taskData = array(
			'user'       => $this->session->user['id'],
			'controller' => 'create',
			'type'       => 'scan',
			'status'     => 'running',
			'start_date' => date('Y-m-d H:i:s')
		);
		$taskId   = $this->tasks->add($taskData);
		//starting scan
		$scanArgs = array(
			'-T' => $taskId,
			'-U' => $this->session->user['id'],
			'-s' => $params['slices'],
			'-i' => $params['iso'],
			//'-b' => $params['start'],
			//'-e' => $params['end'],
			'-W' => $params['width'],
			'-H' => $params['height'],
			'-d' => '/tmp/fabui',
			'-F' => $params['file_name'],
			'-C' => 'v1'
		);
		if($params['object_mode'] == 'new') $scanArgs['-N'] = $params['object'];
		if($params['object_mode'] == 'add') $scanArgs['-O'] = $params['object'];
		
		startScan('r_scan.py', $scanArgs);
		$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => true, 'params'=>$scanArgs)));
		
		
	}
	
	/**
	 * start sweep mode scan task
	 */
	public function sweepStart($params)
	{
		//load helpers
		$this->load->helpers('fabtotum_helper');
		//preparing printer
		$checkPreScanResult = doMacro('check_pre_scan');
		if($checkPreScanResult['response'] == false){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'trace' => $checkPreScanResult['trace'])));
			return;
		}
		$sScanResult = doMacro('start_sweep_scan');
		if($sScanResult['response'] == false){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'trace' => $sScanResult['trace'])));
			return;
		}
		/*
		parser.add_argument("-T", "--task-id",     help=_("Task ID."),              default=0)
		parser.add_argument("-U", "--user-id",     help=_("User ID. (future use)"), default=0)
		parser.add_argument("-O", "--object-id",   help=_("Object ID."),            default=0)
		parser.add_argument("-N", "--object-name", help=_("Object name."),          default='')
		parser.add_argument("-F", "--file-name",   help=_("File name."),            default='')
		
		parser.add_argument("-d", "--dest",     help=_("Destination folder."),     default=destination )
		parser.add_argument("-s", "--slices",   help=_("Number of slices."),       default=100)
		parser.add_argument("-i", "--iso",      help=_("ISO."),                    default=400)
		parser.add_argument("-p", "--power",    help=_("Scan laser power 0-255."), default=230)
		parser.add_argument("-W", "--width",    help=_("Image width in pixels."),  default=1920)
		parser.add_argument("-H", "--height",   help=_("Image height in pixels"),  default=1080)
		parser.add_argument("-b", "--begin",    help=_("Begin scanning from X."),  default=0)
		parser.add_argument("-e", "--end",      help=_("End scanning at X."),      default=360)
		parser.add_argument("-z", "--z-offset", help=_("Z offset."),               default=0)
		parser.add_argument("-y", "--y-offset", help=_("Y offset."),               default=0)
		parser.add_argument("-a", "--a-offset", help=_("A offset/rotation."),      default=0)
		parser.add_argument("-o", "--output",   help=_("Output point cloud file."),default=os.path.join(destination, 'cloud.asc'))
		*/
		//create db record
		$this->load->model('Tasks', 'tasks');
		$taskData = array(
				'user'       => $this->session->user['id'],
				'controller' => 'create',
				'type'       => 'scan',
				'status'     => 'running',
				'start_date' => date('Y-m-d H:i:s')
		);
		$taskId   = $this->tasks->add($taskData);
		//starting scan
		$scanArgs = array(
			'-T' => $taskId,
			'-U' => $this->session->user['id'],
			'-s' => $params['slices'],
			'-i' => $params['iso'],
			'-b' => 10,
			'-e' => 20,
			//'-b' => $params['start'],
			//'-e' => $params['end'],
			'-W' => $params['width'],
			'-H' => $params['height'],
			//'-W' => 1296,
			//'-H' => 972,
			'-d' => '/tmp/fabui',
			'-F' => $params['file_name'],
			'-C' => 'v1'
		);
		if($params['object_mode'] == 'new') $scanArgs['-N'] = $params['object'];
		if($params['object_mode'] == 'add') $scanArgs['-O'] = $params['object'];
		startScan('s_scan.py', $scanArgs);
		$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => true, 'params'=>$scanArgs)));
	}
	
	/**
	 * start probing mode scan task
	 */
	public function probingStart($params)
	{
		//load helpers
		$this->load->helpers('fabtotum_helper');
		
		//preparing printer
		$checkPreScanResult = doMacro('check_pre_scan');
		if($checkPreScanResult['response'] == false){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'trace' => $checkPreScanResult['trace'])));
			return;
		}
		
		$sScanResult = doMacro('start_probe_scan');
		if($sScanResult['response'] == false){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'trace' => $sScanResult['trace'])));
			return;
		}
		
		//create db record
		$this->load->model('Tasks', 'tasks');
		$taskData = array(
				'user'       => $this->session->user['id'],
				'controller' => 'create',
				'type'       => 'scan',
				'status'     => 'running',
				'start_date' => date('Y-m-d H:i:s')
		);
		$taskId   = $this->tasks->add($taskData);
		//~ $taskId = 12;
		//starting scan
		$scanArgs = array();
		$scanArgs = array(
			'-T' => $taskId,
			'-U' => $this->session->user['id'],
			'-d' => '/tmp/fabui',
			'-n' => $params['density'],
			//~ //'-x' => $params['x1'],
			'-x' => 107,
			//~ //'-y' => $params['y1'],
			'-y' => 117,
			//~ //'-i' => $params['x2'],
			'-i' => 110,
			//~ //'-j' => $params['y2'],
			'-j' => 120,
			'-z' => $params['safe_z'],
			'-t' => $params['threshold'],
			'-F' => $params['file_name']
		);
		if($params['object_mode'] == 'new') $scanArgs['-N'] = $params['object'];
		if($params['object_mode'] == 'add') $scanArgs['-O'] = $params['object'];
				
		startScan('p_scan.py', $scanArgs);
		$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => true, 'params'=>$scanArgs)));
	}
	
	/**
	 * 
	 */
	public function action($action)
	{
		$this->load->helper('fabtotum_helper');
		$action($value);
		$this->output->set_content_type('application/json')->set_output(json_encode(array(true)));
	}
 }
 
?>
