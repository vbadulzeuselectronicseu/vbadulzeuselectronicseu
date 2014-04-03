<?php
class Application_Model_Myfiles extends Zend_Db_Table_Abstract
{

    protected $_name = 'myfiles';
    protected $session;
    
    public function init()
    {
        $this->session = new Zend_Session_Namespace('Default');
    }

    /**
    * login
    * @param int       uid
    * @param string    file_description  
    * @param string    cool_search_words 
    * @param string    url
    * @param int       access_modifiers
   */
    public function addFiles($uid , $file_description , $cool_search_words , $url , $access_modifiers) 
    {
        $data = array(
            'id'                => NULL,
            'uid'               => $uid,
            'file_description'  => $file_description ,
            'cool_search_words' => $cool_search_words ,
            'url'               => $url ,
            'access_modifiers'  => $access_modifiers,
            'date'              => time(),
        );
        $this->insert($data);
    }

    /**
    * login
    * @param int       uid
    * @param string    file_description  
    * @param string    cool_search_words 
    * @param string    url
    * @param int       access_modifiers
    * @return array
   */
    public function getFiles() 
    {
        $sql = $this->_db->select()
                    ->from( $this->_name )
                    ->where('uid = ?', (int) $this->session->user['id'] ) 
                    ->order('id  DESC'); 
        $stmt = $this->_db->query($sql);
        $result = $stmt->fetchAll();
        return $result;
    }

    /**
    * updateStatus
    * @param int access_modifiers
    */
    public function updateAccessModifiers($access = 0 , $id = 0) {
        $data = array(
            'access_modifiers' => (int) $access,
        );
        $where = array();
        $where[] = $this->getAdapter()->quoteInto( 'uid = ?', (int) $this->session->user['id'] );
        $where[] = $this->getAdapter()->quoteInto('id = ?', $id);
        $this->update($data, $where);
        $this-> update($data, $where);
    }

    /**
    * getContent
    * @param int $user
    * @param int $post
    * @return array
    */
    public function getContent($user , $post) 
    {
        $sql = $this->_db->select()
                     ->from( $this->_name )
                     ->where('uid = ?', (int) $user )
                     ->where('id = ?', (int) $post )
                     ->where('access_modifiers = ?', '0');
        #  echo $sql->__toString();
        $stmt = $this->_db->query($sql);
        $result = $stmt->fetchAll();
        return ( count($result) != 0 ) ? $result[0] : null;
    }

}
