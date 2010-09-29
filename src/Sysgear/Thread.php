<?php
namespace Realtime;

use Zend\Registry, Zend\JSON\JSON;

class Thread
{
    protected $program;
    protected $params = array();
    protected $pid = null;
    protected $options = array();

    public function __construct($program, array $options = array())
    {
        $this->program = $program;
        $this->options = $options;
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
        $i->params = $params;
        return $i->spawnThread()->getPid();
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
        if (array_key_exists($key, $this->params)) {
            return $this->params[$key];
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
        return $this->params;
    }

    /**
     * Return the childs process id.
     * 
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Spawn the child thread.
     */
    protected function _spawnThread()
    {
        $threadPath = escapeshellcmd(Util::normalizeDirPath($this->options['path']));
        $daemonizer = $threadPath . 'daemonize.sh';
        $phpFile = escapeshellarg($threadPath . $this->program . '.php');
        $options = escapeshellarg(JSON::encode($this->params));
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