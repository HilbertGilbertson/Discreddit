<?php
/**
 * Discreddit Sample API
 * @file Discreddit.API.php
 * @version 1.0
 * @author HilbertGilbertson
 * @url https://github.com/HilbertGilbertson/Discreddit
 */

class API extends Discreddit
{
    public function __construct($configArr)
    {
        parent::__construct($configArr);
        if (!$this->db) {
            $this->respond(['error' => true, 'msg' => "API Disabled"], 403);
        }
    }

    /**
     * Send a POST request to api.php with action=locate and discord_id=[ID] or reddit_username=[reddit handle]
     */
    private function _locate()
    {
        if (isset($_POST['discord_id']) && ctype_digit($_POST['discord_id'])) {
            $user = $this->db->from_discord($_POST['discord_id']);
        } elseif (isset($_POST['reddit_username'])) {
            $user = $this->db->from_reddit($_POST['reddit_username']);
        } else {
            return $this->respond(['error' => true, 'msg' => "Improper Request"], 404);
        }
        return ($user ? $this->respond($user, 200) : $this->respond([], 404));
    }

    public function serve()
    {
        if(isset($_REQUEST['action'])){
            $method = '_' . preg_replace('/[^a-zA-Z]+/', '', $_REQUEST['action']);
            if (method_exists($this, $method)) {
                return call_user_func(array($this, $method));
            }
        }
        return $this->respond(['error' => true, 'msg' => "Resource not found"], 404);
    }

    private function respond($data, $code = 200)
    {
        http_response_code($code);
        header("Content-Type: application/json");
        die(json_encode($data));
    }
}