<?    //Application_Model_DbTable_User
class Application_Modules_Main_Model_Mail extends Zend_Db_Table_Abstract
{
 
    protected $_name = 'test';
 
    public function addUser(  $password=1){
 
        $data = array(
 
            'test_id' => 'NULL',
           
            'test_txt' => $password
 
        );
 
    $this->insert($data);
 
    }
 
}