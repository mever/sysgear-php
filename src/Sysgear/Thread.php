<?php
namespace Realtime;

use Zend\Registry, Zend\JSON\JSON;

class Thread
{
    protected $_program;
    protected $_params = array();
    protected $_pid = null;

    private function __clone()
    {}

    private function __construct($program)
    {
        $this->_program = $program;
    }

    /**
     * Execute thread program and return the PID.
     * 
     * @param string $program
     * @param array $params
     */
    public static function exec($program, array $params = array())
    {
        $i = new self($program);
        $i->_params = $params;
        return $i->_spawnThread()->getPid();
    }

    /**
     * WARNING: Use this only in a child thread.
     */
    public static function child($argv)
    {
        $i = new self(__FILE__);
        // Fetch params from the arguments global.
        try {
            $i->_params = JSON::decode(array_key_exists(1, $argv) ? $argv[1] : '{}');
        } catch (Exception $e) {
            self::err($e->getMessage());
            exit(1);
        }
        // Call the thread run routine.
        return $i;
    }

    /**
     * Return the value of a parameter. Or $default if no parameter named $key is found.
     * 
     * @param string $key
     * @param mixed $default
     */
    public function getParam($key, $default = null)
    {
        if (array_key_exists($key, $this->_params)) {
            return $this->_params[$key];
        } else {
            return $default;
        }
    }

    /**
     * Return a array of parameters given.
     * 
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Return the childs process id.
     * 
     * @return int
     */
    public function getPid()
    {
        return $this->_pid;
    }

    /**
     * Spawn the child thread.
     */
    protected function _spawnThread()
    {
        $threadPath = Registry::get('config')->path->threads;
        $threadPath = escapeshellcmd(Util::normalizeDirPath($threadPath));
        $daemonizer = $threadPath . 'daemonize.sh';
        $phpFile = escapeshellarg($threadPath . $this->_program . '.php');
        $options = escapeshellarg(JSON::encode($this->_params));
        pclose(popen("'{$daemonizer}' {$phpFile} {$options}", 'r'));
        // TODO: Discover a way to obtain the PID.
        return $this;
    }

    /**
     * Report a error.
     * 
     * @param string $msg
     */
    public static function err($msg)
    {
        trigger_error($msg, E_USER_ERROR);
    }
}