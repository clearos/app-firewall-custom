<?php

/**
 * Custom firewall rules controller.
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
 * Custom firewall rules controller.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
 */

class Custom_Rules extends ClearOS_Controller
{
    protected $type = NULL;

    /**
     * Custom firewall rules constructor.
     *
     * @param string $type type (eg. ipv4, ipv6)
     *
     * @return view
     */

    function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Index controller,
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('firewall_custom');
        $this->load->library('firewall_custom/Firewall_Custom');

        // Load view data
        //---------------

        try {
            $data['rules'] = $this->firewall_custom->get_rules($this->type);
            $data['type'] = $this->type;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load view
        //----------

        $this->page->view_form('summary', $data, lang('firewall_custom_app_name'));
    }

    /**
     * Add rule.
     *
     * @param integer $line line number
     *
     * @return view
     */

    function add_edit($line = -1)
    {
        // Load libraries
        //---------------

        $this->load->library('firewall_custom/Firewall_Custom');
        $this->lang->load('firewall_custom');
        $this->lang->load('base');

        $type = 'ipv4';
        if (preg_match('/ipv6/', current_url()))
            $type = 'ipv6';

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('entry', 'firewall_custom/Firewall_Custom', 'validate_entry', TRUE);
        $this->form_validation->set_policy('description', 'firewall_custom/Firewall_Custom', 'validate_description', TRUE);
        $this->form_validation->set_policy('enabled', 'firewall_custom/Firewall_Custom', 'validate_state', TRUE);

        // Handle form submit
        //-------------------

        if ($this->form_validation->run()) {
            try {
                if ($line >= 0) {
                    $this->firewall_custom->update_rule(
                        $line,
                        $this->input->post('entry'),
                        $this->input->post('description'),
                        $this->input->post('enabled')
                    );
                    $this->page->set_status_updated();
                } else {
                    $this->firewall_custom->add_rule(
                        $type,
                        $this->input->post('entry'),
                        $this->input->post('description'),
                        $this->input->post('enabled')
                    );
                    $this->page->set_status_added();
                }

                redirect('/firewall_custom');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        if ($line >= 0)
            $data = $this->firewall_custom->get_rule($line);
        else
            $data['line'] = -1;

        $data['type'] = $type;

        // Load the views
        //---------------

        $this->page->view_form('firewall_custom/add_edit', $data, lang('base_add'));
    }

    /**
     * Toggle enable/disable of rule.
     *
     * @param integer $line line number
     *
     * @return view
     */

    function toggle($line)
    {
        // Load libraries
        //---------------

        $this->load->library('firewall_custom/Firewall_Custom');
        $this->lang->load('firewall_custom');
        $this->lang->load('base');

        // Handle form submit
        //-------------------

        try {
            $data = $this->firewall_custom->get_rule($line);
            $this->firewall_custom->update_rule(
                $line,
                $data['entry'],
                $data['description'],
                !$data['enabled']
            );
            $this->page->set_status_updated();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        redirect('/firewall_custom');
    }

    /**
     * Delete custom rule.
     *
     * @param integer $line    line number
     * @param string  $confirm confirm deletion
     *
     * @return view
     */

    function delete($line, $confirm = NULL)
    {
        $confirm_uri = current_url() . '/1';
        $cancel_uri = '/app/firewall_custom';

        $this->load->library('firewall_custom/Firewall_Custom');
        $this->lang->load('firewall_custom');

        if ($confirm != NULL) {
            try {
                $this->firewall_custom->delete_rule($line);

                $this->page->set_status_deleted();
                redirect('/firewall_custom');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        $rule = $this->firewall_custom->get_rule($line);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, array($rule['description']));
    }

    /**
     * Ajax set rules controller
     *
     * @return JSON
     */

    function set_rules()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        // Load libraries
        //---------------

        $this->load->library('firewall_custom/Firewall_Custom');
        try {
            $this->firewall_custom->set_rules($_POST['type'], json_decode($_POST['rules']));
            echo json_encode(array('code' => 0));
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }
}
