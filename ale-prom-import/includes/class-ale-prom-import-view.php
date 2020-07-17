<?php

/**
 * This view class is intended to render modal form dialog box along with download 
 * buttons. It also contain helper methods, which allow to reduce code in view files.
 */
class  Ale_Prom_Import_View {
    /**
     * Variable that shows if modal form already rendered.
     * 
     * @var boolean
     */
    public $isRendered = false;
    
    /**
     * This method will take care about rendering file only once.
     * 
     * @param resource $file
     * @param mixed $params
     * @return mixed 
     */
    public function renderOnce($file, $params)
    {
        try {
            if (!$this->isRendered) {
                $this->isRendered = true;
                return $this->render($file, $params);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Simple buffered renderer, it puts data to buffer, and then fush it.
     * 
     * @param resource $file
     * @param mixed $params
     * @return mixed
     */
    public function render($file, $params)
    {
        ob_start();
        ob_implicit_flush(false);
        if (file_exists($file)) {
            extract($params);
            require($file);
        }
        return ob_get_clean();
    }
    
    /**
     * Getter for $isRendered variable
     * @return boolean
     */
    public function getIsRendered()
    {
        return $this->isRendered;
    }
    
    
    
}