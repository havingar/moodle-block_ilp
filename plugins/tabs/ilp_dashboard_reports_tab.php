<?php

//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');

class ilp_dashboard_reports_tab extends ilp_dashboard_tab {

	public		$student_id;
	public 		$filepath;
	public		$linkurl;
	public 		$selectedtab;
	public		$role_ids;
	public 		$capability;


	function __construct($student_id=null,$course_id=null)	{
		global 	$CFG,$USER,$PAGE;

		//$this->linkurl				=	$CFG->wwwroot.$_SERVER["SCRIPT_NAME"]."?user_id=".$student_id."&course_id={$course_id}";

		$this->linkurl					=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id=".$student_id."&course_id={$course_id}";

		$this->student_id	=	$student_id;

		$this->course_id	=	$course_id;

		$this->selectedtab	=	false;

		$defaulttab			=	get_config('block_ilp','ilp_dashboard_reports_tab_default');

		//set the id of the tab that will be displayed first as default
		$this->default_tab_id	=	(empty($defaulttab)) ? '1' : get_config('block_ilp','ilp_dashboard_reports_tab_default');

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
    	global 	$CFG,$USER,$PAGE,$OUTPUT,$PARSER;

    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION
		if (!empty($this->plugin_id)) {


			/****
			 * This code is in place as moodle insists on calling the settings functions on normal pages
			 *
			 */
			//check if the set_context method exists
			if (!isset($PAGE->context) === false) {

				$course_id = (is_object($PARSER)) ? $PARSER->optional_param('course_id', SITEID, PARAM_INT)  : SITEID;
				$user_id = (is_object($PARSER)) ? $PARSER->optional_param('user_id', $USER->id, PARAM_INT)  : $USER->id;

				if ($course_id != SITEID && !empty($course_id))	{
					if (method_exists($PAGE,'set_context')) {
						//check if the siteid has been set if not
						$PAGE->set_context(get_context_instance(CONTEXT_COURSE,$course_id));
					}	else {
						$PAGE->context = get_context_instance(CONTEXT_COURSE,$course_id);
					}
				} else {
					if (method_exists($PAGE,'set_context')) {
						//check if the siteid has been set if not
						$PAGE->set_context(get_context_instance(CONTEXT_USER,$user_id));
					}	else {
						$PAGE->context = get_context_instance(CONTEXT_USER,$user_id);
					}
				}
			}


			//get all of the users roles in the current context and save the id of the roles into
			//an array
			$role_ids	=	 array();

			$authuserrole	=	$this->dbc->get_role_by_name(ILP_AUTH_USER_ROLE);
			if (!empty($authuserrole)) $role_ids[]	=	$authuserrole->id;



			//TODO: strange but isset does not seem to work correctly in moodle 2.0
			//it doesn't return true when testing for $PAGE->context even when it is set
			//so I will do different tests depending on moodle version

			$contextset = false;

			if (stripos($CFG->release,"2.") !== false) {
				$contextset	=	(!is_null($PAGE->context)) ? true : false;
			} else {
				$contextset	=	(isset($PAGE->context)) ? true : false;
			}


			if (!empty($contextset))	{

					if ($roles = get_user_roles($PAGE->context, $USER->id)) {
					 	foreach ($roles as $role) {
					 		$role_ids[]	= $role->roleid;
					 	}
					}

					$capability	=	$this->dbc->get_capability_by_name('block/ilp:viewreport');

					$this->secondrow	=	array();


					//get all reports
					$reports	=	$this->dbc->get_reports_by_position(null,null,false);
					if (!empty($reports)) {
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
		}
    }


    /**
     * Override this to define the third tab row should be defined in this function
     */
    function define_third_row()	{

    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION
    	if (!empty($this->plugin_id) && !empty($this->selectedtab)) {


    	}

    }

    /**
     *
     * Simple function to return the header for this tab
     * @param unknown_type $headertext
     */
    function get_header($headertext,$icon)	{
		//setup the icon
		$icon 	=	 "<img id='reporticon' class='icon_med' alt='$headertext ".get_string('reports','block_ilp')."' src='$icon' />";

    	return "<h2>{$icon}{$headertext}</h2></div>";
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

		$pluginoutput	    =	"";



		if ($this->dbc->get_user_by_id($this->student_id)) {

			//get the selecttab param if has been set
			$this->selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

			//get the tabitem param if has been set
			$this->tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_CLEAN);

            $displaysummary     =	$PARSER->optional_param('summary', 0, PARAM_INT);

			//start buffering output
				ob_start();

					//split the selected tab id on up 3 ':'
					$seltab	=	explode(':',$selectedtab);

					//if the seltab is empty then the highest level tab has been selected
					if (empty($seltab))	$seltab	=	array($selectedtab);

					$report_id	= (!empty($seltab[1])) ? $seltab[1] : $this->default_tab_id ;
					$state_id	= (!empty($seltab[2])) ? $seltab[2] : false;

					if ($report	=	$this->dbc->get_report_by_id($report_id)) {

                        //get all of the users roles in the current context and save the id of the roles into
                        //an array
                        $role_ids	=	 array();

                        $authuserrole	=	$this->dbc->get_role_by_name(ILP_AUTH_USER_ROLE);
                        if (!empty($authuserrole)) $role_ids[]	=	$authuserrole->id;

                        if ($roles = get_user_roles($PAGE->context, $USER->id)) {
                            foreach ($roles as $role) {
                                $role_ids[]	= $role->roleid;
                            }
                        }

                        $access_report_viewreports	= false;
                        $capability	=	$this->dbc->get_capability_by_name('block/ilp:viewreport');
                        if (!empty($capability)) $access_report_viewreports		=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

						if ($report->status == ILP_ENABLED && !empty($access_report_viewreports)) {



							$reportname	=	$report->name;
							//get all of the fields in the current report, they will be returned in order as
							//no position has been specified
							$reportfields		=	$this->dbc->get_report_fields_by_position($report_id);

							$reporticon	= (!empty($report->iconfile)) ? '' : '';




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

                            //find if the current user can add reports
                            $access_report_addreports	= false;
                            $capability	=	$this->dbc->get_capability_by_name('block/ilp:addreport');
                            if (!empty($capability)) $access_report_addreports		=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);


							//find out if the current user has the edit report capability for the report
							$access_report_editreports	= false;
							$capability	=	$this->dbc->get_capability_by_name('block/ilp:editreport');
							if (!empty($capability)) $access_report_editreports		=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

							//find out if the current user has the delete report capability for the report
							$access_report_deletereports	=	false;
							$capability	=	$this->dbc->get_capability_by_name('block/ilp:deletereport');
							if (!empty($capability))	$access_report_deletereports	=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

							//find out if the current user has the add comment capability for the report
							$access_report_addcomment	= false;
							$capability	=	$this->dbc->get_capability_by_name('block/ilp:addcomment');
							if (!empty($capability)) $access_report_addcomment		=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

							//find out if the current user has the edit comment capability for the report
							$access_report_editcomment	=	false;
							$capability	=	$this->dbc->get_capability_by_name('block/ilp:editcomment');
							if (!empty($capability))	$access_report_editcomment	=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

							//find out if the current user has the add comment capability for the report
							$access_report_deletecomment	= false;
							$capability	=	$this->dbc->get_capability_by_name('block/ilp:deletecomment');
							if (!empty($capability)) $access_report_deletecomment		=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

							//find out if the current user has the edit comment capability for the report
							$access_report_viewcomment	=	false;
							$capability	=	$this->dbc->get_capability_by_name('block/ilp:viewcomment');
							if (!empty($capability))	$access_report_viewcomment	=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

							// Check to see whether the user can delete the reports entry either single entry or multiple entry.
							$candelete =	(!empty($access_report_deletereports))	?	true	: false;

                            $capability		=	$this->dbc->get_capability_by_name('block/ilp:viewotherilp');
                            if (!empty($capability))	$access_report_viewothers		=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

                            //check to see whether the user can add/view extension for the specific report
                            $capability		=	$this->dbc->get_capability_by_name('block/ilp:addviewextension');
                            if (!empty($capability))	$access_report_addviewextension	 =	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

							//get all of the entries for this report
							$reportentries	=	$this->dbc->get_user_report_entries($report_id,$this->student_id,$state_id);

                            //does the current report allow multiple entries
                            $multiple_entries   =   (!empty($report->frequency)) ? true :   false;

                            //instantiate the report rules class
                            $reportrules    =   new ilp_report_rules($report_id,$this->student_id);

                            //output html elements to screen

                            $icon				=	(!empty($report->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report_id=".$report->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";

                            echo $this->get_header($report->name,$icon);

                            $stateselector	=	(isset($report_id)) ?	$this->stateselector($report_id) :	"";

                            //find out if the rules set on this report allow a new entry to be created
                            $reportavailable =   $reportrules->report_availabilty();

                            echo "<div id='report-entries'>";
                            if (!empty($access_report_addreports)   && !empty($multiple_entries) && !empty($reportavailable['result'])) {
                                echo    "<div class='add' style='float :left'>
                                            <a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_reportentry.php?user_id={$this->student_id}&report_id={$report_id}&course_id={$this->course_id}' >".get_string('addnew','block_ilp')."</a>&nbsp;
                                        </div>";
                            }

                            if (!empty($access_report_viewothers)) {

                                if (!empty($access_report_addviewextension) && $reportrules->can_add_extensions()) {
                                    echo "<div class='add' style='float :left'>
                                        <a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_preference.php?user_id={$this->student_id}&report_id={$report_id}&course_id={$this->course_id}' >".get_string('addextension','block_ilp')."</a>&nbsp;
                                      </div>

                                    <div class='add' style='float :left'>
                                        <a href='{$CFG->wwwroot}/blocks/ilp/actions/view_extensionlist.php?user_id={$this->student_id}&report_id={$report_id}&course_id={$this->course_id}' >".get_string('viewextension','block_ilp')."</a>
                                    </div>";
                                }

                             }
                            echo "</div>
                            <br />";

                            //output the print icon
                            echo "{$stateselector}<div class='entry_floatright'><a href='#' onclick='M.ilp_standard_functions.printfunction()' ><img src='{$CFG->wwwroot}/blocks/ilp/pix/icons/print_icon_med.png' alt='".get_string("print","block_ilp")."' class='ilp_print_icon' width='32px' height='32px' ></a></div>
								 ";



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
										$coursename	=	false;
										$crfield	=	$this->dbc->get_report_coursefield($entry->id,$courserelatedfield_id);
										if (empty($crfield) || empty($crfield->value)) {
											$coursename	=	get_string('allcourses','block_ilp');
										} else if ($crfield->value == '-1') {
											$coursename	=	get_string('personal','block_ilp');
										} else {
											$crc	=	$this->dbc->get_course_by_id($crfield->value);
											if (!empty($crc)) $coursename	=	$crc->shortname;
										}
										$entry_data->coursename = (!empty($coursename)) ? $coursename : '';
									}

									foreach ($reportfields as $field) {

										//get the plugin record that for the plugin
										$pluginrecord	=	$this->dbc->get_plugin_by_id($field->plugin_id);

										//take the name field from the plugin as it will be used to call the instantiate the plugin class
										$classname = $pluginrecord->name;

										// include the class for the plugin
										include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

										if(!class_exists($classname)) {
										 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
										}

										//instantiate the plugin class
										$pluginclass	=	new $classname();

										if ($pluginclass->is_viewable() != false)	{
											$pluginclass->load($field->id);

											//call the plugin class entry data method
											$pluginclass->view_data($field->id,$entry->id,$entry_data);
										} else	{
											$dontdisplay[]	=	$field->id;
										}

									}

									include($CFG->dirroot.'/blocks/ilp/plugins/tabs/ilp_dashboard_reports_tab.html');

								}
							} else {

								echo get_string('nothingtodisplay');

							}

						}//end new if

					}

							// load custom javascript
					$module = array(
					    'name'      => 'ilp_dashboard_reports_tab',
					    'fullpath'  => '/blocks/ilp/plugins/tabs/ilp_dashboard_reports_tab.js',
					    'requires'  => array('event','dom','node','io-form','anim-base','anim-xy','anim-easing','anim')
					);

					// js arguments
					$jsarguments = array(
					    'open_image'   => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_minus.gif",
					    'closed_image' => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_plus.gif",
					);

					// initialise the js for the page
					$PAGE->requires->js_init_call('M.ilp_dashboard_reports_tab.init', $jsarguments, true, $module);


					$pluginoutput = ob_get_contents();



					ob_end_clean();

				} else {
					$pluginoutput	=	get_string('studentnotfound','block_ilp');
				}


