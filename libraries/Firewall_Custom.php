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

    protected $configuration = array();
    protected $ipv4_index = -1;
    protected $ipv6_index = -1;
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

        // $IPTABLES constant for /usr/sbin/iptables to avoid locking issues
        $this->commands = array('ip6tables', 'iptables', '$IPTABLES', 'ebtables');
    }

    /**
     * Returns array of custom firewall rules.
     *
     * @param string $type fule type
     *
     * @return array of rules
     * @throws Engine_Exception
     */

    public function get_rules($type = 'ALL')
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $rules = array();

        $index = 0;

        foreach ($this->configuration as $entry) {

            // If we are not on an actual rule, increment index (used in line number ordering) and bail out of loop
            if (key($entry) != 'ipv4' && key($entry) != 'ipv6') {
                $index++;
                continue;
            }

            // Filter
            if ($type != 'ALL' && $type != key($entry)) {
                $index++;
                continue;
            }

            $rule = array (
                'type' => key($entry),
                'line' => $index,
                'enabled' => FALSE,
                'description' => '',
                'raw' => $entry
            );

            foreach ($this->commands as $command) {
                if (preg_match('/^\s*#\s*' . str_replace('$', '\$', $command) . '\s+([^#]*)#(.*)/', current($entry), $match)) {
                    $rule['entry'] = $command . ' ' . trim($match[1]);
                    $rule['enabled'] = FALSE;
                    $rule['description'] = trim($match[2]);
                } else if (preg_match('/^\s*#\s*' . str_replace('$', '\$', $command) . '\s+(.*)/', current($entry), $match)) {
                    $rule['entry'] = $command . ' ' . trim($match[1]);
                    $rule['enabled'] = FALSE;
                    $rule['description'] = '';
                } else if (preg_match('/^\s*' . str_replace('$', '\$', $command) . '\s+([^#]*)#(.*)/', current($entry), $match)) {
                    $rule['entry'] = $command . ' ' . trim($match[1]);
                    $rule['enabled'] = TRUE;
                    $rule['description'] = trim($match[2]);
                } else if (preg_match('/^\s*' . str_replace('$', '\$', $command) . '\s+(.*)/', current($entry), $match)) {
                    $rule['entry'] = $command . ' ' . trim($match[1]);
                    $rule['enabled'] = TRUE;
                    $rule['description'] = "---";
                }
            }

            if (! empty($rule['entry']))
                $rules[$index] = $rule;
            
            $index++;
        }

        return $rules;
    }

    /**
     * Returns specific firewall rule.
     *
     * @param string $line the line
     *
     * @return string
     * @throws Engine_Exception
     */

    public function get_rule($line)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_line_number($line));

        $rules = $this->get_rules();

        return $rules[$line];
    }

    /**
     * Adds new rule.
     *
     * @param String  $type        ipv4 or ipv6
     * @param String  $entry       line entry
     * @param String  $description rule description
     * @param Boolean $enabled     enabled/disabled
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_rule($type, $entry, $description, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        // Override any occurence of iptables and replace with lock-save $IPTABLES variable
        $entry = preg_replace('/^iptables\s+(.*)/', '$IPTABLES \1', $entry);
        $entry = preg_replace('/^ip6tables\s+(.*)/', '$IPTABLES \1', $entry);

        Validation_Exception::is_valid($this->validate_ip_version($type));
        Validation_Exception::is_valid($this->validate_entry($entry));
        Validation_Exception::is_valid($this->validate_description($description));
        Validation_Exception::is_valid($this->validate_state($enabled));

        array_splice(
            $this->configuration,
            (1 + ($type == 'ipv4' ? $this->ipv4_index : $this->ipv6_index)),
            0,
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

        Validation_Exception::is_valid($this->validate_line_number($line));
        Validation_Exception::is_valid($this->validate_entry($entry));
        Validation_Exception::is_valid($this->validate_description($description));
        Validation_Exception::is_valid($this->validate_state($enabled));

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

        Validation_Exception::is_valid($this->validate_line_number($line));

        if (! $this->is_loaded)
            $this->_load_configuration();

        unset($this->configuration[$line]);

        $this->_save_configuration();
    }

    /**
     * Set rules using array.
     *
     * @param String $type  ipv4 or ipv6
     * @param array  $rules rules
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_rules($type, $rules)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_ip_version($type));

        foreach ($rules as $rule) {
            $valid_rule = FALSE;

            foreach ($this->configuration as $line_number => $details) {
                if (isset($details[$type]) && (trim($details[$type]) == trim($rule)))
                    $valid_rule = TRUE;
            }

            if (!$valid_rule)
                Validation_Exception::is_valid(lang('firewall_custom_firewall_rule_invalid'));
        }

        // Re-ordering
        //------------

        $new_order = array();
        $section_found = FALSE;

        foreach ($this->configuration as $line) {
            if (preg_match("/.*FW_PROTO.*" . $type . ".*/", current($line))) {
                // Add the bash shell
                $new_order[] = $line;
                // Now add the new rules
                foreach ($rules as $rule) {
                    $new_order[] = $rule;
                }
                // Set our marker so we know to ignore the old rules for the section we're working on
                $section_found = TRUE;
            } else if (preg_match("/^fi$/", current($line))) {
                $new_order[] = $line;
                // Found end of bash...
                $section_found = FALSE;
            } else if ($section_found) {
                continue;
            } else {
                $new_order[] = $line;
            }
        }

        unset($this->configuration);
        $this->configuration = $new_order;
        
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
            $lines = $file->get_contents_as_array();
            $type = 'unknown';
            $index = 0;
            foreach ($lines as $line) {
                if (preg_match("/.*FW_PROTO.*ipv4.*/", $line)) {
                    $this->ipv4_index = $index;
                    $this->configuration[] = array('bash' => $line);
                    $type = 'ipv4';
                    $index++;
                    continue;
                } else if (preg_match("/.*FW_PROTO.*ipv6.*/", $line)) {
                    $this->ipv6_index = $index;
                    $this->configuration[] = array('bash' => $line);
                    $index++;
                    $type = 'ipv6';
                    continue;
                } else if (preg_match("/^fi$/", $line)) {
                    $this->configuration[] = array('bash' => $line);
                    $type = 'unknown';
                    $index++;
                    continue;
                }
                $this->configuration[] = array($type => $line);
                $index++;
            }
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

        $contents = array();
        foreach ($this->configuration as $line) {
            if (is_array($line)) {
                if (key($line) == 'bash' || key($line) == 'unknown')
                    $contents[] = current($line);
                else
                    $contents[] = "\t" . trim(current($line));
            } else {
                $contents[] = "\t" . trim($line);
            }
        }
        $file->dump_contents_from_array($contents);
        $this->is_loaded = FALSE;
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

        $valid_command = FALSE;

        foreach ($this->commands as $command) {
            $matches = [];

            if (preg_match('/^(' . str_replace('$', '\$', $command) . ')\s+(.*)/', $entry, $matches)) {
                $valid_command = TRUE;

                if (!preg_match('/^[a-zA-Z0-9:\,\.\-\s\/]+$/', $matches[2]))
                    return lang('firewall_custom_firewall_rule_invalid');
            }
        }

        if (!$valid_command)
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

    /**
     * Validation routine for IP version.
     *
     * @param boolean $version version
     *
     * @return string error message if IP version is invalid
     */

    public function validate_ip_version($version)
    {
        clearos_profile(__METHOD__, __LINE__);

        $versions = [ 'ipv4', 'ipv6' ];

        if (! in_array($version, $versions))
            return lang('firewall_custom_invalid_ip_version');
    }

    /**
     * Validation routine for configuration line number.
     *
     * @param boolean $line line number
     *
     * @return string error message if ccnfiguration line number is invalid
     */

    public function validate_line_number($line)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        if (! array_key_exists($line, $this->configuration))
            return lang('firewall_custom_invalid_configuration_parameter');
    }

    /**
     * Validation routine for state.
     *
     * @param boolean $state state
     *
     * @return string error message if state is invalid
     */

    public function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('base_state_invalid');
    }
}
