<?php

//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_tab.php');

class ilp_dashboard_reports_tab extends ilp_dashboard_tab {
	
	public		$student_id;
	public 		$filepath;	
	public		$linkurl;
	public 		$selectedtab;
	public		$role_ids;
	public 		$capability;
	
	
	function __construct($student_id=null)	{
		global 	$CFG,$USER,$PAGE;
		
		$this->linkurl				=	$CFG->wwwroot.$_SERVER["SCRIPT_NAME"]."?user_id=".$student_id;
		
		$this->student_id	=	$student_id;
		
		$this->selectedtab	=	false;
		
		//set the id of the tab that will be displayed first as default
		$this->default_tab_id	=	'1';
		
		//call the parent constructor
		parent::__construct();
		
	}
	
	/**
	 * Return the text to be displayed on the tab
	 */
	function display_name()	{
		return	get_string('ilp_dashboard_reports_tab_name','block_ilp');
	}
	
    /**
     * Override this to define the second tab row should be defined in this function  
     */
    function define_second_row()	{
    	global 	$USER,$PAGE,$OUTPUT;
    	
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
		if (!empty($this->plugin_id)) {		
			
			
			//get all of the users roles in the current context and save the id of the roles into
			//an array 
			$role_ids	=	 array();
			if ($roles = get_user_roles($PAGE->context, $USER->id)) {
			 	foreach ($roles as $role) {
			 		$role_ids[]	= $role->roleid;
			 	}
			}
			
			$capability	=	$this->dbc->get_capability_by_name('block/ilp:viewreport');
			
			$this->secondrow	=	array();
			
			//get all reports
			$reports	=	$this->dbc->get_reports(ILP_ENABLED);
			
			//create a tab for each enabled report
			foreach($reports as $r)	{
				if ($this->dbc->has_report_permission($r->id,$role_ids,$capability->id)) {

					//the tabitem and selectedtab query string params are added to the linkurl in the 
					//second_row() function  
					$this->secondrow[]	=	array('id'=>$r->id,'link'=>$this->linkurl,'name'=>$r->name);
				}
				
			}
		}
    }
    
    
    /**
     * Override this to define the third tab row should be defined in this function  
     */
    function define_third_row()	{
    	
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
    	if (!empty($this->plugin_id) && !empty($this->selectedtab)) {	
    	
    		//explode the $selectedtab id on '-' which will return an array with up to 3 values
			$seltab	=	explode(':',$this->selectedtab,-2);
			
			//if a second row tab has been selected 
			if (!empty($seltab[1])) {
				
				//find out if the report has state fields
				if ($this->dbc->has_plugin_field('block_ilp_plu_ste',$seltab[1]))	{
					
					//get all state items for the table and create a tab for each.
					$states	=	$this->dbc->get_report_state_items($seltab[1],'ilp_element_plugin_state');
					//create a tab for each enabled state item
					foreach($states as $s)	{
						$this->thirdrow[]	=	array('id'=>$seltab[1].'-'.$s->id,'link'=>$this->linkurl,'name'=>$s->name);
					}		
				} 
			}
    		
    	}
    	    	
    }

