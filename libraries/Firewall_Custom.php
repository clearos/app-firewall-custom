<?php

/**
 * Custom Firewall class.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\firewall_custom;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('firewall_custom');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;

clearos_load_library('base/Engine');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Custom firewall class.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
 */

class Firewall_Custom extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/clearos/firewall.d/custom';
    const FILE_FIREWALL_STATE = '/var/clearos/firewall/invalid.state';
    const MOVE_UP = -1;
    const MOVE_DOWN = 1;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $configuration = NULL;
    protected $is_loaded = FALSE;
    protected $commands = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Custom firewall constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->commands = array('iptables', 'ebtables');
    }

    /**
     * Get array of custom firewall rules.
     *
     * @return array of rules
     * @throws Engine_Exception
     */

    public function get_rules()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $rules = array();

        $index = 0;

        foreach ($this->configuration as $entry) {

            $rule = array (
                'line' => $index,
                'enabled' => FALSE,
                'description' => ''
            );

            foreach ($this->commands as $command) {
                if (preg_match("/^\s*#\s*$command\s+([^#]*)#(.*)/", $entry, $match)) {
                    $rule['entry'] = $command . ' ' . trim($match[1]);
                    $rule['enabled'] = FALSE;
                    $rule['description'] = trim($match[2]);
                } else if (preg_match("/^\s*#\s*$command\s+(.*)/", $entry, $match)) {
                    $rule['entry'] = $command . ' ' . trim($match[1]);
                    $rule['enabled'] = FALSE;
                    $rule['description'] = '';
                } else if (preg_match("/^\s*$command\s+([^#]*)#(.*)/", $entry, $match)) {
                    $rule['entry'] = $command . ' ' . trim($match[1]);
                    $rule['enabled'] = TRUE;
                    $rule['description'] = trim($match[2]);
                }
            }

            if (! empty($rule['entry']))
                $rules[$index] = $rule;
            
            $index++;
        }

        return $rules;
    }

    /**
     * Get rule
     *
     * @param String $line the line
     *
     * @return String
     * @throws Engine_Exception
     */

    public function get_rule($line)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rules = $this->get_rules();

        return $rules[$line];
    }

    /**
     * Add new rule
     *
     * @param String  $entry       line entry
     * @param String  $description rule description
     * @param Boolean $enabled     enabled/disabled
     * @param int     $priority    rule priority
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_rule($entry, $description, $enabled, $priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        Validation_Exception::is_valid($this->validate_entry($entry));
        Validation_Exception::is_valid($this->validate_description($description));

        if ($priority > 0)
            array_unshift(
                $this->configuration,
                ($enabled ? "" : "# ") . $entry . (isset($description) ? " # " . $description : "")
            );
        else
            array_push(
                $this->configuration,
                ($enabled ? "" : "# ") . $entry . (isset($description) ? " # " . $description : "")
            );

        // Rule has been added, but it might be in front of top-header comments
        if ($priority > 0) {
            $linenumber = 0;

            foreach ($this->configuration as $entry) {
                // Line 0 is our new addition
                if ($linenumber == 0) {
                    $swap = $entry;
                } else if (preg_match('/^\s*$/', $entry)) {
                    // Blank line
                    $this->configuration[$linenumber - 1] = $this->configuration[$linenumber];
                    $this->configuration[$linenumber] = $swap;
                } else if (preg_match('/^\s*[\w].*/', $entry)) {
                    // Not a comment...break;
                    break;
                } else if (!preg_match('/^\s*#/', $entry)) {
                    // Comment
                    $this->configuration[$linenumber - 1] = $this->configuration[$linenumber];
                    $this->configuration[$linenumber] = $swap;
                }

                $linenumber++;
            }
        }

        $this->_save_configuration();
    }

    /**
     * Update/Edit rule
     *
     * @param String  $line        line
     * @param String  $entry       new line
     * @param String  $description rule description
     * @param Boolean $enabled     enabled/disabled
     *
     * @return void
     * @throws Engine_Exception
     */

    public function update_rule($line, $entry, $description, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_entry($entry));
        Validation_Exception::is_valid($this->validate_description($description));

        if (! $this->is_loaded)
            $this->_load_configuration();

        $replace = ($enabled ? '' : '# ') . $entry . (isset($description) ? ' # ' . $description : '');

        $this->configuration[$line] = $replace;
        $this->_save_configuration();
    }

    /**
     * Delete rule
     *
     * @param String $line line to delete
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_rule($line)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        unset($this->configuration[$line]);

        $this->_save_configuration();
    }

    /**
     * Move rule up in table
     *
     * @param String $line      line to delete
     * @param int    $direction direction to move up (+1) or down (-1)
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_rule_priority($line, $direction)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        // Original line
        $moving_line = $this->configuration[$line];

        // Line that will take it's place
        $swap_line = $this->configuration[($line - $direction)];

        // Now let's swap
        $this->configuration[($line - $direction)] = $moving_line;
        $this->configuration[$line] = $swap_line;

        // And save
        $this->_save_configuration();
    }

    /**
     * Determine if firewall restart is required
     *
     * @return boolean
     * @throws Engine_Exception
     */

    public function is_firewall_restart_required()
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = new File(self::FILE_CONFIG);
        $state = new File(self::FILE_FIREWALL_STATE); 

        if ($config->last_modified() > $state->last_modified())
            return TRUE;
        else
            return FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Load configuration file
     *
     * @return void;
     */

    function _load_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->configuration = array();

        try {
            $file = new File(self::FILE_CONFIG);
            $this->configuration = $file->get_contents_as_array();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Save configuration file
     *
     * @return void;
     */

    function _save_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete any old temp file lying around
        //--------------------------------------

        $file = new File(self::FILE_CONFIG);

        if ($file->exists())
            $file->delete();

        // Create temp file
        //-----------------

        $file->create('root', 'root', '0755');

        // Write out the file
        //-------------------

        $file->add_lines(implode("\n", $this->configuration) . "\n");
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for firewall entry.
     *
     * @param string $entry entry
     *
     * @return string error message if entry is invalid
     */

    public function validate_entry($entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        $valid = FALSE;

        foreach ($this->commands as $command) {
            if (preg_match("/^$command\s+.*/", $entry))
                $valid = TRUE;

            if (preg_match("/;/", $entry))
                return lang('firewall_custom_firewall_rule_invalid');
        }

        if (!$valid)
            return lang('firewall_custom_command_is_not_permitted');
    }

    /**
     * Validation routine for description.
     *
     * @param int $description description
     *
     * @return mixed void if description is valid, errmsg otherwise
     */

    public function validate_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([\w\.\-_ ])+$/", $description))
            return lang('firewall_custom_description_invalid');
    }
}
