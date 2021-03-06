<?PHP
namespace SiteWorks;
class t_site_works_lang extends siteworks_db_tools
{
    public function __construct($id = NULL, &$odb = NULL){
    	parent::__construct($odb);

	    $this->tableName    = 'site_works_lang';
	    $this->keyField     = 'sw_lang_key';
	    $this->autoInc      = true;
	
		$this->f = Array(
			 'sw_lang_key'                => array( 'value' => 0    , 'error' => null) // Key Field
			,'sw_lang_keep'               => array( 'value' => 0    , 'error' => null) // If true, it will not be removed by automated system.
		    ,'sw_lang_category'           => array( 'value' => null , 'error' => null) // SiteWorks Language Category|key|option name
		    ,'sw_origional'               => array( 'value' => null , 'error' => null) // This will be the developers text
		    ,'english'                    => array( 'value' => null , 'error' => null) // Default Lang - add more as neccessary.
	    );

    	$this->fillData($id);
		return true;
    }

    public function buildQueryArray($sqlName=false){
        switch ($sqlName) {

        	// Set up your queries here that can be called by name. Example below
	        case 'GET_FIRST_KEY':
	            $sqlFn = 'SELECT * FROM `'. $this->tableName . '` WHERE `sw_lang_key` = 1';
			break;

	    default:
		    $sqlFn = NULL;
    	}
	    return $sqlFn;
    }
}
?>