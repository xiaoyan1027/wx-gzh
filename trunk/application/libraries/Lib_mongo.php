<?php
/*
 *  add by xingqian 2016.7.11
 *  mongo操作基类
 */
class Lib_mongo{
    public $mongoClient;
    public $mongoDB;
    public $mongoCollection;
    public $mongoCursor;
    public static $selfs;
    private $_inserted_id = false;
    private $query_safety = true;    
    
    public function __construct($opts = array('group'=>'default','type'=>'w'))
    {      
        if (!class_exists('MongoClient'))
        {
            log_message('error', 'The MongoDB PECL extension has not been installed or enabled');
        }
        $CI =& get_instance();
        if ($CI->config->load('mongo', TRUE, TRUE))
		{
			$config = $CI->config->item('mongo');
            $conf = $config[$opts['group']][$opts['type']];
		}
        else
        {
            log_message('error', "can't find mongodb config");
        }
        $options = array("connect" => true,'db'=>$conf['name']);
        
        $connection_string = 'mongodb://'.$conf['user'].':'.$conf['pass'].'@'.$conf['host'].':'.$conf['port'];
                
        try{
            $this->mongoClient = new MongoClient($connection_string,$options);
            $this->select_db($conf['name']);
            //return $this; 
        }
        catch(MongoConnectionException $e)
        {
            log_message('error', "Unable to connect to MongoDB: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * 切换db
     * @param $database
     */
    public function select_db($database)
    {
        try{
            $this->mongoDB = $this->mongoClient->{$database};
            return $this->mongoDB;
        }
        catch (Exception $e)
        {
            log_message('error', "Unable to switch Mongo Databases: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * 检查db是否初始化
     */
    public function checkdb()
    {
        if($this->mongoDB == null)
        {
            log_message('error', 'No mongoDB selected!');
        }
    }
    /**
     * 
     * 执行一条 Mongo 指令
     * 几乎所有不属于CRUD操作的事情都可以通过一条“数据库指令”完成。
     * 
     */
    public function command($query = array())
    {
        $this->checkdb();
        try{
            $run = $this->mongoDB->command($query);
            return $run;
        }
        catch(MongoCursorException $e)
        {
            log_message('error', "MongoDB command failed to execute: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * aggregate mongoDB聚合管道 
     * @usage : $mongoObj->aggregate('users', array(array('$project' => array('_id' => 1))));
     */
    public function aggregate($collection = "", $opt)
    {
        if(empty($collection))
        {
            log_message('error', 'No Mongo collection selected to insert into');
        }
        
        $this->checkdb();
        try{
            $c = $this->mongoDB->selectCollection($collection);
            return $c->aggregate($opt);
        }
        catch(MongoException $e)
        {
            log_message('error', "MongoDB failed: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * Creates an index on the specified field(s) if it does not already exist.
     * @usage : $mongoObj->ensure_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
     * @param $collection
     * @param $keys
     * @param $options
     */
    public function ensure_index($collection = "", $keys = array(), $options = array())
    {
        if(empty($collection))
        {
            log_message('error',"No Mongo collection specified to add index to");
        }
        
        if(empty($keys) || !is_array($keys))
        {
            log_message('error',"Index could not be created to MongoDB Collection because no keys were specified");
        }
        
        foreach ($keys as $col => $val)
        {
            if($val == -1 || $val === FALSE || strtolower($val) == 'desc')
            {
                $keys[$col] = -1;
            }
            else
           {
                $keys[$col] = 1;
            }
        }
        
        $this->checkdb();
        //createIndex mongo>2.6
        if ($this->mongoDB->{$collection}->createIndex($keys, $options) == TRUE)
        {
            return true;
        }
        else
        {
            log_message('error',"An error occured when trying to add an index to MongoDB Collection");
        }
    }
    
    /**
     * 
     * Remove an index of the keys in a collection. To set values to descending order,
     * you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
     * set to 1 (ASC).
     * @usage : $mongoObj->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
     * @param $collection
     * @param $keys
     */
    public function remove_index($collection = "", $keys = array())
    {
        if (empty($collection))
        {
            log_message('error',"No Mongo collection specified to remove index from");
        }
        if (empty($keys) || !is_array($keys))   
        {
            log_message('error',"Index could not be removed from MongoDB Collection because no keys were specified");
        }
        
        $this->checkdb();
        
        if ($this->mongoDB->{$collection}->deleteIndex($keys, $options) == TRUE)
        {
            return true;
        }
        else
        {
            log_message('error',"An error occured when trying to remove an index from MongoDB Collection");
        }
    }
    
    /**
     * 
     * Remove all indexes from a collection
     * @param $collection
     */
    public function remove_all_indexes($collection = "")
    {
        if (empty($collection))
        {
            log_message('error',"No Mongo collection specified to remove all indexes from");
        }
        
        $this->checkdb();
        
        $this->mongoDB->{$collection}->deleteIndexes();
        return true;
    }
    
    /**
     * 
     * List all indexes in a collection
     * @param $collection
     */
    public function list_indexes($collection = "")
    {
        if (empty($collection))
        {
            log_message('error',"No Mongo collection specified to remove all indexes from");
        }
        
        $this->checkdb();
        
        return $this->mongoDB->{$collection}->getIndexInfo();
    }
    
    /**
     * 
     * 获取引用所指向的对象
     * http://cn2.php.net/manual/zh/mongodbref.get.php
     * @usage : $mongoObj->get_dbref($object);
     * @param $obj
     */
    public function get_dbref($obj)
    {
        if (empty($obj) OR !isset($obj))
        {
            log_message('error','To use MongoDBRef::get() ala get_dbref() you must pass a valid reference object');
        }
        
        $this->checkdb();
        
        return MongoDBRef::get($this->mongoDB,$obj);
    }
    
    /**
     * 
     * 创建一个新的数据库引用
     * http://cn2.php.net/manual/zh/mongodbref.create.php
     * @param $collection
     * @param $id
     * @param $database
     */
    public function create_dbref($collection = "", $id = "", $database = FALSE )
    {
        if (empty($collection)) 
        {
            log_message('error',"In order to retreive documents from MongoDB, a collection name must be passed");
        }
        if (empty($id) OR !isset($id))
        {
            log_message('error','To use MongoDBRef::create() ala create_dbref() you must pass a valid id field of the object which to link');
        }
        
        if($database)
        {
            return MongoDBRef::create($collection,$id,$database);
        }
        else
        {
            return MongoDBRef::create($collection,$id);
        }
    }
    
    /**
     * 
     * 删除库
     * @param $database
     * @return Array([dropped] => foo.$cmd,[ok] => 1)
     */
    public function drop_db($database = '')
    {
        if($database)
        {
            try{
                return $this->mongoClient->{$database}->drop();
            }
            catch(Exception $e)
            {
                log_message('error',"Unable to drop Mongo database `{$database}`: {$e->getMessage()}");
            }
        }
        
        return false;
    }
    
    /**
     * 
     * 删除集合
     * @param $db
     * @param $col
     * @return Array([nIndexesWas] => 1,[msg] => all indexes deleted for collection,[ns] => my_db.articles,[ok] => 1)
     */
    public function drop_collection($db = "", $col = "")
    {
        if($db && $col)
        {
            try{
                return $this->mongoClient->{$db}->{$col}->drop();
            }
            catch(Exception $e)
            {
                log_message('error',"Unable to drop Mongo collection '$col': {$e->getMessage()}");
            }
        }
        
        return false;
    }
    
    /**
     * 
     * 处理结果集
     */
    public function result()
    {
        $result = array();
        
        try{
            foreach($this->mongoCursor as $doc)
            {
                $result[] = $doc;
            }
        }
        catch(Exception  $exception)
        {
            log_message($exception->getMessage(),1);
        }
        return $result;
    }
    
    /**
     * 
     * 返回条数
     */
    public function num_rows()
    {
        return $this->count(TRUE);
    }
    
    /**
     * 
     * 获取结果中某一条数据
     */
    public function row($index)
    {
        $this->mongoCursor->reset();
        $res = array();
        if($this->mongoCursor->hasNext())
        {
            $step = 0;
            while($next = $this->mongoCursor->next())
            {
                if($step == $index)
                {
                    $res = $next;
                    break;
                }
                $step++;
            }
        }
        
        return $res;
    }
    
    /**
     * 
     * Skip结果集
     * @param $num
     */
    public function skip($num)
    {
        if($num)
        {
            return $this->mongoCursor->skip(intval($num));
        }
        
        return $this->mongoCursor;
    }
    
    /**
     * 
     * Limit结果集
     * @param $num
     */
    public function limit($num)
    {
        if($num)
        {
            return $this->mongoCursor->limit(intval($num));
        }
        return $this->mongoCursor;
    }
    
    /**
     * 
     * 排序
     * @param $fields
     */
    public function sort($fields)
    {
        return $this->mongoCursor->sort($fields);
    }
    
    /**
     * 
     * 统计条数
     * @param $foundOnly
     */
    public function count($foundOnly = false)
    {
        $count = 0;
        try{
            $count = $this->mongoCursor->count($foundOnly);
        }
        catch(Exception $exception)
        {
            log_message($exception->getMessage(),1);
        }
        
        return $count;
    }
    
    /**
     * 
     * 获取集合里指定键的不同值的列表
     * @param $key
     * @param $where
     */
    public function distinct($collection = "",$key,$where=array())
    {
        if($collection == '')
        {
            return false;
        }
        $this->checkdb();
        return $this->mongoDB->selectCollection($collection)->distinct($key,$where);
    }
    
    /**
     * 
     * 获取collection数据
     * @param $collection
     * @param $limit
     * @param $offset
     */
    public function get($collection = "", $where = array(),$select = array(), $sorts = array(), $limit = FALSE, $offset = FALSE) 
    {
        if($collection == '')
        {
            return false;
        }
        $this->checkdb();
        $this->mongoCursor = $this->mongoDB->selectCollection($collection)->find($where,$select);
        if($limit)
        {
            $this->limit($limit);
        }
        
        if($offset)
        {
            $this->skip($offset);
        }
        
        if($sorts)
        {
            $this->sort($sorts);
        }
        
        return $this->result();
    }

    /**
     * 
     * 根据条件获取条数
     * @param $collection
     * @param $where
     * @param $limit
     * @param $offset
     */
    public function get_count($collection = "", $where = array(), $limit = FALSE, $offset = FALSE) 
    {
        if($collection == '')
        {
            return false;
        }
        $this->checkdb();
        $this->mongoCursor = $this->mongoDB->selectCollection($collection)->find($where);
        if($limit)
        {
            $this->limit($limit);
        }
        
        if($offset)
        {
            $this->skip($offset);
        }
                
        return $this->count(true);
    }    
    
    
    /**
     * 
     * 统计数据
     * @param $collection
     */
    public function count_all($collection = "") 
    {
        if($collection == '')
        {
            return false;
        }
        $this->checkdb();
        $this->mongoCursor = $this->mongoDB->selectCollection($collection)->find();
        return $this->count(true);
    }
    
    /**
     * 
     * 插入一条数据
     * @param $collection
     * @param $insert
     * @desc 如果不想在参数$insert设置"_id"值，需传引用，如：$b = array('x' => 3);$ref = &$b;$mongo->insert('test',$ref);
     */
    public function insert($collection = "", $insert = array()) 
    {
        if($collection == '' || !$insert)
        {
            return false;
        }
        $this->checkdb();
        $this->_inserted_id = FALSE;
        try{
            $res = $this->mongoDB->selectCollection($collection)->insert($insert, array("w" => $this->query_safety));
            if (isset($insert['_id'])) 
            {
                $this->_inserted_id = $insert['_id'];
            }
            return $res;
        }
        catch(Exception $e)
        {
            log_message('error',"Insert of data into MongoDB failed: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * 批量插入
     * @param $collection
     * @param $insert
     */
    public function insert_batch($collection = "", $insert = array()) 
    {
        if($collection == '' || !$insert)
        {
            return false;
        }
        $this->checkdb();
        $this->_inserted_id = FALSE;
        try{
            $query = $this->mongoDB->selectCollection($collection)->batchInsert($insert, array("w" => $this->query_safety));
            if (is_array($query))
            {
                return $query["err"] === NULL;
            }
            else
           {
                return FALSE;
            }
        }
        catch(Exception $e)
        {
            log_message('error',"Insert of data into MongoDB failed: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * 更新一条数据
     * @param $collection
     * @param $where
     * @param $data
     * @param $options
     */
    public function update($collection = "", $where = array(), $data = array(), $options = array()) 
    {
        if($collection == '' || !$data)
        {
            return false;
        }
        $this->checkdb();
        try{
            $options = array_merge(array("w" => $this->query_safety, 'multiple' => FALSE), $options);
            $query = $this->mongoDB->selectCollection($collection)->update($where, $data, $options);
            if (is_array($query))
            {
                return $query["err"] === NULL;
            }
            else
           {
                return FALSE;
            }
        }
        catch(Exception $e)
        {
            log_message('error',"Update of data into MongoDB failed: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * 批量更新
     * @param $collection
     * @param $where
     * @param $data
     */
    public function update_batch($collection = "", $where = array(), $data = array()) 
    {
        return $this->update($collection, $where, $data, array('multiple' => TRUE));
    }
    
    /**
     * 
     * 删除一条内容
     * @param $collection
     * @param $where
     * @param $options
     */
    public function delete($collection = "", $where=array(), $options = array()) 
    {
        if($collection == '')
        {
            return false;
        }
        $this->checkdb();
        try{
            $options = array_merge(array("w" => $this->query_safety), $options);
            $query = $this->mongoDB->selectCollection($collection)->remove($where, $options);
            if (is_array($query))
            {
                return $query["err"] === NULL;
            }
            else
           {
                return FALSE;
            }
        }
        catch(Exception $e)
        {
            log_message('error',"Delete of data into MongoDB failed: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * 批量删除内容
     * @param $collection
     * @param $where
     * @param $options
     */
    public function delete_batch($collection = "", $where = array(), $options = array()) 
    {
        $options = array_merge(array('justOne' => FALSE), $options);
        return $this->delete($collection, $where, $options);
    }
    
    /**
     * 
     * 获取insert_id
     */
    public function insert_id() 
    {
        return $this->_inserted_id;
    }
    
    /**
     *
     * 关闭
     */
    public function close()
    {
        return $this->mongoClient->close(true);
    }
}