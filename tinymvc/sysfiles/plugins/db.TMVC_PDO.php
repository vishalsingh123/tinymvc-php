<?php

/***
 * Name:       TinyMVC
 * About:      An MVC application framework for PHP
 * Copyright:  (C) 2007-2008 Monte Ohrt, All rights reserved.
 * Author:     Monte Ohrt, monte [at] ohrt [dot] com
 * License:    LGPL, see included license file  
 ***/

// ------------------------------------------------------------------------

/* define SQL actions */
if(!defined('TMVC_SQL_NONE'))
  define('TMVC_SQL_NONE', 0);
if(!defined('TMVC_SQL_INIT'))
  define('TMVC_SQL_INIT', 1);
if(!defined('TMVC_SQL_ALL'))
  define('TMVC_SQL_ALL', 2);

/**
 * TMVC_PDO
 * 
 * PDO database access
 * compile PHP with --enable-pdo (default with PHP 5.1+)
 *
 * @package		TinyMVC
 * @author		Monte Ohrt
 */

class TMVC_PDO
{
 	/**
	 * $pdo
	 *
	 * the PDO object handle
	 *
	 * @access	public
	 */
  var $pdo = null;
  
 	/**
	 * $result
	 *
	 * the query result handle
	 *
	 * @access	public
	 */
  var $result = null;
  
 	/**
	 * $fetch_mode
	 *
	 * the results fetch mode
	 *
	 * @access	public
	 */
  var $fetch_mode = PDO::FETCH_ASSOC;

 	/**
	 * $get_query
	 *
	 * @access	public
	 */
  var $get_query = array('select' => '*');

 	/**
	 * $last_query
	 *
	 * @access	public
	 */
  var $last_query = null;
  
