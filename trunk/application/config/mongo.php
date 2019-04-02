<?php
    $config = array(
         'default' => array(
             'w' => array(
                 // 'host' => isset($_SERVER['SINASRV_MONGODB_HOST']) ? $_SERVER['SINASRV_MONGODB_HOST'] : '10.207.0.188',
                 'host' => isset($_SERVER['SINASRV_MONGODB_HOST']) ? $_SERVER['SINASRV_MONGODB_HOST'] : '10.207.0.93',
                 'port' => isset($_SERVER['SINASRV_MONGODB_PORT']) ? $_SERVER['SINASRV_MONGODB_PORT'] : '27017',
                 'user' => isset($_SERVER['SINASRV_MONGODB_USER']) ? $_SERVER['SINASRV_MONGODB_USER'] : 'xcx_leju_com',
                 'pass' => isset($_SERVER['SINASRV_MONGODB_PASS']) ? $_SERVER['SINASRV_MONGODB_PASS'] : '123456',
                 'name' => isset($_SERVER['SINASRV_MONGODB_NAME']) ? $_SERVER['SINASRV_MONGODB_NAME'] : 'xcx_leju_com',
             ),
             'r' => array(
                 // 'host' => isset($_SERVER['SINASRV_MONGODB_HOST_R']) ? $_SERVER['SINASRV_MONGODB_HOST_R'] : '10.207.0.188',
                 'host' => isset($_SERVER['SINASRV_MONGODB_HOST']) ? $_SERVER['SINASRV_MONGODB_HOST'] : '10.207.0.93',
                 'port' => isset($_SERVER['SINASRV_MONGODB_PORT_R']) ? $_SERVER['SINASRV_MONGODB_PORT_R'] : '27017',
                 'user' => isset($_SERVER['SINASRV_MONGODB_USER_R']) ? $_SERVER['SINASRV_MONGODB_USER_R'] : 'xcx_leju_com',
                 'pass' => isset($_SERVER['SINASRV_MONGODB_PASS_R']) ? $_SERVER['SINASRV_MONGODB_PASS_R'] : '123456',
                 'name' => isset($_SERVER['SINASRV_MONGODB_NAME_R']) ? $_SERVER['SINASRV_MONGODB_NAME_R'] : 'xcx_leju_com',
             ),
         )
     );