			return $pluginoutput;
	}

	function stateselector($report_id)	{
			$stateselector		=	"<div class='report_state'><form action='{$this->linkurl}&selectedtab={$this->plugin_id}' method='get' >
			                                <input type='hidden' name='course_id' value='{$this->course_id}' />
											<input type='hidden' name='user_id' value='{$this->student_id}' />
											<input type='hidden' name='selectedtab' value='{$this->plugin_id}' />
                                            <input type='hidden' name='tabitem' value='{$this->plugin_id}:{$report_id}' />";

           //find out if the report has state fields
			if ($this->dbc->has_plugin_field($report_id,'ilp_element_plugin_state'))	{
					$states		=	$this->dbc->get_report_state_items($report_id,'ilp_element_plugin_state');
					$stateselector	.=	"<label>Report State</label>

											<select name='tabitem' id='reportstateselect'>
											<option value='{$this->plugin_id}:{$report_id}' >Any State</option>";
											if (!empty($states)) {
												foreach($states as $s)	{
													$stateselector .= "<option value='{$this->plugin_id}:{$report_id}:{$s->id}'>{$s->name}</option>";
												}
											}
                $stateselector	.=	"</select>";


			}

            $summarychecked =    (!empty($displaysummary)) ? "checked='checked'" : "";

            $stateselector	.=   "<br />
                                      <label for='summary'>".get_string('displaysummary','block_ilp')."</label>
                                      <input id='summary' type='checkbox' name='summary' value='1' {$summarychecked} >
                                      <p>
					                  <input type='submit' value='Apply Filter' id='stateselectorsubmit' />
					                  </p></div></form>";
			return $stateselector;
	}



	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {
        $string['ilp_dashboard_reports_tab'] 					= 'entries tab';
        $string['ilp_dashboard_reports_tab_name'] 				= 'Reports';
        $string['ilp_dashboard_entries_tab_overview'] 			= 'Overview';
        $string['ilp_dashboard_entries_tab_lastupdate'] 		= 'Last Update';
        $string['ilp_dashboard_reports_tab_default'] 			= 'Default report';

        return $string;
    }


	/**
 	  * Adds config settings for the plugin to the given mform
 	  * by default this allows config option allows a tab to be enabled or dispabled
 	  * override the function if you want more config options REMEMBER TO PUT
 	  *
 	  */
 	 function config_form(&$mform)	{

 	 	$reports	=	$this->dbc->get_reports(ILP_ENABLED);

 	 	$options = array();

 	 	if (!empty($reports)) {
 	 		foreach ($reports as $r) {
 	 			$options[$r->id]	=	$r->name;
 	 		}
 	 	}

 	 	$this->config_select_element($mform,'ilp_dashboard_reports_tab_default',$options,get_string('ilp_dashboard_reports_tab_default_tab', 'block_ilp'),'',0);


 	 	//get the name of the current class
 	 	$classname	=	get_class($this);

 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);

 	 	$this->config_select_element($mform,$classname.'_pluginstatus',$options,get_string($classname.'_name', 'block_ilp'),get_string('tabstatusdesc', 'block_ilp'),0);

 	 }


}
