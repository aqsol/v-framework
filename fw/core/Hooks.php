<?php
namespace core;

class Hooks {

    //hooks
    protected $hooks = [
        'before' => 		[ [] ],
        'before.router' => 	[ [] ],
        'before.dispatch' => 	[ [] ],
        'after.dispatch' => 	[ [] ],
        'after.router' =>	[ [] ],
        'after' =>		[ [] ],
    ];


    /**
     * Assign hook
     * @param  string   $name       The hook name
     * @param  mixed    $callable   A callable object
     * @param  int      $priority   The hook priority; 0 = high, 10 = low
     */
    public function addHook($name, $callable, $priority = 10) {
        if (!isset($this->hooks[$name]))
            $this->hooks[$name] = [ [] ];
        if (is_callable($callable))
            $this->hooks[$name][(int) $priority][] = $callable;
        else
    	    throw new \InvalidArgumentException("addHook received a non-callable argument");
    }

    /**
     * Invoke hook
     * @param  string   $name       The hook name
     * @param  mixed    $hookArgs   (Optional) Argument for hooked functions
     */
    public function applyHook($name, $hookArgs = []) {
        if (!isset($this->hooks[$name])) {
            $this->hooks[$name] = [ [] ];
        }
        if (!empty($this->hooks[$name])) {
            // Sort by priority, low to high, if there's more than one priority
            if (count($this->hooks[$name]) > 1) {
                ksort($this->hooks[$name]);
            }
            foreach ($this->hooks[$name] as $priority) {
                if (!empty($priority)) {
                    foreach ($priority as $callable) {
                	//\V::app()->_dbg("Hooks::applyHook: $name, callable=" . get_class($callable[0]) . "::" . $callable[1]);
                        call_user_func_array($callable, $hookArgs);
                    }
                }
            }
        }
    }

    /**
     * Get hook listeners
     *
     * Return an array of registered hooks. If `$name` is a valid
     * hook name, only the listeners attached to that hook are returned.
     * Else, all listeners are returned as an associative array whose
     * keys are hook names and whose values are arrays of listeners.
     *
     * @param  string     $name     A hook name (Optional)
     * @return array|null
     */
    public function getHooks($name = null) {
        if (!is_null($name)) {
            return isset($this->hooks[(string) $name]) ? $this->hooks[(string) $name] : null;
        } else {
            return $this->hooks;
        }
    }

    /**
     * Clear hook listeners
     *
     * Clear all listeners for all hooks. If `$name` is
     * a valid hook name, only the listeners attached
     * to that hook will be cleared.
     *
     * @param  string   $name   A hook name (Optional)
     */
    public function clearHooks($name = null) {
	$name = (string)$name;
        if (!is_null($name) && isset($this->hooks[$name])) {
            $this->hooks[$name] = [ [] ];
        } else {
            foreach ($this->hooks as $key => $value) {
                $this->hooks[$key] = [ [] ];
            }
        }
    }

}