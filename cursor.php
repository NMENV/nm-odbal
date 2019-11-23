<?php
namespace rastatech\odbal;
use \Exception;

/**
 * Abstraction of the Oracle cursor creation process / resource and related functionality for better maintainability/readability
 * 
 * @package \ODBAL
 * @author todd.hochman
 * @uses dbal_configurator a trait containing some configuration setting code that is used across several of the above classes
 */
class cursor
{
    /**
    *
    * @var Container_object    the Dependency Injection Container  
    */
    protected $ci;
    
    /**
    *
    * @var resource|resource-array	container(s) for the OUT CURSOR(s) if we have one or more
    *
    */
    public $out_cursor; 

    /**
    *
    * @var string|string-array		default placeholder name for the OUT CURSOR
    * @see config/database.php
    */
    public $outcursorName;
    
    /**
    * Configuration loading Trait
    */
    use configurator;
    
    public function __construct($ci, $model_sql_elements)
    {
        $this->ci = $ci;
        $loadedConfigs = $this->_get_configs($model_sql_elements);
        $this->_assign2classVars($loadedConfigs);
        $this->outcursorName = ((isset($this->out_cursor)) AND ( ! is_null($this->out_cursor))) ? $this->out_cursor : NULL;
    }

    /**
     * binds the cursor object or objects to the parsed statement resource
     *
     * @param resource $stmt the parsed statement resource
     * @return cursor the oracle object for chaining operations
     * @throws Exception
     */
    public function bind_cursor($stmt)
    {
        if(is_null($this->outcursorName)){
            return $this;
        }
        if( ! is_array($this->outcursorName)){
            if( ! is_null($this->outcursorName)){
                $bindCursor =  ':'. $this->outcursorName;
                $success = oci_bind_by_name($stmt, $bindCursor, $this->out_cursor, -1, SQLT_RSET);
            }
        }
        else{
            foreach($this->outcursorName as $outcursor_placeholder){
                if( ! is_null($outcursor_placeholder)){
                    $success[$outcursor_placeholder] = oci_bind_by_name($stmt, ':'. $outcursor_placeholder, $this->out_cursor[$outcursor_placeholder], -1, SQLT_RSET);
                }
            }
        } 
        if(( ! isset($success)) OR ( ! $success) OR ((is_array($success)) AND (in_array(FALSE, $success)))){
            $exception = oci_error($stmt);
            $message = preg_replace('~[[:cntrl:]]~', '', htmlentities($exception['message']));
            $this->ci['errorMessage'] = $message;
            $this->ci['errorCode'] = htmlentities($exception['code']);
            throw new Exception('OUT CURSOR binding failed!',528);
        }
        return $this;
    }

    /**
     * creates an oracle cursor resource for use in OUT CURSOR results;
     *
     * @param $conn connection the connection object
     * @return cursor object instance of the class for chaining operations
     * @throws Exception
     */
    public function create_cursor($conn)
    {
        if( ! is_array($this->outcursorName)){// Create a new cursor resource
            $this->out_cursor = oci_new_cursor($conn);
        }
        else{
            foreach($this->outcursorName as $outcursor_placeholder){
               $cursorArray[$outcursor_placeholder] = oci_new_cursor($conn);
            }
            $this->out_cursor = $cursorArray;
        }        
        if(( ! $this->out_cursor) OR (empty($this->out_cursor))){
            $exception = oci_error($conn);
            $message = preg_replace('~[[:cntrl:]]~', '', htmlentities($exception['message']));
            $this->ci['errorMessage'] = $message;
            $this->ci['errorCode'] = htmlentities($exception['code']);
            throw new Exception('OUT CURSOR creation failed!',529);
        }
        return $this;
    }      
}