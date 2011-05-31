<?php
/*
* itemlists are dropdowns and radio/checkbox groups
*/
require_once( $CFG->dirroot . '/blocks/ilp/classes/form_elements/ilp_element_plugin.php' );

class ilp_element_plugin_itemlist extends ilp_element_plugin{


    public function __construct(){
		
    	parent::__construct();
	    
    	}
		
    	public function test(){
			$msg = $this->tablename;
			$reportfield_id = 1;
			$entry_id = false;
		 	$pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
		 	$entry 	=	$this->dbc->get_data_entry_record( $this->tablename, $entry_id );
			$data = new stdClass();
			$data->$reportfield_id = array( 'groucho' , 'harpo' );
			return $this->entry_process_data( $reportfield_id, $entry_id, $data );
		}
		
		/**
	    * this function saves the data entered on a entry form to the plugins _entry table
		* the function expects the data object to contain the id of the entry (it should have been
		* created before this function is called) in a param called id. 
		* as this is a select element, possibly a multi-select, we have to allow
		* for the possibility that the input is an array of strings
	    */
	  	public	function entry_process_data($reportfield_id,$entry_id,$data) {
	 	
	  		$result	=	true;
	  		
		  	//create the fieldname
			$fieldname =	$reportfield_id."_field";
	
		 	//get the plugin table record that has the reportfield_id 
		 	$pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
		 	if (empty($pluginrecord)) {
		 		print_error('pluginrecordnotfound');
		 	}
		 	
		 	//check to see if a entry record already exists for the reportfield in this plugin
		 	$entrydata 	=	$this->dbc->get_pluginentry($this->tablename, $entry_id,$reportfield_id,true);
		 	
		 	//if there are records connected to this entry in this reportfield_id 
			if (!empty($entrydata)) {
				//delete all of the entries
				foreach ($entrydata as $e)	{
					$this->dbc->delete_element_record_by_id($this->data_entry_tablename,$e->id);
				}
			}  
		 	
			//create new entries
			$pluginentry			=	new stdClass();
			$pluginentry->entry_id  = 	$entry_id;
	 		$pluginentry->value		=	$data->$fieldname;
	 		$pluginentry->parent_id	=	$pluginrecord->id;
	 		
			if( is_string( $pluginentry->value ))	{
	 			$result	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
			} else if (is_array( $pluginentry->value ))	{
				$result	=	$this->write_multiple( $this->data_entry_tablename, $pluginentry );
			}
 
	 	
			return	$result;
	 }
	 
	 /**
	  * places entry data for the report field given into the entryobj given by the user 
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
	  */
	 public function entry_data( $reportfield_id,$entry_id,&$entryobj ){
	 	//this function will suffix for 90% of plugins who only have one value field (named value) i
	 	//in the _ent table of the plugin. However if your plugin has more fields you should override
	 	//the function 
	 	
		//default entry_data 	
	 	$fieldname	=	$reportfield_id."_field";
	 	
	 	
	 	$entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id,true);
 