 	/**
	 * class constructor
	 *
	 * @access	public
	 */
  function __construct($config) {
    
   if(!class_exists('PDO'))
     trigger_error("PHP PDO package is required.",E_USER_ERROR);
     
   if(empty($config))
     trigger_error("database definitions required.",E_USER_ERROR);

   if(empty($config['charset']))
    $config['charset'] = 'UTF-8';
     
    /* attempt to instantiate PDO object and database connection */
    try {    
      $this->pdo = new PDO(
        "{$config['type']}:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        array(PDO::ATTR_PERSISTENT => !empty($config['persistent']) ? true : false)
        );
    } catch (PDOException $e) {
        trigger_error(sprintf("Can't connect to PDO database '{$config['type']}'. Error: %s",$e->getMessage()),E_USER_ERROR);
    }
    
    // make PDO handle errors with exceptions
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);    
    
  }

	/**
	 * select
	 *
	 * set the  active record select clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function select($clause)
  {
    return $this->get_query['select'] = $clause;
  }  

	/**
	 * from
	 *
	 * set the  active record from clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function from($clause)
  {
    return $this->get_query['from'] = $clause;
  }  

	/**
	 * where
	 *
	 * set the  active record where clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function where($clause,$args)
  {
    $this->_where($clause,$args,'AND');    
  }  

	/**
	 * orwhere
	 *
	 * set the  active record orwhere clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function orwhere($clause,$args)
  {
    $this->_where($clause,$args,'OR');    
  }  
  
	/**
	 * _where
	 *
	 * set the active record where clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  private function _where($clause, $args=array(), $prefix='AND')
  {    
    // sanity check
    if(empty($clause))
      return false;
    
    // make sure number of ? match number of args
    if(($count = substr_count($clause,'?')) && (count($args) != $count))
      trigger_error(sprintf("Number of where clause args don't match number of ?: '%s'",$clause),E_USER_ERROR);
      
    if(!isset($this->get_query['where']))
      $this->get_query['where'] = array();
      
    return $this->get_query['where'][] = array('clause'=>$clause,'args'=>$args,'prefix'=>$prefix);
  }  

	/**
	 * join
	 *
	 * set the  active record join clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function join($join_table,$join_on,$join_type=null)
  {
    $clause = "JOIN {$join_table} ON {$join_on}";
    
    if(!empty($join_type))
      $clause = $join_type . ' ' . $clause;
    
    if(!isset($this->get_query['join']))
      $this->get_query['join'] = array();
      
    $this->get_query['join'][] = $clause;
  }  

	/**
	 * in
	 *
	 * set an active record IN clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function in($field,$elements,$list=false)
  {
    $this->_in($field,$elements,$list,'AND');
  }

	/**
	 * orin
	 *
	 * set an active record OR IN clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function orin($field,$elements,$list=false)
  {
    $this->_in($field,$elements,$list,'OR');
  }

  
	/**
	 * _in
	 *
	 * set an active record IN clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  private function _in($field,$elements,$list=false,$prefix='AND')
  { 
    if(!$list)
    {
      if(!is_array($elements))
        $elements = explode(',',$elements);
        
      // quote elements for query
      foreach($elements as $idx => $element)
        $elements[$idx] = $this->pdo->quote($element);
      
      $clause = sprintf("{$field} IN (%s)", implode(',',$elements));
    }
    else
      $clause = sprintf("{$field} IN (%s)", $elements);
    
    $this->_where($clause,array(),$prefix);
  }  
  
	/**
	 * orderby
	 *
	 * set the  active record orderby clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function orderby($clause)
  {    
    $this->_set_clause('orderby',$clause);
  }  

	/**
	 * groupby
	 *
	 * set the active record groupby clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  function groupby($clause)
  {    
    $this->_set_clause('groupby',$clause);
  }  

	/**
	 * limit
	 *
	 * set the active record limit clause
	 *
	 * @access	public
	 * @param   int    $limit
	 * @param   int    $offset
	 */    
  function limit($limit, $offset=0)
  {    
    if(!empty($offset))
      $this->_set_clause('limit',sprintf('%d,%d',(int)$offset,(int)$limit));
    else
      $this->_set_clause('limit',sprintf('%d',(int)$limit));
  }  
  
	/**
	 * _set_clause
	 *
	 * set an active record clause
	 *
	 * @access	public
	 * @param   string $clause
	 */    
  private function _set_clause($type, $clause, $args=array())
  {    
    // sanity check
    if(empty($type)||empty($clause))
      return false;
      
    $this->get_query[$type] = array('clause'=>$clause);
    
    if(isset($args))
      $this->get_query[$type]['args'] = $args;
      
  }  
  
	/**
	 * _query_assemble
	 *
	 * get an active record query
	 *
	 * @access	public
	 * @param   string $fetch_mode the PDO fetch mode
	 */    
  private function _query_assemble($fetch_mode=null)
  {
  
    if(empty($this->get_query['from']))
    {
      trigger_error("Unable to get(), set from() first",E_USER_ERROR);
      return false;
    }
    
    $params = array();
    $query = array();
    $where_init = false;
    $query[] = "SELECT {$this->get_query['select']}";
    $query[] = "FROM {$this->get_query['from']}";

    // assemble join clause
    if(!empty($this->get_query['join']))
      foreach($this->get_query['join'] as $cjoin)
        $query[] = $cjoin;
    
    // assemble where clause
    if(!empty($this->get_query['where']))
    {
      foreach($this->get_query['where'] as $cwhere)
      {
        $prefix = !$where_init ? 'WHERE' : $cwhere['prefix'];
        $where = "{$prefix} {$cwhere['clause']}";
        $params = array_merge($params,(array) $cwhere['args']);
        $where_init = true;
        $query[] = $where;
      }
    }

    // assemble groupby clause
    if(!empty($this->get_query['groupby']))
      $query[] = "GROUP BY {$this->get_query['groupby']['clause']}";
    
    // assemble orderby clause
    if(!empty($this->get_query['orderby']))
      $query[] = "ORDER BY {$this->get_query['orderby']['clause']}";
    
    // assemble limit clause
    if(!empty($this->get_query['limit']))
      $query[] = "LIMIT {$this->get_query['limit']['clause']}";
    
    $query_string = implode(' ',$query);
    $this->last_query = $query_string;
    
    $this->get_query = array('select' => '*');
    
    return $query_string;
    
  }  
  
  
	/**
	 * query
	 *
	 * execute a database query
	 *
	 * @access	public
	 * @param   array $params an array of query params
	 * @param   int $fetch_mode the fetch formatting mode
	 */    
  function query($query=null,$params=null,$fetch_mode=null)
  {
    if(!isset($query))
      $query = $this->_query_assemble($fetch_mode);
  
    return $this->_query($query,$params,TMVC_SQL_NONE,$fetch_mode);
  }  

	/**
	 * query_all
	 *
	 * execute a database query, return all records
	 *
	 * @access	public
	 * @param   array $params an array of query params
	 * @param   int $fetch_mode the fetch formatting mode
	 */    
  function query_all($query=null,$params=null,$fetch_mode=null)
  {
    if(!isset($query))
      $query = $this->_query_assemble($fetch_mode);
  
    return $this->_query($query,$params,TMVC_SQL_ALL,$fetch_mode);
  }  

	/**
	 * query_one
	 *
	 * execute a database query, return one record
	 *
	 * @access	public
	 * @param   array $params an array of query params
	 * @param   int $fetch_mode the fetch formatting mode
	 */    
  function query_one($query=null,$params=null,$fetch_mode=null)
  {
    if(!isset($query))
      $query = $this->_query_assemble($fetch_mode);
  
    return $this->_query($query,$params,TMVC_SQL_INIT,$fetch_mode);
  }  
  
	/**
	 * _query
	 *
	 * internal query method
	 *
	 * @access	private
	 * @param   string $query the query string
	 * @param   array $params an array of query params
	 * @param   int $return_type none/all/init
	 * @param   int $fetch_mode the fetch formatting mode
	 */    
  function _query($query,$params=null,$return_type = TMVC_SQL_NONE,$fetch_mode=null)
  {
  
    /* if no fetch mode, use default */
    if(!isset($fetch_mode))
      $fetch_mode = PDO::FETCH_ASSOC;  
  
    /* prepare the query */
    try {
      $this->result = $this->pdo->prepare($query);
    } catch (PDOException $e) {
        trigger_error(sprintf("PDO Error: %s Query: %s",$e->getMessage(),$query),E_USER_ERROR);
    }      
    
    /* execute with params */
    try {
      $this->result->execute($params);  
    } catch (PDOException $e) {
        trigger_error(sprintf("PDO Error: %s Query: %s",$e->getMessage(),$query),E_USER_ERROR);
    }      
  
    /* get result with fetch mode */
    $this->result->setFetchMode($fetch_mode);  
  
    switch($return_type)
    {
      case TMVC_SQL_INIT:
        return $this->result->fetch();
        break;
      case TMVC_SQL_ALL:
        return $this->result->fetchAll();
        break;
      case TMVC_SQL_NONE:
      default:
        break;
    }  
    
  }

	/**
	 * next
	 *
	 * go to next record in result set
	 *
	 * @access	public
	 * @param   int $fetch_mode the fetch formatting mode
	 */    
  function next($fetch_mode=null)
  {
    if(isset($fetch_mode))
      $this->result->setFetchMode($fetch_mode);
    return $this->result->fetch();
  }

	/**
	 * last_insert_id
	 *
	 * get last insert id from previous query
	 *
	 * @access	public
	 * @return	int $id
	 */    
  function last_insert_id()
  {
    return $this->pdo->lastInsertId();
  }

	/**
	 * num_rows
	 *
	 * get number of returned rows from previous select
	 *
	 * @access	public
	 * @return	int $id
	 */    
  function num_rows()
  {
    return count($this->result->fetchAll());
  }

	/**
	 * last_query
	 *
	 * get last query executed
	 *
	 * @access	public
	 */    
  function last_query()
  {
    return $this->last_query;
  }  

 	/**
	 * class destructor
	 *
	 * @access	public
	 */
  function __destruct()
  {
    $this->pdo = null;
  }
  
}

?>
