<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Clickatell Class
 *
 * @package		Clickatell
 * @subpackage	Libraries
 * @category	SMS Gateway
 * @author		Zachie du Bruyn
 */
class Clickatell
{
    const ERR_NONE              = 0;
    const ERR_AUTH_FAIL         = 1;
    const ERR_SEND_MESSAGE_FAIL = 2;
    const ERR_SESSION_EXPIRED   = 3;
    const ERR_PING_FAIL         = 4;
    const ERR_CALL_FAIL         = 5;

    // public vars
    public $error = SELF::ERR_NONE;
    public $error_message = '';

    // private vars
    private $ci;
    private $session_id = FALSE;
    private $username;
    private $password;
    private $api_id;
    private $from_no;

    const BASEURL = "http://api.clickatell.com";

    /**
     * Class constructor - loads CodeIgnighter and Configs
     */
    public function __construct()
    {
        $ci 		= & get_instance();
        if (is_superadmin_loggedin()) {
            $branchID = $ci->input->post('branch_id');
        } else {
            $branchID = get_loggedin_branch_id();
        }
        $clickatell = $ci->db->get_where('sms_credential', array('sms_api_id' => 2, 'branch_id' => $branchID))->row_array();
        $this->username = isset($clickatell['field_one']) ? $clickatell['field_one'] : '';
        $this->password = isset($clickatell['field_two']) ? $clickatell['field_two'] : '';
        $this->api_id   = isset($clickatell['field_three']) ? $clickatell['field_three'] : '';
        $this->from_no  = isset($clickatell['field_four']) ? $clickatell['field_four'] : '';
    }

    /**
     * Method for Authentication with Clickatell
     *
     * @return string $session_id
     */
    public function authenticate()
    {
        $url = self::BASEURL.'/http/auth?user='.$this->username
             . '&password='.$this->password.'&api_id='.$this->api_id;

        $result = $this->_do_api_call($url);
        $result = explode(':',$result);

        if ($result[0] == 'OK')
        {
            $this->session_id = trim($result[1]);
            return $this->session_id;
        }
        else
        {
            $this->error = self::ERR_AUTH_FAIL;
            $this->error_message = $result[0];
            return FALSE;
        }
    }

    /**
     * Method to send a text message to number
     *
     * @access  public
     * @param   string $to
     * @param   string $message
     * @return  message_id
     */
    public function send_message($to, $message)
    {
        if ($this->session_id == FALSE)
        {
            $this->authenticate();
        }

        if ($this->error == self::ERR_NONE)
        {
            $message = urlencode($message);
            $url = self::BASEURL.'/http/sendmsg?session_id='.$this->session_id
                . '&to='.$to.'&text='.$message.'&from='.$this->from_no.'&MO=1';

            $result = $this->_do_api_call($url);
            $result = explode(':',$result);

            if ($result[0] == 'ID')
            {
                $api_message_id = $result[1];
                return $api_message_id;
            }
            else
            {
                $this->error = self::ERR_SEND_MESSAGE_FAIL;
                $this->error_message = $result[0];
                return FALSE;
            }
        }
        else
        {
            return FALSE;
        }
    }
    
    
    public function get_balance()
    {
        if ($this->session_id == FALSE)
        {
            $this->authenticate();
        }

        if ($this->error == self::ERR_NONE)
        {
            $url = self::BASEURL.'/http/getbalance?session_id='.$this->session_id;
            
            $result = $this->_do_api_call($url);
            $result = explode(':',$result);
            
            if ($result[0] == 'Credit')
            {
                return (float)$result[1];
            }
            else
            {
                $this->error = self::ERR_CALL_FAIL;
                $this->error_message = $result[0];
                return FALSE;
            }
        }       
    }
    
    /**
     * Method to send a ping to keep session live
     *
     * @access  public
     * @return  bool $success
     */
    public function ping()
    {
        if ($this->session_id == FALSE)
        {
            $this->authenticate();
        }

        if ($this->error == self::ERR_NONE)
        {
            $url = self::BASEURL.'/http/ping?session_id='.$this->session_id;
            
            $result = $this->_do_api_call($url);
            $result = explode(':',$result);
            
            if ($result[0] == 'OK')
            {
                return TRUE;
            }
            else
            {
                $this->error = self::ERR_PING_FAIL;
                $this->error_message = $result[0];
                return FALSE;
            }
        }
    }
    
    
    /**
     * Method to call HTTP url - to be expanded
     *
     * @param   string $url
     * @return  string response
     */
    private function _do_api_call($url)
    {
        $result = file($url);
        $result = implode("\n",$result);
        return $result;
    }
}

/* End of file Clickatell.php */
/* Location: ./application/libraries/Clickatell.php */