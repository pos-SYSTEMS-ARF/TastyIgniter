<?php
class Paypal_express extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library('user');
		$this->load->model('Statuses_model');	    
	}

	public function index() {
			
		if (!file_exists(APPPATH .'views/admin/paypal_express.php')) {
			show_404();
		}
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/paypal_express')) {
  			redirect('admin/permission');
		}
			
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');
		} else { 
			$data['alert'] = '';
		}		
				
		$data['heading'] 			= 'PayPal Express Checkout';
		$data['sub_menu_save'] 		= 'Save';
		$data['sub_menu_back'] 		= $this->config->site_url('admin/payments');

		if (isset($this->input->post['paypal_status'])) {
			$data['paypal_status'] = $this->input->post['paypal_status'];
		} else {
			$data['paypal_status'] = $this->config->item('paypal_status');
		}				

		if (isset($this->input->post['paypal_mode'])) {
			$data['paypal_mode'] = $this->input->post['paypal_mode'];
		} else {
			$data['paypal_mode'] = $this->config->item('paypal_mode');
		}				

		if (isset($this->input->post['paypal_user'])) {
			$data['paypal_user'] = $this->input->post['paypal_user'];
		} else {
			$data['paypal_user'] = $this->config->item('paypal_user');
		}				

		if (isset($this->input->post['paypal_pass'])) {
			$data['paypal_pass'] = $this->input->post['paypal_pass'];
		} else {
			$data['paypal_pass'] = $this->config->item('paypal_pass');
		}				

		if (isset($this->input->post['paypal_sign'])) {
			$data['paypal_sign'] = $this->input->post['paypal_sign'];
		} else {
			$data['paypal_sign'] = $this->config->item('paypal_sign');
		}				

		if (isset($this->input->post['paypal_action'])) {
			$data['paypal_action'] = $this->input->post['paypal_action'];
		} else {
			$data['paypal_action'] = $this->config->item('paypal_action');
		}				

		if (isset($this->input->post['paypal_total'])) {
			$data['paypal_total'] = $this->input->post['paypal_total'];
		} else {
			$data['paypal_total'] = $this->config->item('paypal_total');
		}				

		if (isset($this->input->post['paypal_order_status'])) {
			$data['paypal_order_status'] = $this->input->post['paypal_order_status'];
		} else {
			$data['paypal_order_status'] = $this->config->item('paypal_order_status');
		}				

		$data['statuses'] = array();
		$results = $this->Statuses_model->getStatuses('order');
		foreach ($results as $result) {					
			$data['statuses'][] = array(
				'status_id'		=> $result['status_id'],
				'status_name'		=> $result['status_name']
			);
		}

		if ($this->input->post() && $this->_updatePayPalExpress() === TRUE){
						
			redirect('admin/payments');
		}
		
		$regions = array(
			'admin/header',
			'admin/footer'
		);
		
		$this->template->regions($regions);
		$this->template->load('admin/paypal_express', $data);
	}

	public function _updatePayPalExpress() {
						
    	if (!$this->user->hasPermissions('modify', 'admin/paypal_express')) {
		
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have the right permission to edit!</p>');
			return TRUE;
    	
    	} else if (!$this->input->post('delete') AND $this->validateForm() === TRUE) { 
			$update = array(
				'paypal_status' 		=> $this->input->post('paypal_status'),
				'paypal_mode' 			=> $this->input->post('paypal_mode'),
				'paypal_user' 			=> $this->input->post('paypal_user'),
				'paypal_pass' 			=> $this->input->post('paypal_pass'),
				'paypal_sign' 			=> $this->input->post('paypal_sign'),
				'paypal_action' 		=> $this->input->post('paypal_action'),
				'paypal_total' 			=> $this->input->post('paypal_total'),
				'paypal_order_status' 	=> $this->input->post('paypal_order_status')
			);

			if ($this->Settings_model->updateSettings('paypal_express', $update)) {
				$this->session->set_flashdata('alert', '<p class="success">PayPal Express Checkout Updated Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Updated!</p>');				
			}
		
			return TRUE;
		}
	}

	public function validateForm() {
		$this->form_validation->set_rules('paypal_status', 'PayPal Status', 'trim|required|integer');
		$this->form_validation->set_rules('paypal_mode', 'PayPal Mode', 'trim|required');
		$this->form_validation->set_rules('paypal_user', 'PayPal Username', 'trim|required');
		$this->form_validation->set_rules('paypal_pass', 'PayPal Password', 'trim|required');
		$this->form_validation->set_rules('paypal_sign', 'PayPal Signature', 'trim|required');
		$this->form_validation->set_rules('paypal_action', 'Payment Action', 'trim|required');
		$this->form_validation->set_rules('paypal_total', 'Order Total', 'trim|required|numeric');
		$this->form_validation->set_rules('paypal_order_status', 'Order Status', 'trim|required|integer');

		if ($this->form_validation->run() === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}		
	}
}