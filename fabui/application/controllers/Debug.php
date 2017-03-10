<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 defined('BASEPATH') OR exit('No direct script access allowed');
 
 class Debug extends FAB_Controller {
 	
	public function index()
	{
		//load libraries, helpers, model, config
		$this->load->library('smart');
		
		$widgetOptions = array(
				'sortable' => false, 'fullscreenbutton' => true,'refreshbutton' => false,'togglebutton' => false,
				'deletebutton' => false, 'editbutton' => false, 'colorbutton' => false, 'collapsed' => false
		);
		
		$headerToolbar = '
			<ul class="nav nav-tabs pull-right">
				<li class="active"><a data-toggle="tab" href="#monitor-tab"> task_monitor.json</a></li>
				<li><a data-toggle="tab" href="#temperatures-tab"> temperatures.json</a></li>
				<li><a data-toggle="tab" href="#notify-tab"> notify.json</a></li>
				<li><a data-toggle="tab" href="#trace-tab"> trace</a></li>
				<li><a data-toggle="tab" href="#json-rpc-tab"> json-rpc</a></li>
			</ul>';
		$data = array();
		$widget = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'debug-widget';
		$widget->header = array('icon' => 'fa-bug', "title" => "<h2>Debug panel</h2>", 'toolbar'=>$headerToolbar);
		$widget->body   = array('content' => $this->load->view('debug/index', null, true ), 'class'=>'');
		$this->content = $widget->print_html(true);
		
		$this->addJSFile('/assets/js/plugin/jsonview/jquery.jsonview.min.js'); //datatable */
		$this->addCssFile('/assets/js/plugin/jsonview/jquery.jsonview.min.css'); //datatable */
		$this->addJsInLine($this->load->view('debug/js', null, true));
		$this->addCSSInLine('<style>hr{margin-top:0px;margin-bottom:0px;}#trace{overflow:auto; height:500px;}</style>'); 
		
		$this->debugLayout();
	}
	
	public function test()
	{
		$this->load->helpers('plugin_helper');
		$this->load->helpers('upload_helper');
		$this->config->load('upload');
		#$tmp = allowedTypesToDropzoneAcceptedFiles( $this->config->item('allowed_types') );
		$tmp = getFileActionList('drl');
		var_dump($tmp);
	}
	
	public function gcodeviewer()
	{
		$this->content = $this->load->view('filemanager/file/preview/index', null, true );
    //~ <script src="lib/modernizr.custom.93389.js"></script>
    //~ <script src="lib/jquery-1.7.1.min.js"></script>
    //~ <script src="lib/bootstrap-modal.js"></script>
    //~ <script src="lib/sugar-1.2.4.min.js"></script>
    //~ <script src="lib/three.js"></script>
    //~ <script src="lib/TrackballControls.js"></script>

    //~ <script src="js/ShaderExtras.js"></script>
    //~ <script src="js/postprocessing/EffectComposer.js"></script>
    //~ <script src="js/postprocessing/MaskPass.js"></script>
    //~ <script src="js/postprocessing/RenderPass.js"></script>
    //~ <script src="js/postprocessing/ShaderPass.js"></script>
    //~ <script src="js/postprocessing/BloomPass.js"></script>

    //~ <script src="js/Stats.js"></script>
    //~ <script src="js/DAT.GUI.min.js"></script>
    //~ <!-- Custom code -->
    //~ <script type="text/javascript" src="gcode_model.js"></script>
    //~ <script type="text/javascript" src="gcode_parser.js"></script>
    //~ <script type="text/javascript" src="gcode_interpreter.js"></script>
    //~ <script type="text/javascript" src="gcode_importer.js"></script>
    //~ <script type="text/javascript" src="gcode_renderer.js"></script>
		$this->addJSFile('/assets/js/plugin/gcode-viewer/lib/modernizr.custom.93389.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/lib/jquery-1.7.1.min.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/lib/sugar-1.2.4.min.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/lib/three.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/lib/TrackballControls.js');
		
		$this->addJSFile('/assets/js/plugin/gcode-viewer/js/ShaderExtras.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/js/postprocessing/EffectComposer.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/js/postprocessing/MaskPass.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/js/postprocessing/RenderPass.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/js/postprocessing/ShaderPass.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/js/postprocessing/BloomPass.js');
		
		$this->addJSFile('/assets/js/plugin/gcode-viewer/js/Stats.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/js/dat.gui.min.js');

		
		$this->addJSFile('/assets/js/plugin/gcode-viewer/gcode_model.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/gcode_parser.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/gcode_interpreter.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/gcode_importer.js');
		$this->addJSFile('/assets/js/plugin/gcode-viewer/gcode_renderer.js');
		
		$this->addCssFile('/assets/js/plugin/gcode-viewer/lib/bootstrap.min.css');
		
		$this->addJsInLine($this->load->view('filemanager/file/preview/renderer_js', null, true));
		$this->addJsInLine($this->load->view('filemanager/file/preview/ui_js', null, true));
		
		$this->view();
	}
	
	public function jsonrpc($method)
	{
		//load jog factory class
		$init['url'] = 'https://my.fabtotum.com/myfabtotum/default/call/jsonrpc2';
		$this->load->library('JsonRPC', $init, 'jsonRPC');
		
		$this->load->helpers('os_helper');
		$this->load->helpers('fabtotum_helper');
		
		$params['serialno']   = getSerialNumber();
		$params['mac']        = getMACAddres();
		$params['apiversion'] = 1;
		
		
		$result_codes[200]  = 'SERVICE_SUCCESS';
		$result_codes[401]  = 'SERVICE_UNAUTHORIZED';
		$result_codes[403]  = 'SERVICE_FORBIDDEN';
		$result_codes[500]  = 'SERVICE_SERVER_ERROR';
		$result_codes[1001] = 'SERVICE_INVALID_PARAMETER';
		$result_codes[1002] = 'SERVICE_ALREADY_REGISTERED';
		
		switch($method){
			case 'fab_register_printer':
				$params['fabid']      = 'fabtest@fabtotum.com';
				break;
			case 'fab_info_update':
				$macroResponse = doMacro('version');
				if($macroResponse['response']){
					$versions = $macroResponse['reply'];
				}
				$head       = getInstalledHeadInfo();
				$interfaces = getInterfaces();
				
				$data['name']      = getUnitName();
				$data['model']     = $versions['production']['batch'];
				$data['head']      = $head['name'];
				$data['fwversion'] = $versions['firmware']['version'];
				$data['iplan']     = $interfaces['wlan0']['ipv4_address'];
				$params['data']    = $data;
				break;
			case 'fab_polling':
				//$params['state'] = "BUSY";
				break;
		}
		$result = $this->jsonRPC->execute($method, $params);
		if(isset($result['status_code'])){
			$result['status_description'] = $result_codes[$result['status_code']];
		}
		$this->output->set_content_type('application/json')->set_output(json_encode(array('method'=>$method, 'params'=>$params, 'result'=>$result)));
	}
 }
 
?>