	 	if (!empty($entry)) {
		 	$fielddata	=	array();

		 	//loop through all of the data for this entry in the particular entry		 	
		 	foreach($entry as $e) {
		 		$fielddata[]	=	$e->value;
		 	}
		 	
		 	//save the data to the objects field
	 		$entryobj->$fieldname	=	$fielddata;
	 	}
	 }

	 
	 protected function write_multiple( $tablename, $multi_pluginentry ){
		//if we're here, assume $pluginentry->value is array
		$pluginentry = $multi_pluginentry;
		$result		=	true;
		foreach( $multi_pluginentry->value as $value ){
			$pluginentry->value = $value;
			if ($this->dbc->create_plugin_entry( $this->data_entry_tablename, $pluginentry )) $result = false;
		}
		//if any of the didn't work $result will be false
		return $result;
	 }
	 
     public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);	
		if (!empty($reportfield)) {
			$this->reportfield_id	=	$reportfield_id;
			$this->plugin_id	=	$reportfield->plugin_id;
			$plugin			=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
			$pluginrecord		=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);
			if (!empty($pluginrecord)) {
				$this->id			=	$pluginrecord->id;
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;
				$this->required			=	$reportfield->req;
				$this->position			=	$reportfield->position;
			}
		}
		return false;	
    }	

	/*
	* get the list options with which to populate the edit element for this list element
	*/
	public function return_data( &$reportfield ){
		$data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $reportfield->id );
		if( empty( $data_exists ) ){
			//if no, get options list
			$reportfield->optionlist = $this->get_option_list_text( $reportfield->id );
		}
		else{
			$reportfield->existing_options = $this->get_option_list_text( $reportfield->id , '<br />' );
		}
	}
	
	protected function get_option_list_text( $reportfield_id , $sep="\n" ){
		$optionlist = $this->get_option_list( $reportfield_id );
		$rtn = '';
		if( !empty( $optionlist ) ){
			foreach( $optionlist as $key=>$value ){
				$rtn .= "$key:$value$sep";
			}
		}
		return $rtn;
	}

	protected function get_option_list( $reportfield_id ){
		//return $this->optlist2Array( $this->get_optionlist() );   	
		$outlist = array();
		if( $reportfield_id ){
			$objlist = $this->dbc->get_optionlist($reportfield_id , $this->tablename );
			foreach( $objlist as $obj ){
				$outlist[ $obj->value ] = $obj->name;
			}
		}
		if( !count( $outlist ) ){
			echo "no items in {$this->items_tablename}";
		}
		return $outlist;
	}

    public static function optlist2Array( $optstring ){
		//split on lines
		$optsep = "\n";
		$keysep = ":";
		$optlist = explode( $optsep , $optstring );
		//now split each entry into key and value
		$outlist = array();
		foreach( $optlist as $row ){
			if( $row ){
				$row = explode( $keysep, $row );
				$key = trim( $row[0] );
				if( 1 == count( $row ) ){
					$value = trim( $row[0] );
				}
				elseif( 1 < count( $row ) ){
					$value = trim( $row[1] );
				}
				$outlist[ $key ] = $value;
			}
		}
		return $outlist;
    }

    /**
    * this function returns the mform elements taht will be added to a report form
	*
    */
    public function entry_form( &$mform ) {
    	
    	//create the fieldname
    	$fieldname	=	"{$this->reportfield_id}_field";
    	
		//definition for user form
		$optionlist = $this->get_option_list( $this->reportfield_id );
    	//text field for element label
        $select = &$mform->addElement(
            'select',
            $fieldname,
            $this->label,
	    	$optionlist,
            array('class' => 'form_input')
        );
		
        if( OPTIONMULTI == $this->selecttype ){
			$select->setMultiple(true);
		}
        
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->setType('label', PARAM_RAW);

    }
    

    
    public function delete_form_element($reportfield_id) {
		return parent::delete_form_element($this->tablename,$reportfield_id); 
    }

    public function uninstall() {
        $table = new $this->xmldb_table( $this->tablename );
        drop_table($table);
        
        $table = new $this->xmldb_table( $this->data_entry_tablename );
        drop_table($table);
        if( $this->items_tablename ){
        	$table = new $this->xmldb_table( $this->items_tablename );
	}
        drop_table($table);
    }

    public function install() {
        global $CFG, $DB;

        // create the table to store report fields
        $table = new $this->xmldb_table( $this->tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_report = new $this->xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
        
        $table_optiontype = new $this->xmldb_field('selecttype');
        $table_optiontype->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, null);	//1=single, 2=multi cf blocks/ilp/constants.php
        $table->addField($table_optiontype);
        
        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('textplugin_unique_reportfield');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('reportfield_id'),'block_ilp_report_field','id');
        $table->addKey($table_key);
        

        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
	    // create the new table to store dropdown options
		if( $this->items_tablename ){
	        $table = new $this->xmldb_table( $this->items_tablename );
	
	        $table_id = new $this->xmldb_field('id');
	        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
	        $table->addField($table_id);
	        
	        $table_textfieldid = new $this->xmldb_field('parent_id');
	        $table_textfieldid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	        $table->addField($table_textfieldid);
	        
	        $table_itemvalue = new $this->xmldb_field('value');
	        $table_itemvalue->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
	        $table->addField($table_itemvalue);
	        
	        $table_itemname = new $this->xmldb_field('name');
	        $table_itemname->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
	        $table->addField($table_itemname);
	
	        $table_timemodified = new $this->xmldb_field('timemodified');
	        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	        $table->addField($table_timemodified);
	
	        $table_timecreated = new $this->xmldb_field('timecreated');
	        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	        $table->addField($table_timecreated);
	
	        $table_key = new $this->xmldb_key('primary');
	        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
	        $table->addKey($table_key);
	  /*      
	   */     
	/*
	       	$table_key = new $this->xmldb_key('textplugin_unique_textfield');
	        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('textfield_id'), $this->tablename, 'id');
	        $table->addKey($table_key);
	 */               
	/*
	        $table_key = new $this->xmldb_key('textplugin_unique_entry');
	        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('entry_id'),'block_ilp_entry','id');
	        $table->addKey($table_key);
	*/
	        
	        if(!$this->dbman->table_exists($table)) {
	            $this->dbman->create_table($table);
	        }
	}
        
	    // create the new table to store responses to fields
        $table = new $this->xmldb_table( $this->data_entry_tablename );

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
       
        $table_maxlength = new $this->xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);
        
        $table_item_id = new $this->xmldb_field('value');	//foreign key -> $this->items_tablename
        $table_item_id->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_item_id);

        $table_report = new $this->xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
        
        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);
        
/*
       	$table_key = new $this->xmldb_key('textplugin_unique_textfield');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('textfield_id'), $this->tablename, 'id');
        $table->addKey($table_key);
 */               
/*
        $table_key = new $this->xmldb_key('textplugin_unique_entry');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('entry_id'),'block_ilp_entry','id');
        $table->addKey($table_key);
*/
        
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
    }
}