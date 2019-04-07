<?php
    //微信配置    
    if(ENVIRONMENT == 'development')
    {
        $config['options'] = array(
                        'component_appid'=>'wxeeabb3cf48c30d14',
                        'component_appsecret'=>'0c79e1fa963cd80cc0be99b20a18faeb',
                        'component_token'=>'leju_weixin_platform',
                        'component_aeskey'=>'LEJUWXeaRHkNYTlW8lpzkpdzNujzo26WO4ntldWeHN2',
                        'template_authorizer_appid'=>'wx09bd6d53ada7d815',
                        'download_laike' => 'https://laike.leju.com/download',
                        'forward_domain' => array(
                            'page.bch.leju.com',
                            'fn.bch.leju.com',
                            'weixin.bch.leju.com',
                        ),
        );
    }
    elseif(ENVIRONMENT == 'production')
    {
        $config['options'] = array(
                        'component_appid'=>'wx87de7990d037f656',
                        'component_appsecret'=>'41efb59875b378b1a51d53f60dfda159',
                        'component_token'=>'leju_weixin_platform',
                        'component_aeskey'=>'LEJUWXeaRHkNYTlW8lpzkpdzNujzo26WO4ntldWeHN2',
                        'template_authorizer_appid'=>'wxeee3bf6feb0a56d1',
                        'download_laike' => 'https://laike.leju.com/download',
                        'forward_domain' => array(
                            'page.leju.com',
                            'fn.leju.com',
                        ),
        );
    }
