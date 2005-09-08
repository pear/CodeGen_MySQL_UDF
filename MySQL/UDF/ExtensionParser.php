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
require_once "CodeGen/ExtensionParser.php";
require_once "CodeGen/Maintainer.php";
require_once "CodeGen/Tools/Indent.php";

/**
 * A class that generates MySQL UDF soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_UDF
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_MySQL_UDF
 */
class CodeGen_MySQL_UDF_ExtensionParser 
    extends CodeGen_ExtensionParser
{
    function tagstart_udf($attr) 
    {
        return $this->tagstart_extension($attr);
    }
    
    
    function tagstart_function($attr)
    {
        $this->pushHelper(new CodeGen_Mysql_UDF_Element_Function);
        
        if (isset($attr["name"])) {
            $err = $this->helper->setName($attr["name"]);
            if (PEAR::isError($err)) {
                return $err;
            }
        } else {
            return PEAR::raiseError("name attribut for function missing");
        }
        
        if (isset($attr['type'])) {
            $err = $this->helper->setType($attr['type']);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
        
        if (isset($attr["returns"])) {
            $err = $this->helper->setReturns($attr["returns"]);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
        
        if (isset($attr['null'])) {
            $err = $this->helper->setNull($attr['null']);
            if (PEAR::isError($err)) {
                return $err;
            }
        }

        if (isset($attr['length'])) {
            $err = $this->helper->setLength($attr['length']);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
        
        if (isset($attr['decimals'])) {
            $err = $this->helper->setDecimals($attr['decimals']);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
        
        return true;
    }

    function tagend_function_summary($attr, $data) 
    {
        return $this->helper->setSummary(trim($data));
    }
    
    function tagend_function_description($attr, $data) 
    {
        return $this->helper->setDescription(CodeGen_Tools_Indent::linetrim($data));
    }
    
    function tagend_function_proto($attr, $data)
    {
        return $this->helper->setProto(trim($data));
    }
    
    
    function tagend_function_code($attr, $data)
    {
        $data = CodeGen_Tools_Indent::linetrim($data);
        
        return $this->helper->setCode($data);
    }
    

    
    
    function tagstart_function_param($attr) 
    {
        if (!isset($attr['name']))  {
            return PEAR::raiseError("name attribut for parameter missing");
        }
        
        if (!isset($attr['type']))  {
                return PEAR::raiseError("type attribut for parameter missing");
        }
        
        return $this->helper->addParam($attr['name'], $attr['type'], @$attr['optional']);
    }

    function tagstart_function_data($attr) 
    {
    }
    
    function tagstart_function_data_element($attr) 
    {
        if (!isset($attr['name'])) {
            return PEAR::raiseError("name attribut for data element missing");                
        }
        
        if (!isset($attr['type'])) {
            return PEAR::raiseError("type attribut for data element missing");                
        }

        return $this->helper->addDataElement($attr['name'], $attr['type'], @$attr['default']);
    }
        
    function tagend_function_init($attr, $data) 
    {
        return $this->helper->setInitCode($data);
    }

    function tagend_function_deinit($attr, $data) 
    {
        return $this->helper->setDeinitCode($data);
    }

    function tagend_function_start($attr, $data) 
    {
        return $this->helper->setStartCode($data);
    }

    function tagend_function_add($attr, $data) 
    {
        return $this->helper->setAddCode($data);
    }

    function tagend_function_clear($attr, $data) 
    {
        return $this->helper->setClearCode($data);
    }

    function tagend_function_result($attr, $data) 
    {
        return $this->helper->setResultCode($data);
    }

    function tagstart_function_documentation($attr) 
    {
    }


    function tagend_function($attr, $data) 
    {
        //TODO check integrity here

        $err = $this->extension->addFunction($this->helper);

        $this->popHelper();
        return $err;
    }

        

    function tagstart_deps($attr)
    {
        if (isset($attr["platform"])) {
            $err = $this->extension->setPlatform($attr["platform"]);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
    }

    function tagstart_deps_file($attr) 
    {
        if (!isset($attr['name'])) {
            return PEAR::raiseError("name attribut for file missing");
        }

        return $this->extension->addSourceFile($attr['name']);
    }
        
    function tagstart_deps_lib($attr)
    {
        $this->extension->libs[$attr['name']] = $attr;
        if (isset($attr['platform'])) {
            $platform = new CodeGen_Tools_Platform($attr["platform"]);
        } else {
            $platform = new CodeGen_Tools_Platform("all");
        }

        if (PEAR::isError($platform)) {
            return $platform;
        }

        $this->extension->libs[$attr['name']]['platform'] = $platform;
        return true;
    }

    function tagstart_deps_header($attr)
    {
        $this->extension->headers[$attr['name']] = $attr; 
    }

    function tagend_extension_code($attr, $data) {
        if(!isset($attr['role'])) {
            return PEAR::raiseError("role attribut for code missing");
        }
        $err = $this->extension->addCode($attr["role"], CodeGen_Tools_Indent::linetrim($data));
        return $err;
    }

    function tagend_udf_code($attr, $data) {
        return $this->tagend_extension_code($attr, $data);
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
