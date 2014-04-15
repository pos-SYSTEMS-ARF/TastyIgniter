<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reservation_module extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library('user');
		$this->load->model('Extensions_model');	    
		$this->load->model('Design_model');	    
	}

	public function index() {
			
		if (!file_exists(EXTPATH .'admin/views/reservation_module.php')) {
			show_404();
		}
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/reservation_module')) {
  			redirect('admin/permission');
		}
			
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');
		} else { 
			$data['alert'] = '';
		}		
				
		$extension = $this->Extensions_model->getExtension('module', 'reservation');

		$data['heading'] 			= 'Reservation';
		$data['sub_menu_save'] 		= 'Save';
		$data['sub_menu_back'] 		= $this->config->site_url('admin/extensions');
		$data['name'] 				= $extension['name'];

		if ($this->config->item('reservation_module')) {
			$result = $this->config->item('reservation_module');
		} else {
			$result = array();
		}

		if (isset($result['dimension_h'])) {
			$data['dimension_h'] = $result['dimension_h'];
		} else {
			$data['dimension_h'] = '360';
		}
		
		if ($this->input->post('modules')) {
			$result['modules'] = $this->input->post('modules');
		}

		$data['modules'] = array();
		if (!empty($result['modules'])) {
			foreach ($result['modules'] as $module) {
	
				$data['modules'][] = array(
					'layout_id'		=> $module['layout_id'],
					'position' 		=> $module['position'],
					'priority' 		=> $module['priority'],
					'status' 		=> $module['status']
				);
			}
		}

		$data['layouts'] = array();
		$results = $this->Design_model->getLayouts();
		foreach ($results as $result) {					
			$data['layouts'][] = array(
				'layout_id'		=> $result['layout_id'],
				'name'			=> $result['name']
			);
		}
		
		if ($this->input->post() && $this->_updateModule() === TRUE){
						
			redirect('admin/extensions');
		}
		
		$regions = array(
			'admin/header',
			'admin/footer'
		);
		
		$this->template->regions($regions);
		$this->template->load('admin/reservation_module', $data);
	}

	public function _updateModule() {
						
    	if (!$this->user->hasPermissions('modify', 'admin/reservation_module')) {
		
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have permission to modify!</p>');
			return TRUE;
    	
    	} else if ($this->validateForm() === TRUE) { 
			$update = array();
		
			$update['reservation_module']['dimension_h'] 	= $this->input->post('dimension_h');
			$update['reservation_module']['modules'] 		= $this->input->post('modules');

			if ($this->Settings_model->updateSettings('reservation', $update)) {
				$this->session->set_flashdata('alert', '<p class="success">Slideshow Module Updated Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Updated!</p>');				
			}
	
			return TRUE;
		}
	}

 	public function validateForm() {
		$this->form_validation->set_rules('name', 'Name', 'trim|required|min_length[2]|max_length[45]');

		foreach ($this->input->post('modules') as $key => $value) {
			$this->form_validation->set_rules('modules['.$key.'][layout_id]', 'Layout', 'trim|required');
			$this->form_validation->set_rules('modules['.$key.'][position]', 'Position', 'trim|required');
			$this->form_validation->set_rules('modules['.$key.'][priority]', 'Priority', 'trim|integer');
			$this->form_validation->set_rules('modules['.$key.'][status]', 'Status', 'trim|required|integer');
		}
		
		if ($this->form_validation->run() === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}