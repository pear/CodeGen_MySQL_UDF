<?php
/**
 * A class that generates MySQL UDF soure and documenation files
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
// {{{ includes

require_once "CodeGen/MySQL/Extension.php";

require_once "System.php";
    
require_once "CodeGen/Element.php";
require_once "CodeGen/MySQL/UDF/Element/Function.php";
require_once "CodeGen/MySQL/UDF/Element/Test.php";

require_once "CodeGen/Maintainer.php";

require_once "CodeGen/License.php";

require_once "CodeGen/Tools/Platform.php";

require_once "CodeGen/Tools/Indent.php";

// }}} 

/**
 * A class that generates UDF extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_UDF
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_MySQL_UDF
 */
class CodeGen_MySQL_UDF_Extension 
    extends CodeGen_MySQL_Extension
{
    /**
    * Current CodeGen_MySQL_UDF version number
    * 
    * @return string
    */
    static function version() 
    {
        return "@package_version@";
    }

    /**
    * CodeGen_MySQL_UDF Copyright message
    *
    * @return string
    */
    static function copyright()
    {
        return "Copyright (c) 2003-2005 Hartmut Holzgraefe";
    }

    // {{{ member variables

    /**
     * The public UDF functions defined by this extension
     *
     * @var array
     */
    protected  $functions = array();
    

    // }}} 

    
    // {{{ constructor
    
    /**
     * The constructor
     *
     */
    function __construct() 
    {
        parent::__construct();

        $this->addConfigFragment("WITH_MYSQL()", "bottom");

        $this->addConfigFragment("MYSQL_USE_UDF_API()", "bottom");
    }
    
    // }}} 
    
    // {{{ member adding functions
    
    /**
     * Add a function to the extension
     *
     * @param  object   a function object
     */
    function addFunction(CodeGen_Mysql_UDF_Element_Function $function)
    {
        $name = $function->getName();

        if (isset($this->functions[$name])) {
            return PEAR::raiseError("public function '$name' has been defined before");
        }
        $this->functions[$name] = $function;
        return true;
    }


    // }}} 

    // {{{ output generation
        
    // {{{   docbook documentation

    // {{{ header file

    /**
     * Write the complete C header file
     *
     * @access protected
     */
    function writeHeaderFile() 
    {
        $filename = "udf_{$this->name}.h";
        
        $this->addPackageFile('header', $filename); 

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/".$filename);
        
        $upname = strtoupper($this->name);
        
        echo $this->getLicense();
        echo "#ifndef UDF_{$upname}_H\n";
        echo "#define UDF_{$upname}_H\n\n";   

        if (isset($this->code['header']['top'])) {
            echo "// {{{ user defined header code\n\n";
            foreach ($this->code['header']['top'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }

?>        

#define RETURN_NULL          { *is_null = 1; DBUG_RETURN(0); }

#define RETURN_INT(x)        { *is_null = 0; DBUG_RETURN(x); }

#define RETURN_REAL(x)       { *is_null = 0; DBUG_RETURN(x); }

#define RETURN_STRINGL(s, l) { \
  if (s == NULL) { \
    *is_null = 1; \
    DBUG_RETURN(NULL); \
  } \
  *is_null = 0; \
  *length = l; \
  if (l < 255) { \
    memcpy(result, s, l); \
    DBUG_RETURN(result); \
  } \
  if (l > data->_resultbuf_len) { \
    data->_resultbuf = realloc(data->_resultbuf, l); \
    if (!data->_resultbuf) { \
      *error = 1; \
      DBUG_RETURN(NULL); \
    } \
    data->_resultbuf_len = l; \
  } \
  memcpy(data->_resultbuf, s, l); \
  DBUG_RETURN(data->_resultbuf); \
}

#define RETURN_STRING(s) { \
  if (s == NULL) { \
    *is_null = 1; \
    DBUG_RETURN(NULL); \
  } \
  RETURN_STRINGL(s, strlen(s)); \
}

#define RETURN_DATETIME(d)   { *length = my_datetime_to_str(d, result); *is_null = 0; DBUG_RETURN(result); }


<?php
        if (isset($this->code['header']['bottom'])) {
            echo "// {{{ user defined header code\n\n";
            foreach ($this->code['header']['bottom'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }


        echo "#endif /* UDF_{$upname}_H */\n\n";

        return $file->write();
    }

    // }}} 



  // {{{ code file

    /**
     * Write the complete C code file
     *
     * @access protected
     */
    function writeCodeFile() {
        $filename = "{$this->name}.".$this->language;  

        $this->addPackageFile('c', $filename); 

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/".$filename);
        
        $upname = strtoupper($this->name);

        echo $this->getLicense();

        echo "// {{{ CREATE and DROP statements for this UDF\n\n";
        echo "/*\n";
        echo  "register the functions provided by this UDF module using\n";
        foreach ($this->functions as $function) {
            echo $function->createStatement($this)."\n";
        }
        echo  "\n";        
        echo  "unregister the functions provided by this UDF module using\n";        
        foreach ($this->functions as $function) {
            echo $function->dropStatement($this)."\n";
        }
        echo "*/\n// }}}\n\n";
        
        foreach ($this->headers as $header) {
            echo $header->hCode(true);
        }        
            
        echo 
"// {{{ standard header stuff
#ifdef STANDARD
#include <stdio.h>
#include <string.h>
#ifdef __WIN__
typedef unsigned __int64 ulonglong; /* Microsofts 64 bit types */
typedef __int64 longlong;
#else
typedef unsigned long long ulonglong;
typedef long long longlong;
#endif /*__WIN__*/
#else
#include <my_global.h>
#include <my_sys.h>
#endif
#include <mysql.h>
#include <m_ctype.h>
#include <m_string.h>       // To get strmov()

// }}}

";

        foreach ($this->headers as $header) {
            echo $header->hCode(false);
        }
        
        echo "#ifdef HAVE_DLOPEN\n\n";

        echo "#include \"udf_{$this->name}.h\"\n\n";

        if (isset($this->code['code']['top'])) {
            echo "// {{{ user defined code\n\n";
            foreach ($this->code['code']['top'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }


        echo "// {{{ prototypes\n\n";
        
        echo "#ifdef  __cplusplus\n";
        echo "extern \"C\" {\n";
        echo "#endif\n";

        foreach ($this->functions as $function) {
            echo $function->cPrototype();
        }

        echo "#ifdef  __cplusplus\n";
        echo "}\n";
        echo "#endif\n";

        echo "// }}}\n\n";


        echo "// {{{ UDF functions\n\n";
        foreach ($this->functions as $function) {
            echo "// {{{ ".$function->signature()."\n";
            echo $function->cData($this);
            echo $function->cCode($this);
            echo "// }}}\n\n";
        }        
        echo "// }}}\n\n";

        if (isset($this->code['code']['bottom'])) {
            echo "// {{{ user defined code\n\n";
            foreach ($this->code['code']['bottom'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }

        echo "#else\n";
        echo "#error your installation does not support loading UDFs\n";
        echo "#endif /* HAVE_DLOPEN */\n";

        echo $this->cCodeEditorSettings();

        return $file->write();
    }

    // }}} 


    /** 
    * Generate README file (custom or default)
    *
    * @param  protected
    */
    function writeReadme() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/README");

?>
This is a standalone UDF extension created using CodeGen_Mysql_UDF <?php echo self::version(); ?>

...
<?php

      return $file->write();
    }


    /** 
    * Generate INSTALL file (custom or default)
    *
    * @access protected
    */
    function writeInstall() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/INSTALL");

?>
This is a standalone UDF extension created using CodeGen_Mysql_UDF <?php echo self::version(); ?>

...
<?php

        $file->write();
    }

    
    function writeTests()
    {
        parent::writeTests();

        $this->addPackageFile("test", "tests/create_functions.inc");
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/tests/create_functions.inc");		
        echo "-- disable_warnings\n";
        foreach ($this->functions as $function) {
            echo $function->dropIfExistsStatement($this)."\n";
            echo $function->createStatement($this)."\n";
        }
        echo "-- enable_warnings\n";
        $file->write();

        $this->addPackageFile("test", "tests/drop_functions.inc");
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/tests/drop_functions.inc");		
        foreach ($this->functions as $function) {
            echo $function->dropStatement($this)."\n";
        }
        $file->write();

        // function related tests
        foreach ($this->functions as $function) {
            $function->writeTest($this);
        }

    }

    function testFactory()
    {
        return new CodeGen_MySQL_UDF_Element_Test(); 
    }
}   


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