    /**
     * 
     * Simple function to return the header for this tab
     * @param unknown_type $headertext
     */
    function get_header($headertext)	{
    	return "<div><h2>{$headertext}<h2></div>";
    }
	
	
	/**
	 * Returns the content to be displayed 
	 *
	 * @param	string $selectedtab the tab that has been selected this variable
	 * this variable should be used to determined what to display
	 * 
	 * @return none
	  */
	function display($selectedtab=null)	{
		global 	$CFG, $PAGE, $USER, $OUTPUT, $PARSER;
		
		$pluginoutput	=	"";

		if ($this->dbc->get_user_by_id($this->student_id)) {
	
			//get the selecttab param if has been set
			$this->selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

			//get the tabitem param if has been set
			$this->tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_INT);
			
			
			
	/*
			// load custom javascript
			$module = array(
			    'name'      => 'view_main',
			    'fullpath'  => '/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_reports_tab/js/animate_accordions.js',
			    'requires'  => array('yui2-dom', 'yui2-event', 'yui2-connection', 'yui2-container', 'yui2-animation')
			);
			
			// js arguments
			$jsarguments = array(
			    'open_image'   => $OUTPUT->pix_url('t/switch_minus'),
			    'closed_image' => $OUTPUT->pix_url('t/switch_plus')
			);
			
			// initialise the js for the page
			$PAGE->requires->js_init_call('M.blocks_ilp_animate_accordions.init', $jsarguments, true, $module);
		*/	
				//start buffering output
				ob_start();
					
					
					
				
					//split the selected tab id on up 3 ':'
					$seltab	=	explode(':',$selectedtab);
					
					//if the seltab is empty then the highest level tab has been selected
					if (empty($seltab))	$seltab	=	array($selectedtab); 
									
					$report_id	= (!empty($seltab[1])) ? $seltab[1] : $this->default_tab_id ;
					$state_id	= (!empty($seltab[2])) ? $seltab[2] : false;
					
					if ($report	=	$this->dbc->get_report_by_id($report_id)) {

						echo $this->get_header($report->name);
						
						$reportname	=	$report->name;	
						//get all of the fields in the current report, they will be returned in order as
						//no position has been specified
						$reportfields		=	$this->dbc->get_report_fields_by_position($report_id);
						
						//does this report give user the ability to add comments 
						$has_comments	=	(!empty($report->comments)) ? true	:	false;

						//this will hold the ids of fields that we dont want to display
						$dontdisplay	=	 array();
						
						//does this report allow users to say it is related to a particular course
						$has_courserelated	=	(!$this->dbc->has_plugin_field($report_id,'ilp_element_plugin_course')) ? false : true;
					
						if (!empty($has_courserelated))	{
							$courserelated	=	$this->dbc->has_plugin_field($report_id,'ilp_element_plugin_course');
							//the should not be anymore than one of these fields in a report	
							foreach ($courserelated as $cr) {
									$dontdisplay[] 	=	$cr->id;
									$courserelatedfield_id	=	$cr->id;	
							}
						} 

						$has_datedeadline	=	(!$this->dbc->has_plugin_field($report_id,'ilp_element_plugin_date_deadline')) ? false : true;
						
						if (!empty($has_datedeadline))	{
							$deadline	=	$this->dbc->has_plugin_field($report_id,'ilp_element_plugin_date_deadline');
							//the should not be anymore than one of these fields in a report	
							foreach ($deadline as $d) {
									$dontdisplay[] 	=	$d->id;	
							}
						} 
						
						//get all of the users roles in the current context and save the id of the roles into
						//an array 
						$role_ids	=	 array();
						if ($roles = get_user_roles($PAGE->context, $USER->id)) {
						 	foreach ($roles as $role) {
						 		$role_ids[]	= $role->roleid;
						 	}
						}
						
						//find out if the current user has the edit report capability for the report 
						$access_report_editreports	= false;
						$capability	=	$this->dbc->get_capability_by_name('block/ilp:editreport');
						if (!empty($capability)) $access_report_editreports		=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);	

						//find out if the current user has the delete report capability for the report
						$access_report_deletereports	=	false;
						$capability	=	$this->dbc->get_capability_by_name('block/ilp:deletereport');
						if (!empty($capability))	$access_report_deletereports	=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

						//get all of the entries for this report
						$reportentries	=	$this->dbc->get_user_report_entries($report_id,$this->student_id,$state_id);

						//create the entries list var that will hold the entry information 
						$entrieslist	=	array();
					
						
						if (!empty($reportentries)) {
							foreach ($reportentries as $entry)	{

								//TODO: is there a better way of doing this?
								//I am currently looping through each of the fields in the report and get the data for it 
								//by using the plugin class. I do this for two reasons it may lock the database for less time then
								//making a large sql query and 2 it will also allow for plugins which return multiple values. However
								//I am not naive enough to think there is not a better way!
								
								$entry_data	=	new stdClass();
								
								//get the creator of the entry
								$creator				=	$this->dbc->get_user_by_id($entry->creator_id);

								//get comments for this entry
								$comments				=	$this->dbc->get_entry_comments($entry->id);
								
								//
								$entry_data->creator		=	(!empty($creator)) ? fullname($creator)	: get_string('notfound','block_ilp');
								$entry_data->created		=	userdate($entry->timecreated);
								$entry_data->modified		=	userdate($entry->timemodified);
								$entry_data->user_id		=	$entry->user_id;
								$entry_data->entry_id		=	$entry->id;
								
								if ($has_courserelated) {
									$course	=	$this->dbc->get_course_by_id($courserelatedfield_id);
									$entry_data->coursename		=	$course->shortname;
								}
								
								foreach ($reportfields as $field) {
		
									//get the plugin record that for the plugin 
									$pluginrecord	=	$this->dbc->get_plugin_by_id($field->plugin_id);
								
									//take the name field from the plugin as it will be used to call the instantiate the plugin class
									$classname = $pluginrecord->name;
								
									// include the class for the plugin
									include_once("{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/{$classname}.php");
								
									if(!class_exists($classname)) {
									 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
									}
									
									//instantiate the plugin class
									$pluginclass	=	new $classname();

									$pluginclass->load($field->id);
								
									//call the plugin class entry data method
									$pluginclass->view_data($field->id,$entry->id,$entry_data);

								}
								include_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_reports_tab.html');							
								
							}
						} else {
							
							echo get_string('nothingtodisplay');
							
						}
					}					
					$pluginoutput = ob_get_contents();
				
					ob_end_clean();
				
				} else {
					$pluginoutput	=	get_string('studentnotfound','block_ilp');
				}
					
			
			return $pluginoutput;
	}


	
	
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 function language_strings(&$string) {
        $string['ilp_dashboard_reports_tab'] 					= 'entries tab';
        $string['ilp_dashboard_reports_tab_name'] 				= 'Reports';
        $string['ilp_dashboard_entries_tab_overview'] 			= 'Overview';
        $string['ilp_dashboard_entries_tab_lastupdate'] 		= 'Last Update';
	        
        return $string;
    }
	
	
	
	
	
}