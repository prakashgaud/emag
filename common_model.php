<?php
	/**
	* Model class for common function.
	* 
	* Models are PHP classes that are designed to work with information in your database.<br>
	* This Model handles the Groups operations with data base<br>
	* File: models/common_model.php<br>
	* This class library is used for 
	* connects to Database and fetches data.<br>	
	* @package Model
	* @subpackage Common
	*/
	class Common_model extends CI_Model
	{
		/**
			* Database object for emagic
			*
			* This holds a database object that will connect to magicmrn db<br>
			* The configuration options of this db can be found in config/database.php file.<br>
			* It is in a default group.
			* private access
			* @var object $db
			*/
		/**
			* Whenever the class is instantiated.
			*
			* calls parent cunstructor Model()<br>
			* loads $this->db object<br>
			*/
		function __construct()
		{
			parent::__construct();
			$this->load->library('errors');
			//$this->db = $this->load->database('new', TRUE);
			$this->tbl_prefix = $this->config->item('table_prefix');
			$this->lic_err_prefix = '<center><div style="background-color:#ffffae;color:red;border:1px solid red; width:60%; padding:25px; font-family:verdana;font-size:14px;text-align:center;font-weight:bold;">';
			//$this->lic_err_suffix = '<p><a href="javascript: void(0);" onClick="javascript: uploadlicense();">Click Here</a> to update the license<p></div></center>';
			$this->lic_err_suffix = '</div></center>';
			$this->license_check();
			$this->validation_rules = array("required", "valid_email", "valid_emails", "valid_ip");
			$this->input_type = array('textbox', 'dropdown', 'date');
			$this->multiple_opts = array('dropdown', 'n');
			$this->no_multiple_opts = array('textbox', 'date', 'y');
			$this->hardware_config = 'hw';
			$this->software_config = 'sw';
			$this->line_seperator = '|#|#|';
			$this->port_array = array('port2082' => 2082, 'port2083' => 2083);
			$this->env_type_array = array('Standard' => '1,20', 'VMWare' => '5', 'Xen' => '18,4', 'Virtuozzo' => '23', 'standard' => '1,20', 'virtuozzo' => '23', 'xen' => '18,4', 'vmware' => '5','HyperV' => '4,18','hyperV' => '4,18','hyper' => '4,18','hyperv' => '4,18');
			$this->default_page = 0;
			$this->default_limit = 30;
			$this->separator_chr = '|#|#|#|#|';
			$this->alerts_path = $this->config->item('emagic_source')."emcache/alerts/alerts.json";
			$this->profile_alerts_path = $this->config->item('emagic_source')."emcache/alerts/profile_alerts.json";
			$this->os_types = array('windows' => 'windows' , 'ssh' =>  'ssh');
			$this->stats_path = $this->config->item('emagic_source')."emcache/stats/";
		}
		function generateconfig()
		{
			$confstr = '';
			$all_array = array();
			$all_sys_settings = $this->select_all_where_records($this->tbl_prefix.'system_settings', "status = 'y'", '', '', 'name,value');
			$setting_cnt = count($all_sys_settings);
			if (is_array($all_sys_settings) && $setting_cnt > 0)
			{
				foreach($all_sys_settings as $setting)
				{
					if (trim($setting['name']) != '')
					$all_array[trim($setting['name'])] = $setting['value'];
				}
				$confstr = json_encode($all_array);
				$filepath = $this->config->item('conf_path');
				if ($confstr != '')
				{					
					$fp = fopen($filepath, 'w');
					fwrite($fp, $confstr);
					fclose($fp);						
				}	
			}
			return true;
		}
		function probedetails($objectid, $type="device", $probe_id="")		
		{
			$probe_name = '';
			if($objectid == 0 && $probe_id == '')
				$probe_id = 1;
			if($probe_id > 0)
			{
				$probe_name = $this->common_model->get_field_value('title', $this->tbl_prefix.'monitoring_probes',"probe_id = '".$probe_id."'");
			}
			else
			{
				if ($objectid > 0)
				{
					if ($type == "device")
					{	
						$probe = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_probes mp, '.$this->tbl_prefix.'object_master om', 'om.monitor_probe_id = mp.probe_id AND om.object_id = "'.$objectid.'"', '', '', 'mp.title as probe_name');
					}
					else if ($type == "profile")
					{	
						$probe = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_probes mp, '.$this->tbl_prefix.'monitoring_profile om', 'om.monitor_probe_id = mp.probe_id AND om.profile_id = "'.$objectid.'"', '', '', 'mp.title as probe_name');
					}
					$probe_name = $probe[0]['probe_name'];
				}
			}	
			return $probe_name;
		}
		function getprobedetails($objectid, $type="device",$probe_id="")		
		{
			$probe = array();
			if($probe_id > 0)
			{
				
				$probe = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_probes mp', 'mp.probe_id = "'.$probe_id.'"', '', '', 'mp.*');
			
			}
			else
			{
				if ($objectid > 0)
				{
					if ($type == "device")
					{	
						$probe = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_probes mp, '.$this->tbl_prefix.'object_master om', 'om.monitor_probe_id = mp.probe_id AND om.object_id = "'.$objectid.'"', '', '', 'mp.*');
					}
					else if ($type == "profile")
					{	
						$probe = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_probes mp, '.$this->tbl_prefix.'monitoring_profile om', 'om.monitor_probe_id = mp.probe_id AND om.profile_id = "'.$objectid.'"', '', '', 'mp.*');
			
					}
					
				}
			}	
			
			return $probe;
		}
		function get_device_name($object_id, $plain="",$html="yes")
		{
			$device_data = $this->common_model->select_all_where_records($this->tbl_prefix.'object_master', 'object_id = "'.$object_id.'"', '', '', 'title,add_title,description,object_id,dep_type_id');			
			$final_tag_array = array();
			$device_name_1 = $device_name_2 = $device_name = "";
			$name_tags = $this->config->item('device_name'); 
			$tag_array = explode(',',trim($name_tags));
			for($j=0;$j < 2;$j++)
			{
				if(trim($tag_array[$j]) == 'title')
				$final_tag_array[] = 'title';
				if(trim($tag_array[$j]) == 'additional title')
				$final_tag_array[] = 'add_title';
				if(trim($tag_array[$j]) == 'hostname')
				$final_tag_array[] = 'description';
			}			
			if($final_tag_array[0] != '')
			$device_name_1 = $device_data[0][$final_tag_array[0]];
			if($final_tag_array[1] != '')	
			$device_name_2 = $device_data[0][$final_tag_array[1]];				
			if (strlen($device_name_1) >= 20)				
			$device_name_1 = substr($device_name_1,0,20).'...';			
			if (strlen($device_name_2) >= 20)				
			$device_name_2 = substr($device_name_2,0,20).'...';
			if($device_name_1 != '')
			$device_name = $device_name_1;
			if($device_name_2 != '')
			{	
				if($html == 'yes')
				{
					$device_name .= '<br>';
					if($plain == '')
					$device_name .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$device_name .= '<font color="#FF0000">['.trim($device_name_2).']</font>';	
				}
				else
				$device_name .= ' ['.trim($device_name_2).']';	
			}			
			if($device_name == '')
			$device_name = $device_data[0]['title'];										
			if($plain != "yes")
			{	
				$device_icons = $this->config->item('device_icons');	
				$icon_file = $device_icons[$device_data[0]['dep_type_id']];
				$where = "object_id = '".$device_data[0]['object_id']."' AND status = 'y'";
				$os = $this->common_model->select_all_where_records($this->tbl_prefix.'object_server_details', $where, '', '', 'os_id,syslog_server');	
				if (is_array($os) && count($os) > 0)
				{
					if ($os[0]['syslog_server'] == 'y')
					$icon_file = $device_icons['Syslog'];
					elseif ($os[0]['os_id'] > 0)
					{
						$where = "os_id = '".$os[0]['os_id']."'";
						$os_detail = $this->common_model->select_all_where_records($this->tbl_prefix.'object_os_master', $where, '', '', 'os_type');
						$ostyp = ucfirst($os_detail[0]['os_type']);
						$icon_file = $device_icons[$ostyp];
					}
				}
				if ($icon_file == '')
				$icon_file = 'server_icon.png';
				$icon = '<img src="'.$this->config->item('theme_images').$icon_file.'">';	
			}			
			if($plain == 'yes')
			$object_title = $device_name;
			else
			$object_title = '<a href="'.$this->config->item('monitor_controller_path').'manage/devicedetails/'.$object_id.'" title = "Click to open device dashboard" target="_blank">'.$icon.' '.$device_name.'</a>';
			return $object_title;
		}		
		function uniquedevice($params, $isvm = false)
		{
			$add_sql = "";
			$objectid = 0;
			$title = trim($params['title']);
			$addtitle = trim($params['add_title']);
			$vmid = trim($params['vmid']);
			if ($isvm == true)
			$add_sql = " OR TRIM(instance_id) = '".$vmid."'";
			$isdevice = $this->common_model->select_all_where_records($this->tbl_prefix.'object_master', "(LOWER(TRIM(title)) = '".$title."' AND LOWER(TRIM(add_title)) = '".$addtitle."') ".$add_sql, '', '', 'object_id');
			if (is_array($isdevice) && count($isdevice) > 0)
			$objectid = $isdevice[0]['object_id'];
			return $objectid;
		}
		function license_load()
		{
			$license_file = $this->config->item('public_path').'license.txt';
			if (file_exists($license_file))
			{
				$license_string = file_get_contents($license_file);
				if (trim($license_string) != '')
				{
					$license_data = $this->decrypt($license_string, 'eMagic');
					if ($license_data != '')
					return $license_data;
					else
					return false;
				}
			}
			return false;
		}
		function license_check()
		{
			if ($this->license_load() == false)
			{
				die($this->lic_err_prefix.'License Not found - Please Upload License On Registered Host'.$this->lic_err_suffix);
			}
		}
		function license_parse($key = '')
		{
			$license_data = $this->license_load();
			if ($license_data != false)
			{
				$license_array = explode("|", $license_data);
				//print_r($license_array);
				$license_array_cnt = count($license_array);
				if ($license_array_cnt > 0)
				{
					for($i = 0; $i < $license_array_cnt; $i++)
					{
						$values_array = explode(":-", $license_array[$i]);
						$license_final_array[$values_array[0]] = $values_array[1];
					}
					if ($key != '')
					return $license_final_array[$key];
					else
					return $license_final_array;
				}
				else
				{
					die($this->lic_err_prefix.'Invalid License - Please Check Your License'.$this->lic_err_suffix);
				}
			}
			else
			{
				die($this->lic_err_prefix.'License Not found - Please Upload License On Registered Host'.$this->lic_err_suffix);
			}
		}
		function license_host_check()
		{
			$server_host = $_SERVER['HTTP_HOST'];
			$server_ip = $_SERVER['SERVER_ADDR'];
			$license_ip = trim($this->license_parse('server_ip'));
			$license_hostname = trim($this->license_parse('hostname'));
			if (($license_ip != $server_ip) && ($license_hostname != $server_host))
			die($this->lic_err_prefix.'Invalid Host - Please Check Your License with Registered Host'.$this->lic_err_suffix);
		}
		function license_expiry_check($days = "")
		{
			$license_date = $this->license_parse('expiry_date');
			$license_date = strtotime($this->license_parse('expiry_date'));
			if ($days > 0)
			{
				$timeremain = $license_date - time();
				$days_remaining_view = $this->secondstowords($timeremain);
				$days_remaining = round(($license_date - time()) / 86400);
				if ($days_remaining <= $days)
				echo '<center><div style="background-color:#ffffae;color:red;width:100%; padding:5px; font-family:verdana;font-size:11px;text-align:center;position:fixed;bottom:0;z-index:10001;margin-right:5px;">Your license will expire in <strong>'.$days_remaining_view.'</strong>. <a href="javascript: void(0);" onClick="javascript: uploadlicense();">Click Here</a> to update the license<p></div></div></center>';
			}
			if ($license_date <= time())
			die($this->lic_err_prefix.'License Expired - Please Renew Your License'.$this->lic_err_suffix);
		}
		function license_device_count()
		{
			$device_count = $this->license_parse('device_count');
			$this->db->where("status_id NOT IN (22,24)", '', FALSE);
			$this->db->from($this->tbl_prefix.'object_master');
			$db_device_count = $this->db->count_all_results();
			if ($db_device_count >= $device_count && $device_count != 0)
			return false;
			else
			return true;
		}
		function license_module_check()
		{
			$modules = $this->license_parse('modules');
			$modules_master = array('ip', 'monitor', 'netflow', 'cms', 'virtual', 'syslog', 'ldap', 'enlightv2', 'swlic', 'itil');
			$function_controller_array = array('inventory', 'monitor', 'admin');
			$functions_master = array('addmrn', 'showmrndetails', 'addtomin', 'addmon', 'mrnlist', 'mrntrackreport', 'minlist', 'monlist', 'addmon', 'mrnlist', 'clientlist', 'clientlistdata', 'clientserverreport');

			$uri_string = $this->uri->uri_string();
			$segment_module = strtolower(trim($this->uri->segment(1)));
			$segment_controller = strtolower(trim($this->uri->segment(2))); // for syslog module
			$segment_function = strtolower(trim($this->uri->segment(3)));
			$modules_array = explode("#", $modules);
			$modules_array = array_map(strtolower, $modules_array);
			if (in_array($segment_module, $modules_master) && !in_array($segment_module, $modules_array))
			die($this->lic_err_prefix.ucfirst($segment_module).' Module Not Available in License'.$this->lic_err_suffix);
			if ($segment_controller == 'syslog' && !in_array($segment_controller, $modules_array))
			die($this->lic_err_prefix.ucfirst($segment_controller).' Module Not Available in License'.$this->lic_err_suffix);
		}
		function license_isvalid_module($module, $returntype="")
		{
			//$module = strtolower(trim($module));
			$modules = $this->license_parse('modules');
			$modules_array1 = explode("#", $modules);
			$modules_array = array_map(strtolower, $modules_array1);
			$menu_modules_array = array(
			'dcm' => array('IP', 'INVENTORY', 'MONITOR'),
			'cloud' => array('IP', 'INVENTORY', 'MONITOR', 'VIRTUAL'), 
			'itil' => array('CMS', 'ITIL')
			);
			
			if(isset($menu_modules_array[$module]))
			{
				foreach($menu_modules_array as $menu => $lic_mod)
				{	
					$check_array = array_intersect($modules_array1,$lic_mod);
					if(count($check_array) >= count($lic_mod))					
					{						
						if ($returntype == 'main')
						die($this->lic_err_prefix.ucfirst($module).' Module Not Available in License'.$this->lic_err_suffix);
						else
						return true;
					}
					return false;
				}
			}
			else if($module == 'sharelink')
			{	
				$output = '';
				$check_arraydcm = array_intersect($modules_array1,$menu_modules_array['dcm']);
				if(count($check_arraydcm) >= count($menu_modules_array['dcm']))
					$output .= 'dcm';
				if(in_array(strtolower('SYSLOG'), $modules_array))
					$output = $output != '' ? $output.'##siem' : $output;
				if(in_array(strtolower('APPMON'), $modules_array))
					$output = $output != '' ? $output.'##appmon' : $output;
				$output = $output != '' ? $output : false;
				if($output == false)				
				{	
					if ($returntype == 'main')
					die($this->lic_err_prefix.'DCM / SIEM Module Not Available in License'.$this->lic_err_suffix);
				}
				return $output;
			}
			else if($module == 'shareinventory')
			{	
				$app = $inventory = $output = '';
				if(in_array(strtolower('APPMON'), $modules_array))
				$inventory = 'inventory';
				if(in_array(strtolower('INVENTORY'), $modules_array))
				$app = 'app';
				$output = $app != '' && $inventory != '' ? 'app##inventory' : ($app != '' ? 'app' : ($inventory != '' ? 'inventory' : false));
				if($output == false)				
				{	
					if ($returntype == 'main')
					die($this->lic_err_prefix.'Application / Inventory Module Not Available in License'.$this->lic_err_suffix);
				}
				return $output;
			}
			if (!in_array(strtolower($module), $modules_array))
			{
				if ($returntype == 'main')
				die($this->lic_err_prefix.ucfirst($module).' Module Not Available in License'.$this->lic_err_suffix);
				else
				return false;
			}
			else
			return true;
		}
		function license_update_link()
		{
			echo '<p><a href="javascript: void(0);" onClick="javascript: uploadlicense();">Click Here</a> to update the license<p>';
		}
		
		function secondstowords($seconds)
		{
				$ret = "";
				/*             * * get the days ** */
				$days = intval(intval($seconds) / (3600 * 24));
				if ($days > 0)
				{
						$ret .= "$days days ";
				}
				/*             * * get the hours ** */
				$hours = (intval($seconds) / 3600) % 24;
				if ($hours > 0)
				{
						$ret .= "$hours hours ";
				}
				/*             * * get the minutes ** */
				$minutes = (intval($seconds) / 60) % 60;
				if ($minutes > 0)
				{
						$ret .= "$minutes minutes ";
				}
				$seconds = (intval($seconds)) % 60;
				if ($seconds > 0)
				{
						$ret .= "$seconds seconds ";
				}
				return $ret;
		}

		
		function secondstowords_old($seconds)
		{
			$ret = "";
			/*             * * get the days ** */
			$days = intval(intval($seconds) / (3600 * 24));
			if ($days > 0)
			{
				$ret .= "$days days ";
			}
			/*             * * get the hours ** */
			$hours = (intval($seconds) / 3600) % 24;
			if ($hours > 0)
			{
				$ret .= "$hours hours ";
			}
			/*             * * get the minutes ** */
			$minutes = (intval($seconds) / 60) % 60;
			if ($minutes > 0)
			{
				$ret .= "$minutes minutes ";
			}
			return $ret;
		}
		function timedifference($seconds)
		{
			$ret = "";
			if ($seconds >= 60)
			{
				$days = intval(intval($seconds) / (3600 * 24));
				if ($days > 0)
				$ret = "$days days";
				else 
				{		
					$hours = (intval($seconds) / 3600) % 24;
					if ($hours > 0)
					$ret = "$hours hrs";
					else
					{
						$minutes = (intval($seconds) / 60) % 60;
						if ($minutes > 0)
						$ret = "$minutes mins";
					}
				}	
			}
			else
			{
				if ($seconds > 0)
					$ret = "$seconds secs";
			}
			
			return $ret;
		}
		function primary_field($tblname)
		{
			$query = $this->db->query("SHOW INDEX FROM ".$tblname." WHERE Key_name = 'PRIMARY'");
			if ($query->num_rows() > 0)
			{
				$row = $query->row_array();
				return $row['Column_name'];
			}
			else
			{
				return '';
			}
		}
		/**
			* This is the general function to insert records in table
			*
			* @access public 
			* @param Array insertArray	 
			* @param String table name	 
			* @return integer last insert id
			*/
		function insert_records($tblname, $insertArray, $flg = 0, $insert_type = "")
		{
			$batch_record_ids = '';
			$folder = $this->uri->segment('1');
			$controller = $this->uri->segment('2');
			$controller = $folder.':'.$controller;
			if ($insert_type == 'batch')
			{
				$primary_key = $this->primary_field($tblname);
				$query = $this->db->insert_batch($tblname, $insertArray);
				if ($flg > 0)
				{
					echo $this->db->last_query();
				}
				$inser_id = $this->db->insert_id();
				$this->db->select_max($primary_key, 'maxid');
				$max_query = $this->db->get($tblname);
				if ($max_query->num_rows() > 0)
				{
					$max_row = $max_query->row_array();
					$max_id = $max_row['maxid'];
				}
				if ($inser_id > 0 && $max_id > 0)
				{
					$inser_ids_array = range($inser_id, $max_id);
					if (count($inser_ids_array) > 0)
					$batch_record_ids = implode(", ", $inser_ids_array);
					if ($batch_record_ids != '')
					{
						$this->logs->activity_logs($controller, 'common_model', $this->db->database, $tblname, $batch_record_ids, 'inserted');
					}
					return $inser_id;
				}
				else
				{
					$error_message = $this->errors->get_error_message($query);
					return $error_message;
				}
			}
			else
			{
				$query = $this->db->insert($tblname, $insertArray);
				if ($flg > 0)
				{
					echo $this->db->last_query();
				}
				$inser_id = $this->db->insert_id();
				if ($inser_id > 0)
				{
					$this->logs->activity_logs($controller, 'common_model', $this->db->database, $tblname, $inser_id, 'inserted');
					return $inser_id;
				}
				else
				{
					$error_message = $this->errors->get_error_message($query);
					return $error_message;
				}
			}
		}
		/**
			* Get total number of record stored in table
			* @access public 
			* @pram string table name 
			* @return integer count of number of records in database
			*/
		function select_all_count($tblname, $where = '')
		{
			if ($where != "")
			{
				$this->db->where($where);
			}
			$this->db->from($tblname);
			return $this->db->count_all_results();
		}
		/**
			* Get all records stored in table
			* @access public 
			* @pram string table name 
			* @return Array all records
			*/
		function select_all_records($tblname, $per_page, $page, $fields = "")
		{
			if ($per_page > 0)
			{
				$this->db->limit($per_page, $page);
			}
			if ($fields != "")
			{
				$this->db->select($fields);
			}
			$query = $this->db->get($tblname);
			$error_message = $this->errors->get_error_message($query);
			if ($error_message == 'yes')
			return $query->result_array();
			else
			return $error_message;
		}
		/**
			* This is general function which returns all records of a table based on where condition paassed.
			* @access public 
			* @pram string table name 
			* @pram integer per_page 
			* @pram integer page 
			* @pram Array where array
			* @return Array all records
			*/
		function select_all_where_records($tblname, $wherearray, $per_page, $offset, $fields = "")
		{
			$this->db->_protect_identifiers=false;
			if ($fields != "")
			{
				$this->db->select($fields, FALSE);
			}
			if ($per_page > 0)
			{
				$query = $this->db->get_where($tblname, $wherearray, $per_page, $offset);
			}
			else
			{
				$query = $this->db->get_where($tblname, $wherearray);
			}
			$error_message = $this->errors->get_error_message($query);
			if ($error_message == 'yes')
			return $query->result_array();
			else
			return $error_message;
		}
		/**
			* This is general function which updates table.
			* @access public 
			* @pram string table name 
			* @pram Array where array
			* @return int rows affected.
			*/
		function update_records($tblname, $wherearray, $data, $flg = 0)
		{
			$folder = $this->uri->segment('1');
			$controller = $this->uri->segment('2');
			$controller = $folder.':'.$controller;
			$this->db->where($wherearray);
			$query = $this->db->update($tblname, $data);
			$affected = $this->db->affected_rows();
			if ($flg > 0)
			{
				echo $this->db->last_query();
			}
			$updated_id = $this->db->get_where($tblname, $wherearray);
			$id = $updated_id->row_array();
			$req_id = array_values($id);
			$error_message = $this->errors->get_error_message($query);
			if ($error_message == 'yes')
			{
				if ($affected > 0)
				{
					$this->logs->activity_logs($controller, 'common_model', $this->db->database, $tblname, $req_id[0], 'updated');
				}
				return $req_id[0];
			}
			else
			{
				return $error_message;
			}
		}
		function insert_update($tblname, $wherecon, $data_array)
		{
			if ($tblname != '' && $wherecon != '' && is_array($data_array))
			{
				if ($this->common_model->chkrecordexists($tblname, $wherecon))
				$result = $this->common_model->update_records($tblname, $wherecon, $data_array);
				else
				$result = $this->common_model->insert_records($tblname, $data_array);
				
				return $result;	
			}
		}
		function delete_records($tblname, $whereArray)
		{
			$folder = $this->uri->segment('1');
			$controller = $this->uri->segment('2');
			$controller = $folder.':'.$controller;
			$updated_id = $this->db->get_where($tblname, $whereArray);
			$id = $updated_id->row_array();
			$req_id = array_values($id);
			$this->db->delete($tblname, $whereArray);
			$this->logs->activity_logs($controller, 'common_model', $this->db->database, $tblname, $req_id[0], 'deleted');
			return true;
		}
		/**
			* This is general function which returns all records of a table based in order of spicified order by field.
			* @access public 
			* @pram string table name 
			* @param string order by condition
			* @return Array all records
			*/
		function select_all_records_orderby($tblname, $where = "", $orderby, $type, $page = '', $per_page = '', $fields = "")
		{
			if ($where != "")
			{
				$this->db->where($where);
			}
			if ($page > 0)
			{
				$query = $this->db->limit($page, $per_page);
			}
			if ($fields != "")
			{
				$this->db->select($fields, false);
			}
			$query = $this->db->order_by($orderby, $type);
			$query = $this->db->get($tblname);
			$error_message = $this->errors->get_error_message($query);
			if ($error_message == 'yes')
			return $query->result_array();
			else
			return $error_message;
		}
		function chkrecordexists($tablename, $where)
		{
			$query = $this->db->get_where($tablename, $where);
			$error_message = $this->errors->get_error_message($query);
			if ($error_message == 'yes')
			return $query->num_rows();
			else
			return false;
		}
		function max_id($table_name, $field_name, $where = "")
		{
			$this->db->select_max($field_name);
			if ($where != "")
			{
				$this->db->where($where);
			}
			$query = $this->db->get($table_name);
			return $query->result_array();
		}
		/**
			* Encrypt string by encryption key specified
			* @access public
			* @param string value to be encrypted
			* @param string key by which encryption done (Default is '')
			* @return string encrypted string
			*/
		function encrypt($string, $key = '')
		{
			$key = trim($key);
			if ($key == '')
			{
				$key = $this->license_parse('en_key');
			}
			$result = '';
			$i = 0;
			while($i < strlen($string))
			{
				$char = substr($string, $i, 1);
				$keychar = substr($key, $i % strlen($key) - 1, 1);
				$char = chr(ord($char) + ord($keychar));
				$result .= $char;
				++$i;
			}
			return base64_encode($result);
		}
		/**
			* Decrypt string by decryption key specified
			* @access public 
			* @param string value to be decrypted
			* @param string key by which decryption done (Default is '')
			* @return string decrypted string
			*/
		function decrypt($string, $key = '')
		{
			$key = trim($key);
			if ($key == '')
			{
				$key = $this->license_parse('en_key');
			}
			$result = '';
			$string = base64_decode($string);
			$i = 0;

			while($i < strlen($string))
			{
				$char = substr($string, $i, 1);
				$keychar = substr($key, $i % strlen($key) - 1, 1);
				$char = chr(ord($char) - ord($keychar));
				$result .= $char;
				++$i;
			}
			return $result;
		}
		/**
			* check user rights 
			*
			* This allows developer to check rights per page.<br>
			* Developer can check for the rights that user has for a page.<br>
			* @access public
			* @param int permission
			* @returns bool true/false
			*/
		function checkRights($pid)
		{
			$user_id = $this->session->userdata('USERID');
			$this->db->select('gid');
			$this->db->from('users');
			$this->db->where('id', $user_id);
			$query = $this->db->get();
			$row = $query->first_row();
			$gid = $row->gid;
			$acl_ar = array();
			$this->db->select('aclid');
			$this->db->from('acl_grp');
			$this->db->where('gid', $gid);
			$query = $this->db->get();
			$rows = $query->result();
			foreach($rows as $row)
			{
				array_push($acl_ar, $row->aclid);
			}
			if (!in_array($pid, $acl_ar))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		/**
		* convert raw bytes to User readble units
		* @access public
		* @param int size (bytes)
		* @return string readable byte format (eg 1024 Bytes to 1KB)
		*/
		function converter($size,$with_space="",$unit="B")
		{
			$names = array('B', 'KB', 'MB', 'GB', 'TB');
			$startfrom = array_search($unit,$names); 
			if($startfrom === false)
				return $size;
			
			$times = 0;
			while($size > 1024)
			{
				$size = round(($size * 100) / 1024) / 100;
				$times++;
			}
			$times = $times + $startfrom;
			if($with_space == 'y')
			return "$size ".$names[$times];
			else
			return "$size".$names[$times];
		}
		function converter_bw($size)
		{
			$names = array('B', 'KB', 'MB', 'GB', 'TB', 'TT');
			$times = 0;
			while($size > 1000)
			{
				$size = round(($size * 100) / 1000) / 100;
				$times++;
			}
			return "$size".$names[$times];
		}
		function converter_bw_stats($size)
		{
			$names = array('b', 'Kb', 'Mb', 'Gb', 'Tb');
			$times = 0;
			while($size > 1000)
			{
				$size = round(($size * 100) / 1000) / 100;
				$times++;
			}
			return "$size".$names[$times];
		}
		function bwspeed($size)
		{
			if ($size > 0)
			{
				$names = array('bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps', 'Pbps');
				$times = 0;
				while($size >= 1000)
				{
					$size = round(($size * 100) / 1000) / 100;
					$times++;
				}
				return "$size ".$names[$times];
			}
			else if ($size == '0.00')
			return '0 bps';
			else
			return '--';
		}
		// converter_speed return Bytes.
		function converter_speed($size, $unit)
		{
			if ($unit == 'Kbps')
			{
				$byte = $size * 1000;
			}
			elseif ($unit == 'Mbps')
			{
				$byte = $size * 1000000;
			}
			elseif ($unit == 'Gbps')
			{
				$byte = $size * 1000000000;
			}
			elseif ($unit == 'Tbps')
			{
				$byte = $size * 1000000000000;
			}
			else
			{
				$byte = '';
			}
			return $byte;
		}
		function check_auth($priv = 0)
		{
			if (!$this->session->userdata('logged_in')) // user NOT logged in
			{
				header("Location: ".base_url()."user");
				die();
			}
		}
		/**
			* build ipmitool command
			* @access public
			* @param string ipAddress
			* @param string action to be performed
			* @param string port number
			* @return string chassis power status
			*/
		function ipmi_action($ipAddress, $action, $port)
		{
			$ipmi_path = $this->config->item('ipmi_path');
			$user = $this->config->item('user');
			$pass = $this->config->item('pass');
			$pass .= sprintf("%02d", $port);
			$cmd = "$ipmi_path -U '$user' -P '$pass' -I lan -H '$ipAddress' chassis power $action";
			return $this->exec_command($cmd);
		}
		/**
			* Opens a pipe to a process executed by forking the command given by $cmd. 
			* @access public
			* @param string command
			* @return string result
			*/
		function exec_command($command)
		{ 
			$rs = "";
			$hd = popen($command, "r");
			while(!feof($hd))
			{
				$rs .= fread($hd, 4096);
			}
			pclose($hd);
			return $rs;
		}
		/**
			* fetch bandwidth usage from rrd tool
			* @access public
			* @param string rrdpath path to rrd files
			* @param string switch_ip ip address of swicth
			* @param int port number
			* @return string result
			*/
		function get_rrd($rrdPath, $switch_ip, $port)
		{
			$filec = "ls ".$rrdPath."/*".$switch_ip."_".$port.".rrd";
			$filename = $this->exec_command($filec);
			# Second try 
			if (trim($filename) == "")
			{
				$port = $port - 10000;
				$filec = "ls ".$rrdPath."/*".$switch_ip."_".$port.".rrd";
				$filename = $this->exec_command($filec);
			}
			return trim($filename);
		}
		/**
			* fetch server bandwidth for last specified period.
			*
			* @access public
			* @param string file name ( rrd file )
			* @param string time period in hours ( eg last 4 hrs, 24 hrs, etc )
			* @return array result 
			*/
		function getLastBandwidth($bwfile, $period, $is_unit = "")
		{
			$file = $bwfile;
			$total_out = 0;
			$total_in = 0;
			$lastdate = 0;
			if ($fp = popen("/usr/bin/rrdtool fetch $file AVERAGE -r 60 -s -".$period."h -e now", 'r'))
			{
				fgets($fp, 4096);
				while(!feof($fp))
				{
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = explode(" ", $line);
						if (is_numeric($data[1]) && is_numeric($data[2]))
						{
							$date = substr($data[0], 0, -1);
							$in = number_format($data[1], 2, '.', '');
							$out = number_format($data[2], 2, '.', '');
							if ($lastdate != 0)
							{
								if (!is_numeric($in))
								$in = 0;
								if (!is_numeric($out))
								$out = 0;

								$in = $in * ($date - $lastdate);
								$out = $out * ($date - $lastdate);
								$total_in = $total_in + $in;
								$total_out = $total_out + $out;
							}
							$lastdate = $date;
						}
					}
				}//while

				pclose($fp);
				$total = $total_in + $total_out;
				if ($is_unit == 'n')
				$last_arr = array("in" => $total_in, "out" => $total_out, "total" => $total);
				else
				$last_arr = array("in" => $this->converter_bw($total_in), "out" => $this->converter_bw($total_out), "total" => $this->converter_bw($total));
				return $last_arr;
			}//4 hour
		}
		function getisp($bwfile, $period)
		{
			$period = 1;
			$file = $bwfile;
			$total_out = 0;
			$total_in = 0;
			$lastdate = 0;
			if ($fp = popen("/usr/bin/rrdtool fetch $file AVERAGE -r 60 -s -".$period."h -e now", 'r'))
			{
				fgets($fp, 4096);
				while(!feof($fp))
				{
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = explode(" ", $line);
						if (is_numeric($data[1]) && is_numeric($data[2]))
						{
							$date = substr($data[0], 0, -1);
							$in = number_format($data[1], 0, '', '');
							$out = number_format($data[2], 0, '', '');
							if ($lastdate != 0)
							{
								if (!is_numeric($in))
								$in = 0;
								if (!is_numeric($out))
								$out = 0;
								$in = $in * ($date - $lastdate);
								$out = $out * ($date - $lastdate);
								$total_in = $total_in + $in;
								$total_out = $total_out + $out;
							}
							$lastdate = $date;
						}
					}
				}//while

				pclose($fp);
				if ($period == 1)
				{
					$total_in = ($total_in / 60) / 8;
					$total_out = ($total_out / 60) / 8;
				}
				elseif ($period == 4)
				{
					$total_in = $total_in / (60 * 4 * 8);
					$total_out = $total_out / (60 * 4 * 8);
				}
				elseif ($period == 24)
				{
					$total_in = $total_in / (60 * 24 * 8);
					$total_out = $total_out / (60 * 24 * 8);
				}
				elseif ($period == 168)
				{
					$total_in = $total_in / (60 * 24 * 7 * 8);
					$total_out = $total_out / (60 * 24 * 7 * 8);
				}
				elseif ($period == 720)
				{
					$total_in = $total_in / (60 * 24 * 30 * 8);
					$total_out = $total_out / (60 * 24 * 30 * 8);
				}
				$total = $total_in + $total_out;
				$last_arr = array("in" => $this->converter($total_in,'y'), "out" => $this->converter($total_out,'y'), "total" => $this->converter($total,'y'));
				return $last_arr;
			}//4 hour
			exit;
		}
		/**
			* 
			* @param string switch IP
			*/
		function esnmp3_get($ip, $sec_name, $sec_level, $auth_protocol, $auth_pass, $priv_protocol, $priv_pass, $oid, $snmp_port = 161)
		{
			if ($sec_level == "noAuthNoPriv")
			$op = $this->exec_command("snmpget -v 3  -r 1 -u '".$sec_name."' -l $sec_level $ip:$snmp_port $oid ");
			elseif ($sec_level == "authNoPriv")
			$op = $this->exec_command("snmpget -v 3  -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' $ip:$snmp_port $oid");
			else
			$op = $this->exec_command("snmpget -v 3  -r 1 -u $sec_name -l $sec_level -a $auth_protocol -A '".$auth_pass."' -x $priv_protocol -X '".$priv_pass."' $ip:$snmp_port $oid");
			$opr = explode(": ", $op);
			return trim($opr[1]);
		}
		/**
			*
			* @param string switch IP
			*/
		function esnmp3_set($ip, $sec_name, $sec_level, $auth_protocol, $auth_pass, $priv_protocol, $priv_pass, $oid, $set_type, $set_value, $snmp_port = 161)
		{
			if ($sec_level == "noAuthNoPriv")
			$op = $this->exec_command("snmpset -v 3  -r 1 -u '".$sec_name."' -l $sec_level $ip:$snmp_port $oid $set_type '".$set_value."'");
			elseif ($sec_level == "authNoPriv")
			$op = $this->exec_command("snmpset -v 3  -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' $ip:$snmp_port $oid $set_type '".$set_value."'");
			else
			$op = $this->exec_command("snmpset -v 3  -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' -x $priv_protocol -X '".$priv_pass."' $ip:$snmp_port $oid $set_type '".$set_value."'");
			$opr = explode(": ", $op);
			return trim($opr[1]);
		}
		/**
			* delete directory from file system
			*
			* Recursively deletes the directory from file system.
			* If Directory is not empty then it will first deletes the inner directory.
			* @access public
			* @param string directory path
			*/
		function deldir($dir)
		{
			$current_dir = opendir($dir);
			while($entryname = readdir($current_dir))
			{
				if (is_dir("$dir/$entryname") and ( $entryname != "." and $entryname != ".."))
				{
					deldir("${dir}/${entryname}");
				}
				elseif ($entryname != "." and $entryname != "..")
				{
					unlink("${dir}/${entryname}");
				}
			}
			closedir($current_dir);
			rmdir(${dir});
		}
		function remove_spaces($file)
		{
			$str = file($file);
			$fp = fopen($file, "w");

			foreach($str as $val)
			if (trim($val) != "")
			fwrite($fp, $val);
		}
		function ip_2long($ip)
		{
			$host_int = '';
			if ($ip != '')
			{
				if (strstr($this->sys_architecture(), 'i386'))
				$host_int = ip2long($ip);
				else if (strstr($this->sys_architecture(), 'x86_64'))
				{
					if (ip2long($ip) == -1 || ip2long($ip) === FALSE)
					echo '<p class="error">Invalid IP, please try again.</p>';
					else
					{
						$ip2long = ip2long($ip);
						if (strstr(ip2long($ip), '-'))
						{
							$host_int = sprintf("%u", ip2long($ip));
						}
						else
						$host_int = ip2long($ip);
					}
				}
			}
			return $host_int;
		}
		function esnmpstatus($ip, $community, $ver = "1", $snmp_port = 161)
		{
			if ($community == "*")
			$community = "\\".$community;

			$op = $this->esds_data("snmpstatus -v $ver -c '".$community."' $ip:$snmp_port");

			if (strpos($op, "Interfaces:"))
			return true;
			else
			return false;
		}
		function esnmpget($ip, $community, $oid, $ver = "1", $snmp_port = 161)
		{
			if ($community == "*")
			$community = "\\".$community;
			$op = $this->esds_data("snmpget -v $ver -c '".$community."' $ip:$snmp_port $oid");
			$opr = explode(": ", $op);
			return trim($opr[1]);
		}
		function esnmpwalk($ip, $community, $oid, $ver = "1", $snmp_port = 161)
		{
			if ($community == "*")
			$community = "\\".$community;
			$cmd = "snmpwalk -v $ver -Cc -c '".$community."' $ip:$snmp_port $oid";
			$op = $this->exec_command($cmd);
			$ar = explode("\n", trim($op));
			$opr = array_map($this->replace_val, $ar);
			return $opr;
		}
		function esnmp3_status($ip, $sec_name, $sec_level, $auth_protocol, $auth_pass, $priv_protocol, $priv_pass, $snmp_port = 161)
		{
			if ($sec_level == "noAuthNoPriv")
			$op = $this->esds_data("snmpstatus -v 3  -r 1 -u '".$sec_name."' -l $sec_level $ip:$snmp_port");
			elseif ($sec_level == "authNoPriv")
			$op = $this->esds_data("snmpstatus -v 3 -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' $ip:$snmp_port");
			else
			$op = $this->esds_data("snmpstatus -v 3  -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' -x $priv_protocol -X '".$priv_pass."' $ip:$snmp_port");

			if (strpos($op, "Interfaces:"))
			return true;
			else
			return false;
		}
		function esnmp3_walk($ip, $sec_name, $sec_level, $auth_protocol, $auth_pass, $priv_protocol, $priv_pass, $oid, $snmp_port = 161)
		{
			if ($sec_level == "noAuthNoPriv")
			$op = $this->esds_data("snmpbulkwalk -v 3 -Cc -r 1 -u '".$sec_name."' -l $sec_level $ip:$snmp_port $oid");
			elseif ($sec_level == "authNoPriv")
			$op = $this->esds_data("snmpbulkwalk -v 3 -Cc -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' $ip:$snmp_port $oid");
			else
			$op = $this->esds_data("snmpbulkwalk -v 3 -Cc -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' -x $priv_protocol -X '".$priv_pass."' $ip:$snmp_port $oid");

			$ar = explode("\n", trim($op));
			$opr = array_map($this->replace_val, $ar);
			return $opr;
		}
		function replace_val($key)
		{
			$str = explode(": ", $key);
			return $str[1];
		}
		function ping($ip)
		{
			$op = $this->esds_data(trim($this->config->item('exe_fping'))." -r 1 -i 10 $ip"); //| awk -F' ' '{print $3 }'
			return ($op);
		}
		function ip_ping($ips)
		{
			return $this->esds_data(trim($this->config->item('exe_fping'))." -r 1 $ips");
		}
		function sys_architecture()
		{
			return $this->esds_data("/bin/uname -i");
		}
		function esds_data($cmd)
		{
			$rs = "";
			$hd = popen($cmd, "r");
			while(!feof($hd))
			{
				$rs .= fread($hd, 4096);
			}
			pclose($hd);
			return $rs;
		}
		function get_refer()
		{
			$refstr = $_SERVER["HTTP_REFERER"];
			$refar = explode("/", $refstr);
			$tmp_page = $refar[count($refar) - 1];
			$tmpar = explode("?", $tmp_page);
			$page = $tmpar[0];
			return $page;
		}
		function getLoad1($bwfile)
		{
			$file = $bwfile;
			$utilized = 0;
			$lastdate = 0;
			if ($fp = popen("/usr/bin/rrdtool fetch $file AVERAGE -r 60 -s -5min", 'r'))
			{
				fgets($fp, 4096);
				while(!feof($fp))
				{
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = array();
						$data = explode(':', $line);
						if ($data[1] != '')
						{
							$load_arr = explode(' ', $data[1]);
							if (is_numeric($load_arr[1]) && $load_arr[1] != '')
							{
								$load = number_format($load_arr[1], 2, '.', '');
								if (!is_numeric($load))
								{
									$load = 0;
								}
							}
						}
						break;
					}
				}//while
				pclose($fp);
				$last_arr = array("load" => $load);
				return $last_arr;
			}//4 hour
		}
		function getLastCPU($bwfile)
		{
			$file = $bwfile;
			$utilized = 0;
			$lastdate = 0;
			if ($fp = popen("/usr/bin/rrdtool fetch $file AVERAGE -r 60 -s -5min", 'r'))
			{
				fgets($fp, 4096);
				while(!feof($fp))
				{
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = array();
						$data = explode(':', $line);
						if (is_numeric($data[1]))
						{
							$usage = number_format($data[1], 2, '.', '');
							$date = $data[0];
							if ($lastdate != 0)
							{
								if (!is_numeric($usage))
								{
									$usage = 0;
								}
								$usage = $usage * ($date - $lastdate);
								$utilized = $utilized + $usage;
							}
							$lastdate = $date;
						}
					}
				}//while
				pclose($fp);
				$last_arr = array("usages" => $utilized);
				return $last_arr;
			}//4 hour
		}
		function getLastRAM($bwfile)
		{
			$file = $bwfile;
			$total_out = 0;
			$total_in = 0;
			$lastdate = 0;
			if ($fp = popen("/usr/bin/rrdtool fetch $file AVERAGE -r 60 -s -5min", 'r'))
			{
				fgets($fp, 4096);
				while(!feof($fp))
				{
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = explode(" ", $line);
						if (is_numeric($data[1]) && is_numeric($data[2]))
						{
							$date = substr($data[0], 0, -1);
							$in = number_format($data[1], 2, '.', '');
							$out = number_format($data[2], 2, '.', '');
							if ($lastdate != 0)
							{
								if (!is_numeric($in))
								$in = 0;
								if (!is_numeric($out))
								$out = 0;
								$in = $in;
								$out = $out;
								$total_in = $total_in + $in;
								$total_out = $total_out + $out;
							}
							$lastdate = $date;
						}
					}
				}//while
				pclose($fp);
				$total = $total_in + $total_out;
				$last_arr = array("total" => $total, "util" => $total_out);
				return $last_arr;
			}//4 hour
		}
		function getLoad($file)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:load_avg1:AVERAGE:step=60 ".
			" DEF:OUT=$file:load_avg5:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" CDEF:COUT=OUT,1,* ".
			" PRINT:CIN:LAST:%6.2lf ".
			" PRINT:COUT:LAST:%6.2lf ";
			$rrdcommand .= " --end now --start end-24h --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last Disk Val' ";
			//echo $rrdcommand."\n";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['1'] > 0)
			$current_in = $last_data_array['1'];
			else
			$current_in = 0;
			if ($last_data_array['2'] > 0)
			$current_out = $last_data_array['2'];
			else
			$current_out = 0;
			return $last_arr = array("load" => $current_in);
		}
		function getLinuxCPU($file)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:cpu_user:AVERAGE:step=60 ".
			" DEF:OUT=$file:cpu_system:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" CDEF:COUT=OUT,1,* ".
			" PRINT:CIN:LAST:%6.2lf ".
			" PRINT:COUT:LAST:%6.2lf ";
			$rrdcommand .= " --end now --start end-24h --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last Disk Val' ";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['1'] > 0)
			$current_in = $last_data_array['1'];
			else
			$current_in = 0;

			if ($last_data_array['2'] > 0)
			$current_out = $last_data_array['2'];
			else
			$current_out = 0;

			$total = $current_in + $current_out;
			return $last_arr = array("usages" => $total);
		}
		function getwinCPU($file)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:cpu_usage:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" PRINT:CIN:LAST:%6.2lf ".
			$rrdcommand .= " --end now --start end-24h --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last Disk Val' ";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['1'] > 0)
			$current_in = $last_data_array['1'];
			else
			$current_in = 0;

			return $last_arr = array("usages" => $current_in);
		}
		function getWinRAM($file)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:mem_usage:AVERAGE:step=60 ".
			" DEF:OUT=$file:total_ram:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" CDEF:COUT=OUT,1,* ".
			" PRINT:CIN:LAST:%6.2lf ".
			" PRINT:COUT:LAST:%6.2lf ";
			$rrdcommand .= " --end now --start end-24h --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last Disk Val' ";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['1'] > 0)
			$current_in = $last_data_array['1'];
			else
			$current_in = 0;

			if ($last_data_array['2'] > 0)
			$current_out = $last_data_array['2'];
			else
			$current_out = 0;
			if ($current_out > 0)
			$used_perc = ($current_in / $current_out) * 100;
			else
			$used_perc = 0;

			return $last_arr = array("used" => $current_in, "total" => $current_out, 'used_perc' => $used_perc);
		}
		function getLinuxRAM($file)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:ram_used:AVERAGE:step=60 ".
			" DEF:OUT=$file:total_ram:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" CDEF:COUT=OUT,1,* ".
			" PRINT:CIN:LAST:%6.2lf ".
			" PRINT:COUT:LAST:%6.2lf ";
			$rrdcommand .= " --end now --start end-24h --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last Disk Val' ";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['1'] > 0)
			$current_in = $last_data_array['1'];
			else
			$current_in = 0;

			if ($last_data_array['2'] > 0)
			$current_out = $last_data_array['2'];
			else
			$current_out = 0;

			if ($current_out > 0)
			$used_perc = ($current_in / $current_out) * 100;
			else
			$used_perc = 0;

			return $last_arr = array("used" => $current_in, "total" => $current_out, 'used_perc' => $used_perc);
		}
		function getLastdiskval($file)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:disk_used:AVERAGE:step=60 ".
			" DEF:OUT=$file:disk_total:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" CDEF:COUT=OUT,1,* ".
			" PRINT:CIN:LAST:%6.2lf ".
			" PRINT:COUT:LAST:%6.2lf ";
			$rrdcommand .= " --end now --start end-24h --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last Disk Val' ";
			//echo $rrdcommand."\n";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['1'] > 0)
			$current_in = $last_data_array['1'];
			else
			$current_in = 0;

			if ($last_data_array['2'] > 0)
			$current_out = $last_data_array['2'];
			else
			$current_out = 0;
			return $last_arr = array("used" => $current_in, "total" => $current_out);
		}
		function getLastdiskiops($file)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:iop_read:AVERAGE:step=60 ".
			" DEF:OUT=$file:iop_write:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" CDEF:COUT=OUT,1,* ".
			" PRINT:CIN:LAST:%6.2lf ".
			" PRINT:COUT:LAST:%6.2lf ";
			$rrdcommand .= " --end now --start end-24h --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last Disk Val' ";
			//echo $rrdcommand."\n";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			$current_in = $last_data_array['1'];
			$current_out = $last_data_array['2'];
			return $last_arr = array("read" => $current_in, "write" => $current_out);
		}
		function getLastpacket($file)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:ping_latency:AVERAGE:step=60 ".
			" DEF:OUT=$file:packet_loss:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" CDEF:COUT=OUT,1,* ".
			" PRINT:CIN:LAST:%6.2lf ".
			" PRINT:COUT:LAST:%6.2lf ";
			$rrdcommand .= " --end now --start end-24h --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last Ping Val' ";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['2'] > 0)
			$packet_loss = $last_data_array['2'];
			else
			$packet_loss = 0;

			if ($last_data_array['1'] > 0)
			$latency = $last_data_array['1'];
			else
			$latency = 0;
			return $last_arr = array("packet_loss" => $packet_loss, 'response_time' => $latency);
		}
		function getLastPower($bwfile)
		{
			$file = $bwfile;
			$used_current = 0;
			$avail_current = 0;
			$tot_voltage = 0;
			$lastdate = 0;
			if ($fp = popen("/usr/bin/rrdtool fetch $file AVERAGE -r 60 -s -5min", 'r'))
			{
				fgets($fp, 4096);
				while(!feof($fp))
				{
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = explode(" ", $line);
						if (is_numeric($data[1]) && is_numeric($data[2]))
						{
							$date = substr($data[0], 0, -1);
							$used = number_format($data[1], 2, '.', '');
							$avail = number_format($data[2], 2, '.', '');
							$voltage = number_format($data[3], 2, '.', '');
							if ($lastdate != 0)
							{
								if (!is_numeric($used))
								$used = 0;
								if (!is_numeric($avail))
								$avail = 0;
								if (!is_numeric($voltage))
								$voltage = 0;
								$used = $used;
								$avail = $avail;
								$voltage = $voltage;
								$used_current = $used_current + $used;
								$avail_current = $avail_current + $avail;
								$tot_voltage = $tot_voltage + $voltage;
							}
							$lastdate = $date;
						}
					}
				}//while
				pclose($fp);
				$last_arr = array("used" => $used_current, "avail" => $avail_current, "voltage" => $tot_voltage);
				return $last_arr;
			}//4 hour
		}
		function get_field_names($field_name)
		{
			$input_value = str_replace(' ', '', $field_name);
			return $input_value;
		}
		/**
			* Get badnwidth for last specified months
			* @access public
			* @param string serverId (srvDepID)
			* @param int limit	
			* @return array bandwidth usage ( e.g. 24MB )
			*/
		function getBandwidthForMonths($interface_id, $limit)
		{
			$total_raw_bw = 0;
			$date = date("Y-m");
			$this->db->select('*');
			$this->db->where('interface_id', $interface_id);
			$this->db->order_by('date', 'desc');
			$this->db->limit($limit);
			$query = $this->db->get('em_monthly_bw');
			return $query->result();
		}
		function curr_cpu_usage($bwfile)
		{
			$file = $bwfile;
			$utilized = 0;
			$lastdate = 0;
			if ($fp = popen("/usr/bin/rrdtool fetch $file AVERAGE -r 60 -s -5min", 'r'))
			{
				fgets($fp, 4096);
				while(!feof($fp))
				{
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = explode(':', $line);
						$usage = number_format($data[1], 2, '.', '');
						echo $usage;
						exit;
						if ($lastdate != 0)
						{
							if (!is_numeric($usage))
							{
								$usage = 0;
							}

							$usage = $usage * ($date - $lastdate);
							$utilized = $utilized + $usage;
						}
						$lastdate = $date;
						$data = "";
					}
				}//while
				pclose($fp);
				$last_arr = array("usages" => $utilized);
				return $last_arr;
			}//4 hour
		}
		function data_transfer($data)
		{
			return $data > 0 ? round((($data * 8) / 3600) / 1000000, 2).' Mbps' : '--';
		}
		function data_convertor($size)
		{
			$names = array('B', 'KB', 'MB', 'GB', 'TB');
			$times = 0;
			while($size > 1024)
			{
				$size = round(($size * 100) / 1000) / 100;
				$times++;
			}
			return number_format($size, 2)." ".$names[$times];
		}
		function get_status($statusid = "")
		{
			$final_array = array();
			$this->db->select('status_id,status_name');
			$this->db->where('status', 'y');
			$this->db->from($this->tbl_prefix.'status_master');
			if ($statusid > 0)
			$this->db->where('status_id', $statusid);
			$query = $this->db->get();
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$final_array[$row['status_id']] = $row['status_name'];
				}
			}
			return $final_array;
		}
		function get_deployment_types($id = "", $type = "")
		{
			$final_array = array();
			$this->db->select('dep_type_id,dep_type,dep_desc');
			$this->db->where('status', 'y');
			$this->db->from($this->tbl_prefix.'deployment_type_master');
			if ($id > 0)
			$this->db->where('dep_type_id', $id);
			if ($type != '')
			$this->db->where('dep_type', trim($type));

			$query = $this->db->get();
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$final_array[$row['dep_type_id']] = array($row['dep_type'], $row['dep_desc']);
				}
			}
			return $final_array;
		}
		function get_subnets($id = "")
		{
			$final_array = array();
			$this->db->select('*');
			$this->db->where('status', 'y');
			$this->db->from($this->tbl_prefix.'subnet');
			if ($id > 0)
			$this->db->where('subnet_id', $id);
			$query = $this->db->get();
			//echo $this->db->last_query();
			return $query->result_array();
		}
		function get_field_value($field, $table, $where = "")
		{	
			$this->db->select($field, FALSE);
			$this->db->from($table, FALSE);
			$this->db->where($where, '', FALSE);
			$query = $this->db->get();

			$error_message = $this->errors->get_error_message($query);
			if ($error_message == 'yes')
			{
				$row = $query->row_array();
				return $row[0];
			}
			else
			return $error_message;
		}
		
		function close_cms_request($req_id)
		{
			$user_id = $this->session->userdata('EMUSERID');
			if ($req_id > 0)
			{
				$last_value = $this->select_all_where_records($this->tbl_prefix.'itil_crf_details', "crf_id = '".$req_id."'", '', '');
				$dev_id = '';
				if ($last_value[0]['object_id'] != '')
				{
					$devices = explode(",", $last_value[0]['object_id']);
					if (count($devices) > 1)
					{
						return "yes";
					}
					$dev_id = $last_value[0]['object_id'];
				}
				$update_array = array('current_status' => 'n');
				$where = "crf_id = '".$req_id."'";
				$this->update_records($this->tbl_prefix.'itil_crf_details', $where, $update_array);
				// add cms request record into device update history
				$request_name = $this->common_model->get_field_value('request_id', $this->tbl_prefix.'itil_crf', "crf_id = '".$req_id."'");
				if ($dev_id != '')
				{
					$dev_id_arr = explode(",", $dev_id);
					$dev_id_arr_cnt = count($dev_id_arr);
					for($dev_cnt = 0; $dev_cnt < $dev_id_arr_cnt; $dev_cnt++)
					{
						$this->common_model->insert_records($this->tbl_prefix.'object_track', array('object_id' => $dev_id_arr[$dev_cnt], 'change_type' => 'close', 'description' => "Change Request : ".$request_name."<br>".'The request has been used.', 'user_id' => $user_id));
					}
				}
				$insert_array = array('crf_id' => $req_id,
				'status_id' => 17,
				'from_user_id' => $user_id,
				'object_id' => $last_value[0]['object_id'],
				'dep_type_id' => $last_value[0]['dep_type_id'],
				'reason' => $last_value[0]['reason'],
				'priority' => $last_value[0]['priority'],
				'assumptions' => $last_value[0]['assumptions'],
				'reference' => $last_value[0]['reference'],
				'schedule_impact' => $last_value[0]['schedule_impact'],
				'cost_impact' => $last_value[0]['cost_impact'],
				'tech_impact' => $last_value[0]['tech_impact'],
				'business_impact' => $last_value[0]['business_impact'],
				'pm_comments' => $last_value[0]['pm_comments'],
				'crm_explanation' => $last_value[0]['crm_explanation'],
				'backup_plan' => $last_value[0]['backup_plan'],
				'implementation_steps' => $last_value[0]['implementation_steps'],
				'backup_plan' => $last_value[0]['backup_plan'],
				'role_ids' => $last_value[0]['role_ids'],
				'date' => date('Y-m-d G:i:s')
				);
				$data = $this->insert_records($this->tbl_prefix.'itil_crf_details', $insert_array);
				return 'yes';
			}
			else
			{
				return 'no';
			}
		}
		function insert_cms_log($crf_id, $record_id, $tbl_name, $action)
		{
			if ($crf_id > 0)
			{
				$user_id = $this->session->userdata('EMUSERID');
				$folder = $this->uri->segment('1');
				$controller = $this->uri->segment('2');
				$insert_log_array = array(
				'crf_id' => $crf_id,
				'user_id' => $user_id,
				'controller' => $controller,
				'model' => 'common_model',
				'db_name' => $this->db->database,
				'tablename' => $tbl_name,
				'recordid' => $record_id,
				'action' => $action,
				'ip_addr' => $_SERVER['REMOTE_ADDR']
				);
				$this->insert_records($this->tbl_prefix.'itil_crf_logs', $insert_log_array);
			}
		}
		function table_status($table_name)
		{
			$query = $this->db->query("SHOW TABLE STATUS LIKE '".$table_name."'");
			$result = $query->result_array();
			return $result;
		}
		function generate_password($length = 9, $strength = 0)
		{
			$vowels = 'aeuy';
			$consonants = 'bdghjmnpqrstvz';
			if ($strength & 1)
			{
				$consonants .= 'BDGHJLMNPQRSTVWXZ';
			}
			if ($strength & 2)
			{
				$vowels .= "AEUY";
			}
			if ($strength & 4)
			{
				$consonants .= '23456789';
			}
			if ($strength & 8)
			{
				$consonants .= '@#$%';
			}

			$password = '';
			$alt = time() % 2;
			for($i = 0; $i < $length; $i++)
			{
				if ($alt == 1)
				{
					$password .= $consonants[(rand() % strlen($consonants))];
					$alt = 0;
				}
				else
				{
					$password .= $vowels[(rand() % strlen($vowels))];
					$alt = 1;
				}
			}
			return $password;
		}
		function mailtemplate($templateid, $value_array = array())
		{
			$ret_array = array();
			if ($templateid > 0)
			{
				$where_template = "template_id = '".$templateid."'";
				$template_details = $this->select_all_where_records($this->tbl_prefix.'email_templates', $where_template, '', '');
				if (is_array($template_details) && count($template_details) > 0)
				{
					$subject = $template_details[0]['subject'];
					$body = $template_details[0]['email_body'];
					if ((is_array($value_array) && count($value_array) > 0) && $subject != '' && $body != '')
					{
						$variables = $this->config->item('mail_variables');
						foreach($value_array as $key => $value)
						{
							if (in_array($key, $variables))
							{
								$subject = str_replace($key, $value, $subject);
								$body = str_replace($key, $value, $body);
							}
						}
						$body = $this->mailtheme($body);
						$ret_array['subject'] = $subject;
						$ret_array['body'] = $body;
					}
				}
			}
			return $ret_array;
		}
		function mailtheme($content)
		{
			$img_content = 'iVBORw0KGgoAAAANSUhEUgAAAHoAAAAuCAYAAADnRg0FAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyBpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNSBXaW5kb3dzIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjk5QTJEMjNDQzA5NTExRTM4M0I5QjA4RDk2MThFNTE2IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjk5QTJEMjNEQzA5NTExRTM4M0I5QjA4RDk2MThFNTE2Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6OTlBMkQyM0FDMDk1MTFFMzgzQjlCMDhEOTYxOEU1MTYiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6OTlBMkQyM0JDMDk1MTFFMzgzQjlCMDhEOTYxOEU1MTYiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz7yxHf7AAAYi0lEQVR42uxcC1RV1brea79AQAQFFQFRRBTlJT7wkYhQZglqPhvHMlPLHtfyWZrlsJu3HHm6mZnjaFrqsTQrn5WahJqmgoamIKCCyEsFFUFAYL/u9+8z526yXGwBKbsN9xhr7M1aa871z//x/d8/51xIFotF9eDz9/9IDwz9wNC/3yRJDzR1j3qW/RaV3qSRVpc9/wqGloRv+YMswrfFTntJQaH1aftnGZkfapl88uNva2i5EtSCMkgwMzsswrfcQcR2oqBmWXvLfTSyhh2ijCSXSSbn39rQaqYELTvUAryRIozsMMuMXVdblaytiR33w9iSIJ9OJiMfl0EwuOUva+jGkjnpP51KXAmLFi3yDA8P9zUYDPrq6mqVg4OD6saNGyXTpk3LxvUarhA8zyxvi0P/+eefB7i6urYwGo0mnU4n4bsUbS+UlJRUc4Nb/mTmCTHVTEb91KlT3d599925np6er2CM2ampqa9FRET8hGvVgrH/MENbL9ztuFvHjTkEb2+GowUGPrWqqioNRr6E74v4zi0rK0vs3bu3L11n92kFONSyc27Lly/vg3v3o00+a3vp1q1be+Pi4rxx3YUUTZHUWFnvYYw0PgccrocOHRpqMpnKLewDeXc9//zz7XHNmTmr1FTQrXRom8hzJQVipUSKVLKo4vCrRRR66vX6AHSl5xfxt8/ChQsj4+PjDwpQLOZmkt9h8ODBsS4uLg+hrVYYsFGr1TriZ7mSEiU7MFWfyJfql89s3AOyOKnVamd+Ab9dAgMDnfDzxl1YuhJTbzBbVzeBjdWyXKlXOLScjND9MsewRmdlZaXRbDZXyxUeFhYWz6KC9yHmZp2bm5tjmzZtokQjWxOg0ViBqNaIRI196PkaWe7UCXlew2VUMibvQ4EfKB02AnbixIlcjDGZ94O0dBjp5qYCcZP3rZN9a+ogn3Y/jY1oOePVyJilWoH9moSDEw/b4ABrGqUc5+HhETV8+PCWO3fu5LmMs1OrIpYuXdq9efPmQQpRSX2q7TB7jQLLt8hkNctsLckcTXOXspDLqZ4+fXqBn5/fu0hFwysqKi5/8sknm9PS0mpk96oVWLpITuW6NNc3urWNNLJYNogep2vWrJkWg2rTuXNn14KCgkp47dVLly5VMSMZBKYpGlpCNBMxvMNDQcrazpw5MwqG/pY9xyzIruvfv3807mmnJCj1KXNKiyw6RJSQs3yjwNotsnShVWgvN7TozFaDwWFP4vs3ds2gUEGI/YsRLPYp6pHLd1djNzai1SLjJXt89dVXPfv06fOYu7t7uEajccWhh6INMFIZYOrk8ePH94wbN+4EY5mSkG8lIYLvEBa5zCEoKOhR/NzJnmniTjZgwAAXwHZPtNPVM1dKPJ189tln3SIjI/sDDfwodwLqb4EgXdq3b9/B2bNn5whyikqsNeaDBw/G+vr6hoBftAABLMvKyjr76KOPHkpMTBzYvn17f/QrlZaWFvTo0WPXxIkT3WfMmPEInueB+zW5ubnJw4YNO4D0Iodra99r164Nwvhioc8g5PfmQCfceivzwIEDP0yZMiVDhkKmRrO0Oli3JDDJ5jg8IWxQdnb2CrDdPBjWaFH40Hlczz1//vz/dunSpSPaeRATZYzYHUe75OTk+VB2GW+DgVXz37dv386bM2dOP9zXht1PR9utW7c+iX4Lfn+M2UDf9AeUkhoVFRXEnuXCGLrLxx9/3PXKlSvL0S6blEePYo1NZGw8KxOfRSQTjpZCWyfG/tvASR6+fv36HpRJV4lXUB/0XVNTUwT02gLjnkTfVThXVV5efgptOi9btuxR6pvuw1FTVFT0KRzYi/XpzPTZCof3uXPnFlP1gD5uy/R4Gw51IScnZ8HYsWPbMpl0Yqqs04YNMDT3PD0bvMdLL70UfvXq1R1ssHf90OALCwu/7NevXwAzgDsfnGhocgwo7Ag3GtpVnjp1aiHu82XGpsMXCllGBmKGvXTz5s0sZmwlQ5NSXC9fvvx2XQ4pyFmB538kGLsFk7XNli1bhiHHnq2rLcnDZWJypKFdd9TQcTB0Dj8PvW3w9vb2Yf27cT3k5eWtoufbk4/GiPteZeOql6HVjYRsPUiF6xtvvDHL09MzTiyJ7DYGDLdt23bsF198QUI6MiE1cvZPJOy33347BoUWsnaOgMJoATb1zz77rCeIWgRjwCqgxVGw2mI7lYQVhvPz809DkUVCHr9NkSOT08nHx2fy/v37hzA5rQeM5Q9oft3JyakW+SNkICflsnOZ2DWCVR1k05IPCefJoUTW73D69Omn27Vr9xQ9XzBcDZDjBsnJzyHF7Pjpp58SGlJiaRtIwmyGBgTGe3l5jRUHRR8MKBvKPIzvEtS2nlBYjKOjY1vBiFoMZsyePXuODx06NJERDLlxJMBTAUV1t27dxtLfUG7gypUrQ4EiqXQD8n0v5LvuzFjVR48eTUK97cXlUcr3dHrUqFFHjxw5sqVly5ZDUPJ8uXv37jQoU9e1a9d2jz322LjWrVtHUh9U54aGhj6NNryGV48YMSLe1dV1AO8MjngOfazbsGHDMcC25YUXXogBT/kv5GB3wVDW8pEqAJFs0t+Qm5dQqvnz57dHXf0iNzI5BdBn97Zt2/4N3nApJibGffz48aPRphIB9uG6detuNGgevxGzWC1nzZrVAzkqQY54Fy9e3PzMM89EwQGCEG1dEL3dFi9ePAL3JsvhB9DzBRzAH/3R7FDHpKSkN8QcvWrVqhlLliyZxuGbjHn27NkVBIN6vT4YEbya33vt2rUUEJfH4WAH+DnkxjMCdDdnMEcQ2bZnz55do6Oje6EGD4cD9UKl0BeEp39sbGwcoiWb94E8Wfjiiy8OpBz75ptvxgCGUwTekAtlTwDj74LrNI5OcOzuv/zyyzuUg/l9JSUlZ3Ct97x580bDMWx9w4k3Ag07s/F3QKn1P5TaODoD2n9EkISwvgni21JODwgI8GT8xomhWy3W3xTQbSviAV+hUFJf8SJKqb1z585dvn79+jx4YhmUXwbSUwYFpSL6/xsk4qp4PykZTtBFCbrpAwOoochMKDeDRageDLs3zjvBs73hSD34vYj+Y1BwCZi+5i4LJ1b50YV+9OjRfijZ4hHdE8Gen9y8eXMUZDZA5lRStLUA1micO3XqRIrVgzkHwJC2Z8Kp9k2aNCkZ46qg4CYwg3NVvP3229/h3JX6KJRFtAbOogPKRVNqY9Fe8c033/wLz7hF/fL+09PTb124cKFCnPuvb0RrGwDbNuiGV/URp/OIrf7www+bIFwxE6BGqPs0a9asuRQXF/cVIukV3gZR1CE8PJw8+gKvo2X1s6q4uPg2BvYTlGzNic7Ozn5wnOBWrVo5cdgmogYid5zEgGHUMrQSjSyhnSNkiRw0aNArKFsGipEAzqEaPHgw5e9aK2SAYUccOn9//24C7FYiIn9jJRifI7A+G05TgmgvAFr5CinE3lq6BuVSG+ijPT8Jh8l8+eWXz7D+bzN9mu5lDVvbwIi2Kg1K9hcvIAqOoo7OkUWQhQ8enmkEeUiGwaqIWLFocUKebFcXeYJuzYD86l27diWGhYVNpXaA7FZwmEdATqrQntKICtB4PCEhgZ5txD1KEybWKIeRtZAxGkZeBpj2UHom5AmVn0PuVQFJ9MjNbQTHxmNLrguTP9zQNH3KJzPqkzatuurbt68P1wt9UJ5lCP0ahOht9Fx3Y8iYGt7nI15APskFBFZ36NDBGQaCHrUUXWYoRIODGKcDcnIVctRVmqDg7aA8LxhPQ0RGHtHogyLLuH379jyQnMMgSQ/THHXnzp0f4dDKUsYJ1NPFbB7doqBIazQjrfihrHtdNDIZDDBbTPUu2mJYzbzJAeXOAsjWoF0z4ZyhmtZSa09L8uleSwPW7603QifOIqkFNyhV2DRxT0uYDYFuG4TDmC3Fi76+vhFgvS/gvNVA8E4aLJEKKyTDkBLOu+G6iyxqnQChGjjKHTM75ChkC5CuyoyMjAQyND0bkGgzFCL72smTJ1PqWstlc91qtNEAliORm0P4NapvIfO6FStWHAZqlI4ZM8YDJPPxiIiIyUgbrRXW7S1C+aVFnzpVE31Ex2X9q+4lepsioq3fHDb5B0aIoKPBD9dqHQhS7RjahHxlgCGSIiMjL8MAXuI9gPYTgOPzDCo1dUS0OiQkxAnwGyLWvYmJiatHjhy5j+VBC/jFFRxfwrE6goDVKhvBFeBThgpBNidEv7OQ/03iAkQD9jdY2EpWuVhjY5xusgUNdR0GtzQk7zZ41Yqzw3v9EEtu0aKFPYGtE/fI70Vg8vvkpBXnTqAevyGb3L9DZpR5DohAd6HWz0tJSbkk285jza2AzZtiX0AdCxzKAIcrEuR2QdppJZv7tk58oL2mvoZmjmn++eef88UJEaSKTrIl1HteqmyoofnmAUNTGJrYLAxgzUUE9wo52hrVgOeK1NTUAyLEQaH5Z86cSRGMbLbjUJJMwXpacJCtxEnC+rokyGh1KpRwF8X23t7eIYg8R3HmjK0BgHboPWSoItnRp2nDhg3FSPmF/CRydhekET/WXzPhsD1DNqsoNaWhbTtFiLyIF1BOFIGFppeVlWUg96XD+8/aO2iuGIY6h99pYOyVdqLRzCMOZQu1Oy1MA55cvnx5uriUWMdsmAWEDY+rKhFqdJ9x48YN4ytFXIGxsbGtkIKCRdimiKb+09LScmhhhp9H3fvIp59+2o9NXDizw+nHH38cif796hnRfG3ZWFRUdJR2xTAnd3v11VdfEBZTrP17eHi4vPPOO76y6WOpPobWNiKiLSBXpVCArdzIzMz8EYPeAUFMiFADPNrESZlCXjZTFMNrJSivOD09nQytV7pXZLXIn1enTZu2GzV8OBRSDcUk/frrrxXsutJWG57nzYj8CsD8GT8/P052HMHen8zNzfVAnX4cDlQKePft2LHjYJRYPXgAkOOwPgybN2++9PTTTye2b99+EsujrZ944on5SUlJ3yCvZ6GdK6qObqi3R6D/ZvJxyJ2QxovDtoFg06ZN2+bPnz8B+rESQR8fn+Go1VUnTpzYl5WVVRgcHNy6V69eQ5ydnTuBOK4PCgraJGP9kr2crW2IgXnHMHQBHhgo5BQ3KCKfSAWbQKixM2tjkbFkLdvJYbGHJIDOqnPnziWBKFWBGBUcP348SfX7NmC10iChNKsiEYmqvXv3JoGUnYbcoeyaC6qFkV5eXjEgZzVUEYi1rLBvjfowIHXUgKXvgkM8gnPebNyBUP7M0NDQUlp/Z3PcipsQeCUiOiEzNI3BsnDhwuwJEyZshKPMYo6gh1ONoW1SQNByyOvMgwtO2gm6DoFzzZXtQGkS6LblTEBujngBkRC7cuXKvrIoNCkU/WLxX599zLUYZ0JCQjbSRAocLQcQliXro5YiCRZhEBvZ+uijj/IRfR8T45Y5gyui04MbmeabRbkAw3ymr3revHkpZ8+eXS7ubaPopUUbVnJKLC8bhRxtHS+TpZahcYhEsHrmzJmrr127tl02Q9gaDN9fRFC0c8fRvCF7xtQNjGhrhCC6jouEjMqt4cOHz/r666+jVLU30ysRBUlp3xaUZxE3+CEKjfK9WR9++OGN06dPr0Zkf33x4kXDa6+95gbW3X3NmjWUt8xUrgkGdEP02vZXgUPUIAcnbN269eXS0tIUhVrWgH7/XVhYuFeoLhzd3KjSsZZg1Rh3+eTJk79KTk6eAeZ+to4+tiEQ8oRSjti00dPTUxLnEfBbj75F1l+zc+fO6xjTAkA2OeRNJUPgfFleXt57S5YsWaRqwBse9d3ALwnbXBwXLVoUMGPGjGUojfrVqoWMxnJ45C8gP0dOnTqVjOsSyE1zNDcJRrbwyQdAsBpt1DCCGfkzALDYnSYjaMIAhjyGvgrg0WbenpZ2cU2De30AuWEEl4juQnI09EMzdFnoswZG1lCZNHHixP1QvJU49uvXz3HBggXhgNt2KI183d3dO9FkDkqnQvRB8+WpQKULU6dO9Ro4cGAnRBE5cCWc9+DixYuLhDxIiyIOiD7vhx9+OBzQGkB1NchhJaD93Pfff391+/btaxHlFIFm6GIr8u0b48ePdwCT7olnN4d8FnCM9Li4uF/JAcW+KYj79+/vAocKgMwDAM+0WteCeBFkTQUvOQw5zwOdbjGkqVV1NMWbGmq+QI6BOtOKT0RExHsKNTUtKxoRGVsh3DV4rT/PQ1CsmAJoKtFqc1I4jGjE/RzezLSvihajmJFrvXuFUuQGIPQXEEBi4gYidiAyfZHT+n/wwQefgo1fI+imLcTUBkTHd/DgwWMA+2Uw6BkY4xqiu2OfPn0GQInOyHd5RPDgFBno5zQcrAZoYMJzalBFGBQ2CNq24sLpHCGnDgbT49CtX79+wFNPPfVP0gtVJ0CgJT169FhHIAU5TTC0leCRbHgOj2ixb1vNDA5BZZwe+tbgfhOcpgbRXC2ggFGeBpvC0GJUO4CAuH333XcL4K2T2D7pWh9E5EZEV1mrVq0C2VyyWTA0bbex/D51bDO4mUc8FGVmr9/YDE3QSLdQ/YqIvbply5adr7/+OsGkGcpQJSYmDgArj0JEbXz//fehlwIjvJ82FIxBBOwDEmUjSlo899xzgyG3dfcoHCUXBsqA4x6OiooKhmFOIvrSZEo0I3JVqCqoUjDLUoqOl2hU/oC0fYB7B5G+gDLX8fzxKJXSGEk1ykiqvffJ1LLySUyf8hf0LOJ6dFMYmsOL1dhDhw71WL169VzUlE+yxQBbPkae2URGAlS2pUUALhA9jxndxr7ZbwszNL/PxEouC9t8x89XI8qKodRAisZdu3Z9DIjO5O0An/3BSmMRLan5+fkXwbSHQJZjcILMsWPHxiCVRNP2HtpdgpyfBod4jOSGU9Iu0CKMxRfndgAiK8GAaWlTCgsL0z300ENegHNXlGQFQITrGRkZJpRtKvytQcpx6d27t+fSpUunBAYGTua7UoEUCWj/LFtTFvelK73Sa1GawFHd+daL3aXKpnrJTpJt9bV6MghRLMqMf8CoIWRwyrOAmC3IRWpETUsWjWaFgZmEvG2SsXbbwPiGONo/RZsBKCfTBj9wgN6A1xwYbe2hQ4fOI+9VIzIrevbs6YiaOwS15gjI0IImaQCZIZRSAPnrIiMjj8bHxzuBRYcC7iMgtz8idRscR4cKoi8MlHLz5s0CnNcDbt3wnO4UndSekAYpIB98oDg7O7ts7dq1pagA4rt27foc4L6HQJpKoZfnkIcPsjXlahlMWwdWx0qXVMfvu75A35RvU9Z6JUac60V+9IOSO8GLW8PTc0CYPEFKHBj5sshWaWx/c6imoOX3UjQL7ehvDQxBTkT1qjNDEDUidDRttgT8HoYBLsNAV2Go65TzYdCniouLkyCDK3JcIU24HDhwoApEyhXEaBiMZ0CkH0W+fogcAelmLz0LeTEsJSVlD/4uQR73ArxXjBo16jyifBA55Lfffvsr+vZE+2YbNmy4vmLFivVwigHCwIwY/6oOHTq8K4vmO97obOpXkuuyZ2M28Msjjk9TambPnk0L5pmyAt5Sj2nV+p63wRmiqHVMTEwgIlZCxA2g/A0yZ0IE0st6jjDWIERyBqI8d8eOHamAY+tmuscff1w3Z86caCDBZUDzPtZfLow6nK2lqwHj5SBmZeABVOtqdu/efeXw4cO9ANWZyME3J0yY0JNe6UVdXwTyVQ7IzuSGpq1mV65c+SY6OvqfgoGNDNXu239eaOybGiILNisQB6kJ1lIt9tbE33rrrUIctA/tSFZW1mXakQoD7QEBuwDb0Ma+HOTutYjY25y0TJo0SQ8HoV2prqhZ9wo7QSRUCWfRhx8QoBglTcCUKVPmo4/M9PT0/Sh1glHaXB8xYkTBxo0b3VhFQAsdNAVLG/9LaPYKzwctyP8WKLAKjlBXXr4vn6b4jwfy/yHyR/7DE8WX+0CgdMeOHXuG9nvhMODvkUDbkzhoBq+aJi0wBiqZWoEzuCBad8MYWXK2e/78+d5AA0/AfJWnp2c3GLQtiN/JI0eOHB03btwV5H8vED1/ONZ5PK88ODjYHdB9c/r06T3wzBDA/VFAfKZ86dNeNP9Z0N3k//FA+uP/hZGisVHGtIaSh1Oev3z5csbWrVvTaacn2LmVLbPZt2uA3hPIyVcUoszaH+poZ5RoDojoMkC2d7du3XyQ+0vhAGqUcM1phydqZ9rsoAIf0Q4ZMqTZe++9Vy3MMpqFdGa6G2T/vzH0/UQjVe3XTMVXYCUFhm+R1a/y0kSS9SnVUQJZ6uIN8hU31X34vyl/R0PLlay+ywK/qg7D2zOavT6UUMZSj3sfGPoeoVzp32rUx+D1qV/rSyzv+79h/DsbWlUPA9+rQaS/iiEfGPrBx66h/0+AAQDJh6VAEFAMdAAAAABJRU5ErkJggg==';
			$body = '<table border="0" cellspacing="0" cellpadding="5" width="60%" align="left" style="font-family:Verdana; font-size:12px;box-shadow:0 0 3px 1px #378ce7;border-radius:3px; border:1px solid #378ce7; border-collapse:collapse;"><tr style="background-color:#378ce7"><td height="25" align="left"><img src="data:image/png;base64,'.$img_content.'" border="0"></td></tr><tr><td height="100" align="left" valign="top" style="padding:15px">'.nl2br($content).'</td></tr><tr style="background-color:#378ce7"><td height="15" style="color:#FFF; font-size:10px;">&copy;&nbsp;'.date("Y").' ESDS Software Solution Private Limited. All Rights Reserved</td></tr></table>';
			return $body;
		}
		
		function userdatacenters($userid)
		{
			$user_data_centers = $this->select_all_where_records($this->tbl_prefix.'user_dc_relation', "status = 'y' AND user_id = '".$userid."'", '', '', 'location_id');
			$user_data_center_str = '';
			$user_dc_arr = array();
			if (is_array($user_data_centers) && count($user_data_centers) > 0)
			{
				foreach($user_data_centers as $user_data_centers_row)
				{
					if($user_data_centers_row['location_id'] > 0)
					array_push($user_dc_arr, $user_data_centers_row['location_id']);
				}
				$user_data_center_str = implode(",", $user_dc_arr);
			}
			return $user_data_center_str;
		}
		function user_bu($userid)
		{
			$user_buv = $this->select_all_where_records($this->tbl_prefix.'user_bv_relation', 'user_id = "'.$userid.'" AND status = "y"', '', '');
			$bu_arr = array();
			$bu_str = '';
			if (is_array($user_buv) && count($user_buv) > 0)
			{
				foreach($user_buv as $each_buv)
				{
					$bu_id = $this->get_field_value('bu_id', $this->tbl_prefix.'business_verticle_master', 'bu_v_id = "'.$each_buv['bu_v_id'].'"');
					array_push($bu_arr, $bu_id);
				}
				$bu_arr = array_filter($bu_arr);
				$bu_str = implode(",", $bu_arr);
			}
			if ($bu_str == "")
			$bu_str = 0;
			return $bu_str;
		}
		function hw_autodiscovery($ip, $cred_id, $dev_type)
		{
			$hw_array = $this->get_asset_tree_array('network');
			$discovered_data = '';
			if ($dev_type == 6 || $dev_type == 7 || $dev_type == 15)
			{
				$final_array = array();
				$desc = "1.3.6.1.2.1.1.1";
				$op = $this->snmp_result($ip, $cred_id, $desc);
				if ($op == 'No Response')
				{
					return 'No Response';
					exit;
				}
				$my_desc = explode(',', $op);
				$sw_image = $my_desc[1];
				$discovered_data .= 'SW Image :'.$sw_image.'<br />';
				$discovered_data .= 'SW Version :'.$my_desc[2].'<br />';
				$swimage_con_id = $this->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($sw_image)."' AND item_config_id = ".$hw_array['Network Module']['info']['SW Image']." AND status = 'y'");	
				if ($swimage_con_id > 0)
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Image']]['con_id'][0] = $swimage_con_id;
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Image']]['new'][0] = $sw_image;
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Image']]['value'][0] = 'SW Image :'.$sw_image;				
				
				if (trim($sw_image) == '')
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Image']]['discovery'][0] = 'n';
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Image']]['discovery'][0] = 'y';
				$sw_version = $my_desc[2];
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Version']]['con_id'][0] = '';
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Version']]['value'][0] = 'SW Version :'.$sw_version;
				if (trim($sw_version) == '')
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Version']]['discovery'][0] = 'n';
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['SW Version']]['discovery'][0] = 'y';
				$serial_oid = '1.3.6.1.2.1.47.1.1.1.1.11.1';
				$op = $this->snmp_result($ip, $cred_id, $serial_oid);
				$serial_data = explode("STRING:", $op);
				$serial = trim(trim($serial_data[1]), '"');
				$discovered_data .= 'Serial Number :'.$serial.'<br />';
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Serial Number']]['con_id'][0] = '';
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Serial Number']]['value'][0] = 'Serial Number :'.$serial;
				if (trim($serial) == '')
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Serial Number']]['discovery'][0] = 'n';
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Serial Number']]['discovery'][0] = 'y';
				$model_oid = '1.3.6.1.2.1.47.1.1.1.1.13.1';
				$op = $this->snmp_result($ip, $cred_id, $model_oid);
				$model_data = explode("STRING:", $op);
				$model = trim(trim($model_data[1]), '"');
				$discovered_data .= 'Model :'.$model.'<br />';

				$model_con_id = $this->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($model)."' AND item_config_id = ".$hw_array['Network Module']['info']['Model']." AND status = 'y'");
				if ($model_con_id == '')
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Model']]['new'][0] = trim($model);
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Model']]['new'][0] = '';
				if (trim($model) == '')
				{
					$model_con_id = $this->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['Network Module']['info']['Model']." AND status = 'y'");
				}

				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Model']]['con_id'][0] = $model_con_id;
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Model']]['value'][0] = 'Model :'.$model;
				if (trim($model) == '')
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Model']]['discovery'][0] = 'n';
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Model']]['discovery'][0] = 'y';
				$make_oid = '1.3.6.1.2.1.47.1.1.1.1.12.1';
				$op = $this->snmp_result($ip, $cred_id, $make_oid);
				$make_data = explode("STRING:", $op);
				$make = trim(trim($make_data[1]), '"');
				$discovered_data .= 'Make : '.trim($make).'<br />';
				$make_con_id = $this->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($make)."' AND item_config_id = ".$hw_array['Network Module']['info']['Make']." AND status= 'y'");
				if ($make_con_id == '')
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Make']]['new'][0] = trim($make);
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Make']]['new'][0] = '';
				if ($make == '')
				{
					$make_con_id = $this->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['Network Module']['info']['Make']." AND status= 'y'");
				}
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Make']]['con_id'][0] = $make_con_id;
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Make']]['value'][0] = 'Make : '.trim($make);

				//autodiscovery (y/n)
				if (trim($model) == '')
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Make']]['discovery'][0] = 'n';
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Make']]['discovery'][0] = 'y';
				$height = '';
				$height_con_id = $this->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['Network Module']['info']['Height']." AND status= 'y'");
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Height']]['con_id'][0] = $height_con_id;

				//autodiscovery (y/n)
				if (trim($Height) == '')
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Height']]['discovery'][0] = 'n';
				else
				$final_array[$hw_array['Network Module']['id']][$hw_array['Network Module']['info']['Height']]['discovery'][0] = 'y';
				$final_array[$hw_array['Network Module']['id']]['count'] = 1;
				$discovered_data = trim($discovered_data, '<br />');

				$fname = $ip.'_first.txt';
				$probe_name = $this->common_model->get_field_value('title', $this->tbl_prefix.'monitoring_probes', "probe_id = '1'");
				$srcpath = $this->config->item('data_dir');
				$srcpath = $srcpath.$probe_name.'/';
				$filename = "$srcpath/audit_data/".$fname;
				if (file_exists($filename))
				unlink($filename);
				$fp = fopen($filename, "w");
				fwrite($fp, $discovered_data);
				fclose($fp);
				return $final_array;
			}
		}
		function storage_discovery($ip, $cred_id, $dep_type, $storage_type, $audit = '')
		{
			$hw_array = $this->get_asset_tree_array();
			$final_array = array();
			$discovered_data = '';
			$desc = "1.3.6.1.2.1.1.1";
			$op = $this->snmp_result($ip, $cred_id, $desc);
			if ($op == 'No Response')
			{
				return 'No Response';
				exit;
			}
			$ph_stor_id = $this->common_model->get_field_value('item_id', $this->tbl_prefix.'inv_items', "item_name = 'Physical Storage' AND status = 'y'");
			$all_options = $this->common_model->select_all_where_records($this->tbl_prefix.'inv_itemconfig', "item_id = '".$ph_stor_id."' AND STATUS = 'y' AND text_field IN ('textbox','date')", '', '');

			if ($storage_type == 'NetApp')
			{
				$oid_array = array();
				$auto_audit_data = '';
				$emag_src = $this->config->item('emagic_source');
				$oid_file = $emag_src.'emlib/OID/NetApp.txt';

				$file_handle = fopen($oid_file, "rb");
				while(!feof($file_handle))
				{
					$line_of_text = fgets($file_handle);
					if ($line_of_text != '')
					{
						$parts = explode('=', $line_of_text);
						if (is_array($parts) && count($parts) > 0)
						$oid_array[$parts[0]] = $parts[1];
					}
				}
				fclose($file_handle);
				if (is_array($all_options) && count($all_options) > 0)
				{
					foreach($all_options as $each_option)
					{
						$item_config_id = $each_option['item_config_id'];
						if ($each_option['config_name'] == 'Make')
						{
							$auto_audit_data .= 'Make : NetApp<br />';
							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Make :NetApp';
						}

						if ($each_option['config_name'] == 'Model')
						{
							$model_oid = trim($oid_array['productModel']);
							$op = $this->snmp_result($ip, $cred_id, $model_oid);
							$model_data = explode("STRING:", $op);
							$model = trim(trim($model_data[1]), '"');
							$auto_audit_data .= 'Model : '.$model.'<br />';

							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Model :'.$model;
						}

						if ($each_option['config_name'] == 'Serial Number')
						{
							$serial_oid = trim($oid_array['productSerialNum']);
							$op = $this->snmp_result($ip, $cred_id, $serial_oid);
							$serial_data = explode("STRING:", $op);
							$serial = trim(trim($serial_data[1]), '"');
							$auto_audit_data .= 'Serial Number : '.$serial.'<br />';

							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Serial Number :'.$serial;
						}
					}
				}
				$diskraid_index = trim($oid_array['raidPIndex']);
				$diskraid_index_res = $this->snmp_result($ip, $cred_id, $diskraid_index);
				$diskraid_index_res = explode("\n", trim($diskraid_index_res));
				$auto_audit_data .= '<br />HDD';
				$auto_audit_data .= '<br />';
				$active_disks = 0;
				if (is_array($diskraid_index_res) && count($diskraid_index_res) > 0)
				{
					$diskraid_sr = trim($oid_array['raidPDiskSerialNumber']);
					$diskraid_sr_res = $this->snmp_result($ip, $cred_id, $diskraid_sr);
					$diskraid_sr_res = explode("\n", trim($diskraid_sr_res));

					$diskraid_vendor = trim($oid_array['raidPDiskVendor']);
					$diskraid_vendor_res = $this->snmp_result($ip, $cred_id, $diskraid_vendor);
					$diskraid_vendor_res = explode("\n", trim($diskraid_vendor_res));

					$diskraid_model = trim($oid_array['raidPDiskModel']);
					$diskraid_model_res = $this->snmp_result($ip, $cred_id, $diskraid_model);
					$diskraid_model_res = explode("\n", trim($diskraid_model_res));

					$diskraid_rpm = trim($oid_array['raidPDiskRPM']);
					$diskraid_rpm_res = $this->snmp_result($ip, $cred_id, $diskraid_rpm);
					$diskraid_rpm_res = explode("\n", trim($diskraid_rpm_res));

					$diskraid_type = trim($oid_array['raidPDiskType']);
					$diskraid_type_res = $this->snmp_result($ip, $cred_id, $diskraid_type);
					$diskraid_type_res = explode("\n", trim($diskraid_type_res));

					$diskraid_cap = trim($oid_array['raidPTotalMb']);
					$diskraid_cap_res = $this->snmp_result($ip, $cred_id, $diskraid_cap);
					$diskraid_cap_res = explode("\n", trim($diskraid_cap_res));
					$active_disks = count($diskraid_index_res);
					for($i = 0; $i < $active_disks; $i++)
					{
						$dev_pos = $i + 1;
						$auto_audit_data .= $dev_pos.'.';
						$auto_audit_data .= '<br />';

						$hdd_make = trim($this->get_snmp_value('STRING:', $diskraid_vendor_res[$i]), '"');
						$hdd_make_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_make)."' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");

						if ($hdd_make_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['new'][$i] = trim($hdd_make);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['new'][$i] = '';

						if ($hdd_make_con_id == '')
						$hdd_make_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['value'][$i] == '')
						$auto_audit_data .= "Make : ".$hdd_make.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['con_id'][$i] = $hdd_make_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['value'][$i] = 'Make :'.trim($hdd_make);

						$hdd_rpm = trim($this->get_snmp_value('STRING:', $diskraid_rpm_res[$i]), '"');
						if ($hdd_rpm > 0)
						$hdd_rpm = round($hdd_rpm / 1000, 1).'K';
						$hdd_rpm_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_rpm)."' AND item_config_id = ".$hw_array['HDD']['info']['RPM']." AND status = 'y'");

						if ($hdd_rpm_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['new'][$i] = trim($hdd_rpm);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['new'][$i] = '';

						if ($hdd_rpm_con_id == '')
						$hdd_rpm_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id =".$hw_array['HDD']['info']['RPM']." AND status = 'y'");
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['value'][$i] == '')
						$auto_audit_data .= "RPM : ".$hdd_rpm.'<br />';
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['con_id'][$i] = $hdd_rpm_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['value'][$i] = 'RPM :'.trim($hdd_rpm);
						$hdd_cap = $this->get_snmp_value('INTEGER:', $diskraid_cap_res[$i]) * 1024;
						$hdd_cap = round(($hdd_cap / (1024)));
						if ($hdd_cap > 1024)
						{
							$hdd_cap = round(($hdd_cap / 1024));
							if ($hdd_cap > 1024)
							{
								$hdd_cap = round(($hdd_cap / 1024), 2);
								$hdd_unit = 'TB';
							}
							else
							{
								$hdd_unit = 'GB';
							}
						}
						else
						{
							$hdd_unit = 'MB';
						}
						$hdd_cap = $hdd_cap.$hdd_unit;
						$hdd_cap_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_cap)."' AND item_config_id = ".$hw_array['HDD']['info']['Capacity']." AND status = 'y'");
						if ($hdd_cap_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['new'][$i] = trim($hdd_cap);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['new'][$i] = '';

						if ($hdd_cap_con_id == '')
						$hdd_cap_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Capacity']." AND status = 'y'");

						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['value'][$i] == '')
						$auto_audit_data .= "Capacity : ".$hdd_cap.'<br />';
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['con_id'][$i] = $hdd_cap_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['value'][$i] = 'Capacity :'.$hdd_cap;
						$hdd_type = trim($this->get_snmp_value('STRING:', $diskraid_type_res[$i]), '"');
						$hdd_type_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_type)."' AND item_config_id = ".$hw_array['HDD']['info']['Type']." AND status = 'y'");

						if ($hdd_type_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['new'][$i] = trim($hdd_type);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['new'][$i] = '';

						if ($hdd_type_con_id == '')
						$hdd_type_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['value'][$i] == '')
						$auto_audit_data .= "Type : ".$hdd_type.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['con_id'][$i] = $hdd_type_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['value'][$i] = 'Type :'.trim($hdd_type);

						$disk_sr = trim($this->get_snmp_value('STRING:', $diskraid_sr_res[$i]), '"');
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['value'][$i] == '')
						$auto_audit_data .= "Serial No : ".$disk_sr.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['con_id'][$i] = "";
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['value'][$i] = 'Serial No :'.$disk_sr;
					}
				}
				######   Discovery of Spare Disks starts ################
				$spare_disks = 0;
				$diskraid_index = trim($oid_array['spareIndex']);
				$diskraid_index_res = $this->snmp_result($ip, $cred_id, $diskraid_index);
				$diskraid_index_res = explode("\n", trim($diskraid_index_res));
				if (is_array($diskraid_index_res) && count($diskraid_index_res) > 0)
				{
					$diskraid_sr = trim($oid_array['spareDiskSerialNumber']);
					$diskraid_sr_res = $this->snmp_result($ip, $cred_id, $diskraid_sr);
					$diskraid_sr_res = explode("\n", trim($diskraid_sr_res));

					$diskraid_vendor = trim($oid_array['spareDiskVendor']);
					$diskraid_vendor_res = $this->snmp_result($ip, $cred_id, $diskraid_vendor);
					$diskraid_vendor_res = explode("\n", trim($diskraid_vendor_res));

					$diskraid_model = trim($oid_array['spareDiskModel']);
					$diskraid_model_res = $this->snmp_result($ip, $cred_id, $diskraid_model);
					$diskraid_model_res = explode("\n", trim($diskraid_model_res));

					$diskraid_rpm = trim($oid_array['spareDiskRPM']);
					$diskraid_rpm_res = $this->snmp_result($ip, $cred_id, $diskraid_rpm);
					$diskraid_rpm_res = explode("\n", trim($diskraid_rpm_res));

					$diskraid_type = trim($oid_array['spareDiskType']);
					$diskraid_type_res = $this->snmp_result($ip, $cred_id, $diskraid_type);
					$diskraid_type_res = explode("\n", trim($diskraid_type_res));

					$diskraid_cap = trim($oid_array['spareTotalMb']);
					$diskraid_cap_res = $this->snmp_result($ip, $cred_id, $diskraid_cap);
					$diskraid_cap_res = explode("\n", trim($diskraid_cap_res));
					$spare_disks = count($diskraid_index_res);
					for($i = 0; $i < $spare_disks; $i++)
					{
						$v = $active_disks + $i;
						$dev_pos = $v + 1;
						$auto_audit_data .= $dev_pos.'.';
						$auto_audit_data .= '<br />';

						$hdd_make = trim($this->get_snmp_value('STRING:', $diskraid_vendor_res[$i]), '"');
						$hdd_make_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_make)."' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");
						if ($hdd_make_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['new'][$v] = trim($hdd_make);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['new'][$v] = '';

						if ($hdd_make_con_id == '')
						$hdd_make_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['value'][$v] == '')
						$auto_audit_data .= "Make : ".$hdd_make.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['con_id'][$v] = $hdd_make_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['value'][$v] = 'Make :'.trim($hdd_make);

						$hdd_rpm = trim($this->get_snmp_value('STRING:', $diskraid_rpm_res[$i]), '"');
						if ($hdd_rpm > 0)
						$hdd_rpm = round($hdd_rpm / 1000, 1).'K';
						$hdd_rpm_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_rpm)."' AND item_config_id = ".$hw_array['HDD']['info']['RPM']." AND status = 'y'");
						if ($hdd_rpm_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['new'][$v] = trim($hdd_rpm);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['new'][$v] = '';

						if ($hdd_rpm_con_id == '')
						$hdd_rpm_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['RPM']." AND status = 'y'");
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['value'][$v] == '')
						$auto_audit_data .= "RPM : ".$hdd_rpm.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['con_id'][$v] = $hdd_rpm_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['value'][$v] = 'RPM :'.trim($hdd_rpm);
						$hdd_cap = $this->get_snmp_value('INTEGER:', $diskraid_cap_res[$i]) * 1024;
						$hdd_cap = round(($hdd_cap / (1024)));
						if ($hdd_cap > 1024)
						{
							$hdd_cap = round(($hdd_cap / 1024));
							if ($hdd_cap > 1024)
							{
								$hdd_cap = round(($hdd_cap / 1024), 2);
								$hdd_unit = 'TB';
							}
							else
							{
								$hdd_unit = 'GB';
							}
						}
						else
						{
							$hdd_unit = 'MB';
						}
						$hdd_cap = $hdd_cap.$hdd_unit;
						$hdd_cap_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_cap)."' AND item_config_id = ".$hw_array['HDD']['info']['Capacity']." AND status = 'y'");
						if ($hdd_cap_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['new'][$v] = trim($hdd_cap);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['new'][$v] = '';

						if ($hdd_cap_con_id == '')
						$hdd_cap_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Capacity']." AND status = 'y'");

						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['value'][$v] == '')
						$auto_audit_data .= "Capacity : ".$hdd_cap.'<br />';
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['con_id'][$v] = $hdd_cap_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['value'][$v] = 'Capacity :'.$hdd_cap;
						$hdd_type = trim($this->get_snmp_value('STRING:', $diskraid_type_res[$i]), '"');
						$hdd_type_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_type)."' AND item_config_id = ".$hw_array['HDD']['info']['Type']." AND status = 'y'");

						if ($hdd_type_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['new'][$v] = trim($hdd_type);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['new'][$v] = '';

						if ($hdd_type_con_id == '')
						$hdd_type_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['value'][$v] == '')
						$auto_audit_data .= "Type : ".$hdd_type.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['con_id'][$v] = $hdd_type_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['value'][$v] = 'Type :'.trim($hdd_type);

						$disk_sr = trim($this->get_snmp_value('STRING:', $diskraid_sr_res[$i]), '"');
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['value'][$v] == '')
						$auto_audit_data .= "Serial No : ".$disk_sr.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['con_id'][$v] = "";
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['value'][$v] = 'Serial No :'.$disk_sr;
					}
				}
				$final_array[$hw_array['HDD']['id']]['count'] = $active_disks + $spare_disks;
			}
			elseif ($storage_type == 'DellEqualLogic')
			{
				$oid_array = array();
				$auto_audit_data = '';
				$emag_src = $this->config->item('emagic_source');
				$oid_file = $emag_src.'emlib/OID/Equallogic.txt';
				$file_handle = fopen($oid_file, "rb");
				while(!feof($file_handle))
				{
					$line_of_text = fgets($file_handle);
					if ($line_of_text != '')
					{
						$parts = explode('=', $line_of_text);
						if (is_array($parts) && count($parts) > 0)
						$oid_array[$parts[0]] = $parts[1];
					}
				}
				fclose($file_handle);

				if (is_array($all_options) && count($all_options) > 0)
				{
					foreach($all_options as $each_option)
					{
						$item_config_id = $each_option['item_config_id'];
						if ($each_option['config_name'] == 'Make')
						{
							$auto_audit_data .= 'Make : Dell EqualLogic<br />';
							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Make :Dell EqualLogic';
						}
						if ($each_option['config_name'] == 'Model')
						{
							$model_oid = trim($oid_array['eqlMemberModel']);
							$op = $this->snmp_result($ip, $cred_id, $model_oid);
							$model_data = explode("STRING:", $op);
							$model = trim(trim($model_data[1]), '"');
							$auto_audit_data .= 'Model : '.$model.'<br />';

							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Model :'.$model;
						}
						if ($each_option['config_name'] == 'Serial Number')
						{
							$serial_oid = trim($oid_array['eqlMemberSerialNumber']);
							$op = $this->snmp_result($ip, $cred_id, $serial_oid);
							$serial_data = explode("STRING:", $op);
							$serial = trim(trim($serial_data[1]), '"');
							$auto_audit_data .= 'Serial Number : '.$serial.'<br />';

							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Serial Number :'.$serial;
						}
					}
				}
				$diskraid_index = trim($oid_array['eqlDiskId']);
				$diskraid_index_res = $this->snmp_result($ip, $cred_id, $diskraid_index);
				$diskraid_index_res = explode("\n", trim($diskraid_index_res));
				$auto_audit_data .= '<br />HDD';
				$auto_audit_data .= '<br />';
				$active_disks = 0;
				if (is_array($diskraid_index_res) && count($diskraid_index_res) > 0)
				{
					$diskraid_sr = trim($oid_array['eqlDiskSerialNumber']);
					$diskraid_sr_res = $this->snmp_result($ip, $cred_id, $diskraid_sr);
					$diskraid_sr_res = explode("\n", trim($diskraid_sr_res));

					$diskraid_model = trim($oid_array['eqlDiskModelNumber']);
					$diskraid_model_res = $this->snmp_result($ip, $cred_id, $diskraid_model);
					$diskraid_model_res = explode("\n", trim($diskraid_model_res));


					$diskraid_type = trim($oid_array['eqlDiskTypeEnum']);
					$diskraid_type_res = $this->snmp_result($ip, $cred_id, $diskraid_type);
					$diskraid_type_res = explode("\n", trim($diskraid_type_res));

					$diskraid_cap = trim($oid_array['eqlDiskSize']);
					$diskraid_cap_res = $this->snmp_result($ip, $cred_id, $diskraid_cap);
					$diskraid_cap_res = explode("\n", trim($diskraid_cap_res));
					$active_disks = count($diskraid_index_res);
					for($i = 0; $i < $active_disks; $i++)
					{
						$dev_pos = $i + 1;
						$auto_audit_data .= $dev_pos.'.';
						$auto_audit_data .= '<br />';
						$hdd_make = '';
						$hdd_make_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_make)."' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");

						if ($hdd_make_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['new'][$i] = trim($hdd_make);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['new'][$i] = '';

						if ($hdd_make_con_id == '')
						$hdd_make_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id =".$hw_array['HDD']['info']['Make']." AND status= 'y'");
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['con_id'][$i] = $hdd_make_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['value'][$i] = 'Make :'.trim($hdd_make);

						$hdd_rpm = '';
						$hdd_rpm_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_rpm)."' AND item_config_id = ".$hw_array['HDD']['info']['RPM']." AND status = 'y'");

						if ($hdd_rpm_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['new'][$i] = trim($hdd_rpm);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['new'][$i] = '';

						if ($hdd_rpm_con_id == '')
						$hdd_rpm_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['RPM']." AND status = 'y'");
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['con_id'][$i] = $hdd_rpm_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['value'][$i] = 'RPM :'.trim($hdd_rpm);
						$hdd_cap = $this->get_snmp_value('INTEGER:', $diskraid_cap_res[$i]) * 1024;
						$hdd_cap = round(($hdd_cap / (1024)));
						if ($hdd_cap > 1024)
						{
							$hdd_cap = round(($hdd_cap / 1024));
							if ($hdd_cap > 1024)
							{
								$hdd_cap = round(($hdd_cap / 1024), 2);
								$hdd_unit = 'TB';
							}
							else
							{
								$hdd_unit = 'GB';
							}
						}
						else
						{
							$hdd_unit = 'MB';
						}
						$hdd_cap = $hdd_cap.$hdd_unit;
						$hdd_cap_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_cap)."' AND item_config_id = ".$hw_array['HDD']['info']['Capacity']." AND status = 'y'");
						if ($hdd_cap_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['new'][$i] = trim($hdd_cap);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['new'][$i] = '';

						if ($hdd_cap_con_id == '')
						$hdd_cap_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Capacity']." AND status = 'y'");

						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['value'][$i] == '')
						$auto_audit_data .= "Capacity : ".$hdd_cap.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['con_id'][$i] = $hdd_cap_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['value'][$i] = 'Capacity :'.$hdd_cap;

						$hdd_type = trim($this->get_snmp_value('INTEGER:', $diskraid_type_res[$i]), '"');

						if ($hdd_type == 1)
						$hdd_type = 'SATA';
						elseif ($hdd_type == 2)
						$hdd_type = 'SAS';
						else
						$hdd_type = '';

						$hdd_type_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_type)."' AND item_config_id = ".$hw_array['HDD']['info']['Type']." AND status = 'y'");

						if ($hdd_type_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['new'][$i] = trim($hdd_type);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['new'][$i] = '';

						if ($hdd_type_con_id == '')
						$hdd_type_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['value'][$i] == '')
						$auto_audit_data .= "Type : ".$hdd_type.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['con_id'][$i] = $hdd_type_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['value'][$i] = 'Type :'.trim($hdd_type);

						$disk_sr = trim($this->get_snmp_value('STRING:', $diskraid_sr_res[$i]), '"');
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['value'][$i] == '')
						$auto_audit_data .= "Serial No : ".$disk_sr.'<br />';

						$disk_model = trim($this->get_snmp_value('STRING:', $diskraid_model_res[$i]), '"');
						if ($final_array[$hw_array['HDD']['id']][292]['value'][$i] == '')
						$auto_audit_data .= "Model : ".$disk_model.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['con_id'][$i] = "";
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['value'][$i] = 'Serial No :'.$disk_sr;

						$final_array[$hw_array['HDD']['id']][292]['con_id'][$i] = "";
						$final_array[$hw_array['HDD']['id']][292]['value'][$i] = 'Model :'.$disk_model;
					}
				}
				$final_array[$hw_array['HDD']['id']]['count'] = count($diskraid_index_res);
			}
			elseif ($storage_type == 'EMC-Isilon')
			{
				$oid_array = array();
				$auto_audit_data = '';
				$emag_src = $this->config->item('emagic_source');
				$oid_file = $emag_src.'emlib/OID/Isilon.txt';

				$file_handle = fopen($oid_file, "rb");
				while(!feof($file_handle))
				{
					$line_of_text = fgets($file_handle);
					if ($line_of_text != '')
					{
						$parts = explode('=', $line_of_text);
						if (is_array($parts) && count($parts) > 0)
						$oid_array[$parts[0]] = $parts[1];
					}
				}
				fclose($file_handle);
				if (is_array($all_options) && count($all_options) > 0)
				{
					foreach($all_options as $each_option)
					{
						$item_config_id = $each_option['item_config_id'];
						if ($each_option['config_name'] == 'Make')
						{
							$auto_audit_data .= 'Make : EMC-Isilon<br />';
							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Make :EMC-Isilon';
						}
						if ($each_option['config_name'] == 'Model')
						{
							$model_oid = trim($oid_array['chassisModel']);
							$op = $this->snmp_result($ip, $cred_id, $model_oid);
							$model_data = explode("STRING:", $op);
							$model = trim(trim($model_data[1]), '"');
							$auto_audit_data .= 'Model : '.$model.'<br />';

							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Model :'.$model;
						}
						if ($each_option['config_name'] == 'Serial Number')
						{
							$serial_oid = trim($oid_array['chassisSerialNumber']);
							$op = $this->snmp_result($ip, $cred_id, $serial_oid);
							$serial_data = explode("STRING:", $op);
							$serial = trim(trim($serial_data[1]), '"');
							$auto_audit_data .= 'Serial Number : '.$serial.'<br />';

							$final_array['txt_op'][$item_config_id]['con_id'][0] = '';
							$final_array['txt_op'][$item_config_id]['value'][0] = 'Serial Number :'.$serial;
						}
					}
				}
				$diskraid_index = trim($oid_array['diskId']);
				$diskraid_index_res = $this->snmp_result($ip, $cred_id, $diskraid_index);
				$diskraid_index_res = explode("\n", trim($diskraid_index_res));
				$auto_audit_data .= '<br />HDD';
				$auto_audit_data .= '<br />';
				$active_disks = 0;
				if (is_array($diskraid_index_res) && count($diskraid_index_res) > 0)
				{
					$diskraid_sr = trim($oid_array['diskSerial']);
					$diskraid_sr_res = $this->snmp_result($ip, $cred_id, $diskraid_sr);
					$diskraid_sr_res = explode("\n", trim($diskraid_sr_res));
					$diskraid_model = trim($oid_array['diskModel']);
					$diskraid_model_res = $this->snmp_result($ip, $cred_id, $diskraid_model);
					$diskraid_model_res = explode("\n", trim($diskraid_model_res));
					$diskraid_cap = trim($oid_array['diskSizeBytes']);
					$diskraid_cap_res = $this->snmp_result($ip, $cred_id, $diskraid_cap);
					$diskraid_cap_res = explode("\n", trim($diskraid_cap_res));
					$active_disks = count($diskraid_index_res);
					for($i = 0; $i < $active_disks; $i++)
					{
						$dev_pos = $i + 1;
						$auto_audit_data .= $dev_pos.'.';
						$auto_audit_data .= '<br />';
						$hdd_make = '';
						$hdd_make_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_make)."' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");

						if ($hdd_make_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['new'][$i] = trim($hdd_make);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['new'][$i] = '';

						if ($hdd_make_con_id == '')
						$hdd_make_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Make']." AND status= 'y'");
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['con_id'][$i] = $hdd_make_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Make']]['value'][$i] = 'Make :'.trim($hdd_make);
						$hdd_rpm = '';
						$hdd_rpm_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_rpm)."' AND item_config_id = ".$hw_array['HDD']['info']['RPM']." AND status = 'y'");

						if ($hdd_rpm_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['new'][$i] = trim($hdd_rpm);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['new'][$i] = '';

						if ($hdd_rpm_con_id == '')
						$hdd_rpm_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['RPM']." AND status = 'y'");
						
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['con_id'][$i] = $hdd_rpm_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['RPM']]['value'][$i] = 'RPM :'.trim($hdd_rpm);
						$hdd_cap = $this->get_snmp_value('Counter64:', $diskraid_cap_res[$i]);
						$hdd_cap = round(($hdd_cap / (1024 * 1024)));
						if ($hdd_cap > 1024)
						{
							$hdd_cap = round(($hdd_cap / 1024));
							if ($hdd_cap > 1024)
							{
								$hdd_cap = round(($hdd_cap / 1024), 2);
								$hdd_unit = 'TB';
							}
							else
							{
								$hdd_unit = 'GB';
							}
						}
						else
						{
							$hdd_unit = 'MB';
						}
						$hdd_cap = $hdd_cap.$hdd_unit;
						$hdd_cap_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_cap)."' AND item_config_id = ".$hw_array['HDD']['info']['Capacity']." AND status = 'y'");
						if ($hdd_cap_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['new'][$i] = trim($hdd_cap);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['new'][$i] = '';

						if ($hdd_cap_con_id == '')
						$hdd_cap_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Capacity']." AND status = 'y'");

						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['value'][$i] == '')
						$auto_audit_data .= "Capacity : ".$hdd_cap.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['con_id'][$i] = $hdd_cap_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Capacity']]['value'][$i] = 'Capacity :'.$hdd_cap;

						$hdd_type = '';

						if ($hdd_type == 1)
						$hdd_type = 'SATA';
						elseif ($hdd_type == 2)
						$hdd_type = 'SAS';
						else
						$hdd_type = '';

						$hdd_type_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = '".trim($hdd_type)."' AND item_config_id = ".$hw_array['HDD']['info']['Type']." AND status = 'y'");

						if ($hdd_type_con_id == '')
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['new'][$i] = trim($hdd_type);
						else
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['new'][$i] = '';

						if ($hdd_type_con_id == '')
						$hdd_type_con_id = $this->common_model->get_field_value('configoption_id', $this->tbl_prefix.'inv_configoption', "config_option_name = 'NA' AND item_config_id = ".$hw_array['HDD']['info']['Type']." AND status = 'y'");
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['value'][$i] == '')
						$auto_audit_data .= "Type : ".$hdd_type.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['con_id'][$i] = $hdd_type_con_id;
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Type']]['value'][$i] = 'Type :'.trim($hdd_type);

						$disk_sr = trim($this->get_snmp_value('STRING:', $diskraid_sr_res[$i]), '"');
						if ($final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['value'][$i] == '')
						$auto_audit_data .= "Serial No : ".$disk_sr.'<br />';

						$disk_model = trim($this->get_snmp_value('STRING:', $diskraid_model_res[$i]), '"');
						if ($final_array[$hw_array['HDD']['id']][292]['value'][$i] == '')
						$auto_audit_data .= "Model : ".$disk_model.'<br />';

						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['con_id'][$i] = "";
						$final_array[$hw_array['HDD']['id']][$hw_array['HDD']['info']['Serial Number']]['value'][$i] = 'Serial No :'.$disk_sr;

						$final_array[$hw_array['HDD']['id']][292]['con_id'][$i] = "";
						$final_array[$hw_array['HDD']['id']][292]['value'][$i] = 'Model :'.$disk_model;
					}
				}
				$final_array[$hw_array['HDD']['id']]['count'] = count($diskraid_index_res);
			}

			$auto_audit_data = trim($auto_audit_data, '<br />');
			if ($audit == 'yes')
			{
				return $auto_audit_data;
			}
			else
			{
				if ($auto_audit_data != '')
				{
					$fname = $ip.'_first.txt';
					$probe_name = $this->common_model->get_field_value('title', $this->tbl_prefix.'monitoring_probes', "probe_id = '1'");
					$srcpath = $this->config->item('data_dir');
					$srcpath = $srcpath.$probe_name.'/';
					$filename = "$srcpath/audit_data/".$fname;

					if (file_exists($filename))
					unlink($filename);
					$fp = fopen($filename, "w");
					fwrite($fp, $auto_audit_data);
					fclose($fp);
				}
				return $final_array;
			}
		}
		function snmp_result($ip, $cred_id, $oid)
		{
			$snmp_where = "credential_detail_id = $cred_id";
			$snmp_details = $this->select_all_where_records($this->tbl_prefix.'object_credentials_snmp', $snmp_where, '', '');
			$snmpv = $snmp_details[0]['snmp_version'];
			$community = $snmp_details[0]['community'];
			$auth_protocol = $snmp_details[0]['auth_protocol'];
			$auth_pass = $this->decrypt($snmp_details[0]['auth_pass']);
			$sec_level = $snmp_details[0]['sec_level'];
			$sec_name = $snmp_details[0]['username'];
			$priv_protocol = $snmp_details[0]['enc_protocol'];
			$priv_pass = $this->decrypt($snmp_details[0]['enc_pass']);
			$snmp_port = $snmp_details[0]['snmp_port'];
			if ($snmp_port == "")
			$snmp_port = 161;

			if ($snmpv == 'v1')
			{
				$snmpv = '1';
			}
			if ($snmpv == 'v2')
			{
				$snmpv = '2c';
			}
			if ($snmpv == 'v3')
			{
				$snmpv = '3';
			}
			if ($snmpv == '1' || $snmpv == '2c')
			{
				$status = $this->esnmpstatus($ip, $community, $snmpv, $snmp_port);
				if (!$status)
				{
					$op_proc = "No Response";
				}
				else
				$op_proc = $this->esds_data("snmpwalk -v $snmpv -Cc -c '".$community."' $ip:$snmp_port $oid");
			}
			else
			{
				$status = $this->esnmp3_status($ip, $sec_name, $sec_level, $auth_protocol, $auth_pass, $priv_protocol, $priv_pass, $snmp_port);
				if (!$status)
				{
					$op_proc = "No Response";
				}
				else
				{	
					if ($sec_level == "noAuthNoPriv")
					$op_proc = $this->esds_data("snmpwalk -v 3 -Cc -r 1 -u '".$sec_name."' -l $sec_level $ip:$snmp_port $oid");
					elseif ($sec_level == "authNoPriv")
					$op_proc = $this->esds_data("snmpwalk -v 3 -Cc -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' $ip:$snmp_port $oid");
					else
					$op_proc = $this->esds_data("snmpwalk -v 3 -Cc -r 1 -u '".$sec_name."' -l $sec_level -a $auth_protocol -A '".$auth_pass."' -x $priv_protocol -X '".$priv_pass."' $ip:$snmp_port $oid");
				}
			}
			if ($op_proc == "")
			{
				$op_proc = "No";
			}
			return $op_proc;
		}
		function reg_match($regex, $input_str)
		{
			$return_array = array();
			preg_match_all($regex, $input_str, $return_array);
			return $return_array;
		}
		function titles($id_field, $title_field, $table, $where = "")
		{
			$return_array = array();
			$fields = $id_field.','.$title_field;
			$this->db->select($fields, FALSE);
			$this->db->from($table, FALSE);
			$this->db->where($where, '', FALSE);
			$this->db->order_by($title_field, "ASC");
			$query = $this->db->get();
			$error_message = $this->errors->get_error_message($query);
			if ($error_message == 'yes')
			{
				if ($query->num_rows() > 0)
				{
					foreach($query->result_array() as $row)
					{
						$return_array[$row[$id_field]] = $row[$title_field];
					}
				}
				return $return_array;
			}
			else
			return $error_message;
		}
		/*
			Date: 11/29/13
			RE: Combine Nulled IP / Subnet column. It's actually "Nulled Subnet" and not "Nulled IP". We need to display Nulled Subnet in CIDR format e.g. 183.153.56.0 / 24.
			APD, no need to integrate in back-end perl script and store Nulled-subnet in DB. Hence, no need to modify 'snorby_nullroute.pl' script.
			Instead, written PHP-function which requires 'nulled-ip' and 'subnet mask' for same to get "Nulled-subnet" in CIDR format.
			Author: Neha Sangore.
			*/
		function subnet_to_cidr($ip, $subnet_mask)
		{
			$input = explode(".", $subnet_mask);
			if (count($input) != 4)
			return -1;
			$opt_str = "";
			for($i = 0; $i < 4; $i++)
			{
				$opt_str .=decbin($input[$i]);
			}

			$cnt = substr_count("$opt_str", "1");
			//return  $cnt;
			return $ip."/".$cnt;
		}
		function userassignobjects($objectid)
		{
			if ($objectid > 0)
			{
				$users = $this->select_all_where_records($this->tbl_prefix.'user_master', "device_auto_assign = 'y' AND status = 'y'", '', '', 'user_id');
				if (is_array($users) && count($users) > 0)
				foreach($users as $users_row)
				{
					$this->db->insert($this->tbl_prefix."user_objects", array('user_id' => $users_row['user_id'], 'object_id' => $objectid, 'status' => 'y', 'date' => date('Y-m-d G:i:s')));
				}
				// check for session user
				$sessuserchk = $this->chkrecordexists($this->tbl_prefix."user_objects", "user_id = '".$this->session->userdata('EMUSERID')."' AND object_id = '".$objectid."' AND status = 'y'");
				if ($sessuserchk <= 0)
				{
					$this->db->insert($this->tbl_prefix."user_objects", array('user_id' => $this->session->userdata('EMUSERID'), 'object_id' => $objectid, 'status' => 'y', 'date' => date('Y-m-d G:i:s')));
				}
				return true;
			}
		}
		function getnullrouter()
		{
			$router = ' -router cisco ';
			$routerid = $this->get_field_value('object_id', $this->tbl_prefix.'object_master', "functional_type LIKE '%distribution%' AND dep_type_id = 6 AND status_id = 1");
			if ($routerid > 0)
			{
				$config_options = $this->select_all_where_records($this->tbl_prefix.'inv_assethw_details', "hwasset_id = (SELECT asset_id FROM ".$this->tbl_prefix."inv_object_asset WHERE object_id = ".$routerid." AND asset_type = 'hw' AND status = 'y') AND status = 'y'", '', '', 'GROUP_CONCAT(config_option_id) as config_option_ids');
				$config_option_ids = $config_options[0]['config_option_ids'];
				if ($config_option_ids != '')
				{
					$is_cisco_router = $this->select_all_where_records($this->tbl_prefix.'inv_configoption', "configoption_id IN ($config_option_ids) AND status = 'y' AND LOWER(config_option_name) LIKE '%cisco%'", '', '', 'GROUP_CONCAT(configoption_id) as config_option_ids');
					if (count($is_cisco_router) > 0)
					$router = ' -router cisco ';
					else
					{
						$is_cisco_router = $this->select_all_where_records($this->tbl_prefix.'inv_configoption', "configoption_id IN ($config_option_ids) AND status = 'y' AND LOWER(config_option_name) LIKE '%hp%'", '', '', 'GROUP_CONCAT(configoption_id) as config_option_ids');
						if (count($is_cisco_router) > 0)
						$router = ' -router hp ';
					}
				}
			}
			return $router;
		}
		//function profileuptime_date($profile_id,$from_date="",$to_date="")
		function datewiseuptime($flag, $id, $param_id, $from_date = "", $to_date = "")
		{
			if ($from_date == "")
			$from_date = date('Y-m-d 00:00:01');
			if ($to_date == "")
			$to_date = date('Y-m-d G:i:s');
			
			$today_date = date('Y-m-d G:i:s');
			if ($to_date == $today_date)
			{
				$to_date = date('Y-m-d G:i:s');
			}
			else
			{
				$to_date = $to_date;
			}
			$from_date = $from_date;					
			$total_secs = strtotime($to_date) - strtotime($from_date);
			$where_flag = "";
			if ($flag == 'object')
			$where_flag = "object_id = '".$id."' ";
			elseif ($flag == 'profile')
			$where_flag = "profile_id = '".$id."' ";
			
			$outage = $this->select_all_where_records($this->tbl_prefix.'object_outage', $where_flag." AND monitor_param_id = '".$param_id."' AND uptime_flag = 'y' AND (down_time BETWEEN '".$from_date."' AND '".$to_date."' OR up_time BETWEEN '".$from_date."' AND '".$to_date."') AND status = 'y' ORDER BY down_time ASC ", '', '');
			
			$ot_len = count($outage);
			$tot_down_time = 0;
			if (is_array($outage) && count($outage) > 0)
			{
				foreach($outage as $key => $data)
				{
					$downtime = $data['down_time'];
					$uptime = $data['up_time'];
					if ($key == 0)
					{
						if (strtotime($from_date) >= strtotime($downtime))
						{
							$downtime = $from_date;
						}
					}
					if ($key == ($ot_len - 1))
					{
						if ($uptime == '0000-00-00 00:00:00')
						$uptime = $to_date;
						else
						{
							if (strtotime($uptime) > strtotime($to_date))
							{
								$uptime = $to_date;
							}
						}
					}
					$downtime_value = strtotime($uptime) - strtotime($downtime);
					$tot_down_time = $tot_down_time + $downtime_value;
				}
				$hours = floor($tot_down_time / 3600);
				$mins = floor(($tot_down_time - ($hours * 3600)) / 60);
				$secs = floor($tot_down_time % 60);
				$down_perc = round(($tot_down_time / $total_secs) * 100, 2);
				$up_perc = 100 - $down_perc;
			}
			else
			{		
				$montype = ($flag == 'profile')?'profile':'service';
				$file_mon_data = $this->common_model->readfile_to_array($id,$param_id,$montype);
				if ($file_mon_data['resolve'] == 'n' && strpos($file_mon_data['description'], 'Latency') === false)
				{
					$last_outage = $this->common_model->select_all_where_records($this->tbl_prefix.'object_outage', $where_flag." ORDER BY DATE DESC", '1', '0', 'down_time');
					if (strtotime($from_date) > strtotime($last_outage[0]['down_time']))
					{
						$up_perc = 0;
						$down_perc = 100;
					}
					else
					{
						$up_perc = 100;
						$down_perc = 0;
					}
				}
				else
				{
					$up_perc = 100;
					$down_perc = 0;
				}
			}
			$myresult = array();
			$myresult['uptime'] = $up_perc;
			$myresult['downtime'] = $down_perc;
			$myresult['down_mins'] = $tot_down_time / 60;
			return $myresult;
		}
		function getLastRRDval($file, $var_name, $ins_val = 'LAST', $req_int)
		{
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file:$var_name:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" PRINT:CIN:$ins_val:%6.2lf ".
			$rrdcommand .= " --end now --start end-".$req_int."min --x-grid MINUTE:10:HOUR:1:HOUR:1:0:%H --title 'Last RRD Val' ";

			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['1'] > 0)
			$current_in = $last_data_array['1'];
			else
			$current_in = 0;
			return $last_arr = array("rrd_val" => $current_in);
		}
		function get_snmp_value($separator, $item)
		{
			if ($item != '')
			{
				$items = explode($separator, $item);
				if ($items != '')
				return trim($items[1]);
			}
		}
		// function to get array by key wise.
		function keytoarray($data, $key)
		{
			$key2array = array();
			if (is_array($data) && count($data) > 0)
			{
				foreach($data as $val)
				{
					$key2array[$val[$key]] = $val;
				}
			}
			return $key2array;
		}
		function valuestring($params, $separator = ",")
		{
			if (is_array($params) && count($params) > 0)
			{
				$table_name = $params['table_name'];
				$field = $params['field'];
				$where = $params['where'];
				$order_by = $params['order_by'] != '' ? $params['order_by'] : $field;
				$group_by = $params['group_by'];
				$separator = trim($separator);
				$query = $this->db->query("SET SESSION GROUP_CONCAT_MAX_LEN = 6000000");
				$select = 'GROUP_CONCAT(DISTINCT '.$field.' ORDER BY '.$order_by.' SEPARATOR "'.$separator.'") AS idstr';				
				$sql_add = '';
				if ($where != '')
				$sql_add .= " AND $where";
				$query = $this->db->query("SELECT $select FROM $table_name WHERE 1=1 $sql_add");
				if (is_object($query))
				{
					if ($query->num_rows() > 0)
					{
						$row = $query->row_array();
						return $row['idstr'];
					}
				}
				else
				return false;
			}
		}
		function settheme($seltheme)
		{
			$this->config->set_item("selectedTheme", $seltheme);
		}
		function gettheme()
		{
			$array_user_info = $this->select_all_where_records($this->tbl_prefix.'user_master', "user_id = '".$this->session->userdata('EMUSERID')."'", '', '');
			if (isset($array_user_info[0]['theme']))
				return $array_user_info[0]['theme'];
			else
				return 'blue';
		}
		function getprofilephoto()
		{
			$array_user_info = array();
			$where = "user_id = '".$this->session->userdata('EMUSERID')."'";
			$array_user_info = $this->select_all_where_records($this->tbl_prefix.'user_master', $where, '', '');		
			if (isset($array_user_info[0]['profilephoto']) && $array_user_info[0]['profilephoto'] != "")
			$photo  = $array_user_info[0]['profilephoto'];
			if(file_exists($this->config->item("profilephotos").$photo) && $photo != '')
			{
				return $this->config->item("profilephoto").$photo;
			}
			else
			{
				$photo  = "avatar.png";
				return $this->config->item("profilephoto").$photo;
			}
		}
		function mon_param_uptime($object_id, $param_id, $from_date = "", $to_date = "")
		{
			if ($from_date == "" || $from_date == 0)
			$from_date = date('Y-m-d 00:00:01');
			if ($to_date == "" || $to_date == 0)
			$to_date = date('Y-m-d G:i:s');

			$today_date = date('Y-m-d G:i:s');
			if ($to_date == $today_date)
			{
				$to_date = date('Y-m-d G:i:s');
			}
			else
			{
				$to_date = $to_date;
			}
			$from_date = $from_date;
			$total_secs = strtotime($to_date) - strtotime($from_date);
			$outage = $this->common_model->select_all_where_records($this->tbl_prefix.'object_outage', "object_id = '".$object_id."' AND monitor_param_id = '".$param_id."' AND uptime_flag = 'y' AND (down_time BETWEEN '".$from_date."' AND '".$to_date."' OR up_time BETWEEN '".$from_date."' AND '".$to_date."') AND status = 'y' ORDER BY down_time ASC ", '', '');
			$ot_len = count($outage);
			$tot_down_time = 0;
			if (is_array($outage) && count($outage) > 0)
			{
				foreach($outage as $key => $data)
				{
					$downtime = $data['down_time'];
					$uptime = $data['up_time'];
					if ($key == 0)
					{
						if (strtotime($from_date) >= strtotime($downtime))
						{
							$downtime = $from_date;
						}
					}
					if ($key == ($ot_len - 1))
					{
						if ($uptime == '0000-00-00 00:00:00')
							$uptime = $to_date;
						else
						{
							if (strtotime($uptime) > strtotime($to_date))
							{
								$uptime = $to_date;
							}
						}
					}
					$downtime_value = strtotime($uptime) - strtotime($downtime);
					$tot_down_time = $tot_down_time + $downtime_value;
				}
				$hours = floor($tot_down_time / 3600);
				$mins = floor(($tot_down_time - ($hours * 3600)) / 60);
				$secs = floor($tot_down_time % 60);
				$down_perc = round(($tot_down_time / $total_secs) * 100, 2);
				$up_perc = 100 - $down_perc;
			}
			else
			{	
				
				$param_data = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param', "monitor_param_id = '".$param_id."'",'','','param,monitor_type_id');
				$monitor_type = $param_data[0]['monitor_type_id'];
				if($monitor_type == '1')
				$monitor_type = 'service';
				elseif($monitor_type == '2')	
				$monitor_type = 'performance';
				else
				$monitor_type = "";
				
				if($monitor_type != '')
				{	
					$file_mon_data = $this->common_model->readfile_to_array($object_id,$param_id,$monitor_type);
				}
				else
				$file_mon_data = array();
				
				/* $mon_alert_id_str = "0,";
					$mon_param_vals = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_values', 'object_id = "'.$object_id.'" AND monitor_param_id = "'.$param_id.'"', '', '', 'monitor_value_id');

					foreach($mon_param_vals as $each_id)
					{
						$mon_alert_id_str .= $each_id['monitor_value_id'].',';
					}
					$mon_alert_id_str = trim($mon_alert_id_str, ',');
					$alertstatus = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_alerts', "monitor_value_id IN (".$mon_alert_id_str.") AND status = 'y' AND bw_param_type = 'n'", '', '', 'resolve,description');*/
				if ($file_mon_data['resolve'] == 'n')
				{
					$last_outage = $this->common_model->select_all_where_records($this->tbl_prefix.'object_outage', "object_id = '".$object_id."' AND monitor_param_id = '".$param_id."' ORDER BY DATE DESC", '1', '0', 'down_time');
					if (strtotime($from_date) > strtotime($last_outage[0]['down_time']))
					{
						$up_perc = 0;
						$down_perc = 100;
					}
					else
					{
						$up_perc = 100;
						$down_perc = 0;
					}
				}
				else
				{
					$up_perc = 100;
					$down_perc = 0;
				}
			}
			$myresult = array();
			$myresult['uptime'] = $up_perc;
			$myresult['downtime'] = $down_perc;
			$myresult['down_mins'] = $tot_down_time / 60;
			return $myresult;
		}
		// check warranty of asset
		function check_warranty($WarrantyStartDate, $WarrantyInMonth)
		{
			if ($WarrantyStartDate != '' && $WarrantyInMonth != '')
			{
				$WarrentyAlertTime = $this->config->item('warrenty_alert');
				$expireDate = date("Y-m-d", strtotime(date("Y-m-d", strtotime($WarrantyStartDate))." +".$WarrantyInMonth." month"));
				$willExpire = date("Y-m-d", strtotime(date("Y-m-d", strtotime($expireDate))." -".$WarrentyAlertTime." month"));
				if ($expireDate < $this->db_date)
				return '<img title="Warranty Expired On '.$expireDate.'." alt="Expired" src="'.$this->config->item('theme_images').'critical.png" style="margin-left:10px;">';
				else if ($willExpire < $this->db_date)
				return '<img title="Warranty Will Expire On '.$expireDate.'." alt="Expire on '.$expireDate.'" src="'.$this->config->item('theme_images').'trouble.png" style="margin-left:10px;">';
				else
				return '';
			}
		}
		function validation_rules_master()
		{
			return $this->validation_rules;
		}
		function input_types_master()
		{
			return $this->input_type;
		}
		function checksshconnection($host, $port = 22, $username, $password)
		{
			$connection = @ssh2_connect($host, $port);
			if (@ssh2_auth_password($connection, $username, $password))
			{
				return "yes";
			}
			else
			{
				return 'no';
			}
		}
		function rrdvalue($file_name, $var_name, $time, $int_hr)
		{
			$pmsec = $time;
			$end = $pmsec - (3600 * $int_hr);
			$rrdcommand = "	rrdtool graph 1.png ".
			" DEF:IN=$file_name:$var_name:AVERAGE:step=60 ".
			" CDEF:CIN=IN,1,* ".
			" PRINT:CIN:AVERAGE:%6.2lf ".
			$rrdcommand .= " --end $pmsec --start $end  --title 'Last RRD Val' ";
			$rrdcommand."\n";
			$last_updated_data = $this->exec_command($rrdcommand);
			$last_data_array = explode("\n", $last_updated_data);
			if ($last_data_array['1'] > 0)
			$val = $last_data_array['1'];
			else
			$val = 0;

			$last_arr = array("rrd_val" => $val);
			return $last_arr;
		}
		function getBandwidthUsage($bwfile, $from_time, $to_time, $is_unit = "", $inoutavg = "AVERAGE")
		{
			$file = $bwfile;
			$total_out = 0;
			$total_in = 0;
			$lastdate = 0;
			if ($fp = popen("/usr/bin/rrdtool fetch $file $inoutavg -r 60 --start ".$from_time." --end ".$to_time."", 'r'))
			{
				fgets($fp, 4096);
				while(!feof($fp))
				{
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = explode(" ", $line);
						if (is_numeric($data[1]) && is_numeric($data[2]))
						{
							$date = substr($data[0], 0, -1);
							$in = number_format($data[1], 2, '.', '');
							$out = number_format($data[2], 2, '.', '');
							if ($lastdate != 0)
							{
								if (!is_numeric($in))
								$in = 0;
								if (!is_numeric($out))
								$out = 0;
								$in = $in * ($date - $lastdate);
								$out = $out * ($date - $lastdate);
								$total_in = $total_in + $in;
								$total_out = $total_out + $out;
							}
							$lastdate = $date;
						}
					}
				}//while
				pclose($fp);
				$total = $total_in + $total_out;
				if ($is_unit == 'n')
				$last_arr = array("in" => $total_in, "out" => $total_out, "total" => $total);
				else
				$last_arr = array("in" => $this->converter_bw($total_in), "out" => $this->converter_bw($total_out), "total" => $this->converter_bw($total));
				return $last_arr;
			}//4 hour
		}
		function getbwusagewithperiod($bwfile, $from_time, $to_time, $interval, $speed = 1000000, $perclimit = 85, $is_unit = "", $inoutavg = "AVERAGE")
		{
			$file = $bwfile;
			$total_out = 0;
			$total_in = 0;
			$lastdate = 0;
			$fp = popen("/usr/bin/rrdtool fetch $file $inoutavg -r 60 --start ".$from_time." --end ".$to_time."", 'r');
			if ($fp)
			{
				fgets($fp, 4096);
				$percexceedseconds = $pertcnt = 0;		
				while(!feof($fp))
				{
					$out = $in = $out_perc = $in_perc = 0;
					$line = trim(fgets($fp, 4096));
					if ($line != '')
					{
						$data = explode(" ", $line);
						if (is_numeric($data[1]) && is_numeric($data[2]))
						{
							$date = substr($data[0], 0, -1);
							$in = number_format($data[1], 2, '.', '');
							$out = number_format($data[2], 2, '.', '');
							if ($lastdate != 0)
							{								
								if (!is_numeric($in))
								$in = 0;
								if (!is_numeric($out))
								$out = 0;
								$in = $in * ($date - $lastdate);
								$out = $out * ($date - $lastdate);
								$total_in = $total_in + $in;
								$total_out = $total_out + $out;
								$in_perc = round(($in / $speed) * 100, 2);
								$out_perc = round(($out / $speed) * 100, 2);
								if ($in_perc >= $perclimit || $out_perc >= $perclimit)
								$pertcnt++;
							}
							$lastdate = $date;
						}
					}
				}//while
				$percexceedseconds = $interval * $pertcnt; // interval in seconds 
				pclose($fp);
				$total = $total_in + $total_out;
				if ($is_unit == 'n')
				$last_arr = array("in" => $total_in, "out" => $total_out, "total" => $total, 'exceededtime' => $percexceedseconds);
				else
				$last_arr = array("in" => $this->converter_bw($total_in), "out" => $this->converter_bw($total_out), "total" => $this->converter_bw($total), 'exceededtime' => $percexceedseconds);
				return $last_arr;
			}//4 hour
		}
		// If $balance  set to true, element is auto managed in case if it's input type is changed at high level.
		// create html tags from config options
		function buildHtmlTag($params = array(), $balance = false)
		{
			//print_r($params);
			$HtmlTag = array();
			$HtmlTag['html'] = '';
			$HtmlTag['javascript'] = '';
			if (is_array($params))
			{
				$name = isset($params['name']) ? $params['name'] : '';
				$options = isset($params['options']) ? $params['options'] : array();
				$otherAttr = isset($params['otherAttr']) ? $params['otherAttr'] : '';
				$eletype = isset($params['eletype']) ? $params['eletype'] : 'text';
				$value = isset($params['value']) ? $params['value'] : '';
				$select = isset($params['select']) ? $params['select'] : '';
				$component_type = isset($params['component_type']) ? $params['component_type'] : '';
				$item_config_id = isset($params['item_config_id']) ? $params['item_config_id'] : '';
				$autoManageBy = isset($params['autoManageBy']) ? $params['autoManageBy'] : '1'; // If $auto_manage_flag set to true, element is auto managed text field and date field in case if it's input type is changed at low level.
				if ($eletype == "textbox" || $eletype == "y")
				{
					// check attribute type changed in component tree or not, if update extra option and extra value by new change.
					if ($autoManageBy == '2')
					{
						
					}
					else
					{
						if ($balance)
						{
							if ($select != '' && $select > 0)
							{
								$value = $this->getConfigOptionName($select, $name, $component_type);
							}
						}
					}
					$HtmlTag['html'] = form_input($name, $value, $otherAttr);
				}
				else if ($eletype == "date")
				{
					// check attribute type changed in component tree or not, if update extra option and extra value by new change.
					if ($autoManageBy == '2')
					{
						
					}
					else
					{
						if ($balance)
						{
							if ($select != '' && $select > 0)
							{
								$value = $this->getConfigOptionName($select, $name, $component_type);
							}
						}
					}
					$HtmlTag['html'] = form_input($name, $value, ' readonly="readonly" '.$otherAttr);
					$HtmlTag['html'] .= '<img border="0" width="20" id="datefield_'.$name.'"  class="curpointer" 
													src="'.$this->config->item('theme_images').'calendar.png'.'"  />';
					$HtmlTag['javascript'] = 'cal.manageFields("datefield_'.$name.'", "'.$name.'", "%Y-%m-%d");';
				}
				else if ($eletype == "dropdown" || $eletype == "n")
				{
					// check attribute type changed in component tree or not, if update extra option and extra value by new change.
					if ($autoManageBy == '2')
					{
						if ($value != '')
						{
							$select = $this->getConfigOptionId($name, $value, $component_type, $item_config_id); //$item_config_id used to check extra value exists in options or not, if not exists then add it into config options
							if ($select != '' && $select != 'EXISTS')
							{
								$options[$value] = $value;
							}
						}
						$select = $value;
					}
					else
					{
						if ($balance)
						{
							if ($select == '')
							{
								if ($value != '')
								{
									$select = $this->getConfigOptionId($name, $value, $component_type);
									if ($select != '' && $select != 'EXISTS')
									{
										$options[$select] = $value;
									}
								}
							}
						}
					}
					$HtmlTag['html'] = form_dropdown($name, $options, $select, $otherAttr);
				}
			}
			return $HtmlTag;
		}
		function getConfigOptionName($config_option_id, $item_config_id, $component_type)
		{
			$config_option_name = '';
			if ($config_option_id != '' && $item_config_id != '')
			{
				if ($component_type == $this->hardware_config)
				{
					$config_option_name = $this->get_field_value('config_option_name', $this->tbl_prefix.'inv_configoption', 'configoption_id = "'.$this->db->escape_str($config_option_id).'" AND item_config_id = "'.$this->db->escape_str($item_config_id).'"');
				}
				else if ($component_type == $this->software_config)
				{
					$config_option_name = $this->get_field_value('config_option_name', $this->tbl_prefix.'inv_sw_configoption', 'configoption_id = "'.$this->db->escape_str($config_option_id).'" AND item_config_id = "'.$this->db->escape_str($item_config_id).'"');
				}
			}
			return $config_option_name;
		}
		function getConfigOptionId($item_config_id, $extra_value, $component_type, $optional_itemconfigid = '')
		{
			if ($optional_itemconfigid != '')
			$item_config_id = $optional_itemconfigid;
			$config_option_id = '';
			if ($item_config_id != '' && $extra_value != '')
			{
				if ($component_type == $this->hardware_config)
				{
					$optionExists = $this->select_all_where_records($this->tbl_prefix."inv_configoption", "item_config_id = '".$this->db->escape_str($item_config_id)."' AND config_option_name = '".$this->db->escape_str($extra_value)."' AND status = 'y'", '', '', 'configoption_id');
					if (count($optionExists) > 0)
					{
						$config_option_id = $optionExists[0]['configoption_id'];
					}
					else
					{
						$config_option_id = $this->common_model->insert_records($this->tbl_prefix.'inv_configoption', array('item_config_id' => $item_config_id, 'config_option_name' => $extra_value));
					}
				}
				else if ($component_type == $this->software_config)
				{
					$optionExists = $this->select_all_where_records($this->tbl_prefix."inv_sw_configoption", "item_config_id = '".$this->db->escape_str($item_config_id)."' AND config_option_name = '".$this->db->escape_str($extra_value)."' AND status = 'y'", '', '', 'configoption_id');
					if (count($optionExists) > 0)
					{
						$config_option_id = $optionExists[0]['configoption_id'];
					}
					else
					{
						$config_option_id = $this->common_model->insert_records($this->tbl_prefix.'inv_sw_configoption', array('item_config_id' => $item_config_id, 'config_option_name' => $extra_value));
					}
				}
			}
			return $config_option_id;
		}
		function filecompare($file1, $file2, $diff_flag = 0)
		{
			$this->load->library('comparefiles');
			$data = $this->comparefiles->difftracker($file1, $file2);
			return $data;
		}
		/**
			* check port connection
			*
			* @param string IP address
			* @param int port number
			* @param string status
			*/
		function connect($addr, $port)
		{
			$churl = @fsockopen($addr, $port, $errno, $errstr, 2);
			if (!$churl)
			$st = "Offline";
			else
			$st = "Online";
			return $st;
		}		
		function disk_monitoring($device_id, $param_id,$return_val = "")
		{
			$disk_index_str = '';
			$snmp_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$device_id."' AND type = 'snmp' AND status = 'y'");
			$prim_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$device_id."' AND type = 'primary' AND status = 'y'");
			$mgt_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$device_id."' AND type = 'management' AND status = 'y'");
			if ($snmp_ip != "")
			$ip = $snmp_ip;
			elseif ($prim_ip != "")
			$ip = $prim_ip;
			else
			$ip = $mgt_ip;

			//$probe_name = $this->common_model->probedetails($device_id);
			$probe = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_probes mp, '.$this->tbl_prefix.'object_master om', 'om.monitor_probe_id = mp.probe_id AND om.object_id = "'.$device_id.'"', '', '', 'mp.title as probe_name,om.monitor_probe_id');
			$probe_name = isset($probe[0]) ? $probe[0]['monitor_probe_id'] : '';
			
			$emag_src = $this->config->item('emagic_source');
			$script_file = $emag_src."cr/snmp_check.pl";
			$perl_path = $this->config->item('perl_command');
			$cmd1 = "$perl_path $script_file -object_id $device_id -probe_id $probe_name -check walk -oid '.1.3.6.1.2.1.25.2.3.1.1'";
			$op = $this->common_model->exec_command($cmd1);
			
			$diskindex = explode("\n",trim($op,"\n"));	
			
			$findme = 'No Response';
			$pos = strpos($op, $findme);
			if ($pos != false)
			{
				return 'SNMP is not responding. Please check SNMP configuration and SNMP details !!!';
				exit;
			}			
			$cmd2 = "$perl_path $script_file -object_id $device_id -probe_id $probe_name -check walk -oid '.1.3.6.1.2.1.25.2.3.1.2'";
			$op1 = $this->common_model->exec_command($cmd2);
			$disktype = explode("\n",trim($op1,"\n"));			
			$cmd3 = "$perl_path $script_file -object_id $device_id -probe_id $probe_name -check walk -oid '.1.3.6.1.2.1.25.2.3.1.3'";
			$op2 = $this->common_model->exec_command($cmd3);
			$diskpath = explode("\n",trim($op2,"\n"));			
			$diskindex_cnt = count($diskindex);
			
			$first_disk_index = $this->get_snmp_value('INTEGER:', $diskindex[0]);
			
			if(is_array($diskindex) && $diskindex_cnt > 0 && $first_disk_index > 0)
			{
				for($i = 0; $i < $diskindex_cnt; $i++)
				{
					$disk_index = $diskindex[$i];
					$disk_index = $this->get_snmp_value('INTEGER:', $disk_index);
					if (strpos($disktype[$i], 'hrStorageFixedDisk') !== false)
					{
						$disk_index_str .= $disk_index.',';
						$disk_path = $diskpath[$i];
						$disk_path = $this->get_snmp_value('STRING:', $disk_path);
						
						if ($this->common_model->chkrecordexists($this->tbl_prefix.'object_disks', "object_id = '".$device_id."' AND disk_index = '".$disk_index."'"))
						{
							$disk_id_gen = $this->common_model->update_records($this->tbl_prefix.'object_disks', "object_id = '".$device_id."' AND disk_index = '".$disk_index."'", array('object_id' => $device_id, 'disk_index' => $disk_index, 'partition_name' => $disk_path,'mounted_on' => $disk_path, 'date' => date('Y-m-d G:i:s'), 'status' => 'y', 'mon_method' => 'snmp'));
						}
						else
						{
							$disk_id_gen = $this->common_model->insert_records($this->tbl_prefix.'object_disks', array('object_id' => $device_id, 'disk_index' => $disk_index, 'partition_name' => $disk_path,'mounted_on' => $disk_path, 'date' => date('Y-m-d G:i:s'),'mon_method' => 'snmp'));
						}	
						$where_param = "monitor_param_id = '".$param_id."'";
						$properties = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', $where_param, '', '');
						foreach($properties as $each_prop)
						{
							if ($each_prop['variable_name'] == 'threshold_alert')
							{
								$prop_array = array(
								'monitor_property_id' => $each_prop['monitor_property_id'],
								'disk_id' => $disk_id_gen,
								'monitor_param_id' => $param_id,
								'property_type_value' => '>',
								'property_value' => $each_prop['default_value']
								);
							}
							else
							{
								$prop_array = array(
								'monitor_property_id' => $each_prop['monitor_property_id'],
								'disk_id' => $disk_id_gen,
								'monitor_param_id' => $param_id,
								'property_type_value' => '',
								'property_value' => $each_prop['default_value']
								);
							}
							if ($this->common_model->chkrecordexists($this->tbl_prefix.'monitoring_disk_param_values', "disk_id = '".$disk_id_gen."' AND monitor_property_id = '".$each_prop['monitor_property_id']."'"))
							{
								$last_id = $this->common_model->update_records($this->tbl_prefix.'monitoring_disk_param_values', "disk_id = '".$disk_id_gen."'", array('status' => 'y'));
							}
							else
							{
								$last_id = $this->common_model->insert_records($this->tbl_prefix.'monitoring_disk_param_values', $prop_array);
							}
						}
						if ($last_id > 0)
						{
							$alert_array = array(
							'disk_id' => $disk_id_gen,
							'monitor_value_id' => $last_id,
							'date' => date('Y-m-d H:i:s'));
							
							if ($this->common_model->chkrecordexists($this->tbl_prefix.'monitoring_disk_alerts', "disk_id = '".$disk_id_gen."'"))
							{
								$this->common_model->update_records($this->tbl_prefix.'monitoring_disk_alerts', "disk_id = '".$disk_id_gen."'", array('status' => 'y'));
							}
							else
							{
								$this->common_model->insert_records($this->tbl_prefix.'monitoring_disk_alerts', $alert_array);
							}
						}	
						$rrdPath = $this->config->item('data_dir');
						$rrdPath = $rrdPath.$probe_name.'/';
						$fname = "$rrdPath/rrd_disks/".$disk_id_gen.".rrd";
						$where = "monitor_param_id = '".$param_id."' AND field_type = 'fetch_method' AND variable_name != 'mon_method' AND status = 'y'";
						$ois_params = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', $where, '', '');
						$mon_values = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', "monitor_param_id = '".$param_id."' AND variable_name = 'interval' AND status = 'y'", '', 'default_value');
						$set_var = "";
						$rrd_interval = $mon_values[0]['default_value'] * 60;
						$rra_steps = 259200 / $mon_values[0]['default_value'];
						$gauge_val = $rrd_interval * 5;
						foreach($ois_params as $each_oid)
						{
							$var_type = $this->common_model->get_field_value('var_type', $this->tbl_prefix.'monitoring_variable', "name = '".$each_oid['variable_name']."'");
							$set_var .= " DS:".$each_oid['variable_name'].":".$var_type.":".$gauge_val.":U:U";
						}
						if (!file_exists($fname))
						{ // IF file doesnt exist then create its rrd file
							$cmd = "rrdtool create $fname  -s ".$rrd_interval." ".$set_var."  RRA:AVERAGE:0.5:1:".$rra_steps."";
							$this->common_model->esds_data($cmd);
						}
					}
					
				}
				$disk_index_str = trim($disk_index_str, ',');
				if ($disk_index_str != '')
				$this->common_model->update_records($this->tbl_prefix.'object_disks', "disk_index NOT IN (".$disk_index_str.") AND is_iops = 'n' AND object_id = '".$device_id."'", array('status' => 'n'));
			}
			else
			{	
				if($return_val == 'yes')
				{
					return 'No Disk data found';
				}
				else
				{
					echo 'No Disk data found';	
				}
			}
		}
		
		function disk_monitoring_script($object_id,$param_id,$type)
		{	
			$disk_str = '';
			$prim_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'primary' AND status = 'y'");
			$mgt_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'management' AND status = 'y'");
			if ($prim_ip != "")
			$ip = $prim_ip;
			else
			$ip = $mgt_ip;
			$probe_name = $this->common_model->probedetails($object_id);
			$emagic_source = $this->config->item('emagic_source');
			$perl_command = $this->config->item('perl_command');
			$ssh_cred_id = $this->common_model->get_field_value('credential_detail_id', $this->tbl_prefix.'object_credentials',"object_id = '".$object_id."' AND credential_type_id = 3 AND status = 'y'");			
			$wmi_cred_id = "";
			if($type == 'windisk')
			{
				$wmi_cred_id = $this->common_model->get_field_value('credential_detail_id', $this->tbl_prefix.'object_credentials',"object_id = '".$object_id."' AND credential_type_id = 2 AND status = 'y'");
			}			
			if($ssh_cred_id > 0 || $wmi_cred_id > 0)
			{	
				if($type == 'windisk')
				{
					$cred_details = $this->common_model->select_all_where_records($this->tbl_prefix.'object_credentials_windows',"credential_detail_id = '".$wmi_cred_id."'",'','');				
					$method = "WMI";
					$port_mthod = "";
				}
				else
				{
					$cred_details = $this->common_model->select_all_where_records($this->tbl_prefix.'object_credentials_sshtelnet',"credential_detail_id = '".$ssh_cred_id."'",'','');				
					$method = "SSH";					
					$port = $cred_details[0]['port'];
					if($port == '')
					$port = 22;					
					$port_mthod = " -port ".$port;					
				}				
				$username = $cred_details[0]['username'];
				$password = $this->decrypt($cred_details[0]['pass']);
				$command = $perl_command.' '.$emagic_source.'monscripts/monitor_script.pl -host '.$ip.' -user '.$username.' -passwd \''.$password.'\' -loginmethod '.$method.' -monitor_param '.$type.' '.$port_mthod;
				
				$result = $this->exec_command($command);				
				
				if(strstr($result,'Filesystem:'))
				{
					$filesys_data = explode("|##|##|",$result);
					$filesys_data = array_filter($filesys_data);
					$filesys_data_cnt = count($filesys_data);
					
					if(is_array($filesys_data) && $filesys_data_cnt > 0)
					{							
						for($i=0;$i < $filesys_data_cnt;$i++)
						{	
							$disdata = array();
							$each_partition = explode('|##|',$filesys_data[$i]);
							$each_partition_cnt = count($each_partition);
							for($v=0; $v < $each_partition_cnt; $v++)
							{
								$detail_arr = explode(':',$each_partition[$v]);	
								$disdata[$detail_arr[0]] = $detail_arr[1];
							}	
							$disk_path = $disdata['Mounted on'];
							$disk_str .= "'".$disk_path."',";
							if ($disk_path !== '')
							{
								if ($this->common_model->chkrecordexists($this->tbl_prefix.'object_disks', "object_id = '".$object_id."' AND mounted_on = '".$disk_path."'"))
								{
									$disk_id_gen = $this->common_model->update_records($this->tbl_prefix.'object_disks', "object_id = '".$object_id."' AND mounted_on = '".$disk_path."'", array('object_id' => $object_id, 'partition_name' => $disdata['Filesystem'], 'mounted_on' => $disk_path, 'date' => date('Y-m-d G:i:s'), 'status' => 'y','mon_method' => 'script','mon_type' => $type));
								}
								else
								{
									$disk_id_gen = $this->common_model->insert_records($this->tbl_prefix.'object_disks', array('object_id' => $object_id, 'mounted_on' => $disk_path,'partition_name' => $disdata['Filesystem'], 'date' => date('Y-m-d G:i:s'),'mon_method' => 'script','mon_type' => $type));
								}							
								$where_param = "monitor_param_id = '".$param_id."' AND status = 'y'";
								$properties = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', $where_param, '', '');
								foreach($properties as $each_prop)
								{	
									if ($each_prop['variable_name'] == 'threshold_alert')
									{
										$prop_array = array(
										'monitor_property_id' => $each_prop['monitor_property_id'],
										'disk_id' => $disk_id_gen,
										'monitor_param_id' => $param_id,
										'property_type_value' => '>',
										'property_value' => $each_prop['default_value'],
										'status' => 'y'
										);
									}
									else
									{	
										$prop_array = array(
										'monitor_property_id' => $each_prop['monitor_property_id'],
										'disk_id' => $disk_id_gen,
										'monitor_param_id' => $param_id,
										'property_type_value' => '',
										'property_value' => $each_prop['default_value'],
										'status' => 'y'
										);
										
										if ($each_prop['variable_name'] == 'mon_method')
										{
											$prop_array['property_type_value'] = $type;
											$prop_array['property_value'] = 'SCRIPT';
										}
									}
									$is_exists = $this->common_model->chkrecordexists($this->tbl_prefix.'monitoring_disk_param_values', "disk_id = '".$disk_id_gen."' AND monitor_property_id = '".$each_prop['monitor_property_id']."'");
									if ($is_exists > 0)
									{
										$last_id = $this->common_model->update_records($this->tbl_prefix.'monitoring_disk_param_values', "disk_id = '".$disk_id_gen."'", $prop_array);									
									}
									else
									{
										
										$last_id = $this->common_model->insert_records($this->tbl_prefix.'monitoring_disk_param_values', $prop_array);									
									}
								}
								if ($last_id > 0)
								{
									$alert_array = array(
									'disk_id' => $disk_id_gen,
									'monitor_value_id' => $last_id,
									'date' => date('Y-m-d H:i:s'));
									
									if ($this->common_model->chkrecordexists($this->tbl_prefix.'monitoring_disk_alerts', "disk_id = '".$disk_id_gen."'"))
									{
										$this->common_model->update_records($this->tbl_prefix.'monitoring_disk_alerts', "disk_id = '".$disk_id_gen."'", array('status' => 'y'));
									}
									else
									{
										$this->common_model->insert_records($this->tbl_prefix.'monitoring_disk_alerts', $alert_array);
									}
								}			
								$rrdPath = $this->config->item('data_dir');
								$rrdPath = $rrdPath.$probe_name.'/';			
								$fname = "$rrdPath/rrd_disks/".$disk_id_gen.".rrd";			
								$where = "monitor_param_id = '".$param_id."' AND field_type = 'fetch_method' AND variable_name != 'mon_method' AND status = 'y'";
								$mon_values = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', "monitor_param_id = '".$param_id."' AND variable_name = 'interval' AND status = 'y'", '', 'default_value');
								$set_var = "";
								$rrd_interval = $mon_values[0]['default_value'] * 60;
								$rra_steps = 259200 / $mon_values[0]['default_value'];
								$gauge_val = $rrd_interval * 2;
								$ois_params = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', $where, '', '');
								if (is_array($ois_params) && count($ois_params) > 0)
								{
									foreach($ois_params as $each_oid)
									{
										$var_type = $this->common_model->get_field_value('var_type', $this->tbl_prefix.'monitoring_variable', "name = '".$each_oid['variable_name']."'");
										$set_var .= " DS:".$each_oid['variable_name'].":".$var_type.":".$gauge_val.":U:U";
									}
								}
								if (!file_exists($fname))
								{ // IF file doesnt exist then create its rrd file
									$cmd = "rrdtool create $fname  -s ".$rrd_interval." ".$set_var."  RRA:AVERAGE:0.5:1:".$rra_steps."";
									$this->common_model->esds_data($cmd);
								}
							}
						}
					}
				}
				else
				{
					echo 'No Response found or Unable to connect using SSH/WMI.';	
				}				
				$disk_str = trim($disk_str, ',');
				if ($disk_str != '')
				$this->common_model->update_records($this->tbl_prefix.'object_disks', "mounted_on NOT IN (".$disk_str.") AND is_iops = 'n' AND object_id = '".$object_id."'", array('status' => 'n'));
				
			}
			else
			{
				echo 'Required Credential not assigned to device';
			}        
		}
		
		function disk_iops_monitoring($object_id, $param_id,$return_val = "", $disk_mon_method="", $type="")
		{
			$snmp_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'snmp' AND status = 'y'");
			if ($snmp_ip != "")
			$ip = $snmp_ip;
			else
			{
				$prim_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'primary' AND status = 'y'");
				if ($prim_ip != "")
				$ip = $prim_ip;
				else
				{
					$mgt_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'management' AND status = 'y'");
					if ($mgt_ip != "")
					$ip = $mgt_ip;
					else
					die("No IP assinged to this device. Please assign IP Address.");
				}
			}	
			$probe_name = $this->common_model->probedetails($object_id);
			if($disk_mon_method == 'snmp')
			{
				$obj_snmp_details = $this->common_model->select_all_where_records($this->tbl_prefix.'object_credentials', 'status = "y" AND credential_type_id = 1 AND object_id = "'.$object_id.'"', '', '');
				$where_snmp = "credential_detail_id = '".$obj_snmp_details[0]['credential_detail_id']."'";
				$snmp_details = $this->common_model->select_all_where_records($this->tbl_prefix.'object_credentials_snmp', $where_snmp, '', '');

				$snmpv = $snmp_details[0]['snmp_version'];
				$community = $snmp_details[0]['community'];
				$auth_protocol = $snmp_details[0]['auth_protocol'];
				$auth_pass = $this->common_model->decrypt($snmp_details[0]['auth_pass']);
				$sec_level = $snmp_details[0]['sec_level'];
				$sec_name = $snmp_details[0]['username'];
				$priv_protocol = $snmp_details[0]['enc_protocol'];
				$priv_pass = $this->common_model->decrypt($snmp_details[0]['enc_pass']);
				$snmp_port = $snmp_details[0]['snmp_port'];
				$mount_disk_arr = array();
				$req_disk_index = array();
				if ($snmp_port == "")
				$snmp_port = 161;

				if ($snmpv == 'v1')
				$snmpv = '1';
				else if ($snmpv == 'v2')
				$snmpv = '2c';
				else if ($snmpv == 'v3')
				$snmpv = '3';

				if ($snmpv == '1' || $snmpv == '2c')
				{
					$snmp_status = $this->common_model->esnmpstatus($ip, $community, $snmpv, $snmp_port);
					if (!$snmp_status)
					{	
						if($return_val == 'yes')
						{
							return 'no';
						}
						else
						{
							return "SNMP connect for $ip fail !!";
							exit();
						}
					}
					$diskindex = $this->common_model->esnmpwalk($ip, $community, ".1.3.6.1.4.1.2021.9.1.3", $snmpv, $snmp_port);
					$all_disk_partiotions = $this->common_model->esnmpwalk($ip, $community, ".1.3.6.1.4.1.2021.13.15.1.1.2", $snmpv, $snmp_port);
					$my_array = array();
					$all_disk_partiotions_cnt = count($all_disk_partiotions);
					for($i = 0; $i < $all_disk_partiotions_cnt; $i++)
					{
						if (preg_match("/sd[a-z]*$|vd[a-z]*$|xvd[a-z]*$|hd[a-z]*$/", trim($all_disk_partiotions[$i],'"')))
						{
							$my_array[] = $all_disk_partiotions[$i];
						}
					}
					if (count($my_array) > 0)
					{
						$my_array_cnt = count($my_array);
						for($j = 0; $j < $my_array_cnt; $j++)
						{
							$req_index = $this->common_model->reg_match("/[0-9].()/", $my_array[$j]);
							$req_disk_index[] = $req_index[0][(count($req_index[0])-1)];
							$mount_disk_arr[] = $this->get_snmp_value('STRING:', $my_array[$j]);
						}
					}
					else
					{	
						if($return_val == 'yes')
						{
							return 'no';
						}
						else
						{
							return "Unable to fetch IOPS data";
							exit();
						}
					}
				}
				else
				{
					$snmp_status = $this->common_model->esnmp3_status($ip, $sec_name, $sec_level, $auth_protocol, $auth_pass, $priv_protocol, $priv_pass, $snmp_port);
					if (!$snmp_status)
					{
						if($return_val == 'yes')
						{
							return 'no';
						}
						else
						{
							return "SNMP connect for $ip fail !!";
							exit();
						}
					}
					// $diskindex = $this->common_model->esnmp3_walk($ip, $sec_name, $sec_level, $auth_protocol, $auth_pass, $priv_protocol, $priv_pass, ".1.3.6.1.4.1.2021.9.1.3", $snmp_port);
					$all_disk_partiotions = $this->common_model->esnmp3_walk($ip, $sec_name, $sec_level, $auth_protocol, $auth_pass, $priv_protocol, $priv_pass, ".1.3.6.1.4.1.2021.13.15.1.1.2", $snmp_port);
					
					$my_array = array();
					$all_disk_partiotions_cnt = count($all_disk_partiotions);
					for($i = 0; $i < $all_disk_partiotions_cnt; $i++)
					{
						if (preg_match("/sd[a-z]*$|vd[a-z]*$|xvd[a-z]*$|hd[a-z]*$/", trim($all_disk_partiotions[$i],'"')))
						{
							$my_array[] = $all_disk_partiotions[$i];
						}
					}
					
					$my_array_cnt = count($my_array);
					if ($my_array_cnt > 0)
					{
						for($j = 0; $j < $my_array_cnt; $j++)
						{
							$req_index = $this->common_model->reg_match("/[0-9].()/", $my_array[$j]);
							$req_disk_index[] = $req_index[0][(count($req_index[0])-1)];
							$mount_disk_arr[] = $this->get_snmp_value('STRING:', $my_array[$j]);
						}
					}
					else
					{
						return "Unable to fetch IOPS data";
						exit();
					}
				}
			}
			else if($disk_mon_method == 'script')
			{
				$iops_param_map = array("windisk" => "winiops", "linuxdisk" => "linuxiops", "hpdisk" => "hpiops", "aixdisk" => "aixiops", "esxdisk" => "esxiops", "winiops" => "winiops", "linuxiops"=> "linuxiops" , "hpiops" => "hpiops", "aixdisk" => "aixdisk", "esxiops" => "esxiops" );
				if(!isset($iops_param_map[$type]))
				{
					die("Invalid IOPS Parameter.");
				}	
				$emagic_source = $this->config->item('emagic_source');
				$perl_command = $this->config->item('perl_command');
				$wmi_cred_id = $ssh_cred_id = "";
				if($type == 'windisk')
				{
					$wmi_cred_id = $this->common_model->get_field_value('credential_detail_id', $this->tbl_prefix.'object_credentials',"object_id = '".$object_id."' AND credential_type_id = 2 AND status = 'y'");
				}else
				{
					$ssh_cred_id = $this->common_model->get_field_value('credential_detail_id', $this->tbl_prefix.'object_credentials',"object_id = '".$object_id."' AND credential_type_id = 3 AND status = 'y'");				
				}			
				if($ssh_cred_id > 0 || $wmi_cred_id > 0)
				{	
					if($type == 'windisk')
					{
						$cred_details = $this->common_model->select_all_where_records($this->tbl_prefix.'object_credentials_windows',"credential_detail_id = '".$wmi_cred_id."'",'','');				
						$method = "WMI";
						$port_mthod = "";
					}
					else
					{
						$cred_details = $this->common_model->select_all_where_records($this->tbl_prefix.'object_credentials_sshtelnet',"credential_detail_id = '".$ssh_cred_id."'",'','');				
						$method = "SSH";					
						$port = $cred_details[0]['port'];
						if($port == '')
						$port = 22;					
						$port_mthod = " -port ".$port;					
					}				
					$username = $cred_details[0]['username'];
					$password = $this->decrypt($cred_details[0]['pass']);
					$command = $perl_command.' '.$emagic_source.'monscripts/monitor_script.pl -host '.$ip.' -user '.$username.' -passwd \''.$password.'\' -loginmethod '.$method.' -monitor_param '.$iops_param_map[$type].' '.$port_mthod;
					$result = $this->exec_command($command);	
					if(strstr($result,'DATA#'))
					{
						$filesys_data = explode("|##|##|",$result);
						$filesys_data_cnt = count($filesys_data);
						if(is_array($filesys_data) && $filesys_data_cnt > 0)
						{							
							for($i=0;$i < $filesys_data_cnt;$i++)
							{	
								$disdata = array();
								if($filesys_data[$i] == '')
								continue;
								$iopsdata = str_replace("DATA#","",$filesys_data[$i]);
								$each_partition = explode('|',$iopsdata);
								$each_partition_cnt = count($each_partition);
								$req_disk_index[] = $i;
								for($v=0; $v < $each_partition_cnt; $v++)
								{
									$detail_arr = explode('=',$each_partition[$v]);	
									$disdata[$detail_arr[0]] = $detail_arr[1];
								}	
								$disk_path = $disdata['diskname'];
								$iops_rate = $disdata['iops'];
								$mount_disk_arr[] = $disk_path;
							}
						}
					}
					else
					{
						echo 'No Response found or Unable to connect using SSH/WMI.';	
					}				
				}
				else
				{
					echo 'Required Credential not assigned to device';
				}   
			}
			$req_disk_index_cnt = count($req_disk_index);
			if (is_array($req_disk_index) && $req_disk_index_cnt > 0)
			{
				for($v = 0; $v < $req_disk_index_cnt; $v++)
				{
					$disk_index = $req_disk_index[$v];
					$disk_path = trim($mount_disk_arr[$v]);
					$disk_path = trim($disk_path,'"');
					if ($this->common_model->chkrecordexists($this->tbl_prefix.'object_disks', "object_id = '".$object_id."' AND mounted_on = '".$disk_path."' AND is_iops = 'y'"))
					{
						$disk_id_gen = $this->common_model->update_records($this->tbl_prefix.'object_disks', "object_id = '".$object_id."' AND mounted_on = '".$disk_path."' AND is_iops = 'y'", array('object_id' => $object_id, 'disk_index' => $disk_index, 'partition_name' => $disk_path,'mounted_on' => $disk_path, 'date' => date('Y-m-d G:i:s'), 'status' => 'y', 'mon_method' => $disk_mon_method, 'mon_type' => $iops_param_map[$type]));
					}
					else
					{
						$disk_id_gen = $this->common_model->insert_records($this->tbl_prefix.'object_disks', array('object_id' => $object_id, 'disk_index' => $disk_index, 'partition_name' => $disk_path, 'date' => date('Y-m-d G:i:s'), 'is_iops' => 'y','mounted_on' => $disk_path, 'mon_method' => $disk_mon_method, 'mon_type' => $iops_param_map[$type]));
					}
					$where_param = "monitor_param_id = '".$param_id."'";
					$properties = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', $where_param, '', '');
					if (is_array($properties) && count($properties) > 0)
					{
						foreach($properties as $each_prop)
						{
							if ($each_prop['variable_name'] == 'threshold_alert')
							{
								$prop_array = array(
								'monitor_property_id' => $each_prop['monitor_property_id'],
								'disk_id' => $disk_id_gen,
								'monitor_param_id' => $param_id,
								'property_type_value' => '>',
								'property_value' => $each_prop['default_value']
								);
							}
							else
							{
								$prop_array = array(
								'monitor_property_id' => $each_prop['monitor_property_id'],
								'disk_id' => $disk_id_gen,
								'monitor_param_id' => $param_id,
								'property_type_value' => '',
								'property_value' => $each_prop['default_value']
								);
							}
							if ($each_prop['variable_name'] == 'mon_method')
							{
								$prop_array['property_value'] = strtoupper($disk_mon_method);
								$carry_forward = array('status' => 'y', 'property_value' => strtoupper($disk_mon_method));
							}else
							$carry_forward = array('status' => 'y');
							
							if ($this->common_model->chkrecordexists($this->tbl_prefix.'monitoring_disk_param_values', "disk_id = '".$disk_id_gen."' AND monitor_property_id = '".$each_prop['monitor_property_id']."'"))
							{
								$last_id = $this->common_model->update_records($this->tbl_prefix.'monitoring_disk_param_values', "disk_id = '".$disk_id_gen."'", $carry_forward);
							}
							else
							{
								$last_id = $this->common_model->insert_records($this->tbl_prefix.'monitoring_disk_param_values', $prop_array);
							}
						}
					}                    
					if ($last_id > 0)
					{
						$alert_array = array('disk_id' => $disk_id_gen, 'monitor_value_id' => $disk_id, 'date' => date('Y-m-d H:i:s'));

						if ($this->common_model->chkrecordexists($this->tbl_prefix.'monitoring_disk_alerts', "disk_id = '".$disk_id_gen."'"))
						{
							$this->common_model->update_records($this->tbl_prefix.'monitoring_disk_alerts', "disk_id = '".$disk_id_gen."'", array('status' => 'y'));
						}
						else
						{
							$this->common_model->insert_records($this->tbl_prefix.'monitoring_disk_alerts', $alert_array);
						}
					}
					$rrdPath = $this->config->item('data_dir');
					$rrdPath = $rrdPath.$probe_name.'/';
					$fname = "$rrdPath/rrd_disks/".$disk_id_gen.".rrd";
					$where = "monitor_param_id = '".$param_id."' AND field_type = 'fetch_method' AND variable_name != 'mon_method' AND status = 'y'";                    
					$mon_values = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', "monitor_param_id = '".$param_id."' AND variable_name = 'interval' AND status = 'y'", '', 'default_value');
					$set_var = "";
					$rrd_interval = $mon_values[0]['default_value'] * 60;
					$rra_steps = 259200 / $mon_values[0]['default_value'];
					$gauge_val = $rrd_interval * 2;
					$ois_params = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', $where, '', '');
					if (is_array($ois_params) && count($ois_params) > 0)
					{
						foreach($ois_params as $each_oid)
						{
							$var_type = $this->common_model->get_field_value('var_type', $this->tbl_prefix.'monitoring_variable', "name = '".$each_oid['variable_name']."'");
							$set_var .= " DS:".$each_oid['variable_name'].":GAUGE:".$gauge_val.":U:U";
						}
					}
					if (!file_exists($fname))
					{ // IF file doesnt exist then create its rrd file
						$cmd = "rrdtool create $fname  -s ".$rrd_interval." ".$set_var."  RRA:AVERAGE:0.5:1:".$rra_steps."";
						$this->common_model->esds_data($cmd);
					}
				}
			}
		}
		function deldiskmon($object_id, $param_id)
		{
			$param_name = $this->common_model->get_field_value('param', $this->tbl_prefix.'monitoring_param', "monitor_param_id = '".$param_id."' ");
			if ($object_id > 0)
			{
				if (trim($param_name) == 'Disk IOPS')
				$this->common_model->update_records($this->tbl_prefix.'object_disks', 'object_id = "'.$object_id.'" AND is_iops = "y"', array('status' => 'n'));
				else
				$this->common_model->update_records($this->tbl_prefix.'object_disks', 'object_id = "'.$object_id.'" AND is_iops = "n"', array('status' => 'n'));
			}
			else
			{
				echo 'no';
			}
		}
		// Function for encryption
		function vs64_encrypt($data)
		{
			return base64_encode(base64_encode(base64_encode(strrev($data))));
		}
		// Function for decryption
		function vs64_decrypt($data)
		{
			return strrev(base64_decode(base64_decode(base64_decode($data))));
		}
		function generatecsv($columnname = "", $csvdataobjects, $report_title = "", $offset = "0", $filename = "Report", $from_date="", $to_date="")
		{
			$csvdata = '';
			$pdf_msg = $report_title;
			if (is_array($csvdataobjects) && count($csvdataobjects) > 0)
			{
				$i = 0;
				foreach($csvdataobjects as $row)
				{
					$row = (array) $row;
					array_walk($row, array($this, 'cleanData'));
					$sr = $i + $offset + 1;
					$csvdata .= $sr.",".implode(",", array_values($row))."\n";
					$i++;
				}
			}
			else
			{
				$pdf_msg = 'No Records';
				$columnname = '';
			}
			$csv_head = $pdf_msg."\n";
			if($from_date != '' && $to_date != '')
			$csv_head .= "From: ".$from_date." To:".$to_date."\n\n";
			$csv_head .= $columnname;
			$csv_data = $csv_head.$csvdata;
			$filename = $filename.'.csv';
			force_download($filename, trim($csv_data));
			exit;
		}
		function cleanData(&$str)
		{
			$str = preg_replace("/,/", ";", $str);
			$str = preg_replace("/\r?\n/", "       ", $str);
			if (strstr($str, '"'))
			$str = '"'.str_replace('"', '""', $str).'"';
		}
		function execute_query($query)
		{
			if ($query != '')
			{
				$query = $this->db->query($query);
				$error_message = $this->errors->get_error_message($query);
				if ($error_message == 'yes')
				{
					if ($query->num_rows() > 0)
					{
						return $query->result_array();
					}
					else
					return array();
				}
				else
				return $error_message;
			}
		}
		function nltobr($val)
		{
			if ($val != '')
			{
				return nl2br($val);
			}
			return '';
		}
		function getobjectdetails($object_ids, $showip = 1, $skiphtml = 0)
		{
			$object_details = array();
			if ($object_ids != '')
			{
				$where_param = "object_id IN (".$object_ids.")";
				$obj_det = $this->common_model->select_all_where_records($this->tbl_prefix.'object_master', $where_param, '', '');

				if (is_array($obj_det) && count($obj_det) > 0)
				{
					foreach($obj_det as $val)
					{
						$object_details['object_id'][] = $val['object_id'];

						$object_id = $val['object_id'];
						$ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'primary' AND status = 'y'");

						if ($ip == '')
						$ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'management' AND status = 'y'");

						if ($val['add_title'] != '')
						$add_title = $val['add_title'];

						$additional_title = $add_title;
						if ($showip && $ip != '')
						{
							if ($additional_title != '')
							$additional_title = $ip."#".$additional_title;
							else
							$additional_title = $ip;
						}
						$additional_title = $additional_title != '' ? '['.$additional_title.']' : '';
						if ($val['title'] != '')
						{
							if ($skiphtml)
							{
								$obj_title = $val['title'].$additional_title;
							}
							else
							{
								$obj_title = $val['title'].'<br/><font color="red">'.$additional_title.'</font>';
							}
						}
						else
						$obj_title = '--';
						$object_details['obj_title'][] = $obj_title;
					}
				}
			}
			return $object_details;
		}
		function gettimegap($datetime1, $datetime2)
		{
			if ($datetime1 != '' && $datetime2 != '')
			{
				$datetime1 = new DateTime($datetime1);
				$datetime2 = new DateTime($datetime2);
				$interval = $datetime1->diff($datetime2);
				$format = array();
				if ($interval->y !== 0)
				{
					$format[] = "%yY";
				}
				if ($interval->m !== 0)
				{
					$format[] = "%mMon";
				}
				if ($interval->d !== 0)
				{
					$format[] = "%dD";
				}
				if ($interval->h !== 0)
				{
					$format[] = "%hH";
				}
				if ($interval->i !== 0)
				{
					$format[] = "%iM";
				}
				if ($interval->s !== 0)
				{
					$format[] = "%sS";
				}
				if (!count($format))
				{
					return "less than a minute ago";
				}
				return $interval->format(implode(":", $format));
			}
			return 'unknown';
		}
		function get_asset_tree_array($type = '')
		{
			$final_array = array();
			if ($type == 'network')
			{
				$asset_array = array('Network Module');
			}
			else
			{
				$asset_array = array('Chassis', 'HDD', 'Ethernet', 'RAM', 'Processor', 'RAID Controller', 'Power Supply Unit');
			}

			foreach($asset_array as $asset)
			{
				$asset_id = $this->common_model->get_field_value('item_id', $this->tbl_prefix.'inv_items', "item_name = '".$asset."' AND status = 'y'");

				if ($asset_id > 0)
				{
					$final_array[$asset]['id'] = $asset_id;
					$where_param = 'item_id = '.$asset_id.' AND status = "y"';
					$asset_attributs = $this->common_model->select_all_where_records($this->tbl_prefix.'inv_itemconfig', $where_param, '', '');
					if (is_array($asset_attributs) && count($asset_attributs) > 0)
					{
						foreach($asset_attributs as $attribut)
						{
							$final_array[$asset]['info'][$attribut['config_name']] = $attribut['item_config_id'];
						}
					}
				}
			}
			if (count($final_array) > 0)
			{
				return $final_array;
			}
			else
			{
				return array();
			}
		}
		function select_data($params = array())
		{
			if (is_array($params) && count($params) > 0)
			{
				$table_name = $params['table_name'];
				$fields = $params['fields'];
				$where = isset($params['where']) ? $params['where'] : '';
				$order_by = isset($params['order_by']) ? $params['order_by'] : '';
				$group_by = isset($params['group_by']) ? $params['group_by'] : '';
				$offset = isset($params['offset']) ? $params['offset'] : '';
				$limit = isset($params['limit']) ? $params['limit'] : '';
				$sql = isset($params['sql']) ? $params['sql'] : '';
				if ($table_name != '')
				{
					if ($fields != '')
					$this->db->select($fields, FALSE);
					else
					$this->db->select("*", FALSE);
					$this->db->from($table_name, FALSE);
					if ($where != '')
					$this->db->where($where, '', FALSE);
					if ($order_by != '')
					$this->db->order_by($order_by);
					if ($group_by != '')
					$this->db->group_by($group_by);
					if ($limit != '')
					{
						if ($offset != '' && $limit != '')
						$this->db->limit($limit, $offset);
						else
						$this->db->limit($limit);
					}
					$query = $this->db->get();
					if ($sql == 'true')
					echo $this->db->last_query();

					if ($query->num_rows() > 0)
					{
						return $query->result_assoc();
					}
					else
					return array();
				}
				else
				return false;
			}
			else
			return false;
		}
		function access_check($permission_id,$module)
		{
			if($permission_id !='' && $module !='')
			{
				if($this->license_isvalid_module($module,'') && $this->authenticate->checkpermission($permission_id))
				{
					return true;
				}	
				
			}else if($module !='')
			{
				if($this->license_isvalid_module($module,''))
				return true;
			}
			return false;		
		}		
		
		function readfile_to_array($object_id,$param_id,$type)
		{	
			if($type == 'profile')
			$probe_name = $this->common_model->probedetails($object_id, "profile");
			else
			$probe_name = $this->common_model->probedetails($object_id);
			$data_file_path = $this->config->item('data_dir');
			$data_file_path = $data_file_path.$probe_name.'/';
			$mon_data_file = $data_file_path.'service_data/'.$type.'/'.$object_id.'_'.$param_id;
			$return_array = array();
			if(file_exists($mon_data_file))
			{
				$line_array = file($mon_data_file);
				foreach ($line_array as $line)
				{
					$line_parts = explode('#:#',$line);
					$return_array[trim($line_parts[0])] = trim($line_parts[1]);
				}
			}
			return $return_array;
		}
		function devicefilealerts($object_id, $probeid, $value_ids)
		{
			$return_array = array();
			$param_types = array('1' => 'service', '2' => 'performance');
			if ($object_id > 0)	
			{
				$probe_name = $this->common_model->get_field_value('title', $this->tbl_prefix.'monitoring_probes', "probe_id = '".$probeid."'");
				if ($value_ids != '')
				{
					$param_details = $this->common_model->select_all_where_records($this->tbl_prefix."monitoring_param_values pv LEFT JOIN ".$this->tbl_prefix."monitoring_param mp ON mp.monitor_param_id = pv.monitor_param_id AND mp.status = 'y'", "pv.monitor_value_id IN (".$value_ids.") AND pv.status = 'y'", '', '', 'pv.monitor_param_id, mp.monitor_type_id');					
					$paramid = $param_details[0]['monitor_param_id'];
					$paramtype = $param_details[0]['monitor_type_id'];
					$data_file_path = trim($this->config->item('data_dir')).'/'.trim($probe_name).'/service_data/'.trim($param_types[trim($paramtype)]).'/'.trim($object_id).'_'.trim($paramid);	
					
					if (file_exists($data_file_path))
					{
						$line_array = file($data_file_path);
						foreach ($line_array as $line)
						{
							$line_parts = explode('#:#',$line);
							$return_array[trim($line_parts[0])] = trim($line_parts[1]);
						}
					}
				}
			}
			
			return $return_array;
		}
		function readqr()
		{
			$user_id = $this->session->userdata('EMUSERID');
			$filepath = $this->config->item('emagic_source').'emtmp/qrdata/'.$user_id.'_qrscan.txt';
			if(file_exists($filepath))
			{			
				$myfile = fopen($filepath, "r+");
				$filedata = fread($myfile,filesize($filepath));
				$str = explode('##', $filedata);			
				echo $str[1];
				fclose($myfile);
				$deleted = unlink($filepath);	
			}
			else
			{
				echo 'No';	
			}
		}
		function multi_upload($files,$title,$config,$returns = '')
		{
			$status = "";			
			if(is_array($files) && isset($files['name']))
			{	
				$images = array();
				$filenames = array();
				$onlyfilenames = array();
				foreach ($files['name'] as $key => $image) 
				{
					$_FILES['images[]']['name']= $files['name'][$key];
					$_FILES['images[]']['type']= $files['type'][$key];
					$_FILES['images[]']['tmp_name']= $files['tmp_name'][$key];
					$_FILES['images[]']['error']= $files['error'][$key];
					$_FILES['images[]']['size']= $files['size'][$key];
					$fileName = $title .'_'. $image;//$title .strtotime($this->db_datetime).'_'. $image;
					$images[] = $fileName;
					$config['file_name'] = $fileName;
					$this->upload->initialize($config);
					if (!$this->upload->do_upload('images[]')) 
					{
						if(count($filenames) > 0)
						{
							foreach($filenames as $filenm)
							{
								unlink($filenm);
							}
						}
						$status = "FALSE";
					} 
					else 
					{
						$uploaded_data = $this->upload->data();
						$file_name = $uploaded_data['file_name'];
						$filenames[$key] = $config['upload_path'].$file_name;
						$onlyfilenames[$key]=$file_name;
					}				  
				}
				if($status == "FALSE")
				return $status;
				else
				{
					if($returns == '')
					return $filenames;
					else
					return $onlyfilenames;
				}
			}
			else
			{
				return $status;
			}
			return $status;
		}
		function executequery($sqlquery)
		{
			$query = $this->db->query($sqlquery);
		}
		function savereport($options)
		{
			extract($options);
			// Add report to database
			$insert_array = array('report_name' => $reportname,'report_type' => $reporttype,'report_category' => $reportcategory, 'report_title' => $reporttitle,'module' => $module, 'filter_value' => $filtervalue,'user_id' => $userid, 'schedule_type' => $schedtype,'gen_report_at' => $genreportat,'gen_report_for' => $genreportfor,'report_format' => $reportformat,'email_to' => $emailids, 'email_subject' => $emailsubject,'email_body' => $emailbody,'next_report_time' => $nextreporttime, 'date' => date('Y-m-d G:i:s'), "enableschedule"=> $enableschedule);			
			$data = $this->common_model->insert_records($this->tbl_prefix . 'reports', $insert_array);		
			if ($data > 0 && is_numeric($data))
			{
				return $data;
			}
			else
			{
				return 0;
			}
		}
		function updatereport($options)
		{
			extract($options);
			// Add report to database
			$update_array = array('report_name' => $reportname,'report_type' => $reporttype,'report_category' => $reportcategory, 'report_title' => $reporttitle,'module' => $module, 'filter_value' => $filtervalue,'user_id' => $userid, 'schedule_type' => $schedtype,'gen_report_at' => $genreportat,'gen_report_for' => $genreportfor,'report_format' => $reportformat,'email_to' => $emailids, 'email_subject' => $emailsubject,'email_body' => $emailbody,'next_report_time' => $nextreporttime, 'date' => date('Y-m-d G:i:s'),'enableschedule' => $enableschedule);
			$where = "report_id = ".$this->db->escape($reportid)."";
			$data = $this->common_model->update_records($this->tbl_prefix . 'reports',$where, $update_array);
			
			if ($data > 0 && is_numeric($data))
			{
				return $data;
			}
			else
			{
				return 0;
			}
		}
		
		function checkreportexists($reportname,$subwhere="")
		{
			$where= "report_title = ".$this->db->escape($reportname)." AND status='y'";
			if($subwhere != '')
			{
				$where .= $subwhere;
			}
			$total_rows = $this->common_model->select_all_count($this->tbl_prefix . 'reports', $where);
			if($total_rows > 0)
			return true;
			else
			return false;
		}	
		function addfavourite($reportid,$status)
		{
			if($status != '')
			$status = $this->common_model->get_field_value('status', $this->tbl_prefix.'favourite_reports', "report_id= ".$this->db->escape($reportid)." AND user_id = ".$this->db->escape($this->session->userdata('EMUSERID'))."");
			if($status == 'y')
			{
				$where =  "report_id = ".$this->db->escape($reportid)." AND user_id = '".$this->session->userdata('EMUSERID')."'";
				$input_array = array("status" => "n");
				$upd = $this->common_model->update_records($this->tbl_prefix.'favourite_reports', $where, $input_array);	
			}
			else if($status == 'n')
			{
				$where =  "report_id = ".$this->db->escape($reportid)." AND user_id = '".$this->session->userdata('EMUSERID')."'";
				$input_array = array("status" => "y");
				$upd = $this->common_model->update_records($this->tbl_prefix.'favourite_reports', $where, $input_array);				
			}
			else
			{
				$input_array = array("status" => "y","report_id" => $reportid, "user_id" => $this->session->userdata('EMUSERID'));
				$ins = $this->common_model->insert_records($this->tbl_prefix.'favourite_reports', $input_array);	
			}
		}
		function sharereport($reportid,$status)
		{
			if($status != '')
			$status = $this->common_model->get_field_value('share_report', $this->tbl_prefix.'reports', "report_id= ".$this->db->escape($reportid)." AND user_id = ".$this->db->escape($this->session->userdata('EMUSERID'))."");
			if($status == 'y')
			{
				$where = "report_id = ".$this->db->escape($reportid)." AND user_id = '".$this->session->userdata('EMUSERID')."'";
				$input_array = array("share_report" => "n");
				$upd = $this->common_model->update_records($this->tbl_prefix.'reports', $where, $input_array);	
			}
			else if($status == 'n')
			{
				$where = "report_id = ".$this->db->escape($reportid)." AND user_id = '".$this->session->userdata('EMUSERID')."'";
				$input_array = array("share_report" => "y");
				$upd = $this->common_model->update_records($this->tbl_prefix.'reports', $where, $input_array);
			}
		}
		function removereport($reportid)
		{
			$where = "report_id = ".$this->db->escape($reportid)." AND user_id = '".$this->session->userdata('EMUSERID')."'";
			$input_array = array("status" => "n");
			$upd = $this->common_model->update_records($this->tbl_prefix.'reports', $where, $input_array);	
			if(is_numeric($upd) && $upd > 0)
			{
				$where = 'status = "y" AND report_name = "siem_alert_report" AND report_id = '.$this->db->escape($reportid).'';
				$params = array('table_name' => $this->tbl_prefix.'reports','fields' => '*','where' => $where);
				$reportdetails = $this->common_model->select_data($params);
				$val = $reportdetails[0];
				$report_id = $val['report_id'];
				$report_title = str_replace(array(" ",",","."),array("_","",""),$val['report_title']);
				$reportpath = $this->config->item('public_path')."/uploads/schedulereports/";
				$filename = $reportpath.strtolower($report_title)."_".$report_id;
				if ($val['report_format'] == 'pdf')
				{	
					if (file_exists($filename.".pdf.tar.gz"))		
					{		
						unlink($filename.".pdf.tar.gz");
					}					
				}
				elseif ($val['report_format'] == 'csv')
				{
					if (file_exists($filename.".csv.tar.gz"))
					{
						unlink($filename.".csv.tar.gz");
					}
				}
				echo "[+] Report deleted successfully.";
			}	
			else
			echo "[!] Report is not deleted.";
		}
		function removecompliance($complianceid)
		{
			$where =  "compliance_id = ".$this->db->escape($complianceid)."";
			$input_array = array("status" => "n");
			$upd = $this->common_model->update_records($this->tbl_prefix.'compliance', $where, $input_array);	
			if(is_numeric($upd) && $upd > 0)
			{
				echo "[+] Compliance deleted successfully.";
			}	
			else
			echo "[!] Compliance is not deleted.";
		}
		function getcompliancedetails($complianceid)
		{
			$data = array();
			if($complianceid > 0)
			{
				$where = 'compliance_id = '.$this->db->escape($complianceid).'';
				$params = array('table_name' => $this->tbl_prefix.'compliance','fields' => '*','where' => $where);
				$compliancedetails = $this->common_model->select_data($params);
				$data['compliancedetails'] = $compliancedetails ;
				if(count($compliancedetails) < 1)
				{
					echo 'Invalid Access';		die();
				}
				$where = 'group_id IN ('.$compliancedetails[0]['groups'].')';
				$params = array('table_name' => $this->tbl_prefix.'compliance_group','fields' => '*','where' => $where);
				$compli_group_det = $this->common_model->select_data($params);	
				$data['compli_group_det'] = $compli_group_det;
			}
			else
			{
				echo 'Invalid Access';		
				die();
			}
			return $data;
		}
		function check_agent($ip,$install='')
		{
			$where = 'status = "y" AND `agent_type`="agentbased" AND agent_ip = "'.$ip.'"';
			if($install == 'yes')
				$where .= ' AND installed = "y"';
				
			$params = array('table_name' => $this->tbl_prefix.'logmon_agents', 'fields' => 'agent_id', 'where' => $where);
			$agentid = $this->common_model->select_data($params);
			if(is_array($agentid) && count($agentid) > 0)
				return true;
			else
				return false;
		}
		function getipaddressbyobjid($object_id)
		{	
			$ip_address = "";
			$ip_address = $this->get_field_value('ip', $this->tbl_prefix.'object_ips', "status = 'y' AND type = 'primary' AND object_id = '".$object_id."'");
			if($ip_address == '')
			$ip_address = $this->get_field_value('ip', $this->tbl_prefix.'object_ips', "status = 'y' AND type = 'management' AND object_id = '".$object_id."'");
			
			return $ip_address;
		}
		function getScriptName($val)
		{
			$payload_len = 8192;
			$hostname = $val['agent_ip'];
			if($hostname != '')
			{
				if($val['os_type'] == $this->os_types['windows'])
					$cmd = $this->config->item('emagic_source')."/emsca/bin/check_nrpe -H '".$hostname."' -2 -P ".$payload_len." ";
				else if($val['os_type'] == $this->os_types['ssh'])
					$cmd = $this->config->item('emagic_source')."/emsca/bin/check_emrpe -H '".$hostname."'";
			}
			
			return $cmd;
		}
		function checkagentservice($val,$checknupdate="n")
		{
			$payload_len = 8192;
			$hostname = $val['agent_ip'];
			$emrpe=$val['emrpe'];
			$agent_id=$val['agent_id'];		
			$output = '';
			$chkservice = array();
			$chkservice['emsca'] = 0;
			$chkservice['emrpe'] = 0;
			if($checknupdate == 'y' && $hostname != '' && $agent_id != '')
			{
				$cmd = $this->getScriptName($val);
				$output = $this->logmonitor_model->buildExecCmd($cmd,true);
				if (strpos($output, "seem to be doing fine") !== false || strpos($output, "EMRPE") !== false) 
				{
					$update_array = array('emrpe' => 'y');
					$where_array = array('agent_id' => $agent_id);
					$this->common_model->update_records($this->tbl_prefix . 'logmon_agents', $where_array, $update_array);	
				}	
			}
			if($hostname != '')
			{
				/*
				$filename = $this->config->item('emagic_source')."/emsca/var/spool/active".$hostname;
				$cmd = "find ".$filename." -cmin -30";
				$output = $this->logmonitor_model->buildExecCmd($cmd,true);
				$output = trim($output);
				if($emrpe == 'y')
				{
					$confstatus = $this->logmonitor_model->controlagent(array(0 => $val),"status","emsca",0);
				}	
				else	
				$confstatus = "RUNNING";
				if($output == $filename && $confstatus == "RUNNING")
				$chkservice['emsca'] = 1;
				if($emrpe == '' && $agent_id != '')
				{
					$emrpe = $this->common_model->get_field_value('emrpe', $this->tbl_prefix.'logmon_agents', "agent_id = '".$agent_id."'");
					if($emrpe == 'y')
					$chkservice['emrpe'] = 1;
				}
				else if($emrpe == 'y')
				$chkservice['emrpe'] = 1;*/
				$chkservice['emrpe'] = $val['emrpe'] == "n" ? false : true;
				$chkservice['emsca'] = $val['emsca'] == "n" ? false : true;
			}	
			return $chkservice;
		}
		function monitoring_disk($object_id, $param_id, $disk_mon_method, $type)
		{	
			$disk_str = '';
			$emagic_source = $this->config->item('emagic_source');
			$perl_command = $this->config->item('perl_command');
			$php_command = $this->config->item('exe_php');
			$return_response ='';
			$probe_id = $this->common_model->get_field_value('monitor_probe_id', $this->tbl_prefix.'object_master', "object_id = '".$object_id."'");
			if($disk_mon_method == "ACTIVEAGENT" || $disk_mon_method == "PASSIVEAGENT" )
			{
				$prim_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'primary' AND status = 'y'");
				if ($prim_ip != "")
				$ip = $prim_ip;
				else
				{
					$mgt_ip = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "object_id = '".$object_id."' AND type = 'management' AND status = 'y'");
					$ip = $mgt_ip;
				}	
				if($ip != '')
				{
					$where = 'agent_ip = "'.$ip.'" AND status="y"';
					$agentdetails = $this->common_model->select_all_where_records($this->tbl_prefix . 'logmon_agents', $where, '', '');
					if(is_array($agentdetails) && count($agentdetails) > 0)
					{
						$val = $agentdetails[0];
						$chkservice['emrpe'] = $val['emrpe'] == 'y' ? 1 : 0;
						$chkservice['emsca'] = $val['emsca'] == 'y' ? 1 : 0;
						if(is_array($chkservice))
						{
							$return_response = '';
							if(!$chkservice['emrpe'] && $disk_mon_method == "ACTIVEAGENT")
							$return_response = "Active";
							if(!$chkservice['emsca'] && $disk_mon_method == "PASSIVEAGENT")
							$return_response .= $return_response != '' ? "/Passive" : "Passive";
							$return_response .= $return_response != '' ? " Monitoring Service is not running. Please check the service and try again" : "";	
							if($return_response == '')
							{
								$cmd = $php_command.' '.$emagic_source.'cr/monitor_disk.php '.$object_id.' disk '.$param_id.' '.$disk_mon_method.' '.$type;
								
								$result = $this->buildExecCmd($cmd,true,$probe_id);
								$result = 'Disk Monitoring started successfully';
								if (strpos($result, "Disk Monitoring started successfully") === FALSE)
								$return_response = "Fail to start disk monitoring.";      	
								else
								{
									
									$cmd = $php_command.' '.$emagic_source.'cr/monitor_disk.php '.$object_id.' devicemon';
									$result = $this->buildExecCmd($cmd,true,$probe_id);
								}
							} 
						}
						else
						{
							$return_response = "Please confirm the eMagic agent is installed on this device.";
						}
					}
					else
					{
						$return_response = "Primary/Management IP is not assinged to this device. Please assign IP before to start monitoring.";
					}
				}
				else
				{
					$return_response = "Primary/Management IP is not assinged to this device. Please assign IP before to start monitoring.";
				}
			}
			return $return_response ;
		}
		
		function getrrdfile_new($object_id,$height,$width,$from_time,$to_time,$mon_param_val,$auto_scale,$app_host_entityname="",$upload="rettype")
		{	
			$graph_width = (int) $height > 0 ? (int) $height : 350;
			$graph_height = (int) $width > 0 ? (int) $width : 100;
			$from_time = $from_time > 0 ? $from_time : (time() - (60 * 60 * 4));
			$to_time = $to_time > 0 ? $to_time : time();
			$img_name = "";
			
			if($upload == 'rettype')
			{
				$graphimage_path = $this->config->item('public_path').'uploads/appmon/live_image_'.$mon_param_val.'.png';
				if (file_exists($graphimage_path))
					unlink($graphimage_path);
				$img_name = $this->config->item('inc_url').'uploads/appmon/live_image_'.$mon_param_val.'.png';
				$final['b_end'] = $graphimage_path;
				$final['f_end'] = $img_name;				
			}
			else
			$graphimage_path = "-";
			
			if($auto_scale == 'y')
			{
				$auto_scale = "%s";
				$au_unit = "";
			}
			else
			{
				$auto_scale = "";
				$au_unit = "--units-exponent 0";
			}
			
			$mon_param_val_details = $this->common_model->select_all_where_records($this->tbl_prefix.'mon_param_values', "mon_param_val_id = '".$mon_param_val."'", '', '');

			$monitor_name = $this->common_model->get_field_value('monitor_name', $this->tbl_prefix.'monitor', "monitor_id = '".$mon_param_val_details[0]['monitor_id']."'");
			$type = $mon_param_val_details[0]['mon_item'];

			if ($type == 'app')
			{
				
				$probe_id = $this->common_model->get_field_value('mon_probe', $this->tbl_prefix.'app_host', "app_host_id = '".$object_id."'");
				if($probe_id == '')
					$probe_id = 1;
			}
			else
			$probe_id = $this->common_model->get_field_value('monitor_probe_id', $this->tbl_prefix.'object_master', "object_id = '".$object_id."'");

			$probe_name = $this->common_model->get_field_value('title', $this->tbl_prefix.'monitoring_probes', "probe_id = '".$probe_id."'");
			$rrdPath = $this->config->item('data_dir');
			$rrdPath = $rrdPath.$probe_name.'/';

			$file_name = $mon_param_val_details[0]['mon_item_id'].'_'.$mon_param_val_details[0]['monitor_id'].'.rrd';

			if ($type == 'disk')
			$rrd_file = "$rrdPath"."rrd_storage_disks/".$file_name;
			elseif ($type == 'pool')
			$rrd_file = "$rrdPath"."rrd_storage_pools/".$file_name;
			elseif ($type == 'volume')
			$rrd_file = "$rrdPath"."rrd_storage_vols/".$file_name;
			elseif ($type == 'lun')
			$rrd_file = "$rrdPath"."rrd_storage_luns/".$file_name;
			elseif ($type == 'device')
			$rrd_file = "$rrdPath"."rrd_performance/".$file_name;
			elseif ($type == 'app')
			{	// newcode
				$param_master_details = $this->common_model->select_all_where_records($this->tbl_prefix.'monitor', "monitor_id = '".$mon_param_val_details[0]['monitor_id']."'", '', '','monitor_categ,dynamic_data,monitor_var_name');
				
				$param_master_details = isset($param_master_details[0]) ? $param_master_details[0] : array();
			   	$monitor_categ = $param_master_details['monitor_categ'];			
				$dynamic_data = $param_master_details['dynamic_data'];			
				$monitor_var_name = $param_master_details['monitor_var_name'];	
				//***********
				if(strtolower($monitor_categ) == 'tablespace' || strtolower($monitor_categ) == 'database')
				{	
					$host_entity_id = $this->common_model->get_field_value('host_entity_id', $this->tbl_prefix.'app_host_entity', "name = '".trim($app_host_entityname)."' AND entity_type IN ('tablespace','database')");	
					
					$file_name = $mon_param_val_details[0]['mon_item_id'].'_'.$mon_param_val_details[0]['monitor_id'].'_'.$host_entity_id.'.rrd';
					$rrd_file = "$rrdPath"."rrd_app/app_host_entities/".$file_name;
				}
				else if($dynamic_data == "y")
				{
					$host_entity_id = $app_host_entityname;			
					$file_name = $mon_param_val_details[0]['mon_item_id'].'_'.$mon_param_val_details[0]['monitor_id'].'_'.$host_entity_id.'.rrd';
					$rrd_file = "$rrdPath"."rrd_app/".$file_name;
				}
				else
				$rrd_file = "$rrdPath"."rrd_app/".$file_name;
			}
			
			$commands = $mon_param_val_details[0]['commands'];
			$mon_param_cmd_master = array();
			if(isJSON($commands))
			{
				$param_properties = json_decode($commands,true);	
				$mon_param_cmd_master = $this->common_model->select_all_where_records($this->tbl_prefix.'mon_param_cmd cmd ', "cmd.status = 'y' AND cmd.plot_graph = 'y' AND cmd.monitor_id = ".$mon_param_val_details[0]['monitor_id'], '', '','mon_cmd_id,mon_var_id');
				$mon_param_cmd_master = keytoarray($mon_param_cmd_master,"mon_cmd_id");
				$frm_dynamic = true;
			}
			else
			{
				$frm_dynamic = false;
				 $param_properties = $this->common_model->select_all_where_records($this->tbl_prefix.'mon_param_th_values th_v LEFT JOIN '.$this->tbl_prefix.'mon_param_cmd cmd ON th_v.mon_cmd_id = cmd.mon_cmd_id', "th_v.mon_param_val_id = '".$mon_param_val."' AND th_v.status = 'y' AND cmd.status = 'y' AND cmd.plot_graph = 'y'", '', '');
			}
            
			$variable_master = $this->common_model->select_all_where_records($this->tbl_prefix.'mon_variable','status = "y"', '', '');
			$variable_data = keytoarray($variable_master,"mon_var_id");
			foreach($param_properties as $key => $row)
            { 
				if($frm_dynamic)
					$mon_var_id = $mon_param_cmd_master[$row['mon_cmd_id']]['mon_var_id'];
				else
					$mon_var_id = $row['mon_var_id'];
                $param_properties[$key]['vertical_label'] = $variable_data[$mon_var_id]['vertical_label'];
                $param_properties[$key]['comment_label'] = $variable_data[$mon_var_id]['comment_label'];
                $param_properties[$key]['alert_unit'] = $variable_data[$mon_var_id]['alert_unit'];
                $param_properties[$key]['graph_color'] = $variable_data[$mon_var_id]['graph_color'];
                $param_properties[$key]['graph_type'] = $variable_data[$mon_var_id]['graph_type'];
                $param_properties[$key]['name'] = $variable_data[$mon_var_id]['name'];
            }
			
			
			/*$param_properties = $this->common_model->select_all_where_records($this->tbl_prefix.'mon_param_th_values th_v LEFT JOIN '.$this->tbl_prefix.'mon_param_cmd cmd ON th_v.mon_cmd_id = cmd.mon_cmd_id', "th_v.mon_param_val_id = '".$mon_param_val."' AND th_v.status = 'y' AND cmd.status = 'y' AND cmd.plot_graph = 'y'", '', '');
			if (is_array($param_properties) && count($param_properties) > 0)
			{
				foreach($param_properties as $key => $row)
				{
					$variable_data = $this->common_model->select_all_where_records($this->tbl_prefix.'mon_variable', 'mon_var_id = "'.$row['mon_var_id'].'" AND status = "y"', '', '');
					$param_properties[$key]['vertical_label'] = $variable_data[0]['vertical_label'];
					$param_properties[$key]['comment_label'] = $variable_data[0]['comment_label'];
					$param_properties[$key]['alert_unit'] = $variable_data[0]['alert_unit'];
					$param_properties[$key]['graph_color'] = $variable_data[0]['graph_color'];
					$param_properties[$key]['graph_type'] = $variable_data[0]['graph_type'];
					$param_properties[$key]['name'] = $variable_data[0]['name'];
				}
			}*/
			
			$rrdcommand = "/usr/bin/rrdtool graph $graphimage_path --imgformat PNG --lazy  --font DEFAULT:7: --height=$graph_height --width=$graph_width  --lower-limit 0 $au_unit   --alt-autoscale-max  --slope-mode --vertical-label='".$param_properties[0]['vertical_label']."'";

			foreach($param_properties as $key => $data)
			{
				$rrdcommand .= " DEF:var".$key."=$rrd_file:".$data['name'].":AVERAGE";
			}
			foreach($param_properties as $key => $data)
			{
				$rrdcommand .= " CDEF:var1".$key."=var".$key.",1,/";
			}
			foreach($param_properties as $key => $data)
			{
				$rrdcommand .= " ".$data['graph_type'].":var1".$key."".$data['graph_color'].":'".$data['comment_label']."' COMMENT:'Curr\:' GPRINT:var1".$key.":LAST:'%6.2lf $auto_scale".$data['alert_unit']."' COMMENT:'Avg\:' GPRINT:var1".$key.":AVERAGE:'%6.2lf $auto_scale".$data['alert_unit']."' COMMENT:'Max\:' GPRINT:var1".$key.":MAX:'%6.2lf $auto_scale".$data['alert_unit']."\l' ";
			}
			$time_diff = 0;
			$time_diff = $to_time - $from_time;
			$xscale_grid = "";
			if ($time_diff > 0 && $time_diff <= 600)
			{
				$xscale_grid = "--x-grid SECOND:20:MINUTE:1:MINUTE:1:0:%d%b/%H:%M";
			}
			else if ($time_diff > 600 && $time_diff <= 3600)
			{
				$xscale_grid = "--x-grid MINUTE:1:MINUTE:10:MINUTE:10:0:%d%b/%H:%M";
			}
			else if ($time_diff > 3600 && $time_diff <= 14400)
			{
				$xscale_grid = "--x-grid MINUTE:10:MINUTE:40:MINUTE:40:0:%d%b/%H:%M";
			}
			elseif ($time_diff > 14400 && $time_diff <= 86440)
			{
				$xscale_grid = "--x-grid HOUR:1:HOUR:6:HOUR:6:0:%d%b/%H:%M";
			}
			elseif ($time_diff > 86440 && $time_diff <= 604800)
			{
				$xscale_grid = "--x-grid HOUR:12:DAY:2:DAY:2:0:%d%b/%H:%M ";
			}
			elseif ($time_diff > 604800 && $time_diff <= 2592000)
			{
				$xscale_grid = "--x-grid DAY:1:DAY:7:DAY:7:0:%d%b/%H:%M";
			}
			else
			$xscale_grid = "";
			$from_date_time = date("d/M/Y G:i", $from_time);
			$to_date_time = date("d/M/Y G:i", $to_time);
			$period = ($from_time - $to_time);
			if ($period == (60 * 60 * 4))
			$rrdcommand .= " --end now --start end-4h --title 'Last 4 Hrs' ";
			else if ($period == (60 * 60 * 1))
			$rrdcommand .= " --end now --start end-1h  --title 'Last 1 Hrs' ";
			else if ($period == (60 * 10))
			$rrdcommand .= " --end now --start end-10m  --title 'Last 10 Min.' ";
			else if ($period == (60 * 30))
			$rrdcommand .= " --end now --start end-30m  --title 'Last 30 Min.' ";
			else if ($period == (60 * 60 * 24))
			$rrdcommand .= " --end now --start end-24h  --title 'Last 24 Hrs'";
			/* Last week */
			else if ($period == (60 * 60 * 24 * 7))
			$rrdcommand .= " --end now --start end-7d  --x-grid DAY:1:DAY:7:DAY:2:86400:%d%b/%H:%M  --title 'Last Week'";
			/* Last month */
			else if ($period == (60 * 60 * 24 * 30))
			$rrdcommand .= " --end now --start end-30d --x-grid DAY:1:WEEK:1:WEEK:1:604800:%d%b/%H:%M  --title 'Last Month'";
			/* Last 6 month */
			else if ($period == (60 * 60 * 24 * 30 * 6))
			$rrdcommand .= " --end now --start end-180d --title 'Last 6 Month'";
			else if ($period == (60 * 1))
			$rrdcommand .= " --end now --start end-1min --title 'Last 1 Min' ";
			else
			$rrdcommand .= " --end $to_time --start $from_time $xscale_grid --title 'From: $from_date_time  To: $to_date_time'";
			$this->common_model->exec_command($rrdcommand);
			return $final;
		}	
		function rrdtest()
		{	
			$rrd_bw_file = "/home/emag/emsrc/data_files/Main/rrd_bw/2061_11.rrd";
			$graph_height = 150;
			$graph_width = 200;
			$brown = "990033";
			$blue = "0000FF";
			$rrdcommand = "rrdtool graph  /home/emag/public_html/emagic_app/latency_graph1.png ".
			"--imgformat PNG ".
			"--font DEFAULT:7: ".
			"--lazy --height=$graph_height --width=$graph_width ".
			"--alt-autoscale-max ".
			"--lower-limit=0  	--slope-mode --vertical-label='Transfer Rate (bps)' ".
			" DEF:IN=$rrd_bw_file:bw_input:AVERAGE: ".
			" DEF:OUT=$rrd_bw_file:bw_output:AVERAGE: ".
			" VDEF:totin=IN,TOTAL ".
			" VDEF:totout=OUT,TOTAL ".
			" COMMENT:'       Curr               Avg         Max\l' ".
			" CDEF:CINBASE=IN,8,* ".
			" CDEF:CIN=CINBASE,UN,0,CINBASE,IF ".
			//" CDEF:innormal=CIN,$alertlimit,LT,CIN,$alertlimit,IF ".
			//" CDEF:inhigh=CIN,$alertlimit,GT,CIN,0,IF ".
			//" AREA:inhigh#FF0000".
			" CDEF:innormal=CIN".
			" AREA:innormal#00CF00:'In ' ".
			" CDEF:COUT=OUT,8,* ".
			" GPRINT:CIN:LAST:'%2.2lf %sb/s' ".
			" GPRINT:CIN:AVERAGE:'%2.2lf %sb/s' ".
			" GPRINT:CIN:MAX:'%2.2lf %sb/s\l' ".
			" LINE2:COUT#$blue:'Out' ".
			" GPRINT:COUT:LAST:'%2.2lf %sb/s' ".
			" GPRINT:COUT:AVERAGE:'%2.2lf %sb/s' ".
			" GPRINT:COUT:MAX:'%2.2lf %sb/s\l' ";
			// " HRULE:300000#FF0000:'Alert Limit\l'";
			$rrdcommand .= "--end now --start end-1h  --title 'Bandwidth :For Last 1 hr'";
			$this->common_model->exec_command($rrdcommand);
			return "latency_graph1.png";
			exit;
			$cmd = "cp /home/emag/rrdtest/latency_graph1.png /home/emag/rrdtest/";
			$this->common_model->exec_command($cmd);
		}
		function buildExecCmd($cmd,$isSSH=false,$probe_id='',$filter=array())
		{
			$skipquote = isset($filter['skipquote']) ?  $filter['skipquote'] : true;
			$operation = isset($filter['operation']) ?  $filter['operation'] : '';
			$source_file = isset($filter['source_file']) ?  $filter['source_file'] : '';
			$dest_file = isset($filter['dest_file']) ?  $filter['dest_file'] : '';
			
			$emagic_source = $this->config->item('emagic_source');
			$perl_command = $this->config->item('perl_command');
			$php_command = $this->config->item('exe_php');
			if($isSSH && $probe_id != '')
			{
				$host_ip = $this->config->item('host_ip');
				$probe_details = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_probes',"probe_id = '".$probe_id."'",'','','object_id,title,key_login,ip,key_path');				
				if(!is_array($probe_details) && count($probe_details) < 1)
				{
					$return_response = "Probe details are not found. Please confirm the probe is assigned to this device";
					return $return_response ;
				}
				else
				{
					$probe_ip = $probe_details[0]['ip'];
					$key_login = $probe_details[0]['key_login'];
					$object_id = $probe_details[0]['object_id'];
					$key_path = $probe_details[0]['key_path'];
					$ostype = 'ssh';
					$usekey = "";
					if($key_login == 'y')
						$usekey = "-sshkey '".$key_path."'";
					
						
					if($host_ip != $probe_ip)
					{	
						$det = $this->objectcredentials_all($object_id);
						if($skipquote)
						{
							$cmd = str_replace("'",'"',$cmd);
						}							
						
						$cmd = $cmd;
						
						$cmd_ = $this->config->item('perl_command')." ".$this->config->item('emagic_source')."emlib/eMagic/remote_execution.pl -host '".$probe_ip."' -user '".$det[$ostype]['username']."' -passwd '".$this->common_model->decrypt($det[$ostype]['pass'])."' -command '".$cmd."' -port '".$det[$ostype]['port']."' ".$usekey." -operation '".$operation."' -dest_file '".$dest_file."'  -source_file '".$source_file."' ";
						$cmd = $cmd_;
					}
                    else
					{
						if($operation == "scp")
						{
							$cmd = "cp -f ".$source_file." ".$dest_file;
						}			
					}			
				}
			}	
			else
			{
				if($operation == "scp" && $source_file != '' )
				{
				  $cmd = "cp -f ".$source_file." ".$dest_file;
				}
			}
			$output = $this->common_model->exec_command($cmd);
			$findme   = "Couldn't establish SSH connection";
			$SSHsuccess = strpos($output, $findme);
			if ($SSHsuccess !== false) 
			{
				$output = "<font color='red'>[!] Couldn't establish SSH connection</font>";
			}
			return $output;
		}
		function objectcredentials_all($object_id)
		{			
			if ($object_id > 0)
			{
				$return_array = array();				
				$ip_address = $this->common_model->get_field_value('ip', $this->tbl_prefix.'object_ips', "status = 'y' AND type IN ('primary','management') AND object_id = '".$object_id."'");
				$return_array['ip_address'] = $ip_address;				
				#get SNMP Details
				$credentail_details_snmp = $this->common_model->select_all_where_records($this->tbl_prefix."object_credentials oc,".$this->tbl_prefix."object_credentials_snmp sm", "oc.credential_type_id = '1' AND oc.status = 'y' AND oc.object_id = '".$object_id."' AND oc.credential_detail_id = sm.credential_detail_id", '', '','username,auth_pass as pass,snmp_port as port');				
				if (is_array($credentail_details_snmp) && count($credentail_details_snmp) > 0)
				{
					$return_array['snmp'] = $credentail_details_snmp[0];
				}				
				# get WMI details
				$credentail_details_wmi = $this->common_model->select_all_where_records($this->tbl_prefix."object_credentials oc,".$this->tbl_prefix."object_credentials_windows sm", "oc.credential_type_id = '2' AND oc.status = 'y' AND oc.object_id = '".$object_id."' AND oc.credential_detail_id = sm.credential_detail_id", '', '','username,pass,port');
				
				if (is_array($credentail_details_wmi) && count($credentail_details_wmi) > 0)
				{
					$return_array['windows'] = $credentail_details_wmi[0];
				}				
				# Get SSH Details
				$credentail_details_ssh = $this->common_model->select_all_where_records($this->tbl_prefix."object_credentials oc,".$this->tbl_prefix."object_credentials_sshtelnet sm", "oc.credential_type_id = '3' AND oc.status='y' AND oc.object_id = '".$object_id."' AND oc.credential_detail_id = sm.credential_detail_id", '', '','username,pass,port');				
				if (is_array($credentail_details_ssh) && count($credentail_details_ssh) > 0)
				{
					$return_array['ssh'] = $credentail_details_ssh[0];
				}
				return $return_array;
			}
		}
		function getbw_rrd_image($interface_id, $graph_width, $graph_height, $from_time, $to_time,$period = 0)
        {
            $graphimage_path = $this->config->item('public_path').'uploads/appmon/bw_'.$interface_id.'.png';
            if(file_exists($graphimage_path))
            unlink($graphimage_path);
            $img_name = $this->config->item('inc_url').'uploads/appmon/bw_'.$interface_id.'.png';
            $final['b_end'] = $graphimage_path;
            $final['f_end'] = $img_name;

            $interface_details =$this->common_model->select_all_where_records($this->tbl_prefix.'object_ports',
"port_id = '".$interface_id."'", '', '');
            $object_id = $interface_details[0]['object_id'];
            $interface = $interface_details[0]['interface'];
            $in_val = "bw_input";
            $out_val = "bw_output";
            $graph_width = $graph_width > 0 ? $graph_width : 350;
            $graph_height = $graph_height > 0 ? $graph_height : 120;
            $from_time = $from_time > 0 ? $from_time : time();
            $to_time = $to_time > 0 ? $to_time : (time() - (60 * 60 * 1));

            $obj_prop_id =$this->common_model->select_all_where_records($this->tbl_prefix."monitoring_param_properties
pp LEFT JOIN ".$this->tbl_prefix."monitoring_param mp ON pp.monitor_param_id = mp.monitor_param_id", "mp.param = 'Interface-BW' AND mp.status = 'y'
AND pp.field_type = 'fetch_method' AND pp.status = 'y'", '', '', "pp.monitor_property_id, pp.def_threshold_unit, pp.def_threshold_value");

            if ($obj_prop_id[0]['def_threshold_unit'] == 'MBps')
            {
                $change_factor = 1000 * 1000 * 8;
            }
            elseif ($obj_prop_id[0]['def_threshold_unit'] == 'GBps')
            {
                $change_factor = 1000 * 1000 * 1000 * 8;
            }
            elseif ($obj_prop_id[0]['def_threshold_unit'] == 'KBps')
            {
                $change_factor = 1000 * 8;
            }
            elseif ($obj_prop_id[0]['def_threshold_unit'] == 'Kbps')
            {
                $change_factor = 1000;
            }
            elseif ($obj_prop_id[0]['def_threshold_unit'] == 'Mbps')
            {
                $change_factor = 1000 * 1000;
            }
            elseif ($obj_prop_id[0]['def_threshold_unit'] == 'Gbps')
            {
                $change_factor = 1000 * 1000 * 1000;
            }
            else
            {
                $change_factor = 8;
            }
            $alert_details =
$this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_bw_param_values',
"monitor_property_id = '".$obj_prop_id[0]['monitor_property_id']."' AND interface_id = '".$interface_id."'", '', '');
            if ($alert_details[0]['threshold_value'] != "" && $alert_details[0]['threshold_value'] != "0")
            {
                if ($alert_details[0]['threshold_operator'] == '<>')
                {
                    $alert_value = explode(',', $alert_details[0]['threshold_value']);
                    $alert_val = $alert_value[1];

                    $alertlt = $alert_value[0] * $change_factor;
                    $rrd_cmd = " 
CDEF:innormal1=CIN,$alertlt,GT,CIN,$alertlt,IF ".
                    " CDEF:inhigh1=CIN,$alertlt,LT,CIN,0,IF ".
                    " AREA:inhigh1#FF0000";
                    " HRULE:$alertlt#FF0000 ";
                }
                else
                {
                    $alert_val = $alert_details[0]['threshold_value'];
                    $rrd_cmd = "";
                }
                $alertlimit = $alert_val * $change_factor;
                $alert = $alert_details[0]['threshold_value'];
            }
            else
            {
                $alertlimit = $obj_prop_id[0]['def_threshold_value'] * $change_factor;
                $alert = $obj_prop_id[0]['def_threshold_value'];

                if ($obj_prop_id[0]['def_threshold_value'] == 0 || $obj_prop_id[0]['def_threshold_value'] == "")
                {
                    $alertlimit = 200 * $change_factor;
                    $alert = 200;
                }
            }
            $unit = $obj_prop_id[0]['def_threshold_unit'];
            $file_name = $object_id.'_'.$interface.'.rrd';
            $probe_name = $this->common_model->probedetails($object_id);
            $rrdPath = $this->config->item('data_dir');
            $rrdPath = $rrdPath.$probe_name.'/';
            $rrd_bw_file = "$rrdPath"."rrd_bw/".$file_name;
			if(!file_exists($rrd_bw_file))
			{
				die('file not available');
			}
            $brown = "990033";
            $blue = "0000FF";
            $yellow = "F3B227";
            $red = "#E84415";
            $rrdcommand = "/usr/bin/rrdtool graph $graphimage_path ".
            "--imgformat PNG ".
            "--font DEFAULT:7: ".
            "--lazy --height=$graph_height --width=$graph_width ".
            "--alt-autoscale-max ".
            "--lower-limit=0      --slope-mode --vertical-label='Transfer 
Rate (bps)' ".
            " DEF:IN=$rrd_bw_file:$in_val:AVERAGE: ".
            " DEF:OUT=$rrd_bw_file:$out_val:AVERAGE: ".
            " VDEF:totin=IN,TOTAL ".
            " VDEF:totout=OUT,TOTAL ".
            " COMMENT:'         Curr           Avg           Max\l' ".
            " CDEF:CINBASE=IN,8,* ".
            " CDEF:CIN=CINBASE,UN,0,CINBASE,IF ".
            //" CDEF:innormal=CIN,$alertlimit,LT,CIN,$alertlimit,IF ".
            //" CDEF:inhigh=CIN,$alertlimit,GT,CIN,0,IF ".
            " CDEF:innormal=CIN ".
            //" AREA:inhigh#FF0000".
            " AREA:innormal#00CF00:'In' ".
            " CDEF:COUT=OUT,8,* ".
            " GPRINT:CINBASE:LAST:' %6.2lf %sb/s' ".
            " GPRINT:CIN:AVERAGE:'%6.2lf %sb/s' ".
            " GPRINT:CIN:MAX:'%6.2lf %sb/s\l' ".
            " LINE2:COUT#$blue:'Out' ".
            " GPRINT:COUT:LAST:'%6.2lf %sb/s' ".
            " GPRINT:COUT:AVERAGE:'%6.2lf %sb/s' ".
            " GPRINT:COUT:MAX:'%6.2lf %sb/s\l' ".
            " VDEF:95pct_in=CINBASE,95,PERCENT ".
            " LINE1:95pct_in#$brown:'IN 95% Percentile- :'".
            " GPRINT:95pct_in:'%0.2lf %sb/s\l' ".
            " VDEF:95pct_out=COUT,95,PERCENT ".
            " LINE1:95pct_out#$yellow:'OUT 95% Percentile- :'".
            " GPRINT:95pct_out:'%0.2lf %sb/s\l' ";

            $time_diff = 0;
            $time_diff = $to_time - $from_time;
            $xscale_grid = "";

            if ($time_diff > 0 && $time_diff <= 600)
            {
                $xscale_grid = "--x-grid SECOND:20:MINUTE:1:MINUTE:1:0:%d%b/%H:%M";
            }
            else if ($time_diff > 600 && $time_diff <= 3600)
            {
                $xscale_grid = "--x-grid MINUTE:1:MINUTE:10:MINUTE:10:0:%d%b/%H:%M";
            }
            else if ($time_diff > 3600 && $time_diff <= 14400)
            {
                $xscale_grid = "--x-grid MINUTE:10:MINUTE:40:MINUTE:40:0:%d%b/%H:%M";
            }
            elseif ($time_diff > 14400 && $time_diff <= 86440)
            {
                $xscale_grid = "--x-grid HOUR:1:HOUR:6:HOUR:6:0:%d%b/%H:%M";
            }
            elseif ($time_diff > 86440 && $time_diff <= 604800)
            {
                $xscale_grid = "--x-grid HOUR:12:DAY:2:DAY:2:0:%d%b/%H:%M ";
            }
            elseif ($time_diff > 604800 && $time_diff <= 2592000)
            {
                $xscale_grid = "--x-grid DAY:1:DAY:7:DAY:7:0:%d%b/%H:%M";
            }
            else
            $xscale_grid = "";

            $from_date_time = date("d/M/Y G:i", $from_time);
            $to_date_time = date("d/M/Y G:i", $to_time);

            $graph_title = "Interface -".$interface." 
::".trim($interface_details[0]['description'])."";
            $period = ($from_time - $to_time);
            if ($period == (60 * 60 * 4))
            $rrdcommand .= " --end now --start end-4h --title '$graph_title:Last 4Hrs' ";
            else if ($period == (60 * 60 * 1))
            $rrdcommand .= " --end now --start end-1h  --title '$graph_title :Last 1Hrs' ";
            else if ($period == (60 * 10))
            $rrdcommand .= " --end now --start end-10m  --title ' 
$graph_title :Last 10Min.' ";
            else if ($period == (60 * 30))
            $rrdcommand .= " --end now --start end-30m  --title ' 
$graph_title :Last 30Min.' ";
            else if ($period == (60 * 60 * 24))
            $rrdcommand .= " --end now --start end-24h  --title '$graph_title :Last 24Hrs'";
            /* Last week */
            else if ($period == (60 * 60 * 24 * 7))
            $rrdcommand .= " --end now --start end-7d  --x-grid DAY:1:DAY:7:DAY:2:86400:%d%b/%H:%M --title '$graph_title :Last Week'";
            /* Last month */
            else if ($period == (60 * 60 * 24 * 30))
            $rrdcommand .= " --end now --start end-30d  --x-grid DAY:1:WEEK:1:WEEK:1:604800:%d%b/%H:%M  --title '$graph_title :Last Month'";
            else if ($period == (60 * 60 * 24 * 30 * 6))
            $rrdcommand .= " --end now --start end-180d  --title '$graph_title :Last 6 Months'";
            else
            $rrdcommand .= " --end $to_time --start $from_time $xscale_grid --title '$graph_title => From: $from_date_time  To: 
$to_date_time ($time_diff Seconds)'";

            $this->common_model->exec_command($rrdcommand);
            return $final;
        }

		function getbw_rrd_image_old($interface_id, $graph_width, $graph_height, $from_time, $to_time,$period = 0)
		{
			$graphimage_path = $this->config->item('public_path').'uploads/appmon/bw_'.$interface_id.'.png';
			if(file_exists($graphimage_path))
			unlink($graphimage_path);
			$img_name = $this->config->item('inc_url').'uploads/appmon/bw_'.$interface_id.'.png';
			$final['b_end'] = $graphimage_path;
			$final['f_end'] = $img_name;
			
			$interface_details = $this->common_model->select_all_where_records($this->tbl_prefix.'object_ports', "port_id = '".$interface_id."'", '', '');
			$object_id = $interface_details[0]['object_id'];
			$interface = $interface_details[0]['interface'];
			$in_val = "bw_input";
			$out_val = "bw_output";
			$graph_width = $graph_width > 0 ? $graph_width : 350;
			$graph_height = $graph_height > 0 ? $graph_height : 120;
			$from_time = $from_time > 0 ? $from_time : time();
			$to_time = $to_time > 0 ? $to_time : (time() - (60 * 60 * 1));
			
			$obj_prop_id = $this->common_model->select_all_where_records($this->tbl_prefix."monitoring_param_properties pp LEFT JOIN ".$this->tbl_prefix."monitoring_param mp ON pp.monitor_param_id = mp.monitor_param_id", "mp.param = 'Interface-BW' AND mp.status = 'y' 
AND pp.field_type = 'fetch_method' AND pp.status = 'y'", '', '', "pp.monitor_property_id, pp.def_threshold_unit, pp.def_threshold_value");
			
			if ($obj_prop_id[0]['def_threshold_unit'] == 'MBps')
			{
				$change_factor = 1000 * 1000 * 8;
			}
			elseif ($obj_prop_id[0]['def_threshold_unit'] == 'GBps')
			{
				$change_factor = 1000 * 1000 * 1000 * 8;
			}
			elseif ($obj_prop_id[0]['def_threshold_unit'] == 'KBps')
			{
				$change_factor = 1000 * 8;
			}
			elseif ($obj_prop_id[0]['def_threshold_unit'] == 'Kbps')
			{
				$change_factor = 1000;
			}
			elseif ($obj_prop_id[0]['def_threshold_unit'] == 'Mbps')
			{
				$change_factor = 1000 * 1000;
			}
			elseif ($obj_prop_id[0]['def_threshold_unit'] == 'Gbps')
			{
				$change_factor = 1000 * 1000 * 1000;
			}
			else
			{
				$change_factor = 8;
			}			
			$alert_details = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_bw_param_values', "monitor_property_id = '".$obj_prop_id[0]['monitor_property_id']."' AND interface_id = '".$interface_id."'", '', '');			
			if ($alert_details[0]['threshold_value'] != "" && $alert_details[0]['threshold_value'] != "0")
			{
				if ($alert_details[0]['threshold_operator'] == '<>')
				{
					$alert_value = explode(',', $alert_details[0]['threshold_value']);
					$alert_val = $alert_value[1];
					
					$alertlt = $alert_value[0] * $change_factor;
					$rrd_cmd = " CDEF:innormal1=CIN,$alertlt,GT,CIN,$alertlt,IF ".
					" CDEF:inhigh1=CIN,$alertlt,LT,CIN,0,IF ".
					" AREA:inhigh1#FF0000";
					" HRULE:$alertlt#FF0000 ";
				}
				else
				{
					$alert_val = $alert_details[0]['threshold_value'];
					$rrd_cmd = "";
				}
				$alertlimit = $alert_val * $change_factor;
				$alert = $alert_details[0]['threshold_value'];
			}
			else
			{
				$alertlimit = $obj_prop_id[0]['def_threshold_value'] * $change_factor;
				$alert = $obj_prop_id[0]['def_threshold_value'];
				
				if ($obj_prop_id[0]['def_threshold_value'] == 0 || $obj_prop_id[0]['def_threshold_value'] == "")
				{
					$alertlimit = 200 * $change_factor;
					$alert = 200;
				}
			}			
			$unit = $obj_prop_id[0]['def_threshold_unit'];
			$file_name = $object_id.'_'.$interface.'.rrd';
			$probe_name = $this->common_model->probedetails($object_id);
			$rrdPath = $this->config->item('data_dir');
			$rrdPath = $rrdPath.$probe_name.'/';
			$rrd_bw_file = "$rrdPath"."rrd_bw/".$file_name;
			$brown = "990033";
			$blue = "0000FF";
			$yellow = "F3B227";
			$red = "#E84415";
			$rrdcommand = "/usr/bin/rrdtool graph $graphimage_path ".
			"--imgformat PNG ".
			"--font DEFAULT:7: ".
			"--lazy --height=$graph_height --width=$graph_width ".
			"--alt-autoscale-max ".
			"--lower-limit=0  	--slope-mode --vertical-label='Transfer Rate (bps)' ".
			" DEF:IN=$rrd_bw_file:$in_val:AVERAGE: ".
			" DEF:OUT=$rrd_bw_file:$out_val:AVERAGE: ".
			" VDEF:totin=IN,TOTAL ".
			" VDEF:totout=OUT,TOTAL ".
			" COMMENT:'         Curr           Avg           Max\l' ".
			" CDEF:CINBASE=IN,8,* ".
			" CDEF:CIN=CINBASE,UN,0,CINBASE,IF ".
			//" CDEF:innormal=CIN,$alertlimit,LT,CIN,$alertlimit,IF ".
			//" CDEF:inhigh=CIN,$alertlimit,GT,CIN,0,IF ".
			" CDEF:innormal=CIN ".
			//" AREA:inhigh#FF0000".
			" AREA:innormal#00CF00:'In' ".
			" CDEF:COUT=OUT,8,* ".
			" GPRINT:CINBASE:LAST:' %6.2lf %sb/s' ".
			" GPRINT:CIN:AVERAGE:'%6.2lf %sb/s' ".
			" GPRINT:CIN:MAX:'%6.2lf %sb/s\l' ".
			" LINE2:COUT#$blue:'Out' ".
			" GPRINT:COUT:LAST:'%6.2lf %sb/s' ".
			" GPRINT:COUT:AVERAGE:'%6.2lf %sb/s' ".
			" GPRINT:COUT:MAX:'%6.2lf %sb/s\l' ".
			" VDEF:95pct_in=CINBASE,95,PERCENT ".
			" LINE1:95pct_in#$brown:'IN 95% Percentile- :'".
			" GPRINT:95pct_in:'%0.2lf %sb/s\l' ".
			" VDEF:95pct_out=COUT,95,PERCENT ".
			" LINE1:95pct_out#$yellow:'OUT 95% Percentile- :'".
			" GPRINT:95pct_out:'%0.2lf %sb/s\l' ";
			
			$time_diff = 0;
			$time_diff = $to_time - $from_time;
			$xscale_grid = "";
			
			if ($time_diff > 0 && $time_diff <= 600)
			{
				$xscale_grid = "--x-grid SECOND:20:MINUTE:1:MINUTE:1:0:%d%b/%H:%M";
			}
			else if ($time_diff > 600 && $time_diff <= 3600)
			{
				$xscale_grid = "--x-grid MINUTE:1:MINUTE:10:MINUTE:10:0:%d%b/%H:%M";
			}
			else if ($time_diff > 3600 && $time_diff <= 14400)
			{
				$xscale_grid = "--x-grid MINUTE:10:MINUTE:40:MINUTE:40:0:%d%b/%H:%M";
			}
			elseif ($time_diff > 14400 && $time_diff <= 86440)
			{
				$xscale_grid = "--x-grid HOUR:1:HOUR:6:HOUR:6:0:%d%b/%H:%M";
			}
			elseif ($time_diff > 86440 && $time_diff <= 604800)
			{
				$xscale_grid = "--x-grid HOUR:12:DAY:2:DAY:2:0:%d%b/%H:%M ";
			}
			elseif ($time_diff > 604800 && $time_diff <= 2592000)
			{
				$xscale_grid = "--x-grid DAY:1:DAY:7:DAY:7:0:%d%b/%H:%M";
			}
			else
			$xscale_grid = "";
			
			$from_date_time = date("d/M/Y G:i", $from_time);
			$to_date_time = date("d/M/Y G:i", $to_time);
			
			$graph_title = "Interface -".$interface." ::".trim($interface_details[0]['description'])."";
			$period = ($from_time - $to_time);
			if ($period == (60 * 60 * 4))
			$rrdcommand .= " --end now --start end-4h --title '$graph_title:Last 4Hrs' ";
			else if ($period == (60 * 60 * 1))
			$rrdcommand .= " --end now --start end-1h  --title '$graph_title :Last 1Hrs' ";
			else if ($period == (60 * 10))
			$rrdcommand .= " --end now --start end-10m  --title ' $graph_title :Last 10Min.' ";
			else if ($period == (60 * 30))
			$rrdcommand .= " --end now --start end-30m  --title ' $graph_title :Last 30Min.' ";
			else if ($period == (60 * 60 * 24))
			$rrdcommand .= " --end now --start end-24h  --title '$graph_title :Last 24Hrs'";
			/* Last week */
			else if ($period == (60 * 60 * 24 * 7))
			$rrdcommand .= " --end now --start end-7d  --x-grid DAY:1:DAY:7:DAY:2:86400:%d%b/%H:%M --title '$graph_title :Last Week'";
			/* Last month */
			else if ($period == (60 * 60 * 24 * 30))
			$rrdcommand .= " --end now --start end-30d  --x-grid DAY:1:WEEK:1:WEEK:1:604800:%d%b/%H:%M  --title '$graph_title :Last Month'";
			else if ($period == (60 * 60 * 24 * 30 * 6))
			$rrdcommand .= " --end now --start end-180d  --title '$graph_title :Last 6 Months'";
			else
			$rrdcommand .= " --end $to_time --start $from_time $xscale_grid --title '$graph_title => From: $from_date_time  To: $to_date_time ($time_diff Seconds)'";            
			
			$this->common_model->exec_command($rrdcommand);
			return $final;
		}
		
		function get_rrdImage_health($object_id, $graph_width, $graph_height, $from_time, $to_time, $param_id, $type)
		{
			$graphimage_path = $this->config->item('public_path').'uploads/appmon/health_'.$object_id.'_'.$param_id.'.png';
			if(file_exists($graphimage_path))
			unlink($graphimage_path);
			
			$img_name = $this->config->item('inc_url').'uploads/appmon/health_'.$object_id.'_'.$param_id.'.png';
			$final['b_end'] = $graphimage_path;
			$final['f_end'] = $img_name;
			
			$graph_width = $graph_width > 0 ? $graph_width : 350;
			$graph_height = $graph_height > 0 ? $graph_height : 100;
			$from_time = $from_time > 0 ? $from_time : time();
			$to_time = $to_time > 0 ? $to_time : (time() - (60 * 60 * 4));
			$prm_dts = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param',"monitor_param_id = '".$param_id."'", '', '', 'param,monitor_type_id'); //new query
			//$param_name = $this->monitor->get_field_value('param', $this->tbl_prefix.'monitoring_param', "monitor_param_id = '".$param_id."'");
			//$param_type = $this->monitor->get_field_value('monitor_type_id', $this->tbl_prefix.'monitoring_param', "monitor_param_id = '".$param_id."'");
			$property_id = $this->monitor->get_field_value('monitor_property_id', $this->tbl_prefix.'monitoring_param_properties', "monitor_param_id = '".$param_id."' AND variable_name = 'threshold_alert'");
			
			$wherecon = "object_id = '".$object_id."' AND monitor_property_id = '".$property_id."'";
			$alert_details = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_values', $wherecon, '', '', 'property_value');
			$th_values = explode(',', $alert_details[0]['property_value']);
			$alertlimit = max($th_values);
			
			if ($type != 'profile')
			{
				if ($object_id == 'disk')
				{
					$disk_details = $this->common_model->select_all_where_records($this->tbl_prefix.'object_disks', "disk_id = '".$param_id."'",'','','object_id,partition_name');
					$obj_id = $disk_details[0]['object_id'];
					
					$probe_name = $this->common_model->probedetails($obj_id);
					
					$device_title = $this->common_model->get_device_name($obj_id,'yes','n');
					$devip = $this->common_model->getipaddressbyobjid($obj_id);
					if($devip != '')	
					$device_title .= ' :'.$devip;
					$device_title .= ' ['.$disk_details[0]['partition_name'].']';	
					$probe_name = $this->common_model->probedetails($obj_id);
				}
				else
				{
					$probe_name = $this->common_model->probedetails($object_id);
					$device_title = $this->common_model->get_device_name($object_id,'yes','n');
					$devip = $this->common_model->getipaddressbyobjid($object_id);
					if($devip != '')	
					$device_title .= ' : '.$devip;
				}
			}
			else
			{
				$monitor_probe_id = $this->common_model->get_field_value('monitor_probe_id', $this->tbl_prefix.'monitoring_profile', "profile_id = '".$object_id."'");
				if($monitor_probe_id == '')
				$monitor_probe_id = 1;
				$probe_name = $this->common_model->get_field_value('title', $this->tbl_prefix.'monitoring_probes', "probe_id = '".$monitor_probe_id."'");
				
				$profile_details = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_profile', "profile_id = '".$object_id."'",'','','profile_value,additional_name');
				$device_title = $profile_details[0]['profile_value'];
				if($profile_details[0]['additional_name'] != '')
				$device_title .= ' : '.$profile_details[0]['additional_name'];
			}			
			if ($type == 'profile')
			{
				$rrdPath = $this->config->item('data_dir');
				$rrdPath = $rrdPath.$probe_name.'/';
				$file_name = $object_id.'_ping.rrd';
				$rrd_file = "$rrdPath"."profile_mon/".$file_name;
			}
			elseif ($object_id == 'disk')
			{
				$rrdPath = $this->config->item('data_dir');
				$rrdPath = $rrdPath.$probe_name.'/';
				$file_name = $param_id.'.rrd';
				$rrd_file = "$rrdPath"."rrd_disks/".$file_name;
				$is_iops = $this->common_model->get_field_value('is_iops', $this->tbl_prefix.'object_disks', "disk_id = '".$param_id."'");
				if ($is_iops == 'y')
				$param_id = $this->common_model->get_field_value('monitor_param_id', $this->tbl_prefix.'monitoring_param', "param = 'Disk IOPS'");
				else
				$param_id = $this->common_model->get_field_value('monitor_param_id', $this->tbl_prefix.'monitoring_param', "param = 'Disk Monitoring'");
			}
			else
			{
				$file_name = $object_id.'_'.$param_id.'.rrd';
				$rrdPath = $this->config->item('data_dir');
				$rrdPath = $rrdPath.$probe_name.'/';
				if ($prm_dts[0]['monitor_type_id'] == 1)
				{
					$rrd_file = "$rrdPath"."rrd_service/".$file_name;
				}
				else
				{
					$rrd_file = "$rrdPath"."rrd_performance/".$file_name;
				}
			}
			$where_con = "monitor_param_id = '".$param_id."' AND status = 'y' AND field_type = 'fetch_method' AND plot_graph = 'y'";
			$param_properties = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_param_properties', $where_con, '', '', 'variable_name');
			
			foreach($param_properties as $key => $row)
			{
				$variable_data = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_variable', 'name = "'.$row['variable_name'].'" AND status = "y"', '', '');
				$param_properties[$key]['vertical_label'] = $variable_data[0]['vertical_label'];
				$param_properties[$key]['comment_label'] = $variable_data[0]['comment_label'];
				$param_properties[$key]['alert_unit'] = $variable_data[0]['alert_unit'];
				$param_properties[$key]['graph_color'] = $variable_data[0]['graph_color'];
				$param_properties[$key]['graph_type'] = $variable_data[0]['graph_type'];
			}
			
			$rrdcommand = "/usr/bin/rrdtool graph $graphimage_path --imgformat PNG --lazy  --font DEFAULT:7: --height=$graph_height --width=$graph_width --units-exponent 0 --lower-limit 0   --alt-autoscale-max  --slope-mode --vertical-label='".$param_properties[0]['vertical_label']."'";
			
			if (trim($prm_dts[0]['param']) == 'PING')
			{
				$ping_var_data = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_variable', 'name = "ping_latency" AND status = "y"', '', '');
				$gr_ttype = $ping_var_data[0]['graph_type'];
				$rrdcommand .= " DEF:pinglatency=$rrd_file:ping_latency:AVERAGE".
				" DEF:packetloss=$rrd_file:packet_loss:AVERAGE".
				" CDEF:PLNone=packetloss,1,2,LIMIT,UN,UNKN,INF,IF".
				" CDEF:PL2=packetloss,2,8,LIMIT,UN,UNKN,INF,IF".
				" CDEF:PL15=packetloss,8,15,LIMIT,UN,UNKN,INF,IF".
				" CDEF:PL25=packetloss,15,25,LIMIT,UN,UNKN,INF,IF".
				" CDEF:PL50=packetloss,25,50,LIMIT,UN,UNKN,INF,IF".
				" CDEF:PL75=packetloss,50,75,LIMIT,UN,UNKN,INF,IF".
				" CDEF:PL100=packetloss,75,100,LIMIT,UN,UNKN,INF,IF".
				" $gr_ttype:pinglatency#02CF02:'Round Trip Time (ms)' ".
				" GPRINT:pinglatency:LAST:'Cur\: %2.2lf ' ".
				" GPRINT:pinglatency:AVERAGE:'Avg\: %2.2lf ' ".
				" GPRINT:pinglatency:MAX:'Max\: %2.2lf' ".
				" GPRINT:pinglatency:MIN:'Min\: %2.2lf \l' ".
				" AREA:PLNone#6c9bcd:'1-2%':STACK ".
				" AREA:PL2#4444ff:'2-8%':STACK ".
				" AREA:PL15#ccff00:'8-15%':STACK ".
				" AREA:PL25#ffff00:'15-25%':STACK ".
				" AREA:PL50#ffcc66:'25-50%':STACK ".
				" AREA:PL75#ff9900:'50-75%':STACK ".
				" AREA:PL100#ff0000:'75-100%':STACK ";
			}
			else
			{
				foreach($param_properties as $key => $data)
				{
					$rrdcommand .= " DEF:var".$key."=$rrd_file:".$data['variable_name'].":AVERAGE";
				}				
				foreach($param_properties as $key => $data)
				{
					$rrdcommand .= " CDEF:var1".$key."=var".$key.",1,/";
				}				
				foreach($param_properties as $key => $data)
				{
					$rrdcommand .= " ".$data['graph_type'].":var1".$key."".$data['graph_color'].":'".$data['comment_label']."' COMMENT:'Curr\:' GPRINT:var1".$key.":LAST:'%6.2lf ".$data['alert_unit']."' COMMENT:'Avg\:' GPRINT:var1".$key.":AVERAGE:'%6.2lf ".$data['alert_unit']."' COMMENT:'Max\:' GPRINT:var1".$key.":MAX:'%6.2lf ".$data['alert_unit']."\l' ";
				}
			}
			
			$time_diff = 0;
			$time_diff = $to_time - $from_time;
			$xscale_grid = "";
			
			if ($time_diff > 0 && $time_diff <= 600)
			{
				$xscale_grid = "--x-grid SECOND:20:MINUTE:1:MINUTE:1:0:%d%b/%H:%M";
			}
			else if ($time_diff > 600 && $time_diff <= 3600)
			{
				$xscale_grid = "--x-grid MINUTE:1:MINUTE:10:MINUTE:10:0:%d%b/%H:%M";
			}
			else if ($time_diff > 3600 && $time_diff <= 14400)
			{
				$xscale_grid = "--x-grid MINUTE:10:MINUTE:40:MINUTE:40:0:%d%b/%H:%M";
			}
			elseif ($time_diff > 14400 && $time_diff <= 86440)
			{
				$xscale_grid = "--x-grid HOUR:1:HOUR:6:HOUR:6:0:%d%b/%H:%M";
			}
			elseif ($time_diff > 86440 && $time_diff <= 604800)
			{
				$xscale_grid = "--x-grid HOUR:12:DAY:2:DAY:2:0:%d%b/%H:%M ";
			}
			elseif ($time_diff > 604800 && $time_diff <= 2592000)
			{
				$xscale_grid = "--x-grid DAY:1:DAY:7:DAY:7:0:%d%b/%H:%M";
			}
			else
			$xscale_grid = "";			
			$from_date_time = date("d/M/Y G:i", $from_time);
			$to_date_time = date("d/M/Y G:i", $to_time);
			$timeshow_diff = $this->common_model->secondstowords($time_diff);
			
			$period = ($from_time - $to_time);
			if ($period == (60 * 60 * 4))
			$rrdcommand .= " --end now --start end-4h --title '$device_title \n Last 4 Hrs' ";
			else if ($period == (60 * 60 * 1))
			$rrdcommand .= " --end now --start end-1h --title '$device_title \n Last 1 Hrs' ";
			else if ($period == (60 * 10))
			$rrdcommand .= " --end now --start end-10m --title '$device_title \n Last 10 Min.' ";
			else if ($period == (60 * 30))
			$rrdcommand .= " --end now --start end-30m --title '$device_title \n Last 30 Min.' ";
			else if ($period == (60 * 60 * 24))
			$rrdcommand .= " --end now --start end-24h --title '$device_title \n Last 24 Hrs'";
			/* Last week */
			else if ($period == (60 * 60 * 24 * 7))
			$rrdcommand .= " --end now --start end-7d  --x-grid DAY:1:DAY:7:DAY:2:86400:%d%b/%H:%M --title '$device_title \n Last Week'";
			/* Last month */
			else if ($period == (60 * 60 * 24 * 30))
			$rrdcommand .= " --end now --start end-30d --x-grid DAY:1:WEEK:1:WEEK:1:604800:%d%b/%H:%M --title '$device_title \n Last Month'";
			/* Last 6 month */
			else if ($period == (60 * 60 * 24 * 30 * 6))
			$rrdcommand .= " --end now --start end-180d --title '$device_title \n Last 6 Month'";
			else if ($period == (60 * 1))
			$rrdcommand .= " --end now --start end-1min --title '$device_title \n Last 1 Min' ";
			else
			$rrdcommand .= " --end $to_time --start $from_time $xscale_grid --title '$device_title \nFrom: $from_date_time  To: $to_date_time ($timeshow_diff)'";	
			
			if (file_exists($rrd_file))
			{
				$this->common_model->exec_command($rrdcommand);             
			}
			
			return $final;
		}		


		function alertscontent($alertstype="device")
		{
			$jsonarr = array();
			$filepath = $alertstype == "device" ? $this->alerts_path : $this->profile_alerts_path;
			if (file_exists($filepath))
			{
				$content = file_get_contents($filepath);
				if ($content != '')
				{
					$jsonarr = json_decode($content, true);
					if (!is_array($jsonarr))
					$jsonarr = array();
				}
			}
			return $jsonarr;
		}
		function alertsdetail($objectid,$alertstype="device")
		{
			$return = array();
			$alert_array = $this->common_model->alertscontent($alertstype);
			if (count($alert_array) > 0)
			{
				$objectalert = isset($alert_array[$objectid]) ? $alert_array[$objectid] : array();
				if (is_array($objectalert) && count($objectalert) > 0)
				{
					$total = 0;
					foreach($objectalert as $type => $alerts)
					{
						$return[$type]['count'] = count($alerts);
						$return[$type]['alerts'] = $alerts;
						$total += count($alerts);
					}
					$return['total_alerts'] = $total;
				}
			}
			return $return;
		}
		function alertslist($objectid='',$oftype='',$alertstype="device")
		{
			$return = array();			
			$alert_array = $this->common_model->alertscontent($alertstype);
			
			if (count($alert_array) > 0)
			{
				if( $objectid != '' )
				{		
					$objectalert = isset($alert_array[$objectid]) ? $alert_array[$objectid] : array();
					
					if (is_array($objectalert) && count($objectalert) > 0)
					{
						$total = 0;
						$alerts_ = array();
						foreach($objectalert as $type => $alerts)
						{	
							foreach($alerts as $key1=>$eachalert)
							{
								$alerts[$key1]['mon_type'] = $type;
							}
							if($oftype != '' && $oftype != $type )
							continue;
							$alerts_ = array_merge($alerts_,$alerts);
							$return['count'] += count($alerts);
							$return['alerts'] = $alerts_;
							$total += count($alerts);
						}
						$return['total_alerts'] = $total;
					}
					
				}
				else
				{
					foreach($alert_array as $objid => $objectalert)
					{
						if (is_array($objectalert) && count($objectalert) > 0)
						{
							$total = 0;
							$alerts_ = array();
							foreach($objectalert as $type => $alerts)
							{
								if($oftype != '' && $oftype != $type )
								continue;
								$alerts_ = array_merge($alerts_,$alerts);
								$return[$objid]= $alerts_;
							}
						}
					}
				}					
			}
			return $return;
		}
		function alertsbydeptype($objectids='',$alertstype="device")
		{
			$return = array();
			$filters['objectid'] = $objectids;
			$devicesbytype = $this->monitor->devicesbytype($filters);
			$alerts = $this->common_model->alertscontent($alertstype);
			foreach($alerts as $id => $data)
			{
				if(is_array($data))
				{
					$dep_type_id = isset($devicesbytype[$id]) ? $devicesbytype[$id]['dep_type_id'] : 0;
					$total = 0;
					foreach($data as $type => $val)
					{
						$return[$dep_type_id]['alerts'] = $val;
						$total = $total + count($val);
					}
					$return[$dep_type_id]['total_alerts'] += $total;
				}	
			}
			return $return;
		}	
		function alertmessages($objectid,$alertstype="device")
		{
			$alert_array = $alerts = array();
			$alert_array = $this->alertsdetail($objectid,$alertstype);
			if (count($alert_array) > 0)
			$alerts = array_column_recursive($alert_array, 'description');
			return $alerts;
		}
		function rmcronfiles($objectid, $type = 'device')
		{
			$path = $this->config->item('data_dir')."/*/service_data/";
			if ($type == 'device')
			{
				$filename = $objectid."_*";
				$srcpath = array($path."service/".$filename, $path."performance/".$filename, $path."disk/".$filename, $path."bandwidth/".$filename);
				foreach($srcpath as $filepath)
					array_map('unlink', glob($filepath)); // delete all cron service data files.
			}
			elseif ($type == 'profile')
			{
				$profileid = $objectid;
				$filename = $profileid."_*";
				array_map('unlink', glob($path."profile/".$filename)); // delete all cron service data files.
			}
			return true;
		}
		function statscontent($type, $objectid="")
 		{
 			$jsonarr = array();
             $filepath = $this->stats_path.strtolower($type)."_stats.json";			
 			if (file_exists($filepath))
 			{
 				$content = file_get_contents($filepath);
 				if ($content != '')
 				{
 					$jsonarr = json_decode($content, true);
 					if (!is_array($jsonarr))
 						$jsonarr = array();
 					else
 					{
 						if ($objectid > 0 )
 							$jsonarr = $jsonarr[$objectid];
 					}
 				}
 			}
 			return $jsonarr;
 		}				
		function cmdexe($val)
		{
			$payload_len = 8192;
			$hostname = $val['agent_ip'];
			$emrpe=$val['emrpe'];
			$agent_id=$val['agent_id'];		
			$output = '';
			$cmd = isset($val['cmd']) ? $val['cmd'] : '';
			$args = isset($val['args'] ) ? $val['args'] : '';
			$opts = isset($val['opts']) ? $val['opts'] : '';
			$extra = isset($val['extra']) ? $val['extra'] : '';
			$scriptpath = $this->getScriptName($val);
			if($val['emrpe'])
			{
				$args = $args != '' ? " -a ".$args : '';
				$cmd = $scriptpath." ".$opts." -c ".$cmd." ".$args." ".$extra;
				$output = $this->logmonitor_model->buildExecCmd($cmd,true);					
			}
			else
			{
				die('no'.$this->separator_chr."<font color='red'>To apply changes \"Active Monitoring\" service should be active on device.</font>");
			}	
			return $output;
		}
		function getprobedetailsbydevip($ip)
		{
			$probe = array();
			if ($ip > 0)
			{
				$object_id = $this->common_model->get_field_value('object_id', $this->tbl_prefix.'object_ips', "status = 'y' AND type IN ('primary','management','snmp','interface','additional') AND ip = '".$ip."'");
				if($object_id != '') 
				{
					$probe = $this->common_model->getprobedetails($object_id);
				}	
			}
			return $probe;
		}
		
		function port_alert($object_id,$port_id)
		{	
			$alert_data = array();
			$alertscontent = $this->alertscontent('device');
			$alert_desc = $alertscontent[$object_id]['bandwidth'][$port_id]['description'];
			$alert_severity = $alertscontent[$object_id]['bandwidth'][$port_id]['severity'];
			$alert_data['description'] = $alert_desc;
			$alert_data['severity'] = $alert_severity;
			return $alert_data;
			
		}
		
		function wan_ip_alert($object_id,$param_id,$type="device")
		{	
			$alert_data = array();
			$alerttype = $type == 'device' ? 'service' : 'profile';
			$alertscontent = $this->alertscontent($type);
			$alert_desc = $alertscontent[$object_id][$alerttype][$param_id]['description'];
			$alert_severity = $alertscontent[$object_id][$alerttype][$param_id]['severity'];
			$alert_data['description'] = $alert_desc;
			$alert_data['severity'] = $alert_severity;
			return $alert_data;
			
		}
		function php_ssh_exec_cmd($cmd,$probe_id='',$probe_details = array())
		{	
			if($probe_id > 0)
			{
				$host_ip = $this->config->item('host_ip');
				if(count($probe_details) < 1)
					$probe_details = $this->common_model->select_all_where_records($this->tbl_prefix.'monitoring_probes',"probe_id = '".$probe_id."'",'','','object_id,title,key_login,ip,key_path');				
				if(!is_array($probe_details) && count($probe_details) < 1)
				{
					$return_response = "Probe details are not found. Please confirm the probe is assigned to this device";
					return $return_response ;
				}
				else
				{
					$probe_ip = $probe_details[0]['ip'];
					$key_login = $probe_details[0]['key_login'];
					$object_id = $probe_details[0]['object_id'];
					$key_path = $probe_details[0]['key_path'];
					
					if($host_ip != $probe_ip)
					{	
						$det = $this->objectcredentials_all($object_id);
						
						$port = $det['ssh']['port'];
							if($port == '')
								$port = 22;
								
						if($key_login == 'n')
						{	
							$connection = @ssh2_connect($probe_ip, $port);
							if(!$connection)
							{
								return "fail: unable to establish connection";	
							}
								
							if (@ssh2_auth_password($connection, $det['ssh']['username'],$this->common_model->decrypt($det['ssh']['pass'])))
							{	
								$stream = ssh2_exec($connection, $cmd);
								
								$errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
								// Enable blocking for both streams
								stream_set_blocking($stream, true);
								stream_set_blocking($errorStream, true);
								// Whichever of the two below commands is listed first will receive its appropriate output.  The second command receives nothing
								$result = stream_get_contents($stream);
								$error_result = stream_get_contents($errorStream);
								// Close the streams       
								fclose($errorStream);
								fclose($stream);
								if($result != '')
									return $result;
								else
									return $error_result;	
							}
							else
							{
								return 'Unable to connect probe using SSH Crendentials';
							}
						}
						else
						{
							$connection = @ssh2_connect($probe_ip, $port,array('hostkey' => 'ssh-rsa'));	
							if (@ssh2_auth_pubkey_file($connection, $det['ssh']['username'],$key_path))
							{
								$stream = ssh2_exec($connection, $cmd);
								$errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
								// Enable blocking for both streams
								stream_set_blocking($stream, true);
								stream_set_blocking($errorStream, true);
								// Whichever of the two below commands is listed first will receive its appropriate output.  The second command receives nothing
								$result = stream_get_contents($stream);
								$error_result = stream_get_contents($errorStream);
								// Close the streams       
								fclose($errorStream);
								fclose($stream);
								
								if($error_result != '')
									return $error_result;
								else
									return $result;	
							}
							else
							{
								return 'Unable to connect probe using SSH Crendentials';
							}
						}
					
					}
					else
					{
						$output = $this->common_model->exec_command($cmd);
						return $output; 
					}
				}
			}
			else
			{
				$output = $this->common_model->exec_command($cmd);
				return $output; 
			}
		}
		function mysqlversion()
		{
			$query = $this->db->query("SELECT VERSION()");
			if ($query->num_rows() > 0)
			{
				$row = $query->row_array();
				return $row[0];
			}
			else
			{
				return '';
			}
		}	
	}
?>
