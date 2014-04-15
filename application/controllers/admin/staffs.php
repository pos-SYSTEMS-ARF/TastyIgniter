<?php
class Staffs extends CI_Controller {

	public function __construct() {
		parent::__construct(); //  calls the constructor
		$this->load->library('user');
		$this->load->library('pagination');
		$this->load->model('Staffs_model');
		$this->load->model('Locations_model'); // load the locations model
		$this->load->model('Staff_groups_model');
	}

	public function index() {
			
		if (!file_exists(APPPATH .'views/admin/staffs.php')) {
			show_404();
		}
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/staffs')) {
  			redirect('admin/permission');
		}
		
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');  // retrieve session flashdata variable if available
		} else {
			$data['alert'] = '';
		}

		$filter = array();
		if ($this->input->get('page')) {
			$filter['page'] = (int) $this->input->get('page');
		} else {
			$filter['page'] = 1;
		}
		
		if ($this->config->item('page_limit')) {
			$filter['limit'] = $this->config->item('page_limit');
		}
				
		$data['heading'] 			= 'Staffs';
		$data['sub_menu_add'] 		= 'Add new staff';
		$data['sub_menu_delete'] 	= 'Delete';
		$data['text_empty'] 		= 'There are no staffs available.';

		$data['staffs'] = array();				
		$results = $this->Staffs_model->getList($filter);
		foreach ($results as $result) {
			
			$data['staffs'][] = array(
				'staff_id' 			=> $result['staff_id'],
				'staff_name' 		=> $result['staff_name'],
				'staff_email' 		=> $result['staff_email'],
				'staff_group_name' 	=> $result['staff_group_name'],
				'staff_location' 	=> $result['location_name'],
				'date_added' 		=> mdate('%d %M %y', strtotime($result['date_added'])),
				'staff_status' 		=> $result['staff_status'],
				'edit' 				=> $this->config->site_url('admin/staffs/edit?id=' . $result['staff_id'])
			);
		}
				
		$config['base_url'] 		= $this->config->site_url('admin/staffs');
		$config['total_rows'] 		= $this->Staffs_model->record_count();
		$config['per_page'] 		= $filter['limit'];
		$config['num_links'] 		= round($config['total_rows'] / $config['per_page']);
		
		$this->pagination->initialize($config);

		$data['pagination'] = array(
			'info'		=> $this->pagination->create_infos(),
			'links'		=> $this->pagination->create_links()
		);

		if ($this->input->post('delete') && $this->_deleteStaff() === TRUE) {
			
			redirect('admin/staffs');  			
		}	

		$regions = array(
			'admin/header',
			'admin/footer'
		);
		
		$this->template->regions($regions);
		$this->template->load('admin/staffs', $data);
	}

	public function edit() {
		
		if (!file_exists(APPPATH .'views/admin/staffs_edit.php')) {
			show_404();
		}
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/staffs')) {
  			redirect('admin/permission');
		}
		
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');  // retrieve session flashdata variable if available
		} else {
			$data['alert'] = '';
		}

		//check if customer_id is set in uri string
		if (is_numeric($this->input->get('id'))) {
			$staff_id = (int)$this->input->get('id');
			$data['action']	= $this->config->site_url('admin/staffs/edit?id='. $staff_id);
		} else {
		    $staff_id = 0;
			$data['action']	= $this->config->site_url('admin/staffs/edit');
		}

		$staff_info = $this->Staffs_model->getStaff($staff_id);
		
		$data['heading'] 			= 'Staff - '. $staff_info['staff_name'];
		$data['sub_menu_save'] 		= 'Save';
		$data['sub_menu_back'] 		= $this->config->site_url('admin/staffs');

		$data['staff_name'] 		= $staff_info['staff_name'];
		$data['staff_email'] 		= $staff_info['staff_email'];
		$data['staff_group_id'] 	= $staff_info['staff_group_id'];
		$data['staff_location'] 	= $staff_info['staff_location'];
		$data['staff_status'] 		= $staff_info['staff_status'];

		$result = $this->Staffs_model->getStaffUser($staff_id);
		$data['username'] 			= $result['username'];
		//$data['staff_group'] 		= $result['staff_group'];

		$data['staff_groups'] = array();
		$results = $this->Staff_groups_model->getStaffGroups();
		foreach ($results as $result) {					
			$data['staff_groups'][] = array(
				'staff_group_id'	=>	$result['staff_group_id'],
				'staff_group_name'	=>	$result['staff_group_name']
			);
		}

		$data['locations'] = array();
		$results = $this->Locations_model->getLocations();
		foreach ($results as $result) {					
			$data['locations'][] = array(
				'location_id'	=>	$result['location_id'],
				'location_name'	=>	$result['location_name'],
			);
		}
	
		if ($this->input->post() && $this->_addStaff() === TRUE) {
		
			redirect('admin/staffs');
		}

		if ($this->input->post() && $this->_updateStaff($data['staff_email'], $data['username']) === TRUE) {
	
			redirect('admin/staffs');

		}
		
		$regions = array(
			'admin/header',
			'admin/footer'
		);
		
		$this->template->regions($regions);
		$this->template->load('admin/staffs_edit', $data);
	}

	public function _addStaff() {
									
    	if (!$this->user->hasPermissions('modify', 'admin/staffs')) {
		
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have the right permission to edit!</p>');
  			return TRUE;
    	
    	} else if ( ! $this->input->get('id') AND $this->validateForm() === TRUE) { 
			$add = array();

			$add['staff_name']			= $this->input->post('staff_name');
			$add['staff_email']			= $this->input->post('staff_email');
			$add['username']			= $this->input->post('username');
			$add['password']			= $this->input->post('password');
			$add['staff_group']			= $this->input->post('staff_group');
			$add['staff_location']		= $this->input->post('staff_location');
			$add['staff_status']		= $this->input->post('staff_status');
			
			if ($this->Staffs_model->addStaff($add)) {
				$this->session->set_flashdata('alert', '<p class="success">Staff Added Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Updated!</p>');				
			}
		
			return TRUE;
		}
	}
	
	public function _updateStaff($staff_email, $username) {
    	if (!$this->user->hasPermissions('modify', 'admin/staffs') OR  $this->input->get('id') !== $this->user->getStaffId()) {
		
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have the right permission to edit!</p>');
  			return TRUE;
    	
    	} else if ($this->input->get('id') AND $this->validateForm($staff_email, $username) === TRUE) { 
			$update = array();
			
			$update['staff_id']		= $this->input->get('id');
			$update['staff_name']	= $this->input->post('staff_name');
		
			if ($staff_email !== $this->input->post('staff_email')) {
				$update['staff_email']	= $this->input->post('staff_email');
			} else {
				$update['staff_email']	= $staff_email;
			}
		
			if ($username !== $this->input->post('username')) {
				$update['username']	= $this->input->post('username');
			} else {
				$update['username']	= $username;
			}
		
			$update['password']			= $this->input->post('password');
			$update['staff_group']		= $this->input->post('staff_group');
			$update['staff_location']	= $this->input->post('staff_location');
			$update['staff_status']		= $this->input->post('staff_status');
			
			if ($this->Staffs_model->updateStaff($update)) {
				$this->session->set_flashdata('alert', '<p class="success">Staff Updated Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Updated!</p>');				
			}
		
			return TRUE;
		}
	}

	public function _deleteStaff() {
    	if (!$this->user->hasPermissions('modify', 'admin/staffs')) {
		
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have the right permission to edit!</p>');
    	
    	} else { 
			if (is_array($this->input->post('delete'))) {
				foreach ($this->input->post('delete') as $key => $value) {
					$staff_id = $value;
					$this->Staffs_model->deleteStaff($staff_id);
				}			
			
				$this->session->set_flashdata('alert', '<p class="success">Staff(s) Deleted Sucessfully!</p>');
			}
		}
				
		return TRUE;
	}

	public function validateForm($staff_email = FALSE, $username = FALSE) {
		$this->form_validation->set_rules('staff_name', 'Staff Name', 'trim|required|min_length[2]|max_length[128]');
	
		if ($staff_email !== $this->input->post('staff_email')) {
			$this->form_validation->set_rules('staff_email', 'Staff Email', 'trim|required|valid_email|is_unique[staffs.staff_email]|max_length[96]');
		}
	
		if ($username !== $this->input->post('username')) {
			$this->form_validation->set_rules('username', 'Username', 'trim|required|is_unique[users.username]|min_length[2]|max_length[32]');
		}
	
		$this->form_validation->set_rules('password', 'Password', 'trim|min_length[6]|max_length[32]|matches[password_confirm]');
		$this->form_validation->set_rules('password_confirm', 'Password Confirm', 'trim');
		$this->form_validation->set_rules('staff_group', 'Staff Department', 'trim|required|integer');
		$this->form_validation->set_rules('staff_location', 'Location Name', 'trim|required|integer');
		$this->form_validation->set_rules('staff_status', 'Status', 'trim|integer');

		if ($this->form_validation->run() === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}