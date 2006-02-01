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

require_once "CodeGen/Extension.php";

require_once "System.php";
    
require_once "CodeGen/Element.php";
require_once "CodeGen/MySQL/UDF/Element/Function.php";

require_once "CodeGen/Maintainer.php";

require_once "CodeGen/License.php";

require_once "CodeGen/Tools/Platform.php";

require_once "CodeGen/Tools/Indent.php";

// }}} 

/**
 * A class that generates UDF extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_UDF_MySQL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_UDF_MySQL
 */
class CodeGen_MySQL_UDF_Extension 
    extends CodeGen_Extension
{
    /**
    * Current CodeGen_MySQL_UDF version number
    * 
    * @return string
    */
    static function version() 
    {
        return "0.9.1dev";
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


    /**
     * Add a source file to be copied to the extension dir
     *
     * @param  string path
     */
    function addSourceFile($name) 
    {
        $filename = realpath($name);
        
            if (!is_file($filename)) {
                return PEAR::raiseError("'$name' is not a valid file");
            }
            
            if (!is_readable($filename)) {
                return PEAR::raiseError("'$name' is not readable");
            }
            
            $pathinfo = pathinfo($filename);
            $ext = $pathinfo["extension"];

            switch ($ext) {
            case 'cpp':
            case 'cxx':
            case 'c++':
            case 'h':
            case 'hpp':
            case 'hxx':
            case 'h++':
            case 'o':
            case 'a':
                $this->addPackageFile('source', $filename);
                break;
            case 'c':
                $this->configure_in[] = "AC_PROG_CC";
                $this->addPackageFile('source', $filename);
                break;
            case 'l':
            case 'lex':
            case 'flex':
                $this->configure_in[] = "AM_PROG_LEX";
                $this->addPackageFile('source', $filename);
                break;
            case 'y':
            case 'yacc':
            case 'bison':
                $this->configure_in[] = "AM_PROG_YACC";
                $this->addPackageFile('source', $filename);
                break;
            default:
                break;
            }

            return $this->addPackageFile('copy', $filename);
    }

    // }}} 

    // {{{ output generation
        
    /**
     * Create the extensions including
     *
     * @param  string Directory to create (default is ./$this->name)
     */
    function createExtension($dirpath = false, $force = false) 
    {
        // default: create dir in current working directory, 
        // dirname is the extensions base name
        if (empty($dirpath) || $dirpath == ".") {
            $dirpath = "./" . $this->name;
        } 
        
        // purge and create extension directory
        if (file_exists($dirpath)) {
            if ($force) {
                if (!is_writeable($dirpath) || !@System::rm("-rf $dirpath")) {
                    return PEAR::raiseError("can't purge '$dirpath'");
                }
            } else {
                return PEAR::raiseError("'$dirpath' already exists, can't create that directory (use '--force' to override)"); 
            }
        }
        if (!@System::mkdir("-p $dirpath")) {
            return PEAR::raiseError("can't create '$dirpath'");
        }
        
        // make path absolute to be independant of working directory changes
        $this->dirpath = realpath($dirpath);
        
        echo "Creating '{$this->name}' extension in '$dirpath'\n";
        
        // generate complete source code
        $this->generateSource();
        
        // generate README file
        $this->writeReadme();

        // generate INSTALL file
        $this->writeInstall();

        // generate NEWS file
        $this->writeNews();
        
        // generate ChangeLog file
        $this->writeChangelog();

        // generate AUTHORS file
        $this->writeAuthors();

        // copy additional source files
        if (isset($this->packageFiles['copy'])) {
            foreach ($this->packageFiles['copy'] as $basename => $filepath) {
                copy($filepath, $this->dirpath."/".$basename);
            }
        }

        // let autoconf and automake take care of the rest
        $olddir = getcwd();
        chdir($this->dirpath);

        $return = 0;
        
        echo "\nRunning 'aclocal'\n";
        system("aclocal", $return);

        if ($return === 0) {
            echo "\nRunning 'autoconf'\n";
            system("autoconf", $return);
        }

        if ($return === 0) {
            echo "\nRunning 'libtoolize'\n";
            system("libtoolize --automake", $return);
        }

        if ($return === 0) {
            echo "\nRunning 'automake'\n";
            system("automake --add-missing", $return);
        }

        chdir($olddir);

        if ($return != 0) {
            return PEAR::raiseError("autotools failed");
        }

        return true;
    }
    
    /**
     * Create the extensions code soure and project files
     *
     * @access  protected
     */
    function generateSource() 
    {
        // generate source and header files
        $this->writeHeaderFile();
        $this->writeCodeFile();

        // generate .cvsignore file entries
        $this->writeDotCvsignore();

        // generate EXPERIMENTAL file for unstable release states
        $this->writeExperimental();
        
        // generate LICENSE file if license given
        if ($this->license) {
            $this->license->writeToFile($this->dirpath."/COPYING");
            $this->files['doc'][] = "COPYING";
        }

        // generate autoconf/automake files
        $this->writeConfig();
    }
    
    // {{{   docbook documentation

    // {{{ license and authoers
    /**
     * Create the license part of the source file header comment
     *
     * @return string  code fragment
     */
    function getLicense() 
    {    
        $code = "/*\n";
        $code.= "   +----------------------------------------------------------------------+\n";
        
        if (is_object($this->license)) {
            $code.= $this->license->getComment();
        } else {
            $code.= sprintf("   | unkown license: %-52s |\n", $this->license);
        }
        
        $code.= "   +----------------------------------------------------------------------+\n";
        
        foreach ($this->authors as $author) {
            $code.= $author->comment();
        }
        
        $code.= "   +----------------------------------------------------------------------+\n";
        $code.= "*/\n\n";
        
        $code.= "/* $ Id: $ */ \n\n";
        
        return $code;
    }
    
    // }}} 


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

        echo "#ifdef HAVE_DLOPEN\n\n";

        echo "#include \"udf_{$this->name}.h\"\n\n";

        if (isset($this->code['header']['top'])) {
            echo "// {{{ user defined header code\n\n";
            foreach ($this->code['header']['top'] as $code) {
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

        if (isset($this->code['header']['bottom'])) {
            echo "// {{{ user defined header code\n\n";
            foreach ($this->code['header']['bottom'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }

        echo "#else\n";
        echo "#error your installation does not support loading UDFs\n";
        echo "#endif /* HAVE_DLOPEN */\n";

        echo CodeGen_Element::cCodeEditorSettings();

        return $file->write();
    }

    // }}} 


    /**
     * Write authors to the AUTHORS file
     *
     * @access protected
     */
    function writeAuthors() 
    {
        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/AUTHORS");
        if (count($this->authors)) {
            $this->addPackageFile("doc", "AUTHORS");
            echo "{$this->name}\n";
            $names = array();
            foreach($this->authors as $author) {
                $names[] = $author->getName();
            }
            echo join(", ", $names) . "\n" 
        }
        
        return $file->write();
    }


    /**
    * Write EXPERIMENTAL file for non-stable extensions
    *
    * @access protected
    */
    function writeExperimental() 
    {
        if (($this->release) && isset($this->release->state) && $this->release->state !== 'stable') {
            $this->addPackageFile("doc", "EXPERIMENTAL");


            $file =  new CodeGen_Tools_Outbuf($this->dirpath."/EXPERIMENTAL");
?>
this extension is experimental,
its functions may change their names 
or move to extension all together 
so do not rely to much on them 
you have been warned!
<?php

            return $file->write();
        }
    }

    /**
    * Write .cvsignore entries
    *
    * @access protected
    */
    function writeDotCvsignore()
    {
        // open output file
        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/.cvsignore", "w");

        // unix specific entries
        if ($this->platform->test("unix")) {
            echo "*.lo\n";
            echo "*.la\n";
            echo ".deps\n";
        }

        // windows specific entries
        if ($this->platform->test("windows")) {
            echo "*.plg\n";
            echo "*.opt\n";
            echo "*.ncb\n";
            echo "Release\n";
            echo "Release_inline\n";
            echo "Debug\n";
            echo "Release_TS\n";
            echo "Release_TSDbg\n";
            echo "Release_TS_inline\n";
            echo "Debug_TS\n";
        }

        return $file->write();
    }



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



    /** 
    * Generate NEWS file (custom or default)
    *
    * @access protected
    */
    function writeNews() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/NEWS");

?>
This is a standalone UDF extension created using CodeGen_Mysql_UDF <?php echo self::version(); ?>

...
<?php

        $file->write();
    }


    /** 
    * Generate ChangeLog file (custom or default)
    *
    * @access protected
    */
    function writeChangelog() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/ChangeLog");
?>
This is a standalone UDF extension created using CodeGen_Mysql_UDF <?php echo self::version(); ?>

...
<?php

        $file->write();
    }


    /**
     * Generate configure files for this UDF extension
     *
     * @access protected
     */
    function writeConfig() {
        // Makefile.am
        $makefile = new CodeGen_Tools_Outbuf($this->dirpath."/Makefile.am");

        echo "lib_LTLIBRARIES = {$this->name}.la\n";
        echo "{$this->name}_la_CFLAGS = @MYSQL_CFLAGS@\n";
        echo "{$this->name}_la_CXXFLAGS = @MYSQL_CFLAGS@\n";
        echo "{$this->name}_la_LDFLAGS = -module -avoid-version -no-undefined\n";
        echo "{$this->name}_la_SOURCES = {$this->name}.".$this->language;
        if (isset($this->packageFiles['source'])) {
            foreach ($this->packageFiles['source'] as $file) {
                echo " ".basename($file);
            }
        }
        echo "\n";

        $makefile->write();
    


        // configure.in
        $configure = new CodeGen_Tools_Outbuf($this->dirpath."/configure.in");

        echo "AC_INIT({$this->name}.".$this->language.")\n";
        echo "AM_INIT_AUTOMAKE({$this->name}.so, 1.0)\n";
        echo "\n";
        
        echo "AC_PROG_LIBTOOL\n";

        if ($this->language === "cpp") {
            echo "AC_PROG_CXX\n";
        }

        if (isset($this->configure_in)) {
            echo join("\n", array_unique($this->configure_in));
            echo "\n";
        }
        
        echo '
searchin="/usr /usr/local /usr/local/mysql"
AC_ARG_WITH(mysql, 
    AC_HELP_STRING([--with-mysql=PATH], [path to mysql_config or mysql install dir]), 
    [
      case $withval in
        (yes)
          mysql_config=""
        ;;
        (no)
          mysql_config=""
        ;;
        (*)
          mysql_config="$withval"
        ;;  
      esac
    ], 
    [])

AC_MSG_CHECKING(for mysql_config)

if test -z "$mysql_config"
then
  if ! mysql_config=`which mysql_config`
  then
    AC_MSG_ERROR(mysql_config not found in PATH, please use --with-mysql=...)
  fi
else
  if test -d "$mysql_config"
  then
    if test -x "$mysql_config/bin/mysql_config"
    then
      mysql_config="$mysql_config/bin/mysql_config"
    else
      AC_MSG_ERROR($mysql_config not found or not executable)
    fi
  fi
fi

if test -z "$mysql_config"
then
  AC_MSG_ERROR(UDFs require mysql_config to detect needed CFLAGS)
fi

AC_MSG_RESULT($mysql_config)

AC_MSG_CHECKING(for MySQL CFLAGS) 

if ! mysql_cflags=`$mysql_config --udf-cflags 2>/dev/null`
then
  if ! mysql_cflags=`$mysql_config --cflags 2>/dev/null`
  then
    AC_MSG_ERROR(cannot detect --udf-cflags or --cflags)
  fi
fi

AC_MSG_RESULT($mysql_cflags)

AC_ARG_WITH(debug,
    [  --with-debug            Build test version with debugging code],
    [with_debug=$withval],
    [with_debug=no])
if test "$with_debug" = "yes"
then
  mysql_cflags="$mysql_cflags -DDEBUG -DDBUG_ON"
else
  mysql_cflags="$mysql_cflags -DNDEBUG -DDBUG_OFF"
fi

AC_SUBST(MYSQL_CFLAGS, $mysql_cflags)

AC_OUTPUT(Makefile)
';

        $configure->write();
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
