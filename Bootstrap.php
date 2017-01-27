<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected $_docRoot;

    protected function _initPath() {
        $this->_docRoot = realpath(APPLICATION_PATH . '/../');
        Zend_Registry::set('docRoot', $this->_docRoot);
    }

    protected function _initLoaderResource() {
        $resourceLoader = new Zend_Loader_Autoloader_Resource(array(
            'basePath' => $this->_docRoot . '/application',
            'namespace' => 'Saffron'
        ));
        $resourceLoader->addResourceTypes(array(
            'model' => array(
                'namespace' => 'Model',
                'path' => 'models'
            )
        ));
    }

    protected function _initLog() {
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/error.log');
        return new Zend_Log($writer);
    }

    protected function _initView() {
        $view = new Zend_View();
        return $view;
    }

    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->setEncoding('UTF-8');
        $view->doctype('XHTML1_STRICT');
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
    }
    /*protected function _initViewHelpers() {
        $view = new Zend_View();
        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");
        $view->jQuery()->addStylesheet('/public/js/jquery/css/ui-lightness/jquery-ui-1.10.2.custom.css')
                ->setLocalPath('/public/js/jquery/js/jquery-1.10.2.min.js')
                ->setUiLocalPath('/public/js/jquery/js/jquery-ui-1.10.2.custom.min.js');
        return $view;
    }*/

}