<?php
/**
 * Flash Messages helper class
 *
 * @package FlashMessages
 * @author Jess Green <jgreen @ psy-dreamer.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class Ale_Prom_Import_Flash
{
    /**
     * CSS classes. Filtered by flash_message_classes filter
     *
     * @var array
     */
    protected $classes = array('error', 'updated');
    /**
     * Default messages
     * @var array
     */
    static protected $messages = array();
    /**
     * PHP5 Constructor function
     *
     * @param array $options Set properties when class is initialized
     */
    public function __construct($options=[])
    {
        foreach ($options as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
        if (!session_id())
            session_start();

        add_action('admin_notices', array($this, 'show_flash_message'));
    }
    /**
     * End session on logout and login
     *
     * @return void
     */
    public function session_end()
    {
        unset($_SESSION['flash_messages']);
    }
    /**
     * Set messages in array
     *
     * @param array $messages Array of messages to set
     * @return void
     */
    
    

    public function setFlash($flash)
    {
        if (isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = array_merge($_SESSION['flash_messages'], $flash);
        } else {
            $_SESSION['flash_messages'] = $flash;
        }
    }

    public function setMessages($messages)
    {
        if(!$messages) {return;}
        if (isset($_SESSION['flash_messages']['messages'])) {
            $_SESSION['flash_messages']['messages'] = array_merge($_SESSION['flash_messages']['messages'], $messages);
        } else {
            $_SESSION['flash_messages']['messages'] = $messages;
        }
    }

    public function setErrors($errors)
    {
        if(!$errors) {return;}
        if (isset($_SESSION['flash_messages']['errors'])) {
            $_SESSION['flash_messages']['errors'] = array_merge($_SESSION['flash_messages']['errors'], $errors);
        } else {
            $_SESSION['flash_messages']['errors'] = $errors;
        }
    }

    public function getFlash()
    {
        if (isset($_SESSION['flash_messages'])) {
            $flash = $_SESSION['flash_messages'];
            $this->session_end();
            return $flash;
        }
        
        return false;
    }

    /**
     * Get messages
     *
     * @return array
     */
    public function getMessages()
    {
        if (isset($_SESSION['flash_messages']['messages'])) {
            $messages = $_SESSION['flash_messages']['messages'];
            $this->session_end();
            return $messages;
        }
        
        return false;
    }

    public function getErrors()
    {
        if (isset($_SESSION['flash_messages']['errors'])) {
            $errors = $_SESSION['flash_messages']['errors'];
            $this->session_end();
            return $errors;
        }
        
        return false;
    }
    /**
     * Queue flash messages
     *
     * @param string $name Name of message. updated or error
     * @param string $message Message body
     *
     * @return FlashMessages
     */
    public function queue_flash_message($name, $message)
    {
        $messages = array();
        /**
         * Filter for modifying default array of classes for Flash messaging
         * @param array $classes Array of classes for message div
         * @return string
         */
        $classes       = apply_filters('flashmessage_classes', $this->classes);
        /**
         * Filter for changing default
         * @param string $default_class Default 'updated' class
         * @return string
         */
        $default_class = apply_filters('flashmessages_default_class', 'updated');
        $class = $name;
        if (!in_array($name, $classes)) {
            $class = $default_class;
        }
        $messages[$class][] = $message;
        $this->setMessages($messages);
        return $this;
    }
    /**
     * Get flash message
     *
     * @return mixed
     */
    public function show_flash_message()
    {
        $messages = $this->getMessages();
        if (is_array($messages)) {
            foreach ($messages as $class => $messages) {
                $this->display_flash_message_html($messages, $class);
            }
        }
        $this->session_end();
    }
    /**
     * Display message HTML
     *
     * @param array $messages Array of messages
     * @param string $class Message CSS class
     * @return void
     */
    private function display_flash_message_html($messages, $class)
    {
        foreach ($messages as $message) {
            $message_html = "<div id=\"message\" class=\"{$class}\"><p>{$message}</p></div>";
            echo apply_filters('flashmessage_html', $message_html, $message, $class);
        }
    }
}