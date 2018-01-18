<?php

/**
 * Firewall Custom controller.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2018 ClearFoundation
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall Custom controller.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
 */

class Firewall_Custom extends ClearOS_Controller
{
    /**
     * Firewall Custom default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('firewall_custom');
        $this->load->library('firewall/Firewall');
        $this->load->library('firewall_custom/Firewall_Custom');
        $this->load->library('network/Network');

        // Load view data
        //---------------

        $views = array('firewall_custom/warnings', 'firewall_custom/ipv4', 'firewall_custom/ipv6');

        $this->page->view_forms($views, lang('firewall_custom_app_name'));
    }
}
