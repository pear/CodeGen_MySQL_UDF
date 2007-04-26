<?php
/**
 * Class describing a function within a UDF extension 
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_UDF
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen_MySQL_UDF
 */

/** 
 * includes
 */
require_once "CodeGen/Tools/Code.php";

/**
 * Class describing a function within a UDF extension 
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_UDF
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_MySQL_UDF
 */
class CodeGen_MySQL_UDF_Element_Function 
    extends CodeGen_Element 
{
    /**
     * The function name
     *
     * @var     string
     */
    protected $name  = "unknown";

    /**
     * Name setter
     *
     * @param  string  function name
     * @return bool    success status
     */
    function setName($name) 
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid function name");
        }
            
        // keywords are not allowed as function names
        if (self::isKeyword($name)) {
            return PEAR::raiseError("'$name' is a reserved word which is not valid for function names");
        }

        $this->name = $name;
            
        return true;
    }


    /**
     * Name getter
     *
     * @return  string  function name
     */
    function getName() 
    {

        return $this->name;
    }






    /**
     * Function type: normal or aggregate
     *
     * @var    string  
     */
    protected $type = "normal";

    /**
     * Function type setter
     *
     * @param  string  "normal" or "aggregate"
     * @return bool    success status
     */        
    function setType($type) 
    {
        switch ($type) {
        case "regular":
            $this->type = "normal";
            return true;
        case "normal":
        case "aggregate":
            $this->type = $type;
            return true;

        default:
            return PEAR::raiseError("'$type' is not a valid function type");
        }
    }


    /**
     * max. Length of return value
     *
     * @var    mixed
     */
    protected $length = false;

    /**
     * Length setter
     *
     * @param  mixed  max. lengt as int or flase if not applicable
     * @return bool    success status
     */
    function setLength($length) 
    {
        if (!is_numeric($length) || $length != (int)$length || $length <= 0) {
            return PEAR::raiseError("length attribute needs an integer value greater than zero");
        }

        $this->length = $length;

        return true;
    }


    /**
     * decimal digits for REAL return values
     *
     * @var    mixed
     */
    protected $decimals = false;

    /**
     * Decimals setter
     *
     * @param  mixed  max. lengt as int or flase if not applicable
     * @return bool    success status
     */
    function setDecimals($decimals) 
    {
        if (!is_numeric($decimals) || $decimals != (int)$decimals || $decimals < 0) {
            return PEAR::raiseError("decimals attribute needs a non-negative integer value");
        }

        $this->decimals = $decimals;

        return true;
    }


    /**
     * Function may return NULL values?
     *
     * @var    bool
     */
    protected $null = 0;

    /**
     * NULL setter
     *
     * @param  bool  truth value
     * @return bool   success status
     */
    function setNull($null) 
    {
        $this->null = $null;
    }


    /**
     * Function returntype
     *
     * @var     string
     */
    protected $returns = "string";

        
    /**
     * Return type setter
     *
     * one of "string", "int", "real", or "datetime"
     *
     * @param  string  return type
     * @return bool    success status
     */
    function setReturns($returns) 
    {
        $returns = strtolower($returns);

        switch ($returns) {
        case "string":
            if (!$this->length) {
                $this->length = 255;
            }
            $this->addDataElement("_resultbuf",     "char *",          "NULL");
            $this->addDataElement("_resultbuf_len", "unsigned long",   "0L");
            // fallthru
        case "int":
            $this->returns = $returns;
            return true;
        case "real":
            $this->returns = $returns;
            $this->decimals = 14; 
            return true;
        case "datetime":
            $this->returns = "string";
            $this->length  = 20;
            return true;
        default:
            return PEAR::raiseError("'$returns' is not a valid return type");
        }
    }



    /**
     * Function parameters 
     *
     * @var     array
     */
    protected $params = array();

    /**
     * Number of mandatory parameters 
     *
     * @var   int
     */
    protected $mandatoryParams = 0;

    /**
     * Number of optional parameters 
     *
     * @var   int
     */
    protected $optionalParams  = 0;

    /**
     * Total number of parameters 
     *
     * @var   int
     */
    protected $totalParams     = 0;

    /**
     * Add a function parameter to the parameter list
     *
     * @param  string  parameter name
     * @param  string  parameter type
     * @param  string  optional?
     * @param  string  default value
     * @return bool    success status
     */
    function addParam($name, $type, $optional = null, $default = null) 
    {
        if (isset($this->params[$name])) {
            return PEAR::raiseError("duplicate parameter name '$name'");
        }

        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid parameter name");
        }
            
        if ($name == "data") {
            return PEAR::raiseError("'data' is a reserved name");
        }
            
        // keywords are not allowed as parameter names
        if (self::isKeyword($name)) {
            return PEAR::raiseError("'$name' is a reserved word which is not valid for data element names");
        }

        $type = strtolower($type);
        switch ($type) {
        case "int":
        case "real":
        case "string":
        case "datetime":
            break;
        default:
            return PEAR::raiseError("'$type' is not a valid parameter type");
        }

        if ($optional !== null) {
            switch (strtolower($optional)) {
            case "yes":
            case "true":
            case "1":
                $optional = true;
                break;
                    
            case "no":
            case "false":
            case "0":
                $optional = false;
                break;
                    
            default:
                return PEAR::raiseError("'$optional' is not a valid value for the 'optional' attribute");
            }
        } 
            
        if ($optional) {
            $this->optionalParams++;
        } else {
            if ($this->optionalParams) {
                return PEAR::raiseError("only optional parameters are allowed after the first optonal");
            }
            if ($default !== null) {
                return PEAR::raiseError("only optional parameters may have default values");
            }
            $this->mandatoryParams++;
        }

        $this->totalParams++;

        $this->params[$name] = array("type"=>$type, "optional"=>$optional, "default"=>$default);

        return true;
    }

    /**
     * Private data elements
     *
     * @var    array
     */
    protected $dataElements = array();

    /**
     * Add an element to the functions private data structure
     *
     * @param  string  element name
     * @param  string  element type
     * @param  stirng  default value
     * @return bool    success status
     */
    function addDataElement($name, $type, $default=false) 
    {
        if (isset($this->dataElements[$name])) {
            return PEAR::raiseError("duplicate data element name '$name'");
        }

        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid data element name");
        }
            
        // keywords are not allowed as data element names
        if (self::isKeyword($name)) {
            return PEAR::raiseError("'$name' is a reserved word which is not valid for data element names");
        }

        $this->dataElements[$name] = array("type"=>$type, "default"=>$default);

        return true;
    }
    
        
        


    /**
     * Code snippet
     *
     * @var     string
     */
    protected $code = "";

    /**
     * Function code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setCode($text)
    {
        $this->code = $text;
        return true;
    }


    /**
     * Code snippet for init function
     *
     * @var    string
     */
    protected $initCode;

    /**
     * Function init code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setInitCode($code) 
    {
        $this->initCode = $code;
        return true;
    }


    /**
     * Code snippet for deinit function
     *
     * @var    string
     */
    protected $deinitCode;

    /**
     * Function deinit code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setDeinitCode($code) 
    {
        $this->deinitCode = $code;
        return true;
    }


    /**
     * Code snippet for aggregate start function
     *
     * @var    string
     */
    protected $startCode;

    /**
     * Function aggregate start code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setStartCode($code) 
    {
        if ($this->type != "aggregate") {
            return PEAR::raiseError("start function can only be set for aggregate functions");
        }

        $this->startCode = $code;
        return true;
    }

    /**
     * Code snippet for aggregate add function
     *
     * @var    string
     */
    protected $addCode;

    /**
     * Function aggregate add code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setAddCode($code) 
    {
        if ($this->type != "aggregate") {
            return PEAR::raiseError("add function can only be set for aggregate functions");
        }

        $this->addCode = $code;
        return true;
    }


    /**
     * Code snippet for aggregate clear function
     *
     * @var    string
     */
    protected $clearCode;

    /**
     * Function aggregate clear code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setClearCode($code) 
    {
        if ($this->type != "aggregate") {
            return PEAR::raiseError("clear function can only be set for aggregate functions");
        }

        $this->clearCode = $code;
        return true;
    }



    /**
     * Code snippet for aggregate result function
     *
     * @var    string
     */
    protected $resultCode;

    /**
     * Function aggregate result code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setResultCode($code) 
    {
        if ($this->type != "aggregate") {
            return PEAR::raiseError("result function can only be set for aggregate functions");
        }

        $this->resultCode = $code;
        return true;
    }



    /**
     * Generate code for this user definend function
     *
     * @param   object  Extension object
     * @return  string  C code
     */
    function cCode($extension) 
    {
        $codegen = new CodeGen_Tools_Code();
        $codegen->setLanguage($extension->getLanguage());

        // private data access
        if (count($this->dataElements)) {
            $dataCode = "    struct {$this->name}_t *data = (struct {$this->name}_t *)initid->ptr;\n\n";
        } else {
            $dataCode = "";
        }

        // generate param variables decalaration and initialization
        ob_start(); // we are going to reuse this part for aggregates
            
        // parameter access variables
        foreach ($this->params as $name => $param) {
            if ($param['default'] === null) {
                switch ($param['type']) {
                case 'string':
                    echo "    char *$name = NULL;\n";
                    echo "    long long {$name}_len = 0;\n";
                    break;
                case 'int':
                    echo "    long long $name = 0;\n";
                    break;
                case 'real':
                    echo "    double $name = 0.0;\n";
                    break;
                case 'datetime':
                    echo "    MYSQL_TIME $name;\n";
                    // TODO init
                    break;
                }
                echo "    int {$name}_is_null = 1;\n";
            } else {
                $default = $param["default"];
                switch ($param['type']) {
                case 'string':
                    echo "    char *$name = \"$default\";\n";
                    echo "    long long {$name}_len = ".strlen($default).";\n";
                    break;
                case 'int':
                    echo "    long long $name = $default;\n";
                    break;
                case 'real':
                    echo "    double $name = $default;\n";
                    break;
                case 'datetime':
                    echo "    MYSQL_TIME $name;\n";
                    // TODO default
                    break;
                }
                echo "    int {$name}_is_null = 0;\n";
            }

        }
        echo "\n";

        //parameter value access
        $n = 0;
        foreach ($this->params as $name => $param) {
            if ($n >= $this->mandatoryParams) {
                echo "    if (args->arg_count > $n) {\n";
                $ind = "    ";
            } else {
                $ind = "";
            }

            echo "$ind    {$name}_is_null = (args->args[$n]==NULL);\n";

            switch ($param['type']) {
            case 'string':
                echo "$ind    $name = (char *)args->args[$n];\n";
                echo "$ind    {$name}_len = (args->args[$n] == NULL) ? 0 : args->lengths[$n];\n";
                break;

            case 'int':
                echo "$ind    $name = (args->args[$n] == NULL) ? 0 : *((long long *)args->args[$n]);\n";
                break;

            case 'real':
                echo "$ind    $name = (args->args[$n] == NULL) ? 0.0 : *((double *)args->args[$n]);\n";
                break;

            case 'datetime':
                // TODO rework
                echo "$ind    if (args->args[$n] != NULL) {\n";
                echo "$ind        int was_cut;\n";
                echo "$ind        switch (str_to_datetime((const char *)args->args[{$n}], args->lengths[{$n}], &{$name}, TIME_FUZZY_DATE, &was_cut)) {\n";
                echo "$ind          case MYSQL_TIMESTAMP_NONE:\n";
                echo "$ind          case MYSQL_TIMESTAMP_ERROR:\n";
                echo "$ind            break;\n\n";
                echo "$ind          default:\n";
                echo "$ind            {$name}_is_null = 0;\n";
                echo "$ind            break;\n";
                echo "$ind        }\n";
                echo "$ind    }\n";
                break;
            }

            if ($n >= $this->mandatoryParams) {
                echo "    }\n";
            }
                
            $n++;
        }

        $paramCode = ob_get_contents();
        ob_end_clean();



        ob_start();

        // init function
        echo "/* {$this->name} init function */\n";
        echo "my_bool {$this->name}_init(UDF_INIT *initid, UDF_ARGS *args, char *message)\n{\n";
        echo '    DBUG_ENTER("'.$extension->getName()."::{$this->name}_init\");\n";
            
        if (count($this->dataElements)) {
            echo "    struct {$this->name}_t *data = (struct {$this->name}_t *)calloc(sizeof(struct {$this->name}_t), 1);\n\n";
        }

        echo $paramCode;

        if (count($this->dataElements)) {
            echo "    if (!data) {\n";
            echo "        strcpy(message, \"out of memory in {$this->name}()\");\n";
            echo "        DBUG_RETURN(1);\n";
            echo "    }\n\n";
            foreach ($this->dataElements as $name => $data) {
                if (is_string($data["default"]) && strlen($data["default"])) {
                    echo "    data->$name = $data[default];\n";
                }
            }
            echo "    initid->ptr = (char *)data;\n\n";
        }

        echo "    initid->maybe_null = {$this->null};\n";
        if ($this->length !== false) {
            echo "    initid->max_length = {$this->length};\n";
        }
        if ($this->decimals !== false && $this->returns == "real") {
            echo "    initid->decimals = {$this->decimals};\n";
        }
        echo "\n";
                
        // check parameter count
        if ($this->optionalParams) {
            echo "    if ((args->arg_count < {$this->mandatoryParams}) || (args->arg_count > {$this->totalParams})) {\n";
        } else {
            echo "    if (args->arg_count != {$this->totalParams}) {\n";
        }
        echo "        strcpy(message,\"wrong number of parameters for {$this->name}()\");\n";
        echo "        DBUG_RETURN(1);\n";
        echo "    }\n";
            
        // parameter types
        $n = 0;
        foreach ($this->params as $name => $param) {
            echo "    ";
            if ($n >= $this->mandatoryParams) {
                echo "if (args->arg_count > $n) ";
            }
            echo "args->arg_type[$n] = ";
            switch ($param['type']) {
            case "int":
            case "real":
            case "string":
                echo strtoupper($param['type']);
                break;
            case "datetime":
                echo "STRING";
                break;
            }
            echo "_RESULT;\n";

            $n++;
        }
        echo "\n";

        // custom code
        if ($this->initCode) {
            echo $codegen->varblock($this->initCode, 1);
            echo "\n";
        }
            
        echo "    DBUG_RETURN(0);\n";
        echo "}\n\n";


        // deinit function
        echo "/* {$this->name} deinit function */\n";
        echo "void {$this->name}_deinit(UDF_INIT *initid)\n{\n";
        echo "    DBUG_ENTER(\"".$extension->getName()."::{$this->name}_deinit\");\n";

        if (count($this->dataElements)) {
            echo "    struct {$this->name}_t *data = (struct {$this->name}_t *)(initid->ptr);\n\n";
        }

        if ($this->deinitCode) {
            echo $codegen->block($this->deinitCode, 1);
            echo "\n";
        }
        echo "    if (initid->ptr) {\n";
        // TODO free string stuff (or general data cleanup?)
        echo "        free(initid->ptr);\n";
        echo "    }\n";
        echo "    DBUG_VOID_RETURN;\n";
        echo "}\n\n";




        // result function
        echo "/* {$this->name} actual processing function */\n";
        echo $this->returnType()." {$this->name}(UDF_INIT *initid, UDF_ARGS *args,";
        if ($this->returnType() == "char *") {
            echo " char *result, unsigned long *length,";
        }
        echo " char *is_null, char *error)\n";
        echo "{\n";

        echo "    DBUG_ENTER(\"".$extension->getName()."::{$this->name}\");\n";


        echo $dataCode;
            
        echo $paramCode;

        if ($this->type == "aggregate") {
            echo $codegen->varblock($this->resultCode, 1);
        } else {
            echo $codegen->varblock($this->code, 1);
        }

        echo "}\n\n";




        // additional functions for aggregates
        if ($this->type == "aggregate") {

            // add function
            echo "/* {$this->name} aggregate add function */\n";
            echo "void {$this->name}_add(UDF_INIT* initid, UDF_ARGS* args, char* is_null, char *error )\n";
            echo "{\n";
            echo "    DBUG_ENTER(\"".$extension->getName()."::{$this->name}_add\");\n";
            echo $dataCode;
            echo $paramCode;
            echo $codegen->varblock($this->addCode, 1);             
            echo "    DBUG_VOID_RETURN;\n";
            echo "}\n\n";

            // reset function
            echo "/* {$this->name} aggregate reset function */\n";
            echo "void {$this->name}_reset(UDF_INIT* initid, UDF_ARGS* args, char* is_null, char *error )\n";
            echo "{\n";
            echo "    DBUG_ENTER(\"".$extension->getName()."::{$this->name}_reset\");\n";
            echo $dataCode;
            echo $paramCode;
            echo $codegen->varblock($this->startCode, 1);
            echo "    DBUG_VOID_RETURN;\n";
            echo "}\n\n";

            // clear function
            echo "/* {$this->name} aggregate clear function */\n";
            echo "void {$this->name}_clear(UDF_INIT* initid, char* is_null, char *error )\n";
            echo "{\n";
            echo "    DBUG_ENTER(\"".$extension->getName()."::{$this->name}_clear\");\n";
            echo $dataCode;
            echo $codegen->varblock($this->clearCode, 1);
            echo "    DBUG_VOID_RETURN;\n";
            echo "}\n\n";
        }

        $code = ob_get_contents();
        ob_end_clean();
        return $code;
    }
        


    /**
     * Generate function prototypes for this user definend function
     *
     * @return  string  C code
     */
    function cPrototype() 
    {   
        $return = $this->returnType();

        $result = "/* FUNCTION {$this->name} */\n";

        $result.= "my_bool {$this->name}_init(UDF_INIT *initid, UDF_ARGS *args, char *message);\n";

        if ($this->type == "aggregate") {
            $result.= "void {$this->name}_reset( UDF_INIT* initid, UDF_ARGS* args, char* is_null, char *error );\n";
            $result.= "void {$this->name}_add( UDF_INIT* initid, UDF_ARGS* args, char* is_null, char *error );\n";
            $result.= "void {$this->name}_clear( UDF_INIT* initid, char* is_null, char* error);\n";
        } 

        $result.= "{$return} {$this->name}(UDF_INIT *initid, UDF_ARGS *args,";
        if ($this->returnType() == "char *") {
            $result.= " char *result, unsigned long *length,";
        }
        $result.= " char *is_null, char *error);\n";

        $result.= "void {$this->name}_deinit(UDF_INIT *initid);\n";

        return "$result\n";
    }
        

    /**
     * Generate data structure definition for the functions private data
     *
     * @return  string  C code
     */
    function cData() 
    {
        if (empty($this->dataElements)) {
            return "";
        }

        $return = "struct {$this->name}_t {\n";
            
        foreach ($this->dataElements as $name => $data) {
            $return.= "  $data[type] $name;\n";
        }

        $return.= "};\n\n";

        return $return;
    }


        
    /**
     * The C return type of this function
     *
     * @return  string  C code
     */
    function returnType() 
    {
        switch ($this->returns) {
        case "int":
            return "long long";
            break;
        case "real":
            return "double";
            break;
        case "string":
        case "datetime":
            return "char *";
            break;
        default:
            return "???";
            break;          
        }
    }


    /**
     * Return SQL function signature of this UDF
     *
     * @param  void
     * @return string
     */
    function signature() 
    {
        $signature = "";
        if ($this->type == "aggregate") {
            $signature.= "AGGREGATE ";
        }
        $signature.= "FUNCTION {$this->name} RETURNS ";
        switch($this->returns) {
        case "int":
            $signature.= "INTEGER";
            break;
        case "real":
            $signature.= "REAL";
            break;
        case "string":
        case "datetime":
            $signature.= "STRING";
            break;
        }

        return $signature;
    }

    /**
     * Return SQL CREATE FUNCTION statement for this function
     *
     * @param  object  Extension to generate for
     * @return string
     */
    function createStatement($extension) 
    {
        $create = "CREATE ";
        $create.= $this->signature();
        $create.= ' SONAME "'.$extension->getName().'.so";';

        return $create;
    }

    /**
     * Return SQL DROP FUNCTION statement for this function
     *
     * @param  object  Extension to generate for
     * @return string
     */
    function dropStatement($extension) 
    {
        return "DROP FUNCTION {$this->name};";
    }

    /**
     * Return SQL conditional DROP FUNCTION statement for this function
     *
     * @param  object  Extension to generate for
     * @return string
     */
    function dropIfExistsStatement($extension) 
    {
        /* there is no DROP FUNCTION IF EXISTS in MySQL < 5.0 so we
         check for the error code for dropping a noneexistant function
         which is 1128 in 4.1 and 1305 in 5.0 ... */
        return "--error 0, 1128, 1305\nDROP FUNCTION {$this->name};";
    }


    /**
     * test code snippet
     *
     * @var string
     */
    protected $testCode = "";
    
    /**
     * testCode setter
     *
     * @param  string code snippet
     */
    function setTestCode($code)
    {
        $this->testCode = $code;
    }
    
    /**
     * testCode getter
     *
     * @return string
     */
    function getTestCode()
    {
        return $this->testCode;
    }
    
    
    /**
     * expected test result string
     *
     * @var array
     */
    protected $testResult = "";
    
    /**
     * testResult setter
     *
     * @param  string result text
     */
    function setTestResult($text)
    {
        $this->testResult = $text;
    }
    
    /**
     * testResult getter
     *
     * @return string
     */
    function getTestResult()
    {
        return $this->testResult;
    }
    
    
    /**
     * test code description
     *
     * @var string
     */
    protected $testDescription = "";
    
    /**
     * testDescritpion setter
     *
     * @param  string text
     */
    function setTestDescription($text)
    {
        $this->testDescription = $text;
    }
    
    /**
     * testDescription getter
     *
     * @return string
     */
    function getTestDescription()
    {
        return $this->testDescription;
    }
    
    
    /**
     * write test case for this function
     *
     * @access public
     * @param  class Extension  extension the function is part of
     */
    function writeTest(CodeGen_MySQL_UDF_Extension $extension) 
    {
        $test = $this->createTest($extension);
      
        if ($test instanceof CodeGen_MySQL_Element_Test) {
            $test->writeTest($extension);
        }
    }
    
    /**
     * Create test case for this function
     *
     * @access public
     * @param  object  extension the function is part of
     * @return object  generated test case
     */
    function createTest(CodeGen_MySQL_UDF_Extension $extension) 
    {
        if (!$this->testCode) {
            return false;
        }
      
        $test = new CodeGen_MySQL_UDF_Element_Test;
            
        $test->setName($this->name);

        if ($this->testDescription) {
            $test->setDescription($this->testDescription);
        }
            
        $test->setCode($this->testCode);
      
        if (!empty($this->testResult)) {
            $test->setResult($this->testResult);
        }
      
        return $test;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
