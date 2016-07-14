<?php

/**
 * Custom firewall warnings and messages view.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2016 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network\Network as Network;

$this->lang->load('firewall');
$this->lang->load('firewall_custom');

///////////////////////////////////////////////////////////////////////////////
// Warnings
///////////////////////////////////////////////////////////////////////////////

if ($panic)
    $this->load->view('firewall/panic');

if ($network_mode == Network::MODE_TRUSTED_STANDALONE)
    $this->load->view('network/firewall_verify');

echo dialogbox_confirm(
    lang('firewall_custom_restart_required'),
    '/app/firewall_custom/restart',
    '/app/firewall_custom',
    array(
        'id' => 'restart_required',
        'hidden' => TRUE
    )
);
