<?php
include_once 'zf.php';
define( 'PROJECT_PATH', realpath( dirname( __FILE__ ) . '/../' ) );
class App extends ZF
{
    protected function _detectHomeDirectory($mustExist = true, $returnMessages = true)
    {
        return dirname( __FILE__ );
    }

    public static function main()
    {
        $app = new self();
        $app->bootstrap();
        $app->run();
    }

}

App::main();
