<?php
namespace Atlas\Model;

abstract class Entity
{
    protected $_id;
    
    private $__startingValues = array();
    
    private $__observers = array();

    public function __construct($properties = array())
    {
        foreach ($properties as $key => $value) {
            $this->$key = $value;
        }
        
        $this->__startingValues = $this->toArray();
    }
    
    public function getId()
    {
        if ($this->_id === null) {
            return null;
        }

        return (int) $this->_id;
    }

    public function setId($value)
    {
        if ($this->_id !== null) {
            throw new Exception ('Model ID may not be overwritten');
        }
        
        $this->_id = $value;
    }

    public function get($property)
    {
        if (!$property) {
            throw new Exception("Property key is required to get value");
        }

        if (!property_exists($this, $property)) {
            throw new Exception("Property '{$property}' not declared for " . get_class($this));
        }

        return $this->{$property};
    }

    public function set($property, $value)
    {
        if (!property_exists($this, $property)) {
            throw new Exception("Property '{$property}' not declared for " . get_class($this));
        }

        $this->{$property} = $value;
    }
    
    public function diff()
    {
        $diff = array();
        $before = $this->__startingValues;
        $after = $this->toArray();
         
        foreach ($after as $key => $value) {
            //ignore special variables
            if (substr($key,0,2) == '__') {
                continue;
            }
             
            if (!array_key_exists($key,$before)) {
                $diff[$key] = null;
                continue;
            }
             
            if ($value != $before[$key]) {
                $diff[$key] = $before[$key];
            }
        }
         
        foreach ($before as $key => $value) {
            //ignore special variables
            if (substr($key,0,2) == '__') {
                continue;
            }
             
            if (!array_key_exists($key,$after)) {
                $diff[$key] = $value;
            }
        }
         
        return $diff;
    }
    
    /**
     * @param array|Atom_Model_Entity $observers
     */
    public function attachObserver($observers)
    {
        if (is_array($observers)) {
            foreach ($observers as $observer) {
                $class = get_class($observer);
                $this->__observers[$class] = $observer;
            }
        } else {
            $class = get_class($observer);
            $this->__observers[$class] = $observer;
        }
    }
    
    /**
     * @param string $name
     * @throws Exception
     * @return Atom_Model_Observer
     */
    public function getObserver($name)
    {
        if (!array_key_exists($name, $this->__observers)) {
            throw new Exception('Observer ' . $name . ' not registered');
        }
        
        return $this->__observers[$name];
    }
    
    public function detachObserver($spec)
    {
        foreach ($this->__observers as $key => $observer)
        {
            if ($observer == $spec) unset($this->__observers[$key]);
        }
        return $this;
    }
    
    public function notify($action)
    {
        foreach ($this->__observers as $observer) {
            $observer->update($this, $action);
        }
    }
    
    public function toArray()
    {
        return $this->_objectToArray($this);
    }
    
    protected function _objectToArray($object)
    {
        $vars = get_object_vars($object);
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                $vars[$key] = $this->_objectToArray($value);
            }
        }
    
        return $vars;
    }
    
    public function __clone()
    {
        $this->__startingValues = $this->toArray();
    }
}
