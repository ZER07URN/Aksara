<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Administrative > Users > Privileges
 *
 * @version			2.1.1
 * @author			Aby Dahana
 * @profile			abydahana.github.io
 */
class Privileges extends Aksara
{
	private $_table									= 'app__users_privileges';
	
	public function __construct()
	{
		parent::__construct();
		
		$this->set_permission(1);
		$this->set_theme('backend');
		
		$this->set_method('update');
		$this->parent_module('administrative/users');
		$this->insert_on_update_fail();
		
		$this->_primary								= $this->input->get('user_id');
		
		$this->_user								= $this->model->select
		('
			app__users.user_id,
			app__users.username,
			app__users.first_name,
			app__users.last_name,
			app__users.photo,
			app__users.group_id,
			' . $this->_table . '.sub_level_1,
			' . $this->_table . '.access_year
		')
		->join
		(
			$this->_table,
			$this->_table . '.user_id = app__users.user_id',
			'left'
		)
		->get_where
		(
			'app__users',
			array
			(
				'app__users.user_id'				=> $this->_primary
			),
			1
		)
		->row();
		
		/* check if user is exists */
		if(!$this->_user || in_array($this->_user->group_id, array(1)))
		{
			/* otherwise, throw the exception */
			return throw_exception(404, phrase('you_are_not_allowed_to_modify_the_selected_user'), current_page('../'));
		}
	}
	
	public function index()
	{
		$this->set_title(phrase('custom_user_privileges'))
		->set_icon('mdi mdi-account-check-outline')
		->set_primary('user_id')
		->set_output
		(
			array
			(
				'userdata'							=> $this->_user,
				'sub_level_1'						=> $this->_sub_level_1(),
				'visible_menu'						=> $this->_visible_menu()
			)
		)
		->set_default
		(
			array
			(
				'user_id'							=> $this->_primary,
				'access_year'						=> $this->input->post('year')
			)
		)
		->where('user_id', $this->_primary)
		->limit(1)
		->render($this->_table);
	}
	
	/**
	 * List the relation to sub level 1
	 */
	private function _sub_level_1()
	{
		return array();
	}
	
	/**
	 * List the visible menu
	 */
	private function _visible_menu()
	{
		/* get existing user menu if any */
		$existing_menu								= $this->model->select('visible_menu')->get_where($this->_table, array('user_id' => $this->_primary), 1)->row('visible_menu');
		$existing_menu								= json_decode($existing_menu);
		
		/* get sidebar menu by user group from the database */
		$visible_menu								= $this->model
		->select
		('
			app__menus.serialized_data
		')
		->join
		(
			'app__groups',
			'app__groups.group_id = app__users.group_id'
		)
		->join
		(
			'app__menus',
			'app__menus.group_id = app__groups.group_id'
		)
		->get_where
		(
			'app__users',
			array
			(
				'app__users.user_id'				=> $this->_primary,
				'app__menus.menu_placement'			=> 'sidebar'
			),
			1
		)
		->row('serialized_data');
		
		/* decode serialized menu */
		$visible_menu								= json_decode($visible_menu);
		
		/* set default item */
		$items										= null;
		if($visible_menu)
		{
			foreach($visible_menu as $menu => $item)
			{
				if(!isset($item->id) || !isset($item->slug) || !isset($item->label)) continue;
				$items								.= '
					<li' . (isset($item->children) && $item->children ? ' class="check-group"' : null) . '>
						<label class="control-label big-label">
							<input type="checkbox"name="menus[]" value="' . $item->id . '"' . (isset($item->children) && $item->children ? ' role="checker" data-parent=".check-group"' : null) . (isset($existing_menu->$item->id) ? ' checked' : null) . ' />
							&nbsp;
							<i class="' . (isset($item->icon) ? $item->icon : 'mdi mdi-circle-outline') . '"></i>
							' . phrase($item->label) . '
						</label>
						' . (isset($item->children) ? $this->_children_menu($item->children, $existing_menu) : null) . '
					</li>
				';
			}
			$items							= '
				<ul class="list-unstyled">
					' . $items . '
				</ul>
			';
		}
		
		return $items;
	}
	
	/**
	 * Re-loop the available menu to find the children
	 */
	private function _children_menu($children = array(), $existing_menu = array())
	{
		$items										= null;
		if($children)
		{
			foreach($children as $menu => $item)
			{
				if(!isset($item->id) || !isset($item->slug) || !isset($item->label)) continue;
				$items								.= '
					<li' . (isset($item->children) && $item->children ? ' class="check-group"' : null) . '>
						<label class="control-label big-label">
							<input type="checkbox"name="menus[]" value="' . $item->id . '" class="checker-children"' . (isset($item->children) && $item->children ? ' role="checker" data-parent=".check-group"' : null) . (isset($existing_menu->$item->id) ? ' checked' : null) . ' />
							&nbsp;
							<i class="' . (isset($item->icon) ? $item->icon : 'mdi mdi-circle-outline') . '"></i>
							' . phrase($item->label) . '
						</label>
						' . (isset($item->children) ? $this->_children_menu($item->children) : null) . '
					</li>
				';
			}
			$items									= '
				<ul class="list-unstyled ml-3">
					' . $items . '
				</ul>
			';
		}
		return $items;
	}
}