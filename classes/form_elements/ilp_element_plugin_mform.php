<?php

abstract class ilp_element_plugin_mform extends ilp_moodleform {
	
	public		$report_id;
	public 		$plugin_id;
	public 		$creator_id;
	public 		$course_id;
	public 		$dbc;
	
	
	function __construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null) {
		global $CFG;
		
		$this->report_id		=	$report_id;
		$this->plugin_id		=	$plugin_id;
		$this->creator_id		=	$creator_id;
		$this->course_id		=	$course_id;
		$this->reportfield_id	=	$reportfield_id;
		$this->dbc				=	new ilp_db();
		
		parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_field.php?course_id={$course_id}&plugin_id={$plugin_id}&report_id={$report_id}");

	}
	
	function definition() {
        global $USER, $CFG;

        $mform =& $this->_form;
        $fieldsettitle	=	get_string("addfield",'block_ilp');
        
        //define the elements that should be present on all plugin element forms

		//create a fieldset to hold the form        
        $mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
        $mform->addElement('html', '<legend class="ftoggler">'.$fieldsettitle.'</legend>');       	
        
        //the id of the report that the element will be in
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        //button to state whether the element is required
        $mform->addElement('checkbox', 
        				   'req', 
        					get_string('req', 'block_ilp')
        );
        
        
        //the id of the report that the element will be in
        $mform->addElement('hidden', 'report_id');
        $mform->setType('report_id', PARAM_INT);
        $mform->setDefault('report_id', $this->report_id);
        
        //the id of the report that the element will be in
        $mform->addElement('hidden', 'report_id');
        $mform->setType('report_id', PARAM_INT);
        $mform->setDefault('report_id', $this->report_id);
        
        //the id of the plugin in use
        $mform->addElement('hidden', 'plugin_id');
        $mform->setType('plugin_id', PARAM_INT);
        $mform->setDefault('plugin_id', $this->plugin_id);
        
        //the id of the form element creator
        $mform->addElement('hidden', 'creator_id');
        $mform->setType('creator_id', PARAM_INT);
        $mform->setDefault('creator_id', $this->creator_id);
        
        //the id of the course that the element is being created in
        $mform->addElement('hidden', 'course_id');
        $mform->setType('course_id', PARAM_INT);
        $mform->setDefault('course_id', $this->course_id);
        
        
        //the id of the reportfield this is only used in edit instances
        $mform->addElement('hidden', 'reportfield_id');
        $mform->setType('reportfield_id', PARAM_INT);
        $mform->setDefault('reportfield_id', $this->reportfield_id);
        
        //the id of the form element creator
        $mform->addElement('hidden', 'position');
        $mform->setType('position', PARAM_INT);
        //set the field position of the field
        $mform->setDefault('position', $this->dbc->get_new_report_field_position($this->report_id));
        
       	//text field for element label
        $mform->addElement(
            'text',
            'label',
            get_string('label', 'block_ilp'),
            array('class' => 'form_input')
        );
        
        $mform->addRule('label', null, 'maxlength', 255, 'client',array('size'=>'10'));
        $mform->addRule('label', null, 'required', null, 'client');
        $mform->setType('label', PARAM_RAW);
        
        
       	//text field for element label
        $mform->addElement(
            'textarea',
            'description',
            get_string('description', 'block_ilp'),
            array('class' => 'form_input')
        );
        
        $mform->addRule('description', null, 'maxlength', 1000, 'client');
        $mform->addRule('description', null, 'required', null, 'client');
        $mform->setType('description', PARAM_RAW);
        
        
        $this->specific_definition($mform);

        //add the submit and cancel buttons
        $this->add_action_buttons(true, get_string('submit'));
	} 
	 
	 /**
     * Force extending class to add its own form fields
     */
    abstract protected function specific_definition($mform);

    /**
     * Performs server-side validation of the unique constraints.
     *
     * @param object $data The data to be saved
     */
    function validation($data) {
        $this->errors = array();

        // now add fields specific to this type of evidence
        $this->specific_validation($data);

        return $this->errors;
    }

    /**
     * Force extending class to add its own server-side validation
     */
    abstract protected function specific_validation($data);

    /**
     * Saves the posted data to the database.
     *
     * @param object $data The data to be saved
     */
    function process_data($data) {
        if (empty($data->id)) {
            //create the ilp_report_field record
        	$data->id	=	$this->dbc->create_report_field($data);
        } else {
        	//update the report

        	$reportfield	=	$this->dbc->update_report_field($data);
	    }

        if(!empty($data->id)) {
        	$data->reportfield_id = $data->id;
        	
            $this->specific_process_data($data);
        }
        return $data->id;
    }

    /**
     * Force extending class to add its own processing method
     */
    abstract protected function specific_process_data($data);
}


?>
