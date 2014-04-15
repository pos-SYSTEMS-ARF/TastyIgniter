<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Menus extends CI_Controller {

	private $error = array();

	public function __construct() {
		parent::__construct(); //  calls the constructor
		$this->load->library('user');
		$this->load->library('pagination');
		$this->load->library('currency'); // load the currency library
		$this->load->model('Menus_model'); // load the menus model
	}

	public function index() {

		if (!file_exists(APPPATH .'views/admin/menus.php')) {
			show_404();
		}
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/menus')) {
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
				
		if ($this->input->get('filter_name')) {
			$filter['filter_name'] = $this->input->get('filter_name');
			$data['filter_name'] = $filter['filter_name'];
		} else {
			$data['filter_name'] = '';
		}
		
		if (is_numeric($this->input->get('filter_price'))) {
			$filter['filter_price'] = $this->input->get('filter_price');
			$data['filter_price'] = $filter['filter_price'];
		} else {
			$filter['filter_price'] = ''; 
			$data['filter_price'] = '';
		}
		
		if ($this->input->get('filter_category')) {
			$filter['category_id'] = (int) $this->input->get('filter_category');
			$data['category_id'] = $this->input->get('filter_category');
		} else {
			$data['category_id'] = '';
		}
		
		$filter_stock = $this->input->get('filter_stock');
		if (is_numeric($filter_stock)) {
			$filter['filter_stock'] = $filter_stock;
			$data['filter_stock'] = $filter['filter_stock'];
		} else {
			$filter['filter_stock'] = '';
			$data['filter_stock'] = '';
		}
		
		$filter_status = $this->input->get('filter_status');
		if (is_numeric($filter_status)) {
			$filter['filter_status'] = $filter_status;
			$data['filter_status'] = $filter['filter_status'];
		} else {
			$filter['filter_status'] = '';
			$data['filter_status'] = '';
		}
		
		$data['heading'] 			= 'Menus';
		$data['sub_menu_add'] 		= 'Add new menu';
		$data['sub_menu_delete'] 	= 'Delete';
		$data['text_no_menus'] 		= 'There are no menus available.';
		
		$this->load->model('Image_tool_model');

		$data['menus'] = array();		
		$results = $this->Menus_model->getList($filter);
		foreach ($results as $result) {
			
			if (!empty($result['menu_photo'])) {
				$menu_photo_src = $this->Image_tool_model->resize($result['menu_photo'], 64, 64);
			} else {
				$menu_photo_src = $this->Image_tool_model->resize('data/no_photo.png', 64, 64);
			}
						
			$data['menus'][] = array(
				'menu_id'			=> $result['menu_id'],
				'menu_name'			=> $result['menu_name'],
				'menu_description'	=> $result['menu_description'],
				'category_name'		=> $result['category_name'],
				'menu_price'		=> $this->currency->format($result['menu_price']),
				'menu_photo'		=> $menu_photo_src,
				'stock_qty'			=> $result['stock_qty'],
				'menu_status'		=> ($result['menu_status'] === '1') ? 'Enabled' : 'Disabled',
				'edit' 				=> $this->config->site_url('admin/menus/edit?id='. $result['menu_id'])
			);
		}	

		//load category data into array
		$data['categories'] = array();
		$categories = $this->Menus_model->getCategories();
		foreach ($categories as $category) {					
			$data['categories'][] = array(
				'category_id'	=>	$category['category_id'],
				'category_name'	=>	$category['category_name']
			);
		}
		
		$data['has_options'] = $this->Menus_model->hasMenuOptions();

		$this->load->model('Specials_model');
		$data['is_specials'] = $this->Specials_model->getIsSpecials();

		//load food option data into array
		$data['menu_options'] = array();
		$menu_options = $this->Menus_model->getMenuOptions();
		foreach ($menu_options as $food_option) {
			$data['menu_options'][] = array(
				'option_id' 	=> $food_option['option_id'],
				'option_name' 	=> $food_option['option_name'],
				'option_price' 	=> $food_option['option_price']
			);
		}

		$config['base_url'] 		= $this->config->site_url('admin/menus');
		$config['total_rows'] 		= $this->Menus_model->menus_record_count($filter);
		$config['per_page'] 		= $filter['limit'];
		$config['num_links'] 		= round($config['total_rows'] / $config['per_page']);
		
		$this->pagination->initialize($config);

		$data['pagination'] = array(
			'info'		=> $this->pagination->create_infos(),
			'links'		=> $this->pagination->create_links()
		);

		//check if POST update_food then remove menu_id
		if ($this->input->post('delete') && $this->_deleteMenu() === TRUE) {

			redirect('admin/menus');  			
		}	

		$regions = array(
			'admin/header',
			'admin/footer'
		);
		
		$this->template->regions($regions);
		$this->template->load('admin/menus', $data);
	}

	public function edit() {
		
		if (!file_exists(APPPATH .'views/admin/menus_edit.php')) {
			show_404();
		}
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/menus')) {
  			redirect('admin/permission');
		}
		
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');  // retrieve session flashdata variable if available
		} else { 
			$data['alert'] = '';
		}		
		
		if (is_numeric($this->input->get('id'))) {
			$menu_id = $this->input->get('id');
			$data['action']	= $this->config->site_url('admin/menus/edit?id='. $menu_id);
		} else {
			$menu_id = 0;
			$data['action']	= $this->config->site_url('admin/menus/edit');
		}
		
		$menu_info = $this->Menus_model->getAdminMenu($menu_id);

		$data['heading'] 			= 'Menu - '. $menu_info['menu_name'];
		$data['sub_menu_save'] 		= 'Save';
		$data['sub_menu_back'] 		= $this->config->site_url('admin/menus');

		$this->load->model('Image_tool_model');
		if ($this->input->post('menu_photo')) {
			$data['menu_image'] = $this->input->post('menu_photo');
			$data['image_name'] = basename($this->input->post('menu_photo'));
			$data['menu_image_url'] = $this->Image_tool_model->resize($this->input->post('menu_photo'), 120, 120);
		} else if (!empty($menu_info['menu_photo'])) {
			$data['menu_image'] = $menu_info['menu_photo'];
			$data['image_name'] = basename($menu_info['menu_photo']);
			$data['menu_image_url'] = $this->Image_tool_model->resize($menu_info['menu_photo'], 120, 120);
		} else {
			$data['menu_image'] = 'data/no_photo.png';
			$data['image_name'] = 'no_photo.png';
			$data['menu_image_url'] = $this->Image_tool_model->resize('data/no_photo.png', 120, 120);
		}

		$data['menu_id'] 			= $menu_info['menu_id'];
		$data['menu_name'] 			= $menu_info['menu_name'];
		$data['menu_description']	= $menu_info['menu_description'];
		$data['menu_price'] 		= $menu_info['menu_price'];
		$data['menu_category'] 		= $menu_info['category_id'];
		$data['stock_qty'] 			= $menu_info['stock_qty'];
		$data['minimum_qty'] 		= (isset($menu_info['minimum_qty'])) ? $menu_info['minimum_qty'] : '1';
		$data['special_id'] 		= $menu_info['special_id'];
		$data['start_date'] 		= (isset($menu_info['start_date'])) ? mdate('%d-%m-%Y', strtotime($menu_info['start_date'])) : '';
		$data['end_date'] 			= (isset($menu_info['end_date'])) ? mdate('%d-%m-%Y', strtotime($menu_info['end_date'])) : '';
		$data['special_price'] 		= $menu_info['special_price'];
		$data['special_status'] 	= $menu_info['special_status'];
		$data['subtract_stock']		= $menu_info['subtract_stock'];
		$data['menu_status'] 		= $menu_info['menu_status'];
		$data['no_photo'] 			= $this->Image_tool_model->resize('data/no_photo.png', 120, 120);

		$data['has_options'] 		= $this->Menus_model->hasMenuOptions($menu_id);

		//load category data into array
		$data['categories'] = array();
		$results = $this->Menus_model->getCategories();
		foreach ($results as $result) {					
			$data['categories'][] = array(
				'category_id'	=>	$result['category_id'],
				'category_name'	=>	$result['category_name']
			);
		}

		$data['menu_options'] = array();
		$results = $this->Menus_model->getMenuOptions();
		foreach ($results as $result) {
			$data['menu_options'][] = array(
				'option_id' 	=> $result['option_id'],
				'option_name' 	=> $result['option_name'],
				'option_price' 	=> $result['option_price']
			);
		}
		
		if ( ! $this->input->get('id') && $this->input->post() && $this->_addMenu() === TRUE) {
		
			redirect('admin/menus');
		}

		if ($this->input->get('id') && $this->input->post() && $this->_updateMenu() === TRUE) {
					
			redirect('admin/menus');
		}
							
		$regions = array(
			'admin/header',
			'admin/footer'
		);
		
		$this->template->regions($regions);
		$this->template->load('admin/menus_edit', $data);
	}

	public function autocomplete() {
		$json = array();
		
		if ($this->input->get('menu')) {
			$filter_data = array();
			$filter_data = array(
				'menu_name' => $this->input->get('menu')
			);
		}
		
		$results = $this->Menus_model->getAutoComplete($filter_data);

		if ($results) {
			foreach ($results as $result) {
				$json[] = array(
					'menu_id' 		=> $result['menu_id'],
					'menu_name' 	=> $result['menu_name']
				);
			}
		}
		
		$this->output->set_output(json_encode($json));
	}
	
	public function _addMenu() {
    	if ( ! $this->user->hasPermissions('modify', 'admin/menus')) {
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have the right permission to edit!</p>');
  			return TRUE;
    	} else if ( ! $this->input->get('id') AND $this->validateForm() === TRUE) { 
			$add = array();
			
			//Sanitizing the POST values
			$add['menu_name'] 			= $this->input->post('menu_name');
			$add['menu_description'] 	= $this->input->post('menu_description');
			$add['menu_price'] 			= $this->input->post('menu_price');
			$add['menu_category'] 		= $this->input->post('menu_category');
			$add['menu_photo'] 			= $this->input->post('menu_photo');			
			$add['stock_qty'] 			= $this->input->post('stock_qty');			
			$add['minimum_qty'] 		= $this->input->post('minimum_qty');			
			$add['subtract_stock'] 		= $this->input->post('subtract_stock');			
			$add['menu_options'] 		= $this->input->post('menu_options');
			$add['special_status'] 		= $this->input->post('special_status');
			$add['start_date'] 			= $this->input->post('start_date');
			$add['end_date'] 			= $this->input->post('end_date');
			$add['special_price'] 		= $this->input->post('special_price');
			$add['menu_status'] 		= $this->input->post('menu_status');
			
			if ($this->Menus_model->addMenu($add)) {
				$this->session->set_flashdata('alert', '<p class="success">Menu Added Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Added!</p>');				
			}
			
			return TRUE;
		}	
	}

	public function _updateMenu() {
    	if (!$this->user->hasPermissions('modify', 'admin/menus')) {
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have the right permission to edit!</p>');
  			return TRUE;
    	} else if ($this->input->get('id') AND $this->validateForm() === TRUE) { 
			$update = array();

			$update['menu_id'] 				= $this->input->get('id');
			
			//Sanitizing the POST values
			$update['menu_name'] 			= $this->input->post('menu_name');
			$update['menu_description'] 	= $this->input->post('menu_description');
			$update['menu_price']			= $this->input->post('menu_price');
			$update['menu_category'] 		= $this->input->post('menu_category');
			$update['menu_photo'] 			= $this->input->post('menu_photo');					
			$update['stock_qty'] 			= $this->input->post('stock_qty');			
			$update['minimum_qty'] 			= $this->input->post('minimum_qty');			
			$update['subtract_stock'] 		= $this->input->post('subtract_stock');			
			$update['menu_options'] 		= $this->input->post('menu_options');
			$update['special_id'] 			= $this->input->post('special_id');
			$update['start_date'] 			= $this->input->post('start_date');
			$update['end_date'] 			= $this->input->post('end_date');
			$update['special_status'] 		= $this->input->post('special_status');
			$update['special_price'] 		= $this->input->post('special_price');
			$update['menu_status'] 			= $this->input->post('menu_status');


			if ($this->Menus_model->updateMenu($update)) {						
				$this->session->set_flashdata('alert', '<p class="success">Menu Updated Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Updated!</p>');				
			}
			
			return TRUE;
		}
	}

	public function _deleteMenu($menu_id = FALSE) {
    	if (!$this->user->hasPermissions('modify', 'admin/menus')) {
		
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have the right permission to edit!</p>');
    	
    	} else { 
			if (is_array($this->input->post('delete'))) {
				foreach ($this->input->post('delete') as $key => $value) {
					$menu_id = $value;
					$this->Menus_model->deleteMenu($menu_id);
				}			

				$this->session->set_flashdata('alert', '<p class="success">Menu(s) Deleted Sucessfully!</p>');
			}
		}
				
		return TRUE;
	}
	
 	public function validateForm() {
		$this->form_validation->set_rules('menu_name', 'Menu Name', 'trim|required|min_length[2]|max_length[255]');
		$this->form_validation->set_rules('menu_description', 'Menu Description', 'trim|min_length[2]|max_length[1028]');
		$this->form_validation->set_rules('menu_price', 'Menu Price', 'trim|required|numeric');
		$this->form_validation->set_rules('menu_category', 'Menu Category', 'trim|required|integer');
		$this->form_validation->set_rules('menu_photo', 'Menu Photo', 'trim|required');
		$this->form_validation->set_rules('stock_qty', 'Stock Quantity', 'trim|required|integer');
		$this->form_validation->set_rules('minimum_qty', 'Minimum Quantity', 'trim|required|integer');
		$this->form_validation->set_rules('subtract_stock', 'Subtract Stock', 'trim|requried|integer');
		$this->form_validation->set_rules('menu_status', 'Menu Status', 'trim|requried|integer');
		$this->form_validation->set_rules('menu_options[]', 'Menu Options', 'trim|integer');
		$this->form_validation->set_rules('special_status', 'Menu Special', 'trim|required|integer');
		$this->form_validation->set_rules('start_date', 'Start Date', 'trim|valid_date');
		$this->form_validation->set_rules('end_date', 'End Date', 'trim|valid_date');
		$this->form_validation->set_rules('special_price', 'Special Price', 'trim|numeric');

		if ($this->form_validation->run() === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}