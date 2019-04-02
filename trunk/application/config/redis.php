<?php
    $redis_cluster = $_SERVER['SINASRV_REDIS_CLUSTER_HOST_PORT'];
    $config['cluster'] = $redis_cluster;

    $config['key_set'] = array(
        'real_time_task_queue' => 'real_time_task_queue_%s',//实时任务队列

        
    );



