<?php
class Application_Model_User extends Zend_Db_Table_Abstract
{
    protected $_name = 'users';

   /**
   * login
   * @param string  $username 
   * @param string $password
   * @return array
   */
    public function login($username , $password)
    {
        $sql = $this->_db->select()
            ->from( $this->_name)
            ->where('username = ?', $username)
            ->where('password = ?', $password);
        // echo $sql->__toString();
        $stmt = $this->_db->query($sql);
        $result = $stmt->fetchAll();
        return !empty($result) ? $result[0] : null;
    }

    public function simpleACL()
    {
        // $authNamespace = new Zend_Session_Namespace('Zend_Auth');
        // $authNamespace->user = $username;
    }

   /**
   * logout
   * @param  void
   * @return void
   */
    public function logout()
    {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time()-1000);
            setcookie($name, '', time()-1000, '/');
            //$host  = $_SERVER['HTTP_HOST'];
            //header("Location: http://$host/");
        }
    }

    /**
    * passWord
    * new pass Word
    * @param int
    * @return string
    */
    public function passWord($max=10)
    {
        $chars="qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP"; 
        $size=StrLen($chars)-1;
        $password=null; 
        while($max--){
            $password.=$chars[rand(0,$size)];
        }
        return $password;
    }

    /**
    * registration
    * @param  strign
    * @return void
    */
    public function registration($mail = '')
    {   
        $mail = strip_tags($mail);
        $mail = trim($mail);
        if ($mail != '') {


            $sql = $this->_db->select()
                ->from( $this->_name)
                ->where('username = ?', $mail);
            // echo $sql->__toString();
            $stmt = $this->_db->query($sql);
            $result = $stmt->fetchAll();
            $sqlResult = !empty($result) ? $result[0] : null;
            $_passWord = $this->passWord(10);
            if ( !isset($sqlResult['id']) ) {
                #var_dump(1);
                $data = array( 
                'id' => 'NULL',
                'username' => $mail,
                'password' => $_passWord,
                'date'     => time()
                );
                $this->insert($data);
            } else {
                #var_dump(2);
                $data = array(
                    'password' => $_passWord,
                    'date'     => time() 
                );
                $where = $this
                    ->getAdapter()
                    ->quoteInto('id = ?', $sqlResult['id'] );
                $this-> update($data, $where);
            }
 
            $Zend_Mail = new Zend_Mail();
            //$mail->setBodyText('My Nice Test Text');
            $Zend_Mail->setBodyHtml(" your login $mail <br /> , your password $_passWord ");
            $Zend_Mail->setFrom('admin@valentin.in.ua', 'Admin');
            $Zend_Mail->addTo( $mail , 'was set a new password');
            $Zend_Mail->setSubject('was set a new password');
            $Zend_Mail->send();

            // $to      = strip_tags(trim($mail));
            // $subject = 'the subject';
            // $message = 'password: login: ';
            // $headers = 'From: webmaster@valentin.in.ua' . "\r\n" .
            //     'Reply-To: webmaster@valentin.in.ua' . "\r\n" .
            //     'X-Mailer: PHP/' . phpversion();
            // mail($to, $subject, strip_tags($message), $headers);
            // $sql = $this->_db->select()
            //     ->from( $this->_name)
            //     ->where('username = ?', $username)
            //     ->where('password = ?', $password);
            //     // echo $sql->__toString();
            // $stmt = $this->_db->query($sql);
            // $result = $stmt->fetchAll(); 
        }

    }



}